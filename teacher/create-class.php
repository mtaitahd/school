<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $class_name = trim($_POST['class_name'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '2026');
    $teacher_id = $_SESSION['user_id'];
    
    if (!empty($class_name) && !empty($grade_level)) {
        // Insert new class into database
        $result = $database->execute(
            "INSERT INTO classes (class_name, grade_level, academic_year, teacher_id, created_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [$class_name, $grade_level, $academic_year, $teacher_id]
        );
        
        if ($result) {
            header('Location: dashboard?success=class_created');
            exit;
        } else {
            $error = "Failed to create class. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Class - Kona Ya Hisabati</title>
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
    $sidebar_active = 'classes';
    $lang_page = 'create-class.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="container-child">
        <div class="text-center mb-30">
            <h1 class="activity-title">Create a New Class</h1>
            <p class="activity-instruction">Set up a new class to manage students and assign learning activities.</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-card" style="max-width: 600px; margin: 0 auto;">
            <form method="POST" action="">
                <div class="form-group-child">
                    <label for="class_name" class="form-label-child">
                        <i class="fas fa-users me-2"></i>Class Name
                    </label>
                    <input type="text" 
                           class="form-control-child" 
                           id="class_name" 
                           name="class_name" 
                           placeholder="e.g. Standard One A"
                           required>
                    <small style="color: var(--text-light);">Example: Standard One A, Standard Two B, etc.</small>
                </div>

                <div class="form-group-child">
                    <label for="grade_level" class="form-label-child">
                        <i class="fas fa-layer-group me-2"></i>Select Grade Level
                    </label>
                    <select class="form-control-child" 
                            id="grade_level" 
                            name="grade_level" 
                            required>
                        <option value="">-- Select Grade Level --</option>
                        <option value="Pre-Primary 1">Pre-Primary 1</option>
                        <option value="Pre-Primary 2">Pre-Primary 2</option>
                        <option value="Standard 1">Standard 1</option>
                        <option value="Standard 2">Standard 2</option>
                    </select>
                </div>

                <div class="form-group-child">
                    <label for="academic_year" class="form-label-child">
                        <i class="fas fa-calendar me-2"></i>Academic Year
                    </label>
                    <input type="number" 
                           class="form-control-child" 
                           id="academic_year" 
                           name="academic_year" 
                           value="2026"
                           min="2024"
                           max="2030"
                           required>
                </div>

                <div class="text-center mt-30">
                    <button type="button" 
                            class="btn-child btn-child-secondary" 
                            onclick="window.location.href='dashboard'">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" 
                            class="btn-child btn-child-primary">
                        <i class="fas fa-plus-circle me-2"></i>Create Class
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



