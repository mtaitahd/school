<?php
/**
 * SubscriptionMiddleware — Enforces trial/subscription access control
 *
 * Usage:
 *   SubscriptionMiddleware::requireAccess();   // redirects if blocked
 *   SubscriptionMiddleware::canAccess();       // returns bool
 *   SubscriptionMiddleware::getTrialInfo();    // returns array for UI
 */

class SubscriptionMiddleware {

    /**
     * Redirect to payment if access is blocked.
     * Call early in any protected page (learner/parent content).
     */
    public static function requireAccess(): void {
        if (!self::canAccess()) {
            $_SESSION['subscription_blocked'] = true;
            header('Location: ' . self::paymentUrl());
            exit;
        }
    }

    /**
     * Check if current user can access learning content.
     * Handles both parent and learner accounts.
     */
    public static function canAccess(): bool {
        global $database;

        $userId = auth_user_id();
        if (!$userId) return false;

        $role = auth_role();

        // Admins and teachers always have access
        if (in_array($role, ['admin', 'teacher'], true)) return true;

        // Learners get access through their parent
        if ($role === 'learner') {
            $parent = $database->fetchOne(
                "SELECT psl.parent_id FROM parent_student_links psl
                 JOIN users u ON psl.parent_id = u.user_id
                 WHERE psl.student_id = ? AND psl.is_active = 1
                 LIMIT 1",
                [$userId]
            );
            if (!$parent) return true; // unlinked learners get free access
            $parentId = (int) $parent['parent_id'];
        } elseif ($role === 'parent') {
            $parentId = $userId;
        } else {
            return true;
        }

        // Check subscription status
        $sub = $database->fetchOne(
            "SELECT status, trial_end, current_period_end
             FROM subscriptions
             WHERE parent_id = ?
             ORDER BY id DESC
             LIMIT 1",
            [$parentId]
        );

        if (!$sub) return true; // no subscription record yet = allow

        if ($sub['status'] === 'active') return true;

        if ($sub['status'] === 'trial') {
            // Check if trial is still valid
            $trialEnd = $sub['trial_end'];
            if ($trialEnd && strtotime($trialEnd) > time()) return true;
        }

        return false;
    }

    /**
     * Get trial/subscription info for UI display.
     * Returns: ['status', 'days_remaining', 'label', 'expiry_date', 'is_active']
     */
    public static function getTrialInfo(int $parentId): array {
        global $database;

        $sub = $database->fetchOne(
            "SELECT status, trial_end, current_period_end
             FROM subscriptions
             WHERE parent_id = ?
             ORDER BY id DESC
             LIMIT 1",
            [$parentId]
        );

        if (!$sub) {
            return [
                'status' => 'none',
                'days_remaining' => 0,
                'label' => 'No subscription',
                'expiry_date' => null,
                'is_active' => true,
            ];
        }

        $now = time();
        $endDate = $sub['status'] === 'trial' ? $sub['trial_end'] : $sub['current_period_end'];
        $endTs = $endDate ? strtotime($endDate) : $now;
        $daysRemaining = max(0, (int) floor(($endTs - $now) / 86400));
        $isActive = $sub['status'] === 'active' || ($sub['status'] === 'trial' && $endTs > $now);

        $labels = [
            'trial' => $daysRemaining > 0 ? "Free Trial Remaining: $daysRemaining Days" : 'Trial Expired',
            'active' => $daysRemaining > 0 ? "Subscription Expires In: $daysRemaining Days" : 'Subscription Expired',
            'expired' => 'Subscription Expired',
            'cancelled' => 'Subscription Cancelled',
        ];

        return [
            'status' => $sub['status'],
            'days_remaining' => $daysRemaining,
            'label' => $labels[$sub['status']] ?? 'Unknown',
            'expiry_date' => $endDate,
            'is_active' => $isActive,
        ];
    }

    /**
     * Get trial info for a learner (looks up their parent's subscription).
     */
    public static function getLearnerTrialInfo(int $learnerId): array {
        global $database;

        $parent = $database->fetchOne(
            "SELECT psl.parent_id FROM parent_student_links psl
             WHERE psl.student_id = ? AND psl.is_active = 1
             LIMIT 1",
            [$learnerId]
        );

        if (!$parent) {
            return [
                'status' => 'none',
                'days_remaining' => 0,
                'label' => '',
                'expiry_date' => null,
                'is_active' => true,
            ];
        }

        return self::getTrialInfo((int) $parent['parent_id']);
    }

    private static function paymentUrl(): string {
        $base = rtrim((defined('APP_URL') ? APP_URL : (sec_env('APP_URL', '/'))), '/');
        return $base . '/payment.php';
    }
}
