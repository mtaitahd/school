<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/claim_code_generator.php';
require_once __DIR__ . '/../php/sms_service.php';

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

$classes = $database->fetchAll(
    "SELECT * FROM classes WHERE teacher_id = ? AND is_active = 1 ORDER BY class_name",
    [$teacher_id]
);

// Handle template download
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student_import_template.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['username', 'first_name', 'last_name', 'password', 'phone', 'parent_phone']);
    fputcsv($output, ['john.doe', 'John', 'Doe', 'pass123', '+255700000000', '+255711000000']);
    fputcsv($output, ['jane.doe', 'Jane', 'Doe', 'pass456', '+255700000001', '+255711000001']);
    fputcsv($output, ['kamau.k', 'Kamau', 'Kip', 'pass789', '', '']);
    fclose($output);
    exit;
}

$import_results = [];
$preview_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    if (isset($_POST['confirm_import']) && isset($_SESSION['import_preview'])) {
        $preview_data = $_SESSION['import_preview'];
        $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;

        $success_count = 0;
        $error_count = 0;
        $results = [];

        foreach ($preview_data as $index => $row) {
            $username = trim($row['username']);
            $first_name = trim($row['first_name']);
            $last_name = trim($row['last_name']);
            $password = trim($row['password']);
            $phone = trim($row['phone'] ?? '');
            $parent_phone = trim($row['parent_phone'] ?? '');

            if (empty($username) || empty($first_name) || empty($last_name) || empty($password)) {
                $error_count++;
                $results[] = "Row " . ($index + 1) . ": Missing required fields";
                continue;
            }

            $existing = $database->fetchOne("SELECT user_id FROM users WHERE username = ?", [$username]);
            if ($existing) {
                $error_count++;
                $results[] = "Row " . ($index + 1) . ": Username '$username' already exists";
                continue;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $codeGenerator = new ClaimCodeGenerator();
            $claim_code = $codeGenerator->generateCode();

            $sql = "INSERT INTO users (username, password, role, first_name, last_name, phone, parent_phone, claim_code, claim_code_created_at)
                    VALUES (?, ?, 'learner', ?, ?, ?, ?, ?, NOW())";
            $student_id = $database->insert($sql, [$username, $hashed_password, $first_name, $last_name, $phone, $parent_phone, $claim_code]);

            if ($student_id) {
                $access_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                $database->insert("INSERT INTO student_access_codes (student_id, teacher_id, access_code) VALUES (?, ?, ?)", [$student_id, $teacher_id, $access_code]);

                if ($class_id) {
                    $database->insert("INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)", [$class_id, $student_id]);
                }

                if (!empty($parent_phone)) {
                    try {
                        $smsService = new SmsService();
                        $message = "Kona Ya Hisabati: Your child account has been created. Use this claim code to connect your child to your parent dashboard: $claim_code";
                        $smsService->sendSMS($parent_phone, $message, 'parent_link', 'parent', $student_id);
                    } catch (Exception $e) {
                        error_log("SMS sending failed: " . $e->getMessage());
                    }
                }

                $success_count++;
                $results[] = "Row " . ($index + 1) . ": $first_name $last_name ($username) - Code: $claim_code";
            } else {
                $error_count++;
                $results[] = "Row " . ($index + 1) . ": Failed to create student";
            }
        }

        unset($_SESSION['import_preview']);

        $import_results = [
            'success' => $success_count,
            'error' => $error_count,
            'messages' => $results,
            'type' => 'confirm'
        ];
    } elseif (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['import_file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $parsed_data = [];
        $parse_errors = [];

        if ($extension === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle) {
                $headers = fgetcsv($handle);
                if ($headers) {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', $headers[0]);
                    $headers = array_map(function($h) { return trim(strtolower($h)); }, $headers);

                    $line = 1;
                    while (($data = fgetcsv($handle)) !== false) {
                        $line++;
                        if (count($data) !== count($headers)) {
                            continue;
                        }
                        $row = array_combine($headers, array_map('trim', $data));
                        $username = $row['username'] ?? '';
                        $first_name = $row['first_name'] ?? '';
                        $last_name = $row['last_name'] ?? '';
                        if (!empty($username) || !empty($first_name) || !empty($last_name)) {
                            if (!isset($row['password'])) $row['password'] = '';
                            if (!isset($row['phone'])) $row['phone'] = '';
                            if (!isset($row['parent_phone'])) $row['parent_phone'] = '';
                            $parsed_data[] = $row;
                        }
                    }
                }
                fclose($handle);
            }
        } elseif (in_array($extension, ['xlsx'])) {
            try {
                $parsed_data = parseXLSX($file['tmp_name']);
            } catch (Exception $e) {
                $parse_errors[] = 'Failed to parse Excel file: ' . $e->getMessage();
            }
        } else {
            $parse_errors[] = 'Unsupported format. Please upload a .csv or .xlsx file.';
        }

        if (!empty($parsed_data)) {
            $_SESSION['import_preview'] = $parsed_data;
            $_SESSION['import_class_id'] = isset($_POST['class_id']) ? intval($_POST['class_id']) : '';
            $preview_data = $parsed_data;
        } else {
            if (empty($parse_errors)) {
                $parse_errors[] = 'No valid student data found. Ensure columns: username, first_name, last_name, password';
            }
            $import_results = ['error' => count($parse_errors), 'messages' => $parse_errors, 'type' => 'upload'];
        }
    } elseif (isset($_POST['cancel_import'])) {
        unset($_SESSION['import_preview']);
        unset($_SESSION['import_class_id']);
    }
}

function parseXLSX($filePath) {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new Exception('Cannot open XLSX file');
    }

    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $ss = simplexml_load_string($ssXml);
        if ($ss !== false) {
            foreach ($ss->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        throw new Exception('No worksheet found in XLSX file');
    }

    $xml = simplexml_load_string($sheetXml);
    if ($xml === false) {
        throw new Exception('Cannot parse worksheet data');
    }

    $ns = $xml->getNamespaces(true);
    $mainNs = $ns[''] ?? '';

    $sheetData = $xml->sheetData;
    if ($mainNs && !$sheetData) {
        $sheetData = $xml->children($mainNs)->sheetData;
    }
    if (!$sheetData) {
        throw new Exception('No sheet data found');
    }

    $rows = $sheetData->row;
    if (!$rows || count($rows) === 0) {
        throw new Exception('No rows found in worksheet');
    }

    $data = [];
    $headers = [];
    $rowIndex = 0;

    foreach ($rows as $row) {
        $cells = $mainNs ? $row->children($mainNs) : $row->children();
        $cellList = [];
        foreach ($cells->c as $cell) {
            $cellList[] = $cell;
        }

        $rowData = [];
        foreach ($cellList as $cell) {
            $type = (string)$cell['t'];
            if ($type === 's' && isset($cell->v)) {
                $idx = (int)$cell->v;
                $rowData[] = $sharedStrings[$idx] ?? '';
            } elseif (isset($cell->v)) {
                $rowData[] = (string)$cell->v;
            } else {
                $rowData[] = '';
            }
        }

        if ($rowIndex === 0) {
            if (!empty($rowData)) {
                $rowData[0] = preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', $rowData[0]);
            }
            $headers = array_map(function($h) { return trim(strtolower($h)); }, $rowData);
        } else {
            if (!empty($rowData)) {
                $assoc = [];
                foreach ($headers as $i => $h) {
                    $assoc[$h] = $rowData[$i] ?? '';
                }
                if (!isset($assoc['password'])) $assoc['password'] = '';
                if (!isset($assoc['phone'])) $assoc['phone'] = '';
                if (!isset($assoc['parent_phone'])) $assoc['parent_phone'] = '';

                $un = trim($assoc['username'] ?? '');
                $fn = trim($assoc['first_name'] ?? '');
                $ln = trim($assoc['last_name'] ?? '');
                if (!empty($un) || !empty($fn) || !empty($ln)) {
                    $data[] = $assoc;
                }
            }
        }
        $rowIndex++;
    }

    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Students - Kona Ya Hisabati</title>
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
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
        <div>
            <h1 class="activity-title mb-0">Import Students</h1>
            <p class="activity-instruction mb-0">Import multiple students from a CSV or Excel file</p>
        </div>
        <a href="learners" class="btn-child btn-child-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Learners
        </a>
    </div>

    <?php if (!empty($import_results)): ?>
        <?php if (($import_results['success'] ?? 0) > 0): ?>
            <div class="alert-child alert-child-success mb-30">
                <i class="fas fa-check-circle me-2"></i>
                Successfully created <strong><?php echo $import_results['success']; ?></strong> student(s)!
                <?php if (($import_results['error'] ?? 0) > 0): ?>
                    <br><small><?php echo $import_results['error']; ?> row(s) had errors.</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($import_results['messages'])): ?>
            <div class="dashboard-card mb-30">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                        <i class="fas fa-list"></i>
                    </div>
                    <h3 class="dashboard-card-title">Import Details</h3>
                </div>
                <div style="padding: 20px; max-height: 400px; overflow-y: auto; font-size: 0.9rem; line-height: 1.8;">
                    <?php foreach ($import_results['messages'] as $msg): ?>
                        <div><?php echo htmlspecialchars($msg); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($preview_data) && ($import_results['type'] ?? '') !== 'confirm'): ?>

    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-green);">
                <i class="fas fa-file-upload"></i>
            </div>
            <h3 class="dashboard-card-title">Upload File</h3>
        </div>
        <div style="padding: 20px;">
            <div class="alert-child alert-child-info mb-20">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Required columns:</strong> <code>username</code>, <code>first_name</code>, <code>last_name</code>, <code>password</code>
                <br><strong>Optional columns:</strong> <code>phone</code> (student phone), <code>parent_phone</code> (parent will receive SMS with claim code)
                <br><br>
                <a href="?download_template=1" class="btn-child btn-child-primary" style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 15px; font-size: 0.85rem;">
                    <i class="fas fa-download"></i> Download CSV Template
                </a>
            </div>

            <?php if (isset($import_results['type']) && $import_results['type'] === 'upload' && !empty($import_results['messages'])): ?>
                <div class="alert-child alert-child-error mb-20">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo implode('<br>', array_map('htmlspecialchars', $import_results['messages'])); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="">
                <?php echo csrf_field(); ?>
                <div class="form-group-child">
                    <label class="form-label-child">Select File *</label>
                    <input type="file" class="form-control-child" name="import_file" accept=".csv,.xlsx" required>
                    <small style="color: var(--text-light);">Accepted formats: .csv, .xlsx</small>
                </div>
                <?php if (!empty($classes)): ?>
                <div class="form-group-child">
                    <label class="form-label-child">Assign to Class (Optional)</label>
                    <select class="form-control-child" name="class_id">
                        <option value="">-- No Class --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="text-center mt-30">
                    <button type="submit" class="btn-child btn-child-primary btn-child-large">
                        <i class="fas fa-upload me-2"></i>Upload & Preview
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>

    <?php if (!empty($preview_data)): ?>
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-green);">
                <i class="fas fa-eye"></i>
            </div>
            <h3 class="dashboard-card-title">Preview: <?php echo count($preview_data); ?> student(s) found</h3>
        </div>
        <div style="padding: 20px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="background: var(--background-light);">
                        <th style="padding: 10px; border-bottom: 2px solid var(--primary-blue);">#</th>
                        <th style="padding: 10px; border-bottom: 2px solid var(--primary-blue);">Username</th>
                        <th style="padding: 10px; border-bottom: 2px solid var(--primary-blue);">First Name</th>
                        <th style="padding: 10px; border-bottom: 2px solid var(--primary-blue);">Last Name</th>
                        <th style="padding: 10px; border-bottom: 2px solid var(--primary-blue);">Password</th>
                        <th style="padding: 10px; border-bottom: 2px solid var(--primary-blue);">Phone</th>
                        <th style="padding: 10px; border-bottom: 2px solid var(--primary-blue);">Parent Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $max_preview = min(count($preview_data), 50); ?>
                    <?php for ($i = 0; $i < $max_preview; $i++): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px; text-align: center;"><?php echo $i + 1; ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($preview_data[$i]['username'] ?? ''); ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($preview_data[$i]['first_name'] ?? ''); ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($preview_data[$i]['last_name'] ?? ''); ?></td>
                            <td style="padding: 10px;"><?php echo str_repeat('&bull;', strlen($preview_data[$i]['password'] ?? '')); ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($preview_data[$i]['phone'] ?? ''); ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($preview_data[$i]['parent_phone'] ?? ''); ?></td>
                        </tr>
                    <?php endfor; ?>
                    <?php if (count($preview_data) > 50): ?>
                        <tr><td colspan="7" style="padding: 15px; text-align: center; color: var(--text-light);">
                            <em>... and <?php echo count($preview_data) - 50; ?> more row(s)</em>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="padding: 20px; border-top: 1px solid #eee;">
            <form method="POST" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="confirm_import" value="1">
                <select class="form-control-child" name="class_id" style="width: auto; min-width: 200px;">
                    <option value="">-- No Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>"
                            <?php echo (isset($_SESSION['import_class_id']) && $_SESSION['import_class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-child btn-child-primary" onclick="return confirm('Import <?php echo count($preview_data); ?> student(s)?')">
                    <i class="fas fa-check me-2"></i>Confirm Import
                </button>
                <button type="submit" name="cancel_import" value="1" class="btn-child btn-child-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
