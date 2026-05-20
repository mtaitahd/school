<?php
session_start();
require_once '../php/db_connection.php';
require_once '../php/includes/lang.php';
require_once '../php/includes/auth.php';

auth_require_role(['admin'], 'login.php');

$admin_id = auth_user_id();
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'upload';
$lang_page = 'upload-content.php';
$message = '';
$error = '';

$upload_dir = dirname(__DIR__) . '/uploads/content/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$modules = $database->fetchAll("SELECT module_id, module_name FROM modules ORDER BY order_index");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'upload';

    if ($action === 'module') {
        $name = trim($_POST['module_name'] ?? '');
        $icon = trim($_POST['module_icon'] ?? 'fa-star');
        $color = trim($_POST['module_color'] ?? '#4A90E2');
        if ($name !== '') {
            $database->insert(
                "INSERT INTO modules (module_name, module_description, module_icon, module_color, order_index) VALUES (?, ?, ?, ?, ?)",
                [$name, trim($_POST['module_description'] ?? ''), $icon, $color, (int) ($_POST['order_index'] ?? 99)]
            );
            $message = 'Module created successfully.';
        } else {
            $error = 'Module name is required.';
        }
    } elseif ($action === 'activity') {
        $module_id = (int) ($_POST['module_id'] ?? 0);
        $name = trim($_POST['activity_name'] ?? '');
        $type = $_POST['activity_type'] ?? 'game';
        if ($module_id > 0 && $name !== '') {
            $database->insert(
                "INSERT INTO activities (module_id, activity_name, activity_description, activity_type, difficulty_level, activity_data, order_index)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $module_id,
                    $name,
                    trim($_POST['activity_description'] ?? ''),
                    $type,
                    $_POST['difficulty_level'] ?? 'easy',
                    json_encode(['engine' => $type]),
                    (int) ($_POST['order_index'] ?? 99),
                ]
            );
            $message = 'Activity created successfully.';
        } else {
            $error = 'Module and activity name are required.';
        }
    } elseif ($action === 'upload' && !empty($_FILES['content_file']['name'])) {
        $file = $_FILES['content_file'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'audio/mpeg', 'audio/wav', 'application/pdf'];
        $max_size = 5 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed. Please try again.';
        } elseif ($file['size'] > $max_size) {
            $error = 'File is too large (max 5MB).';
        } elseif (!in_array($file['type'], $allowed, true)) {
            $error = 'File type not allowed. Use JPG, PNG, MP3, or PDF.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $filename = $safe . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $related_type = $_POST['related_type'] ?? 'image';
                $related_id = !empty($_POST['related_id']) ? (int) $_POST['related_id'] : null;
                $database->insert(
                    "INSERT INTO content_uploads (uploaded_by, file_name, file_path, file_type, related_type, related_id, description)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $admin_id,
                        $file['name'],
                        'uploads/content/' . $filename,
                        $file['type'],
                        $related_type,
                        $related_id,
                        trim($_POST['description'] ?? ''),
                    ]
                );
                $message = 'File uploaded successfully.';
            } else {
                $error = 'Could not save file on server.';
            }
        }
    }
}

$uploads = $database->fetchAll(
    "SELECT cu.*, u.first_name FROM content_uploads cu JOIN users u ON cu.uploaded_by = u.user_id ORDER BY cu.created_at DESC LIMIT 20"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
            <div>
                <h1 class="activity-title mb-0">Upload & Manage Content</h1>
                <p class="activity-instruction mb-0">Add modules, activities, and media files</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn-child btn-child-primary" onclick="openModal('moduleModal')"><i class="fas fa-cubes me-1"></i>Module</button>
                <button type="button" class="btn-child btn-child-green" onclick="openModal('activityModal')"><i class="fas fa-tasks me-1"></i>Activity</button>
                <button type="button" class="btn-child btn-child-yellow" onclick="openModal('uploadModal')"><i class="fas fa-upload me-1"></i>File</button>
            </div>
        </div>

        <?php if ($message): ?><div class="alert-child alert-child-success mb-20"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-child alert-child-error mb-20"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="row-child mb-30" style="display:none;" aria-hidden="true">
            <div class="col-child-3">
                <div class="dashboard-card">
                    <h3 class="dashboard-card-title mb-20"><i class="fas fa-cubes me-2"></i>New Module</h3>
                    <form method="POST" id="moduleFormLegacy">
                        <input type="hidden" name="action" value="module">
                        <div class="form-group-child">
                            <label class="form-label-child">Name</label>
                            <input type="text" name="module_name" class="form-control-child" required>
                        </div>
                        <div class="form-group-child">
                            <label class="form-label-child">Icon (Font Awesome)</label>
                            <input type="text" name="module_icon" class="form-control-child" value="fa-star" placeholder="fa-calculator">
                        </div>
                        <div class="form-group-child">
                            <label class="form-label-child">Color</label>
                            <input type="color" name="module_color" class="form-control-child" value="#4A90E2">
                        </div>
                        <button type="submit" class="btn-child btn-child-primary mt-20">Create Module</button>
                    </form>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <h3 class="dashboard-card-title mb-20"><i class="fas fa-tasks me-2"></i>New Activity</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="activity">
                        <div class="form-group-child">
                            <label class="form-label-child">Module</label>
                            <select name="module_id" class="form-control-child" required>
                                <?php foreach ($modules as $m): ?>
                                    <option value="<?php echo (int) $m['module_id']; ?>"><?php echo htmlspecialchars($m['module_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group-child">
                            <label class="form-label-child">Activity name</label>
                            <input type="text" name="activity_name" class="form-control-child" required>
                        </div>
                        <div class="form-group-child">
                            <label class="form-label-child">Type</label>
                            <select name="activity_type" class="form-control-child">
                                <option value="counting">Counting</option>
                                <option value="shapes">Shapes</option>
                                <option value="addition">Addition</option>
                                <option value="subtraction">Subtraction</option>
                                <option value="matching">Matching</option>
                                <option value="game">Game</option>
                                <option value="quiz">Quiz</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-child btn-child-green mt-20">Create Activity</button>
                    </form>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <h3 class="dashboard-card-title mb-20"><i class="fas fa-cloud-upload-alt me-2"></i>Upload File</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        <div class="form-group-child">
                            <label class="form-label-child">File (image, audio, PDF)</label>
                            <input type="file" name="content_file" class="form-control-child" accept=".jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.pdf" required>
                        </div>
                        <div class="form-group-child">
                            <label class="form-label-child">Content type</label>
                            <select name="related_type" class="form-control-child">
                                <option value="image">Image</option>
                                <option value="audio">Audio</option>
                                <option value="worksheet">Worksheet (PDF)</option>
                                <option value="activity">Activity asset</option>
                            </select>
                        </div>
                        <div class="form-group-child">
                            <label class="form-label-child">Description</label>
                            <input type="text" name="description" class="form-control-child">
                        </div>
                        <button type="submit" class="btn-child btn-child-yellow mt-20">Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h3 class="dashboard-card-title mb-20">Recent Uploads</h3>
            <?php if (empty($uploads)): ?>
                <p class="activity-instruction">No files uploaded yet.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:var(--background-light);">
                                <th style="padding:12px;text-align:left;">File</th>
                                <th style="padding:12px;text-align:left;">Type</th>
                                <th style="padding:12px;text-align:left;">By</th>
                                <th style="padding:12px;text-align:left;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uploads as $u): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:12px;"><a href="../<?php echo htmlspecialchars($u['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($u['file_name']); ?></a></td>
                                <td style="padding:12px;"><?php echo htmlspecialchars($u['related_type']); ?></td>
                                <td style="padding:12px;"><?php echo htmlspecialchars($u['first_name']); ?></td>
                                <td style="padding:12px;"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <div id="moduleModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal" role="dialog">
            <div class="kona-modal-header"><h3>New Module</h3><button type="button" class="kona-modal-close" data-modal-close>&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="module">
                <div class="kona-modal-body">
                    <div class="form-group-child"><label class="form-label-child">Name *</label><input type="text" name="module_name" class="form-control-child" required></div>
                    
                    <div class="form-group-child"><label class="form-label-child">Icon</label><input type="text" name="module_icon" class="form-control-child" value="fa-star"></div>
                    <div class="form-group-child"><label class="form-label-child">Color</label><input type="color" name="module_color" class="form-control-child" value="#4A90E2"></div>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
    <div id="activityModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal" role="dialog">
            <div class="kona-modal-header"><h3>New Activity</h3><button type="button" class="kona-modal-close" data-modal-close>&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="activity">
                <div class="kona-modal-body">
                    <div class="form-group-child"><label class="form-label-child">Module *</label>
                        <select name="module_id" class="form-control-child" required>
                            <?php foreach ($modules as $m): ?><option value="<?php echo (int)$m['module_id']; ?>"><?php echo htmlspecialchars($m['module_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-child"><label class="form-label-child">Name *</label><input type="text" name="activity_name" class="form-control-child" required></div>
                    <div class="form-group-child"><label class="form-label-child">Type</label>
                        <select name="activity_type" class="form-control-child"><option value="counting">Counting</option><option value="game">Game</option><option value="quiz">Quiz</option></select>
                    </div>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-green">Create</button>
                </div>
            </form>
        </div>
    </div>
    <div id="uploadModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal" role="dialog">
            <div class="kona-modal-header"><h3>Upload File</h3><button type="button" class="kona-modal-close" data-modal-close>&times;</button></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="kona-modal-body">
                    <div class="form-group-child"><label class="form-label-child">File *</label><input type="file" name="content_file" class="form-control-child" required accept=".jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.pdf"></div>
                    <div class="form-group-child"><label class="form-label-child">Type</label>
                        <select name="related_type" class="form-control-child"><option value="image">Image</option><option value="audio">Audio</option><option value="worksheet">PDF</option></select>
                    </div>
                    <div class="form-group-child"><label class="form-label-child">Description</label><input type="text" name="description" class="form-control-child"></div>
                </div>
                
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-yellow">Upload</button>
                </div>
            </form>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
<script src="../js/modals.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>



