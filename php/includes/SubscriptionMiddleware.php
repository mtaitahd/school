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

        // Learners get access through their parent (or their own subscription if unlinked)
        if ($role === 'learner') {
            $parent = $database->fetchOne(
                "SELECT psl.parent_id FROM parent_student_links psl
                 JOIN users u ON psl.parent_id = u.user_id
                 WHERE psl.student_id = ? AND psl.is_active = 1
                 LIMIT 1",
                [$userId]
            );
            if (!$parent) {
                // Fallback to legacy users.parent_id column
                $parent = $database->fetchOne(
                    "SELECT parent_id FROM users WHERE user_id = ? AND role = 'learner' AND parent_id IS NOT NULL",
                    [$userId]
                );
            }
            if (!$parent) {
                // Unlinked learner: check if they have their own subscription record
                $learnerSub = $database->fetchOne(
                    "SELECT status, current_period_end FROM subscriptions WHERE parent_id = ? ORDER BY id DESC LIMIT 1",
                    [$userId]
                );
                if ($learnerSub && $learnerSub['status'] === 'active') return true;
                return false;
            }
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

        if (!$sub) return false; // no subscription = blocked

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
                'is_active' => false,
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
            // Fallback to legacy users.parent_id column
            $parent = $database->fetchOne(
                "SELECT parent_id FROM users WHERE user_id = ? AND role = 'learner' AND parent_id IS NOT NULL",
                [$learnerId]
            );
        }

        if (!$parent) {
            // Unlinked learner: check their own subscription record
            $learnerSub = $database->fetchOne(
                "SELECT status, current_period_end FROM subscriptions WHERE parent_id = ? ORDER BY id DESC LIMIT 1",
                [$learnerId]
            );
            if ($learnerSub) {
                $now = time();
                $endTs = $learnerSub['current_period_end'] ? strtotime($learnerSub['current_period_end']) : $now;
                $daysRemaining = max(0, (int) floor(($endTs - $now) / 86400));
                return [
                    'status' => $learnerSub['status'],
                    'days_remaining' => $daysRemaining,
                    'label' => $learnerSub['status'] === 'active' ? "Subscription Expires In: $daysRemaining Days" : ucfirst($learnerSub['status']),
                    'expiry_date' => $learnerSub['current_period_end'],
                    'is_active' => $learnerSub['status'] === 'active' && $endTs > $now,
                ];
            }
            return [
                'status' => 'none',
                'days_remaining' => 0,
                'label' => '',
                'expiry_date' => null,
                'is_active' => false,
            ];
        }

        return self::getTrialInfo((int) $parent['parent_id']);
    }

    private static function paymentUrl(): string {
        $base = rtrim((defined('APP_URL') ? APP_URL : (sec_env('APP_URL', '/'))), '/');
        return $base . '/payment.php';
    }
}
