<?php
/**
 * Subscription & Trial access control
 */

require_once __DIR__ . '/settings.php';

function sub_init_trial(int $parentId): void {
    global $database;
    $now = date('Y-m-d H:i:s');
    $trialEnd = date('Y-m-d H:i:s', strtotime('+5 days'));

    // Create subscription record
    $database->insert(
        "INSERT INTO `subscriptions` (parent_id, status, trial_start, trial_end)
         VALUES (?, 'trial', ?, ?)",
        [$parentId, $now, $trialEnd]
    );

    // Update users table
    $database->execute(
        "UPDATE `users` SET trial_start = ?, trial_end = ?, subscription_status = 'trial' WHERE user_id = ?",
        [$now, $trialEnd, $parentId]
    );

    // Create wallet
    $database->execute(
        "INSERT IGNORE INTO `wallet` (parent_id, balance) VALUES (?, 0.00)",
        [$parentId]
    );
}

function sub_get_status(int $parentId): array {
    global $database;
    $sub = $database->fetchOne(
        "SELECT * FROM `subscriptions` WHERE parent_id = ? ORDER BY id DESC LIMIT 1",
        [$parentId]
    );
    if (!$sub) {
        // Check users table fallback
        $user = $database->fetchOne(
            "SELECT trial_start, trial_end, subscription_status FROM `users` WHERE user_id = ?",
            [$parentId]
        );
        if ($user && $user['subscription_status'] === 'trial') {
            return [
                'status' => 'trial',
                'trial_start' => $user['trial_start'],
                'trial_end' => $user['trial_end'],
                'is_active' => strtotime($user['trial_end']) >= time(),
                'days_remaining' => max(0, floor((strtotime($user['trial_end']) - time()) / 86400)),
            ];
        }
        return ['status' => 'none', 'is_active' => false, 'days_remaining' => 0];
    }

    $now = time();
    $isActive = false;
    $daysRemaining = 0;

    if ($sub['status'] === 'trial') {
        $trialEnd = strtotime($sub['trial_end']);
        $isActive = $trialEnd >= $now;
        $daysRemaining = max(0, floor(($trialEnd - $now) / 86400));
        // Auto-expire trial
        if (!$isActive) {
            $database->execute(
                "UPDATE `subscriptions` SET status = 'expired' WHERE id = ?",
                [$sub['id']]
            );
            $database->execute(
                "UPDATE `users` SET subscription_status = 'expired' WHERE user_id = ?",
                [$parentId]
            );
            $sub['status'] = 'expired';
        }
    } elseif ($sub['status'] === 'active') {
        // Validate that active subscription has a valid completed payment
        $validPayment = false;
        if (!empty($sub['last_payment_id'])) {
            $payment = $database->fetchOne(
                "SELECT status FROM `payments` WHERE id = ?",
                [(int) $sub['last_payment_id']]
            );
            $validPayment = $payment && $payment['status'] === 'completed';
        }
        if (!$validPayment) {
            $database->execute(
                "UPDATE `subscriptions` SET status = 'expired' WHERE id = ?",
                [$sub['id']]
            );
            $database->execute(
                "UPDATE `users` SET subscription_status = 'expired' WHERE user_id = ?",
                [$parentId]
            );
            $sub['status'] = 'expired';
            $isActive = false;
            $daysRemaining = 0;
        } else {
            $periodEnd = strtotime($sub['current_period_end']);
            $isActive = $periodEnd >= $now;
            $daysRemaining = max(0, floor(($periodEnd - $now) / 86400));
            if (!$isActive) {
                $database->execute(
                    "UPDATE `subscriptions` SET status = 'expired' WHERE id = ?",
                    [$sub['id']]
                );
                $database->execute(
                    "UPDATE `users` SET subscription_status = 'expired' WHERE user_id = ?",
                    [$parentId]
                );
                $sub['status'] = 'expired';
            }
        }
    }

    return [
        'id' => $sub['id'],
        'status' => $sub['status'],
        'trial_start' => $sub['trial_start'],
        'trial_end' => $sub['trial_end'],
        'current_period_start' => $sub['current_period_start'],
        'current_period_end' => $sub['current_period_end'],
        'is_active' => $isActive,
        'days_remaining' => $daysRemaining,
        'payment_method' => $sub['payment_method'],
    ];
}

function sub_can_access(int $parentId): bool {
    if (!is_payment_enabled()) return true;
    $status = sub_get_status($parentId);
    return $status['is_active'];
}

function sub_require_access(): void {
    if (!is_payment_enabled()) return;
    if (session_status() === PHP_SESSION_NONE) {
        sec_session_start();
    }
    $role = $_SESSION['role'] ?? '';
    if ($role !== 'parent') return;

    $parentId = (int) ($_SESSION['user_id'] ?? 0);
    if ($parentId <= 0) return;

    if (!sub_can_access($parentId)) {
        header('Location: ' . dirname($_SERVER['SCRIPT_NAME'], 2) . '/payment');
        exit;
    }
}

function sub_activate_after_payment(int $parentId, int $paymentId, string $method = 'snippe'): void {
    global $database;

    $now = date('Y-m-d H:i:s');
    $periodEnd = date('Y-m-d H:i:s', strtotime('+30 days'));

    // Check if subscription exists
    $sub = $database->fetchOne(
        "SELECT id FROM `subscriptions` WHERE parent_id = ? ORDER BY id DESC LIMIT 1",
        [$parentId]
    );

    if ($sub) {
        $database->execute(
            "UPDATE `subscriptions`
             SET status = 'active',
                 current_period_start = ?,
                 current_period_end = ?,
                 payment_method = ?,
                 last_payment_id = ?
             WHERE id = ?",
            [$now, $periodEnd, $method, $paymentId, $sub['id']]
        );
    } else {
        $database->insert(
            "INSERT INTO `subscriptions` (parent_id, status, current_period_start, current_period_end, payment_method, last_payment_id)
             VALUES (?, 'active', ?, ?, ?, ?)",
            [$parentId, $now, $periodEnd, $method, $paymentId]
        );
    }

    $database->execute(
        "UPDATE `users` SET subscription_status = 'active' WHERE user_id = ?",
        [$parentId]
    );
}

function sub_add_days(int $parentId, int $days = 30): void {
    global $database;

    $sub = $database->fetchOne(
        "SELECT id, current_period_end, status FROM `subscriptions` WHERE parent_id = ? ORDER BY id DESC LIMIT 1",
        [$parentId]
    );

    $now = time();
    if ($sub && ($sub['status'] === 'active' || $sub['status'] === 'trial')) {
        $base = $sub['current_period_end']
            ? max($now, strtotime($sub['current_period_end']))
            : $now;
        $newEnd = date('Y-m-d H:i:s', $base + ($days * 86400));
        $database->execute(
            "UPDATE `subscriptions` SET current_period_end = ?, status = 'active' WHERE id = ?",
            [$newEnd, $sub['id']]
        );
    } else {
        $newEnd = date('Y-m-d H:i:s', $now + ($days * 86400));
        $database->insert(
            "INSERT INTO `subscriptions` (parent_id, status, current_period_start, current_period_end, payment_method)
             VALUES (?, 'active', ?, ?, 'manual')",
            [$parentId, date('Y-m-d H:i:s', $now), $newEnd]
        );
    }

    $database->execute(
        "UPDATE `users` SET subscription_status = 'active' WHERE user_id = ?",
        [$parentId]
    );
}

function sub_get_trial_days_remaining(int $parentId): int {
    $status = sub_get_status($parentId);
    return $status['days_remaining'];
}

function sub_is_trial_expired(int $parentId): bool {
    $status = sub_get_status($parentId);
    return $status['status'] === 'trial' && !$status['is_active'];
}

function sub_check_trial_ending_notifications(): void {
    global $database;

    $parents = $database->fetchAll("
        SELECT u.user_id, u.phone, u.first_name, s.trial_end
        FROM `users` u
        JOIN `subscriptions` s ON s.parent_id = u.user_id
        WHERE u.role = 'parent'
          AND s.status = 'trial'
          AND s.trial_end IS NOT NULL
          AND s.trial_end > NOW()
          AND s.trial_end <= DATE_ADD(NOW(), INTERVAL 2 DAY)
    ");

    if (empty($parents)) return;

    try {
        require_once __DIR__ . '/../sms_service.php';
        $sms = new SmsService();

        foreach ($parents as $parent) {
            $daysLeft = max(1, floor((strtotime($parent['trial_end']) - time()) / 86400));
            $msg = "Smart Math Corner: Majaribio yako yanaisha ndani ya siku $daysLeft. Lipa 1,500 TZS kuendelea. Tembelea tovuti yetu au tuma 1,500 kwa 440783070 (Smart Math Corner).";
            $sms->sendSMS($parent['phone'], $msg, 'trial_ending', 'parent', (int) $parent['user_id']);
        }
    } catch (Exception $e) {
        error_log('Trial ending SMS error: ' . $e->getMessage());
    }
}

function sub_check_overdue_notifications(): void {
    global $database;

    $parents = $database->fetchAll("
        SELECT u.user_id, u.phone, u.first_name
        FROM `users` u
        JOIN `subscriptions` s ON s.parent_id = u.user_id
        WHERE u.role = 'parent'
          AND s.status = 'expired'
          AND s.current_period_end IS NOT NULL
          AND s.current_period_end <= DATE_SUB(NOW(), INTERVAL 2 DAY)
    ");

    if (empty($parents)) return;

    try {
        require_once __DIR__ . '/../sms_service.php';
        $sms = new SmsService();

        foreach ($parents as $parent) {
            $msg = "Smart Math Corner: Uanachama wako umeisha. Lipa 1,500 TZS kurejesha huduma kwa mtoto wako. Tuma kwa 440783070 (Smart Math Corner) au tembelea tovuti yetu.";
            $sms->sendSMS($parent['phone'], $msg, 'overdue', 'parent', (int) $parent['user_id']);
        }
    } catch (Exception $e) {
        error_log('Overdue SMS error: ' . $e->getMessage());
    }
}
