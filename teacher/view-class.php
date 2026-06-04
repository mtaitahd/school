<?php
require_once '__DIR__ . '/../php/includes/session.php';
require_once '__DIR__ . '/../php/includes/security.php';
require_once '__DIR__ . '/../php/includes/csrf.php';
require_once '__DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Get class ID
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Fetch class details
$class = $database->fetchOne("
    SELECT * FROM classes WHERE class_id = ? AND teacher_id = ?
", [$class_id, $teacher_id]);

if (!$class) {
    header('Location: manage-classes.php');
    exit;
}

// Handle student addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    csrf_require();
    $student_id = intval($_POST['student_id']);
    
    // Check if student exists
    $student = $database->fetchOne("SELECT * FROM users WHERE user_id = ? AND role = 'learner'", [$student_id]);
    
    if ($student) {
        // Generate unique access code
        $access_code = generateAccessCode();
        
        // Enroll student in class
        $sql = "INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)";
        $enrolled = $database->insert($sql, [$class_id, $student_id]);
        
        if ($enrolled) {
            // Create access code
            $sql = "INSERT INTO student_access_codes (student_id, teacher_id, access_code) VALUES (?, ?, ?)";
            $database->insert($sql, [$student_id, $teacher_id, $access_code]);
            
            $success = "Student added successfully! Access Code: $access_code";
        } else {
            $error = "Student is already enrolled in this class.";
        }
    } else {
        $error = "Student not found.";
    }
}

// Fetch enrolled students
$students = $database->fetchAll("
    SELECT u.*, 
           sac.access_code,
           sac.expires_at,
           (SELECT COUNT(*) FROM progress p WHERE p.user_id = u.user_id AND p.completed = 1) as completed_activities,
           (SELECT SUM(p.stars_earned) FROM progress p WHERE p.user_id = u.user_id) as total_stars
    FROM users u
    JOIN class_enrollments ce ON u.user_id = ce.student_id
    LEFT JOIN student_access_codes sac ON u.user_id = sac.student_id AND sac.teacher_id = ?
    WHERE ce.class_id = ? AND u.role = 'learner'
    ORDER BY u.created_at DESC
", [$teacher_id, $class_id]);

// Fetch available students (not enrolled in this class)
$available_students = $database->fetchAll("
    SELECT u.* FROM users u
    WHERE u.role = 'learner' 
    AND u.user_id NOT IN (
        SELECT ce.student_id FROM class_enrollments ce WHERE ce.class_id = ?
    )
    ORDER BY u.created_at DESC
", [$class_id]);

// Helper function to generate access code
function generateAccessCode() {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Class - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once '__DIR__ . '/../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'classes';
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title"><?php echo htmlspecialchars($class['class_name']); ?></h1>
        <p class="activity-instruction"><?php echo htmlspecialchars($class['class_description']); ?></p>
        <p style="margin-top:8px;color:var(--text-light);">
            <i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($class['grade_level'] ?: 'Not set'); ?>
            <span style="margin: 0 10px;">|</span>
            <i class="fas fa-calendar me-2"></i><?php echo htmlspecialchars($class['academic_year'] ?: 'Not set'); ?>
        </p>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert-child alert-child-success mb-30">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert-child alert-child-error mb-30">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Add Student Section -->
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-green);">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3 class="dashboard-card-title">Add Student to Class</h3>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_student">
            <div class="form-group-child">
                <label class="form-label-child">Select Student</label>
                <select class="form-control-child" name="student_id" required>
                    <option value="">-- Select a student --</option>
                    <?php foreach ($available_students as $student): ?>
                        <option value="<?php echo $student['user_id']; ?>">
                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> 
                            (<?php echo htmlspecialchars($student['username']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="text-center mt-30">
                <button type="submit" class="btn-child btn-child-primary btn-child-large">
                    <i class="fas fa-plus-circle me-2"></i>Add Student
                </button>
            </div>
        </form>
    </div>

    <!-- Enrolled Students -->
    <?php if (!empty($students)): ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="dashboard-card-title">Enrolled Students (<?php echo count($students); ?>)</h3>
            </div>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($students as $student): ?>
                    <div style="padding: 20px; border-bottom: 1px solid #eee;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-blue); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 5px 0;">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </h4>
                                    <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">
                                        @<?php echo htmlspecialchars($student['username']); ?>
                                    </p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="margin-bottom: 10px;">
                                    <span style="background: var(--primary-green); color: white; padding: 5px 10px; border-radius: 10px; font-size: 0.9rem;">
                                        <i class="fas fa-check-circle me-1"></i><?php echo $student['completed_activities']; ?> Activities
                                    </span>
                                    <span style="background: var(--primary-yellow); color: white; padding: 5px 10px; border-radius: 10px; font-size: 0.9rem;">
                                        <i class="fas fa-star me-1"></i><?php echo $student['total_stars']; ?> Stars
                                    </span>
                                </div>
                                <?php if ($student['access_code']): ?>
                                    <div style="background: var(--primary-purple); color: white; padding: 8px 12px; border-radius: 8px; font-size: 0.85rem;">
                                        <i class="fas fa-key me-1"></i>Code: <?php echo htmlspecialchars($student['access_code']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="dashboard-card text-center">
            <i class="fas fa-users" style="font-size: 4rem; color: var(--text-light); margin-bottom: 20px;"></i>
            <h3>No Students Enrolled</h3>
            <p style="color: var(--text-light);">Add students to this class to get started!</p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-30">
        <a href="manage-classes" class="btn-child btn-child-secondary btn-child-large">
            <i class="fas fa-arrow-left me-2"></i>Back to Classes
        </a>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



