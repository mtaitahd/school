<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/migrate.php';
ensure_schema_v2($database);

sec_require_rate_limit();

// Check if user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: ../login.php');
    exit;
}

$parent_id = $_SESSION['user_id'];

// Handle claim code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_code'])) {
    csrf_require();
    $access_code = trim(strtoupper($_POST['access_code']));
    
    // Validate access code format
    if (strlen($access_code) !== 8) {
        $error = "Invalid access code format. Code must be 8 characters.";
    } else {
        // Look up the access code with expiration check
        $access_record = $database->fetchOne("
            SELECT sac.*, u.first_name, u.last_name, u.user_id as student_id
            FROM student_access_codes sac
            JOIN users u ON sac.student_id = u.user_id
            WHERE sac.access_code = ? AND sac.is_active = 1
        ", [$access_code]);
        
        if (!$access_record) {
            $error = "Invalid or expired access code. Please check and try again.";
        } else {
            // Check if code has expired
            if ($access_record['expires_at'] && strtotime($access_record['expires_at']) < time()) {
                $error = "This access code has expired. Please ask the teacher for a new code.";
            } else {
                // Check if already linked
                $existing_link = $database->fetchOne("
                    SELECT * FROM parent_student_links 
                    WHERE parent_id = ? AND student_id = ? AND is_active = 1
                ", [$parent_id, $access_record['student_id']]);
                
                if ($existing_link) {
                    $error = "You are already linked to this student.";
                } else {
                    // Create the link
                    $sql = "INSERT INTO parent_student_links (parent_id, student_id, access_code) 
                            VALUES (?, ?, ?)";
                    $link_id = $database->insert($sql, [$parent_id, $access_record['student_id'], $access_code]);
                    
                    if ($link_id) {
                        $success = "Successfully linked to " . htmlspecialchars($access_record['first_name'] . ' ' . $access_record['last_name']) . "!";
                        
                        // Send SMS confirmation if parent has phone number
                        $parent = $database->fetchOne("SELECT phone FROM users WHERE user_id = ?", [$parent_id]);
                        if ($parent && $parent['phone']) {
                            require_once __DIR__ . '/../php/sms_service.php';
                            $smsService = new SmsService();
                            $smsResult = $smsService->sendParentLinkingConfirmation(
                                $parent['phone'],
                                $access_record['first_name'] . ' ' . $access_record['last_name'],
                                $access_code,
                                $access_record['student_id']
                            );
                            if (is_array($smsResult) && !$smsResult['success']) {
                                $success .= ' Message not sent';
                            } else {
                                $success .= ' Message sent';
                            }
                        }
                    } else {
                        $error = "Failed to link student. Please try again.";
                    }
                }
            }
        }
    }
}

// Handle reset code request (for teachers to reset a student's access code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_code' && isset($_POST['student_id'])) {
    csrf_require();
    $student_id = intval($_POST['student_id']);
    
    // Verify parent is linked to this student
    $link = $database->fetchOne("
        SELECT * FROM parent_student_links 
        WHERE parent_id = ? AND student_id = ? AND is_active = 1
    ", [$parent_id, $student_id]);
    
    if ($link) {
        // Generate new access code
        $new_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        // Update the access code with new expiration (30 days from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $database->execute("
            UPDATE student_access_codes 
            SET access_code = ?, expires_at = ?, is_active = 1, updated_at = NOW()
            WHERE student_id = ?
        ", [$new_code, $expires_at, $student_id]);
        
        $success = "Access code has been reset. New code: $new_code (Valid for 30 days)";
    } else {
        $error = "You are not authorized to reset this student's access code.";
    }
}

// Fetch linked children
$children = $database->fetchAll("
    SELECT u.*, 
           psl.linked_at,
           (SELECT COUNT(*) FROM progress p WHERE p.user_id = u.user_id AND p.completed = 1) as completed_activities,
           (SELECT SUM(p.stars_earned) FROM progress p WHERE p.user_id = u.user_id) as total_stars
    FROM users u
    JOIN parent_student_links psl ON u.user_id = psl.student_id
    WHERE psl.parent_id = ? AND psl.is_active = 1
    ORDER BY psl.linked_at DESC
", [$parent_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Student - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once __DIR__ . '/../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'parent';
    $sidebar_active = 'claim';
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Claim Student</h1>
        <p class="activity-instruction">Enter the access code provided by the teacher to link to your child</p>
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

    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                <i class="fas fa-key"></i>
            </div>
            <h3 class="dashboard-card-title">Enter Access Code</h3>
        </div>
        <form method="POST" action="">
            <div class="form-group-child">
                <label class="form-label-child">Access Code</label>
                <input type="text" class="form-control-child" name="access_code" 
                       placeholder="Enter 8-character code" maxlength="8" 
                       style="text-transform: uppercase; letter-spacing: 2px; font-size: 1.2rem; text-align: center;" required>
                <p style="margin-top: 10px; color: var(--text-light); font-size: 0.9rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    The teacher will provide you with a unique 8-character access code for your child.
                </p>
            </div>
            <div class="text-center mt-30">
                <button type="submit" class="btn-child btn-child-primary btn-child-large">
                    <i class="fas fa-link me-2"></i>Link to Student
                </button>
            </div>
        </form>
    </div>

    <!-- Linked Children -->
    <?php if (!empty($children)): ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="dashboard-card-title">Linked Children (<?php echo count($children); ?>)</h3>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($children as $child): ?>
                    <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-blue); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                            <i class="fas fa-child"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 5px 0;">
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                            </h4>
                            <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">
                                <i class="fas fa-check-circle me-1"></i><?php echo $child['completed_activities']; ?> Activities
                                <span style="margin: 0 10px;">|</span>
                                <i class="fas fa-star me-1"></i><?php echo $child['total_stars']; ?> Stars
                            </p>
                            <p style="margin: 5px 0 0 0; color: var(--text-light); font-size: 0.8rem;">
                                Linked on <?php echo date('M d, Y', strtotime($child['linked_at'])); ?>
                            </p>
                        </div>
                        <a href="child-progress.php?child_id=<?php echo $child['user_id']; ?>" 
                           class="btn-child btn-child-info" style="min-height: 40px; min-width: 40px;">
                            <i class="fas fa-chart-line"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mt-30">
        <a href="dashboard" class="btn-child btn-child-secondary btn-child-large">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



