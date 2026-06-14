<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();

auth_require_role(['admin'], 'index');

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
    csrf_require();
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
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'audio/mpeg', 'audio/wav', 'application/pdf'];
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp3', 'wav', 'pdf'];
        $max_size = 5 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed. Please try again.';
        } elseif ($file['size'] > $max_size) {
            $error = 'File is too large (max 5MB).';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($detected_mime, $allowed_mimes, true)) {
                $error = 'File type not allowed. Use JPG, PNG, WebP, MP3, WAV, or PDF.';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_exts, true)) {
                    $error = 'File extension not allowed.';
                } else {
                    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                    $filename = $safe . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
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
                                $detected_mime,
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
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-1 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Upload & Manage Content</h1>
                <p class="text-muted mb-0" style="font-size:0.9rem;">Add modules, activities, and media files</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#moduleModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 20px;font-size:0.85rem;font-weight:600;"><i class="fas fa-cubes me-1"></i>Module</button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#activityModal" style="background:var(--primary-green);border:none;border-radius:50px;padding:8px 20px;font-size:0.85rem;font-weight:600;"><i class="fas fa-tasks me-1"></i>Activity</button>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#uploadModal" style="background:#e6a800;border:none;border-radius:50px;padding:8px 20px;font-size:0.85rem;font-weight:600;color:#fff;"><i class="fas fa-upload me-1"></i>File</button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success py-2 px-3 mb-4 text-center" style="border:none;border-radius:10px;font-size:0.9rem;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 mb-4 text-center" style="border:none;border-radius:10px;font-size:0.9rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">Recent Uploads</h6>
            </div>
            <div class="card-body">
                <?php if (empty($uploads)): ?>
                    <p class="text-muted mb-0" style="font-size:0.9rem;">No files uploaded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Type</th>
                                    <th>By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uploads as $u): ?>
                                <tr>
                                    <td><a href="../<?php echo htmlspecialchars($u['file_path']); ?>" target="_blank" style="color:var(--primary-blue);text-decoration:none;font-weight:600;"><?php echo htmlspecialchars($u['file_name']); ?></a></td>
                                    <td><?php echo htmlspecialchars($u['related_type']); ?></td>
                                    <td style="text-transform:lowercase"><?php echo htmlspecialchars($u['first_name']); ?></td>
                                    <td style="color:var(--text-light);"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- New Module Modal -->
    <div class="modal fade" id="moduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);"><i class="fas fa-cubes me-2" style="color:var(--primary-blue);"></i>New Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="module">
                    <div class="modal-body" style="padding:20px 24px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Name *</label>
                            <input type="text" name="module_name" class="form-control" required style="border-radius:10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Icon (Font Awesome class)</label>
                            <input type="text" name="module_icon" class="form-control" value="fa-star" placeholder="fa-calculator" style="border-radius:10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Color</label>
                            <input type="color" name="module_color" class="form-control form-control-color" value="#4A90E2" style="border-radius:10px;padding:4px;height:40px;">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">Create Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Activity Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);"><i class="fas fa-tasks me-2" style="color:var(--primary-green);"></i>New Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="activity">
                    <div class="modal-body" style="padding:20px 24px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Module *</label>
                            <select name="module_id" class="form-select" required style="border-radius:10px;">
                                <?php foreach ($modules as $m): ?>
                                    <option value="<?php echo (int)$m['module_id']; ?>"><?php echo htmlspecialchars($m['module_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Activity Name *</label>
                            <input type="text" name="activity_name" class="form-control" required style="border-radius:10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Type</label>
                            <select name="activity_type" class="form-select" style="border-radius:10px;">
                                <option value="counting">Counting</option>
                                <option value="shapes">Shapes</option>
                                <option value="addition">Addition</option>
                                <option value="subtraction">Subtraction</option>
                                <option value="matching">Matching</option>
                                <option value="game">Game</option>
                                <option value="quiz">Quiz</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                        <button type="submit" class="btn btn-success" style="background:var(--primary-green);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">Create Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload File Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);"><i class="fas fa-cloud-upload-alt me-2" style="color:#e6a800;"></i>Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="upload">
                    <div class="modal-body" style="padding:20px 24px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">File (image, audio, PDF) *</label>
                            <input type="file" name="content_file" class="form-control" required accept=".jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.pdf" style="border-radius:10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Content Type</label>
                            <select name="related_type" class="form-select" style="border-radius:10px;">
                                <option value="image">Image</option>
                                <option value="audio">Audio</option>
                                <option value="worksheet">Worksheet (PDF)</option>
                                <option value="activity">Activity asset</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Description</label>
                            <input type="text" name="description" class="form-control" style="border-radius:10px;">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                        <button type="submit" class="btn btn-warning" style="background:#e6a800;border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;color:#fff;">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>

<script src="../js/dashboard.js"></script>
</body>
</html>



