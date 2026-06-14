<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login');
    exit;
}
header('Location: learners?add=1');
exit;

$teacher_id = $_SESSION['user_id'];

// Handle student creation (single or multiple)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : null;
    
    // Check if this is a bulk submission (multiple students)
    if (isset($_POST['students']) && is_array($_POST['students'])) {
        $students = $_POST['students'];
        $success_count = 0;
        $error_count = 0;
        $results = [];
        
        foreach ($students as $index => $student_data) {
            $fullname = trim($student_data['fullname'] ?? '');
            $age = intval($student_data['age'] ?? 0);
            $gender = trim($student_data['gender'] ?? '');
            $parent_phone = trim($student_data['parent_phone'] ?? '');
            
            // Skip empty rows
            if (empty($fullname)) {
                continue;
            }
            
            // Split fullname into first and last name
            $nameParts = explode(' ', $fullname, 2);
            $first_name = $nameParts[0];
            $last_name = $nameParts[1] ?? '';
            
            // Validate inputs
            if (empty($fullname)) {
                $error_count++;
                $results[] = "Row " . ($index + 1) . ": Missing required fields";
                continue;
            }
            
            // Auto-generate username from fullname
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '.', $fullname));
            $username = preg_replace('/\.+/', '.', $username);
            $username = trim($username, '.');
            $base_username = $username;
            $suffix = 1;
            while ($database->fetchOne("SELECT user_id FROM users WHERE LOWER(username) = LOWER(?)", [$username])) {
                $username = $base_username . '.' . $suffix;
                $suffix++;
            }
            
            // Auto-generate password
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate claim code
            $codeGenerator = new ClaimCodeGenerator();
            $claim_code = $codeGenerator->generateCode();
            
            // Insert student with claim code
            $sql = "INSERT INTO users (username, password, role, first_name, last_name, age, gender, parent_phone, claim_code, claim_code_created_at) 
                    VALUES (?, ?, 'learner', ?, ?, ?, ?, ?, ?, NOW())";
            $params = [$username, $hashed_password, $first_name, $last_name, $age, $gender, $parent_phone, $claim_code];
            
            $student_id = $database->insert($sql, $params);
            
            if ($student_id) {
                // Generate unique access code for student login
                $access_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                
                // Create access code record
                $sql = "INSERT INTO student_access_codes (student_id, teacher_id, access_code) 
                        VALUES (?, ?, ?)";
                $database->insert($sql, [$student_id, $teacher_id, $access_code]);
                
                // Enroll in class if specified
                if ($class_id) {
                    $sql = "INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)";
                    $database->insert($sql, [$class_id, $student_id]);
                }
                
                // Send SMS to parent with claim code if parent phone provided
                if (!empty($parent_phone)) {
                    try {
                        $smsService = new SmsService();
                        $message = "Kona Ya Hisabati: Your child account has been created. Use this claim code to connect your child to your parent dashboard: $claim_code";
                        $smsResult = $smsService->sendSMS($parent_phone, $message, 'parent_link', 'parent', $student_id);
                        if (!$smsResult['success']) {
                            $results[] = "Row " . ($index + 1) . ": SMS failed to parent " . $parent_phone . " - " . $smsResult['message'];
                        }
                    } catch (Exception $e) {
                        error_log("SMS sending failed: " . $e->getMessage());
                        $results[] = "Row " . ($index + 1) . ": SMS sending failed - " . $e->getMessage();
                    }
                }
                
                $success_count++;
                $results[] = "Row " . ($index + 1) . ": $fullname - Claim Code: $claim_code";
            } else {
                $error_count++;
                $results[] = "Row " . ($index + 1) . ": Failed to create student";
            }
        }
        
        if ($success_count > 0) {
            $success = "Successfully created $success_count student(s)." . ($error_count > 0 ? " Failed: $error_count" : "");
            $success .= "<br><br>" . implode("<br>", $results);
            $_POST = [];
        } else {
            $error = "No students were created. " . implode("<br>", $results);
        }
    } else {
        // Single student submission (backward compatibility)
        $fullname = trim($_POST['fullname'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $gender = trim($_POST['gender'] ?? '');
        $parent_phone = trim($_POST['parent_phone'] ?? '');
        
        // Split fullname into first and last name
        $nameParts = explode(' ', $fullname, 2);
        $first_name = $nameParts[0];
        $last_name = $nameParts[1] ?? '';
        
        // Validate inputs
        if (empty($fullname)) {
            $error = "All required fields must be filled.";
        } else {
            // Auto-generate username from fullname
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '.', $fullname));
            $username = preg_replace('/\.+/', '.', $username);
            $username = trim($username, '.');
            $base_username = $username;
            $suffix = 1;
            while ($database->fetchOne("SELECT user_id FROM users WHERE LOWER(username) = LOWER(?)", [$username])) {
                $username = $base_username . '.' . $suffix;
                $suffix++;
            }
            
            // Auto-generate password
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate claim code
            $codeGenerator = new ClaimCodeGenerator();
            $claim_code = $codeGenerator->generateCode();
            
            // Insert student with claim code
            $sql = "INSERT INTO users (username, password, role, first_name, last_name, age, gender, parent_phone, claim_code, claim_code_created_at) 
                    VALUES (?, ?, 'learner', ?, ?, ?, ?, ?, ?, NOW())";
            $params = [$username, $hashed_password, $first_name, $last_name, $age, $gender, $parent_phone, $claim_code];
                
                $student_id = $database->insert($sql, $params);
                
                if ($student_id) {
                    // Generate unique access code for student login
                    $access_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                    
                    // Create access code record
                    $sql = "INSERT INTO student_access_codes (student_id, teacher_id, access_code) 
                            VALUES (?, ?, ?)";
                    $database->insert($sql, [$student_id, $teacher_id, $access_code]);
                    
                    // Enroll in class if specified
                    if ($class_id) {
                        $sql = "INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)";
                        $database->insert($sql, [$class_id, $student_id]);
                    }
                    
                    // Send SMS to parent with claim code if parent phone provided
                    $sms_sent = false;
                    $sms_error_message = '';
                    if (!empty($parent_phone)) {
                        try {
                            $smsService = new SmsService();
                            $message = "Kona Ya Hisabati: Your child account has been created. Use this claim code to connect your child to your parent dashboard: $claim_code";
                            $smsResult = $smsService->sendSMS($parent_phone, $message, 'parent_link', 'parent', $student_id);
                            $sms_sent = $smsResult['success'] && empty($smsResult['queued']);
                            if (!$smsResult['success']) {
                                $sms_error_message = ' SMS failed: ' . $smsResult['message'];
                            } elseif (!empty($smsResult['queued'])) {
                                $sms_error_message = ' SMS request accepted; delivery pending. Please verify the parent phone number.';
                            }
                        } catch (Exception $e) {
                            error_log("SMS sending failed: " . $e->getMessage());
                            $sms_error_message = ' SMS failed: ' . $e->getMessage();
                        }
                    }
                    
                    $success = "Student created successfully! Claim Code: $claim_code" . ($sms_sent ? " (SMS sent to parent)" : "") . $sms_error_message;
                    
                    // Clear form
                    $_POST = [];
                } else {
                    $error = "Failed to create student.";
                }
        }
    }
}

// Fetch teacher's classes
$classes = $database->fetchAll("
    SELECT * FROM classes WHERE teacher_id = ? AND is_active = 1 ORDER BY class_name
", [$teacher_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Kona Ya Hisabati</title>
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
    $sidebar_active = 'students';
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Add New Students</h1>
        <p class="activity-instruction">Create one or multiple student accounts with unique claim codes</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert-child alert-child-success mb-30">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert-child alert-child-error mb-30">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-green);">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3 class="dashboard-card-title">Student Information</h3>
        </div>
        <form method="POST" action="" id="studentForm">
            <?php if (!empty($classes)): ?>
                <div class="form-group-child">
                    <label class="form-label-child">Assign to Class (Optional)</label>
                    <select class="form-control-child" name="class_id">
                        <option value="">-- No Class --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"
                                    <?php echo isset($_POST['class_id']) && $_POST['class_id'] == $class['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div id="studentsContainer">
                <!-- First student row (static) -->
                <div class="student-row" id="studentRow1" style="background: var(--background-light); padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 2px solid var(--primary-blue);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin: 0; color: var(--primary-blue);">
                            <i class="fas fa-user-graduate me-2"></i>Student #1
                        </h4>
                    </div>
                    
                    <div class="form-group-child">
                        <label class="form-label-child">FULLNAME *</label>
                        <input type="text" class="form-control-child" name="students[1][fullname]" required placeholder="e.g. John Doe">
                    </div>
                    
                    <div class="row-child">
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">AGE *</label>
                                <input type="number" class="form-control-child" name="students[1][age]" required min="1" max="120">
                            </div>
                        </div>
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">GENDER *</label>
                                <select class="form-control-child" name="students[1][gender]" required>
                                    <option value="">-- Select --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group-child">
                        <label class="form-label-child">Parent Phone Number</label>
                        <input type="text" class="form-control-child" name="students[1][parent_phone]" placeholder="+255XXXXXXXXX">
                        <small style="color: var(--text-light);">Parent will receive SMS with claim code to link their child</small>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-20">
                <button type="button" class="btn-child btn-child-secondary" onclick="addStudentRow()">
                    <i class="fas fa-plus-circle me-2"></i>Add Another Student
                </button>
            </div>
            
            <div class="text-center mt-30">
                <button type="submit" class="btn-child btn-child-primary btn-child-large">
                    <i class="fas fa-save me-2"></i>Create Student(s)
                </button>
            </div>
        </form>
    </div>

    <div class="text-center mt-30">
        <a href="dashboard" class="btn-child btn-child-secondary btn-child-large">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        let studentCount = 1;

        function addStudentRow() {
            studentCount++;
            const container = document.getElementById('studentsContainer');
            
            const row = document.createElement('div');
            row.className = 'student-row';
            row.id = 'studentRow' + studentCount;
            row.style.cssText = 'background: var(--background-light); padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 2px solid var(--primary-blue);';
            
            row.innerHTML = 
                '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">' +
                    '<h4 style="margin: 0; color: var(--primary-blue);">' +
                        '<i class="fas fa-user-graduate me-2"></i>Student #' + studentCount +
                    '</h4>' +
                    '<button type="button" class="btn-child btn-child-danger" onclick="removeStudentRow(' + studentCount + ')" style="min-height: 35px; min-width: 35px;">' +
                        '<i class="fas fa-times"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="form-group-child">' +
                    '<label class="form-label-child">FULLNAME *</label>' +
                    '<input type="text" class="form-control-child" name="students[' + studentCount + '][fullname]" required placeholder="e.g. John Doe">' +
                '</div>' +
                '<div class="row-child">' +
                    '<div class="col-child-2">' +
                        '<div class="form-group-child">' +
                            '<label class="form-label-child">AGE *</label>' +
                            '<input type="number" class="form-control-child" name="students[' + studentCount + '][age]" required min="1" max="120">' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-child-2">' +
                        '<div class="form-group-child">' +
                            '<label class="form-label-child">GENDER *</label>' +
                            '<select class="form-control-child" name="students[' + studentCount + '][gender]" required>' +
                                '<option value="">-- Select --</option>' +
                                '<option value="Male">Male</option>' +
                                '<option value="Female">Female</option>' +
                                '<option value="Other">Other</option>' +
                            '</select>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="form-group-child">' +
                    '<label class="form-label-child">Parent Phone Number</label>' +
                    '<input type="text" class="form-control-child" name="students[' + studentCount + '][parent_phone]" placeholder="+255XXXXXXXXX">' +
                    '<small style="color: var(--text-light);">Parent will receive SMS with claim code to link their child</small>' +
                '</div>';
            
            container.appendChild(row);
        }

        function removeStudentRow(rowId) {
            const row = document.getElementById('studentRow' + rowId);
            if (row) {
                row.remove();
            }
        }
    </script>
</body>
</html>



