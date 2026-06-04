<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/claim_code_generator.php';
require_once __DIR__ . '/../php/sms_service.php';

sec_require_rate_limit();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

$classes = $database->fetchAll(
    "SELECT * FROM classes WHERE teacher_id = ? AND is_active = 1 ORDER BY class_name",
    [$teacher_id]
);

// Handle regenerate code action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regenerate_code') {
    csrf_require();
    $student_id = intval($_POST['student_id']);
    $codeGenerator = new ClaimCodeGenerator();
    
    try {
        $new_code = $codeGenerator->regenerateCode($student_id);
        $success = "Claim code regenerated successfully: $new_code";
    } catch (Exception $e) {
        $error = "Failed to regenerate claim code: " . $e->getMessage();
    }
}

// Handle update learner action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_learner') {
    csrf_require();
    $student_id = intval($_POST['student_id']);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!empty($first_name) && !empty($last_name)) {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, parent_phone = ?, is_active = ? WHERE user_id = ?";
        $params = [$first_name, $last_name, $phone, $parent_phone, $is_active, $student_id];
        
        if ($database->execute($sql, $params)) {
            $success = "Learner information updated successfully!";
        } else {
            $error = "Failed to update learner information.";
        }
    } else {
        $error = "First name and last name are required.";
    }
}

// Handle resend SMS action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_sms') {
    csrf_require();
    $student_id = intval($_POST['student_id']);
    
    $student = $database->fetchOne("SELECT claim_code, parent_phone, first_name FROM users WHERE user_id = ?", [$student_id]);
    
    if ($student && $student['claim_code'] && $student['parent_phone']) {
        try {
            $smsService = new SmsService();
            $message = "Kona Ya Hisabati: Your child account has been created. Use this claim code to connect your child to your parent dashboard: " . $student['claim_code'];
            $smsResult = $smsService->sendSMS($student['parent_phone'], $message, 'parent_link', 'parent', $student_id);
            
            if ($smsResult['success']) {
                if (!empty($smsResult['queued'])) {
                    $success = "SMS request accepted; delivery pending. Please verify the parent phone number.";
                } else {
                    $success = "SMS sent successfully to parent";
                }
            } else {
                $error = "Failed to send SMS: " . $smsResult['message'];
            }
        } catch (Exception $e) {
            $error = "Failed to send SMS: " . $e->getMessage();
        }
    } else {
        $error = "Student has no claim code or parent phone number";
    }
}

// Fetch teacher's assigned learners
$learners = $database->fetchAll("
    SELECT u.*, 
           (SELECT COUNT(*) FROM progress p WHERE p.user_id = u.user_id AND p.completed = 1) as completed_activities,
           (SELECT SUM(p.stars_earned) FROM progress p WHERE p.user_id = u.user_id) as total_stars,
           (SELECT ROUND(AVG(sa.score), 0) FROM student_assignments sa JOIN assignments a ON sa.assignment_id = a.assignment_id WHERE sa.student_id = u.user_id AND sa.score IS NOT NULL) as avg_assignment_score,
           (SELECT COUNT(*) FROM student_assignments sa WHERE sa.student_id = u.user_id AND sa.status = 'completed') as completed_assignments,
           (SELECT COUNT(*) FROM student_assignments sa WHERE sa.student_id = u.user_id) as total_assignments
    FROM users u 
    WHERE u.role = 'learner' 
    ORDER BY u.created_at DESC
");

// Fetch learner details if editing
$editing_learner = null;
if (isset($_GET['edit'])) {
    $learner_id = intval($_GET['edit']);
    $editing_learner = $database->fetchOne("
        SELECT * FROM users WHERE user_id = ? AND role = 'learner'
    ", [$learner_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learners Progress - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once __DIR__ . '/../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'learners';
    $lang_page = 'learners.php';
    include '../php/includes/dashboard-start.php';
    ?>

    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
        <div>
            <h1 class="activity-title mb-0">Learners Progress</h1>
            <p class="activity-instruction mb-0">Monitor learner performance and manage students</p>
        </div>
        <button type="button" class="btn-child btn-child-primary" onclick="openModal('addStudentModal')">
            <i class="fas fa-user-plus me-2"></i>Add Student
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; max-width: 400px;" role="alert">
            <i class="fas fa-check-circle me-2"></i>Student created! Claim code: <strong><?php echo htmlspecialchars($_GET['code'] ?? ''); ?></strong> � share with parent.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>setTimeout(function(){ document.querySelector('.alert-success')?.remove(); }, 5000);</script>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; max-width: 400px;" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php
            $errs = ['missing_fields' => 'Please fill all required fields.', 'username_exists' => 'Username already exists.', 'create_failed' => 'Failed to create student.'];
            echo htmlspecialchars($errs[$_GET['error']] ?? 'An error occurred.');
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>setTimeout(function(){ document.querySelector('.alert-danger')?.remove(); }, 5000);</script>
    <?php endif; ?>

    <?php if (isset($_GET['sms_error'])): ?>
        <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; max-width: 420px;" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['sms_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>setTimeout(function(){ document.querySelector('.alert-warning')?.remove(); }, 7000);</script>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; max-width: 400px;" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>setTimeout(function(){ document.querySelector('.alert-success')?.remove(); }, 5000);</script>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; max-width: 400px;" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>setTimeout(function(){ document.querySelector('.alert-danger')?.remove(); }, 5000);</script>
    <?php endif; ?>


    <!-- Learners List -->
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-green);">
                <i class="fas fa-child"></i>
            </div>
            <h3 class="dashboard-card-title">All Learners</h3>
        </div>
        <?php if (empty($learners)): ?>
            <p class="text-center activity-instruction">No learners registered yet.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--background-light);">
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid var(--primary-blue);">Name</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Claim Code</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Parent Status</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Activities</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Stars</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Assignments</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Avg Score</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Joined</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid var(--primary-blue);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($learners as $learner): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px;">
                                    <strong><?php echo htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']); ?></strong>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php if ($learner['claim_code']): ?>
                                        <code style="background: var(--background-light); padding: 5px 10px; border-radius: 5px; font-weight: 600; color: var(--primary-blue);">
                                            <?php echo htmlspecialchars($learner['claim_code']); ?>
                                        </code>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">No code</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php if ($learner['parent_claimed']): ?>
                                        <span style="background: var(--primary-green); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.85rem;">
                                            <i class="fas fa-check-circle me-1"></i>Claimed
                                        </span>
                                    <?php else: ?>
                                        <span style="background: var(--primary-orange); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.85rem;">
                                            <i class="fas fa-clock me-1"></i>Not Claimed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="background: var(--primary-green); color: white; padding: 5px 15px; border-radius: 15px;">
                                        <?php echo $learner['completed_activities']; ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="background: var(--primary-yellow); color: var(--text-dark); padding: 5px 15px; border-radius: 15px;">
                                        <i class="fas fa-star me-1"></i><?php echo $learner['total_stars']; ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="background: var(--primary-blue); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.85rem;">
                                        <?php echo $learner['completed_assignments'] ?? 0; ?>/<?php echo $learner['total_assignments'] ?? 0; ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="background: <?php echo ($learner['avg_assignment_score'] ?? 0) >= 70 ? 'var(--primary-green)' : (($learner['avg_assignment_score'] ?? 0) >= 40 ? 'var(--primary-orange)' : 'var(--primary-red)'); ?>; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.85rem;">
                                        <?php echo $learner['avg_assignment_score'] ?? '—'; ?>%
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php echo date('M d, Y', strtotime($learner['created_at'])); ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                        <button type="button" class="btn-child btn-child-warning" style="min-height: 35px; min-width: 35px; font-size: 0.85rem;" 
                                                onclick="openEditLearnerModal(<?php echo $learner['user_id']; ?>, 
                                                    '<?php echo htmlspecialchars($learner['first_name'], ENT_QUOTES); ?>', 
                                                    '<?php echo htmlspecialchars($learner['last_name'], ENT_QUOTES); ?>', 
                                                    '<?php echo htmlspecialchars($learner['username'], ENT_QUOTES); ?>', 
                                                    '<?php echo htmlspecialchars($learner['phone'] ?? '', ENT_QUOTES); ?>', 
                                                    '<?php echo htmlspecialchars($learner['parent_phone'] ?? '', ENT_QUOTES); ?>', 
                                                    <?php echo $learner['is_active'] ? 'true' : 'false'; ?>)" 
                                                title="Edit Learner">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($learner['claim_code']): ?>
                                            <button class="btn-child btn-child-info" style="min-height: 35px; min-width: 35px; font-size: 0.85rem;" onclick="copyCode('<?php echo htmlspecialchars($learner['claim_code']); ?>')" title="Copy Code">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="regenerate_code">
                                                <input type="hidden" name="student_id" value="<?php echo $learner['user_id']; ?>">
                                                <button type="submit" class="btn-child btn-child-warning" style="min-height: 35px; min-width: 35px; font-size: 0.85rem;" title="Regenerate Code" onclick="return confirm('Are you sure you want to regenerate the claim code? The old code will no longer work.');">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                            <?php if ($learner['parent_phone']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="resend_sms">
                                                    <input type="hidden" name="student_id" value="<?php echo $learner['user_id']; ?>">
                                                    <button type="submit" class="btn-child btn-child-primary" style="min-height: 35px; min-width: 35px; font-size: 0.85rem;" title="Send SMS Again" onclick="return confirm('Send claim code SMS to parent again?');">
                                                        <i class="fas fa-sms"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <button class="btn-child btn-child-primary" style="min-height: 35px; min-width: 35px; font-size: 0.85rem;" onclick="viewLearnerDetails(<?php echo $learner['user_id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal" role="dialog" aria-labelledby="addStudentTitle">
            <div class="kona-modal-header">
                <h3 id="addStudentTitle"><i class="fas fa-user-plus me-2"></i>Add Student</h3>
                <button type="button" class="kona-modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="student-actions">
                <input type="hidden" name="action" value="add_student">
                <input type="hidden" name="redirect" value="learners.php">
                <div class="kona-modal-body">
                    <?php if (!empty($classes)): ?>
                    <div class="form-group-child">
                        <label class="form-label-child">Class (optional)</label>
                        <select class="form-control-child" name="class_id">
                            <option value="">-- No class --</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo (int)$class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group-child">
                        <label class="form-label-child">Username *</label>
                        <input type="text" class="form-control-child" name="username" required autocomplete="off">
                    </div>
                    <div class="row-child">
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">First name *</label>
                                <input type="text" class="form-control-child" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Last name *</label>
                                <input type="text" class="form-control-child" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Password *</label>
                        <input type="password" class="form-control-child" name="password" required minlength="6">
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Parent phone (for claim code SMS)</label>
                        <input type="text" class="form-control-child" name="parent_phone" placeholder="+255XXXXXXXXX">
                        <small style="color:var(--text-light);">Parent uses claim code on their dashboard � no direct add.</small>
                    </div>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-primary"><i class="fas fa-save me-2"></i>Create Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Learner Modal -->
    <div id="editLearnerModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal" role="dialog" aria-labelledby="editLearnerTitle">
            <div class="kona-modal-header">
                <h3 id="editLearnerTitle"><i class="fas fa-edit me-2"></i>Edit Learner</h3>
                <button type="button" class="kona-modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_learner">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="kona-modal-body">
                    <div class="row-child">
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">First Name</label>
                                <input type="text" class="form-control-child" name="first_name" id="edit_first_name" required>
                            </div>
                        </div>
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Last Name</label>
                                <input type="text" class="form-control-child" name="last_name" id="edit_last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Username</label>
                        <input type="text" class="form-control-child" id="edit_username" disabled>
                        <small style="color: var(--text-light);">Username cannot be changed</small>
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Phone Number</label>
                        <input type="text" class="form-control-child" name="phone" id="edit_phone" placeholder="+255XXXXXXXXX">
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Parent Phone Number</label>
                        <input type="text" class="form-control-child" name="parent_phone" id="edit_parent_phone" placeholder="+255XXXXXXXXX">
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">
                            <input type="checkbox" name="is_active" id="edit_is_active">
                            Active Account
                        </label>
                    </div>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-primary"><i class="fas fa-save me-2"></i>Update Learner</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/modals.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        <?php if (!empty($_GET['add'])): ?>document.addEventListener('DOMContentLoaded', function(){ openModal('addStudentModal'); });<?php endif; ?>
        function viewLearnerDetails(learnerId) {
            window.location.href = '../parent/child-progress.php?child_id=' + learnerId;
        }

        function copyCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                alert('Claim code copied to clipboard: ' + code);
            }, function(err) {
                alert('Failed to copy code: ' + err);
            });
        }

        function openEditLearnerModal(studentId, firstName, lastName, username, phone, parentPhone, isActive) {
            document.getElementById('edit_student_id').value = studentId;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_parent_phone').value = parentPhone;
            document.getElementById('edit_is_active').checked = isActive;
            openModal('editLearnerModal');
        }
    </script>
</body>
</html>




