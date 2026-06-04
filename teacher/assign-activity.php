<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/migrate.php';
ensure_schema_v2($database);

sec_require_rate_limit();

auth_require_role(['teacher'], 'login.php');

$teacher_id = auth_user_id();
$base_path = '../';
$dashboard_role = 'teacher';
$sidebar_active = 'assign';
$lang_page = 'assign-activity.php';
$message = '';
$error = '';

$learners = $database->fetchAll(
    "SELECT user_id, first_name, last_name, username FROM users WHERE role = 'learner' AND is_active = 1 ORDER BY first_name"
);
$activities = $database->fetchAll(
    "SELECT a.activity_id, a.activity_name, m.module_name
     FROM activities a
     JOIN modules m ON a.module_id = m.module_id
     WHERE a.is_active = 1
     ORDER BY m.order_index, a.order_index"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $learner_id = $_POST['learner_id'] ?? '';
    $activity_id = (int) ($_POST['activity_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if (empty($learner_id) || $activity_id <= 0) {
        $error = 'Please select a learner and an activity.';
    } else {
        // Get activity details
        $activity = $database->fetchOne(
            "SELECT a.activity_name, m.module_name FROM activities a JOIN modules m ON a.module_id = m.module_id WHERE a.activity_id = ?",
            [$activity_id]
        );
        
        $title = $activity['module_name'] . ' — ' . $activity['activity_name'];
        $description = $notes ?: 'Activity assignment';
        
        // Handle bulk assignment to all students
        if ($learner_id === 'all') {
            $all_learners = $database->fetchAll(
                "SELECT user_id FROM users WHERE role = 'learner' AND is_active = 1"
            );
            
            $assignment_created = false;
            foreach ($all_learners as $learner) {
                $exists = $database->fetchOne(
                    "SELECT assignment_id FROM assignments WHERE teacher_id = ? AND title LIKE ?",
                    [$teacher_id, '%Activity ID: ' . $activity_id . '%']
                );
                
                if (!$exists) {
                    $ok = $database->insert(
                        "INSERT INTO assignments (teacher_id, title, description, assignment_type, due_date, activity_id) VALUES (?, ?, ?, 'task', ?, ?)",
                        [$teacher_id, $title, $description, $due_date, $activity_id]
                    );
                    
                    if ($ok) {
                        $assignment_id = $ok;
                        $database->insert(
                            "INSERT INTO student_assignments (assignment_id, student_id) VALUES (?, ?)",
                            [$assignment_id, $learner['user_id']]
                        );
                        $assignment_created = true;
                    }
                }
            }
            
            if ($assignment_created) {
                $message = 'Activity assigned to all students successfully!';
            } else {
                $error = 'This activity is already assigned to all students.';
            }
        } else {
            // Single learner assignment
            $learner_id = (int) $learner_id;
            $exists = $database->fetchOne(
                "SELECT assignment_id FROM assignments WHERE teacher_id = ? AND title LIKE ?",
                [$teacher_id, '%Activity ID: ' . $activity_id . '%']
            );
            if ($exists) {
                $error = 'This activity is already assigned to that learner.';
            } else {
                $ok = $database->insert(
                    "INSERT INTO assignments (teacher_id, title, description, assignment_type, due_date, activity_id) VALUES (?, ?, ?, 'task', ?, ?)",
                    [$teacher_id, $title, $description, $due_date, $activity_id]
                );
                
                if ($ok) {
                    $assignment_id = $ok;
                    // Create student assignment link
                    $database->insert(
                        "INSERT INTO student_assignments (assignment_id, student_id) VALUES (?, ?)",
                        [$assignment_id, $learner_id]
                    );
                    
                    // Send SMS notification if parent has phone
                    $student = $database->fetchOne(
                        "SELECT u.*, p.phone FROM users u LEFT JOIN parent_student_links psl ON u.user_id = psl.student_id LEFT JOIN users p ON psl.parent_id = p.user_id WHERE u.user_id = ? AND u.role = 'learner'",
                        [$learner_id]
                    );
                    
                    $sms_status = '';
                    if ($student && $student['phone']) {
                        require_once __DIR__ . '/../php/sms_service.php';
                        $smsService = new SmsService();
                        $smsResult = $smsService->sendAssignmentReminder(
                            $student['phone'],
                            $student['first_name'] . ' ' . $student['last_name'],
                            $title,
                            $due_date ?: 'No due date',
                            $assignment_id
                        );
                        if (is_array($smsResult) && !$smsResult['success']) {
                            $sms_status = ' Message not sent';
                        } else {
                            $sms_status = ' Message sent';
                        }
                    }
                    
                    $message = 'Activity assigned successfully!' . $sms_status;
                } else {
                    $message = 'Could not save assignment. Please try again.';
                }
            }
        }
    }
}

$selected_learner = $_POST['learner_id'] ?? '';
$selected_activity = $_POST['activity_id'] ?? '';
$selected_due_date = $_POST['due_date'] ?? '';
$notes_value = trim($_POST['notes'] ?? '');

$assignments = $database->fetchAll(
    "SELECT a.*, u.first_name, u.last_name, sa.status
     FROM assignments a
     JOIN student_assignments sa ON a.assignment_id = sa.assignment_id
     JOIN users u ON sa.student_id = u.user_id
     WHERE a.teacher_id = ?
     ORDER BY a.created_at DESC",
    [$teacher_id]
);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Activity - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <h1 class="activity-title">Assign Activity</h1>
        <p class="activity-instruction mb-30">Choose a learner and activity for class or home practice.</p>

        <?php if ($message): ?>
            <div class="alert-child alert-child-success mb-20"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-child alert-child-error mb-20"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-card mb-30">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="dashboard-card-title">Assign Activity</h3>
                    <p class="activity-instruction mb-0">Open the form to assign an activity to a learner or all students.</p>
                </div>
                <button type="button" class="btn-child btn-child-primary" data-bs-toggle="modal" data-bs-target="#assignActivityModal">
                    <i class="fas fa-plus me-2"></i>Assign Activity
                </button>
            </div>
        </div>

        <div class="modal fade" id="assignActivityModal" tabindex="-1" aria-labelledby="assignActivityModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignActivityModalLabel">Assign Activity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <div class="row-child">
                                <div class="col-child-3 form-group-child">
                                    <label class="form-label-child">Learner</label>
                                    <select name="learner_id" class="form-control-child" required>
                                        <option value="">Select learner</option>
                                        <option value="all"<?php echo $selected_learner === 'all' ? ' selected' : ''; ?>>All Registered Students</option>
                                        <?php foreach ($learners as $l): ?>
                                            <option value="<?php echo (int) $l['user_id']; ?>"<?php echo $selected_learner === (string) $l['user_id'] ? ' selected' : ''; ?>>
                                                <?php echo htmlspecialchars($l['first_name'] . ' ' . $l['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-child-3 form-group-child">
                                    <label class="form-label-child">Activity</label>
                                    <select name="activity_id" class="form-control-child" required>
                                        <option value="">Select activity</option>
                                        <?php foreach ($activities as $a): ?>
                                            <option value="<?php echo (int) $a['activity_id']; ?>"<?php echo $selected_activity === (string) $a['activity_id'] ? ' selected' : ''; ?>>
                                                <?php echo htmlspecialchars($a['module_name'] . ' — ' . $a['activity_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-child-3 form-group-child">
                                    <label class="form-label-child">Due date (optional)</label>
                                    <input type="date" name="due_date" class="form-control-child" value="<?php echo htmlspecialchars($selected_due_date); ?>">
                                </div>
                            </div>
                            <div class="form-group-child">
                                <label class="form-label-child">Notes for learner/parent</label>
                                <textarea name="notes" class="form-control-child" rows="2" placeholder="e.g. Practice before Friday"><?php echo htmlspecialchars($notes_value); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-child btn-child-primary">
                                <i class="fas fa-paper-plane me-2"></i>Assign Activity
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h3 class="dashboard-card-title mb-20">Recent Assignments</h3>
            <?php if (empty($assignments)): ?>
                <p class="activity-instruction">No assignments yet.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:var(--background-light);">
                                <th style="padding:12px;text-align:left;">Learner</th>
                                <th style="padding:12px;text-align:left;">Assignment</th>
                                <th style="padding:12px;text-align:left;">Status</th>
                                <th style="padding:12px;text-align:left;">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $row): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:12px;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td style="padding:12px;"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td style="padding:12px;"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['status']))); ?></td>
                                <td style="padding:12px;"><?php echo $row['due_date'] ? htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) : '—'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($error && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
<script>
    var assignModal = new bootstrap.Modal(document.getElementById('assignActivityModal'));
    assignModal.show();
</script>
<?php endif; ?>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>



