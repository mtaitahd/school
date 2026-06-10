<?php
session_start();
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/includes/migrate.php';
require_once __DIR__ . '/../php/includes/subscription.php';
require_once __DIR__ . '/../php/includes/payment.php';

ensure_schema_v2($database);

// Check if user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: login');
    exit;
}

$parent_id = $_SESSION['user_id'];

// Handle phone number update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_phone') {
    csrf_require();
    require_once __DIR__ . '/../php/includes/validator.php';
    try {
        $phone = Validator::phone($_POST['phone'] ?? '');
        $database->execute("UPDATE users SET phone = ? WHERE user_id = ?", [$phone ?: null, $parent_id]);
        $phone_saved = true;
    } catch (Exception $e) {
        $phone_error = $e->getMessage();
    }
}

// Subscription access check
$subStatus = sub_get_status($parent_id);
$canAccess = $subStatus['is_active'];

if (!$canAccess) {
    // Allow access to dashboard only if they have children and are redirecting to topup
    // If they hit a child-progress page, the access control will block them
}

// Children linked via claim code (parent_student_links) or legacy parent_id
$children = $database->fetchAll("
    SELECT DISTINCT u.*,
           (SELECT COUNT(*) FROM progress p WHERE p.user_id = u.user_id AND p.completed = 1) as completed_activities,
           (SELECT SUM(p.stars_earned) FROM progress p WHERE p.user_id = u.user_id) as total_stars
    FROM users u
    LEFT JOIN parent_student_links psl ON psl.student_id = u.user_id AND psl.parent_id = ? AND psl.is_active = 1
    WHERE u.role = 'learner' AND (u.parent_id = ? OR psl.link_id IS NOT NULL)
    ORDER BY u.created_at DESC
", [$parent_id, $parent_id]);

// Fetch badges earned by children
$badges = [];
foreach ($children as $child) {
    $child_badges = $database->fetchAll("
        SELECT b.* 
        FROM badges b
        JOIN user_badges ub ON b.badge_id = ub.badge_id
        WHERE ub.user_id = ?
    ", [$child['user_id']]);
    $badges[$child['user_id']] = $child_badges;
}

$child_ids = array_column($children, 'user_id');
$recent_activity = [];
if (!empty($child_ids)) {
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    $recent_activity = $database->fetchAll("
        SELECT p.*, u.first_name, u.last_name, a.activity_name, m.module_name
        FROM progress p
        JOIN users u ON p.user_id = u.user_id
        JOIN activities a ON p.activity_id = a.activity_id
        JOIN modules m ON a.module_id = m.module_id
        WHERE u.user_id IN ($placeholders)
        ORDER BY p.last_attempt_at DESC
        LIMIT 10
    ", $child_ids);
}

// Fetch assignments for all children
$assignments = [];
if (!empty($child_ids)) {
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    $assignments = $database->fetchAll("
        SELECT sa.*, a.title, a.description, a.due_date, a.assignment_type, u.first_name, u.last_name,
               act.activity_id, act.activity_name, m.module_name, m.module_color
        FROM student_assignments sa
        JOIN assignments a ON sa.assignment_id = a.assignment_id
        JOIN users u ON sa.student_id = u.user_id
        LEFT JOIN activities act ON a.activity_id = act.activity_id
        LEFT JOIN modules m ON act.module_id = m.module_id
        WHERE sa.student_id IN ($placeholders)
        ORDER BY a.due_date ASC, a.created_at DESC
    ", $child_ids);
}

// Fetch current user info for profile
$current_user = $database->fetchOne("SELECT phone FROM users WHERE user_id = ?", [$parent_id]);

require_once __DIR__ . '/../php/includes/lang.php';
$current_lang = $_SESSION['lang'] ?? 'en';
$base_path = '../';
$active_nav = 'parent_dashboard';
$lang_page = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
<?php
$dashboard_role = 'parent';
$sidebar_active = 'dashboard';
$dashboard_page_title = 'Parent Dashboard';
include '../php/includes/dashboard-start.php';
?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Parent Dashboard</h1>
            <?php if (!empty($children)): ?>
                <button class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;" onclick="showClaimChildModal()">
                    <i class="fas fa-key me-2"></i>Claim Child
                </button>
            <?php endif; ?>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['claimed'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-4 text-center" style="border-radius:10px;font-size:0.9rem;border:none;max-width:700px;margin:0 auto;" role="alert">
                <i class="fas fa-check-circle me-1"></i>Child successfully linked to your account!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>setTimeout(function(){ document.querySelector('.alert-success')?.remove(); }, 5000);</script>
        <?php endif; ?>
        <?php if (isset($_GET['sms_error'])): ?>
            <div class="alert alert-warning alert-dismissible fade show py-2 px-3 mb-4 text-center" style="border-radius:10px;font-size:0.9rem;border:none;max-width:700px;margin:0 auto;" role="alert">
                <i class="fas fa-exclamation-triangle me-1"></i><?php echo htmlspecialchars($_GET['sms_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>setTimeout(function(){ document.querySelector('.alert-warning')?.remove(); }, 7000);</script>
        <?php endif; ?>

        <!-- Subscription Status Banner -->
        <?php if ($subStatus['status'] === 'trial'): ?>
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 mb-4" style="border-radius:10px;border:none;">
                <div>
                    <i class="fas fa-clock me-2"></i>
                    <strong><?= $current_lang === 'sw' ? 'Majaribio ya Bure' : 'Free Trial' ?></strong> â€”
                    <?php if ($subStatus['days_remaining'] > 0): ?>
                        <?= $current_lang === 'sw' ? 'Umesalia siku' : 'You have' ?> <strong><?= $subStatus['days_remaining'] ?></strong> <?= $current_lang === 'sw' ? 'siku za majaribio' : 'trial days remaining' ?>.
                    <?php else: ?>
                        <?= $current_lang === 'sw' ? 'Muda wa majaribio umeisha. Tafadhali lipa ili kuendelea.' : 'Trial period has ended. Please subscribe to continue.' ?>
                    <?php endif; ?>
                </div>
                <a href="../payment" class="btn btn-warning btn-sm fw-bold px-4" style="border-radius:50px;">
                    <i class="fas fa-wallet me-1"></i> <?= $current_lang === 'sw' ? 'Lipa Sasa' : 'Subscribe Now' ?> â€” 1,500 TZS
                </a>
            </div>
        <?php elseif ($subStatus['status'] === 'active'): ?>
            <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 mb-4" style="border-radius:10px;border:none;">
                <div>
                    <i class="fas fa-check-circle me-2"></i>
                    <strong><?= $current_lang === 'sw' ? 'Uanachama Unatumika' : 'Subscription Active' ?></strong> â€”
                    <?= $current_lang === 'sw' ? 'Siku zilizobaki' : 'Days remaining' ?>: <strong><?= $subStatus['days_remaining'] ?></strong>
                    <?php if ($subStatus['days_remaining'] <= 3): ?>
                        <span class="ms-2 badge bg-warning text-dark"><?= $current_lang === 'sw' ? 'Itaisha hivi karibuni' : 'Expiring soon' ?></span>
                    <?php endif; ?>
                </div>
                <a href="../payment" class="btn btn-outline-success btn-sm fw-bold px-3" style="border-radius:50px;">
                    <i class="fas fa-wallet me-1"></i> <?= $current_lang === 'sw' ? 'Jaza Salio' : 'Topup' ?>
                </a>
            </div>
        <?php elseif ($subStatus['status'] === 'expired' || !$canAccess): ?>
            <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 mb-4" style="border-radius:10px;border:none;">
                <div>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong><?= $current_lang === 'sw' ? 'Uanachama Umeisha' : 'Subscription Expired' ?></strong> â€”
                    <?= $current_lang === 'sw' ? 'Tafadhali lipa 1,500 TZS ili kuendelea kutumia huduma.' : 'Please pay 1,500 TZS to continue accessing the service.' ?>
                </div>
                <a href="../payment" class="btn btn-danger btn-sm fw-bold px-4" style="border-radius:50px;">
                    <i class="fas fa-wallet me-1"></i> <?= $current_lang === 'sw' ? 'Lipa Sasa' : 'Pay Now' ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Claim Child Alert -->
        <?php if (empty($children)): ?>
            <div class="text-center mb-4">
                <div class="alert alert-info py-3 px-4 text-center" style="border-radius:10px;font-size:0.9rem;border:none;">
                    <i class="fas fa-info-circle me-2"></i>
                    No children linked to your account yet. Use the claim code provided by the teacher to link your child!
                </div>
                <button class="btn btn-primary btn-lg" style="background:var(--primary-blue);border:none;border-radius:50px;padding:12px 30px;font-family:'Poppins',sans-serif;font-weight:600;font-size:1rem;" onclick="showClaimChildModal()">
                    <i class="fas fa-key me-2"></i>Claim Child
                </button>
            </div>
        <?php endif; ?>

        <!-- Children Cards -->
        <?php if (!empty($children)): ?>
            <div class="row g-4 mb-4">
                <?php foreach ($children as $child): ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 py-2" style="border-left:4px solid var(--primary-blue);">
                            <div class="card-body text-center">
                                <div class="icon-circle mb-3" style="background:var(--primary-blue);width:72px;height:72px;font-size:1.8rem;margin:0 auto;">
                                    <i class="fas fa-child text-white"></i>
                                </div>
                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($child['first_name']); ?></h5>
                                <p class="mb-1"><i class="fas fa-check-circle me-1" style="color:var(--primary-green);"></i> Activities: <?php echo $child['completed_activities']; ?></p>
                                <p class="mb-2"><i class="fas fa-star me-1" style="color:var(--primary-yellow);"></i> Stars: <?php echo $child['total_stars']; ?></p>
                                <?php if (isset($badges[$child['user_id']]) && !empty($badges[$child['user_id']])): ?>
                                    <div class="d-flex justify-content-center gap-2 mb-2">
                                        <?php foreach ($badges[$child['user_id']] as $badge): ?>
                                            <span class="badge" style="background:<?php echo $badge['badge_color']; ?>;width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;" title="<?php echo htmlspecialchars($badge['badge_name']); ?>">
                                                <i class="fas <?php echo $badge['badge_icon']; ?> text-white"></i>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <button class="btn btn-primary btn-sm mt-2" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 18px;font-size:0.85rem;font-weight:600;" onclick="viewChildProgress(<?php echo $child['user_id']; ?>)">
                                    <i class="fas fa-chart-line me-1"></i> View Progress
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 py-2" style="border:3px dashed var(--primary-blue);cursor:pointer;" onclick="showClaimChildModal()">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div class="text-center">
                                <i class="fas fa-key" style="font-size:3rem;color:var(--primary-blue);"></i>
                                <h5 class="mt-2" style="color:var(--primary-blue);">Claim Another Child</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <?php if (!empty($recent_activity)): ?>
            <div class="card mb-4">
                <div class="card-header py-3 d-flex align-items-center">
                    <div class="icon-circle me-3" style="background:var(--primary-orange);width:40px;height:40px;font-size:1rem;"><i class="fas fa-history text-white"></i></div>
                    <h6 class="m-0 font-weight-bold" style="color:var(--navbar-dark);">Recent Activity</h6>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <div class="icon-circle me-3" style="background:var(--primary-green);width:44px;height:44px;font-size:1.1rem;"><i class="fas fa-child text-white"></i></div>
                            <div class="flex-grow-1">
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($activity['first_name']); ?></p>
                                <p class="mb-0 text-muted small">Completed: <?php echo htmlspecialchars($activity['activity_name']); ?> (<?php echo htmlspecialchars($activity['module_name']); ?>)</p>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge" style="background:var(--primary-green);padding:5px 12px;"><?php echo $activity['score']; ?>%</span>
                                <p class="mb-0 text-muted small mt-1"><?php echo date('M d, H:i', strtotime($activity['last_attempt_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Teacher Assignments -->
        <?php if (!empty($assignments)): ?>
            <div class="card mb-4">
                <div class="card-header py-3 d-flex align-items-center">
                    <div class="icon-circle me-3" style="background:var(--primary-blue);width:40px;height:40px;font-size:1rem;"><i class="fas fa-clipboard-list text-white"></i></div>
                    <h6 class="m-0 font-weight-bold" style="color:var(--navbar-dark);">Teacher Assignments</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Child</th>
                                    <th>Assignment</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                            <?php if ($assignment['activity_name']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($assignment['activity_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge" style="background:var(--primary-purple);padding:4px 10px;"><?php echo htmlspecialchars(ucfirst($assignment['assignment_type'])); ?></span></td>
                                        <td>
                                            <span class="badge" style="background:<?php echo match($assignment['status']) { 'completed' => 'var(--primary-green)', 'in_progress' => 'var(--primary-blue)', 'overdue' => 'var(--primary-red)', default => '#e6a800' }; ?>;padding:4px 10px;">
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $assignment['status']))); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?php echo $assignment['due_date'] ? date('M d, Y', strtotime($assignment['due_date'])) : 'â€”'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="dashboard-card mb-4">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background:var(--primary-blue);">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3 class="dashboard-card-title">My Profile</h3>
            </div>
            <div class="p-3">
                <?php if (!empty($phone_saved)): ?>
                    <div class="alert alert-success py-2 px-3 text-center" style="border-radius:10px;font-size:0.9rem;border:none;">Phone number updated successfully.</div>
                <?php endif; ?>
                <?php if (!empty($phone_error)): ?>
                    <div class="alert alert-danger py-2 px-3 text-center" style="border-radius:10px;font-size:0.9rem;border:none;"><?php echo htmlspecialchars($phone_error); ?></div>
                <?php endif; ?>
                <form method="POST" action="" class="d-flex align-items-center gap-3 flex-wrap">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_phone">
                    <label class="fw-semibold text-nowrap" style="font-size:0.9rem;">Phone Number:</label>
                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" placeholder="e.g., 0712345678" style="max-width:250px;font-size:0.9rem;">
                    <button type="submit" class="btn btn-primary btn-sm" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 20px;font-size:0.85rem;">
                        <i class="fas fa-save me-1"></i>Save
                    </button>
                </form>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>Used for SMS notifications about your child's progress and assignments.
                </small>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <!-- Claim Child Modal -->
    <div class="modal fade" id="claimChildModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header">
                    <h5 class="modal-title">Claim Child</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="claim-child">
                    <div class="modal-body">
                        <p class="text-muted mb-3">Enter the claim code provided by the teacher</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Claim Code</label>
                            <input type="text" class="form-control" name="claim_code" required
                                   placeholder="KH-XXXXXX" maxlength="9"
                                   style="text-transform:uppercase;letter-spacing:2px;font-size:1.2rem;text-align:center;">
                            <small class="text-muted">Format: KH-XXXXXX (e.g., KH-7F92K1)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;font-weight:600;">
                            <i class="fas fa-key me-2"></i>Claim Child
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        <?php if (!empty($_GET['claim'])): ?>
        document.addEventListener('DOMContentLoaded', function(){ $('#claimChildModal').modal('show'); });
        <?php endif; ?>
        function showClaimChildModal() {
            $('#claimChildModal').modal('show');
        }

        function viewChildProgress(childId) {
            window.location.href = 'child-progress?child_id=' + childId;
        }
    </script>
</body>
</html>
