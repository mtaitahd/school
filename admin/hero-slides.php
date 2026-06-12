<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$csrf_error = $_SESSION['_csrf_error'] ?? null;
unset($_SESSION['_csrf_error']);

$message = '';
$message_type = '';

$upload_dir = '../uploads/hero/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle toggle active
if (isset($_GET['toggle_active'])) {
    $id = (int) $_GET['toggle_active'];
    $slide = $database->fetchOne("SELECT is_active FROM hero_slides WHERE slide_id = ?", [$id]);
    if ($slide) {
        $new = $slide['is_active'] ? 0 : 1;
        $database->execute("UPDATE hero_slides SET is_active = ? WHERE slide_id = ?", [$new, $id]);
        $message = 'Slide status updated.';
        $message_type = 'success';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $slide = $database->fetchOne("SELECT image FROM hero_slides WHERE slide_id = ?", [$id]);
    if ($slide) {
        $img_path = '../' . $slide['image'];
        if (file_exists($img_path)) {
            unlink($img_path);
        }
        $database->execute("DELETE FROM hero_slides WHERE slide_id = ?", [$id]);
        $message = 'Slide deleted successfully.';
        $message_type = 'success';
    }
}

// Handle create/update/reorder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $action = $_POST['action'];

    if ($action === 'reorder') {
        $slides = $database->fetchAll("SELECT slide_id FROM hero_slides ORDER BY sort_order ASC, created_at DESC");
        $pos = 0;
        foreach ($slides as $s) {
            $database->execute("UPDATE hero_slides SET sort_order = ? WHERE slide_id = ?", [$pos, $s['slide_id']]);
            $pos++;
        }
        $message = 'Slides reordered successfully.';
        $message_type = 'success';
    } else {
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($action === 'create') {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $message = 'Image is required.';
                $message_type = 'error';
            } else {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    $message = 'Invalid image type. Allowed: ' . implode(', ', $allowed);
                    $message_type = 'error';
                } else {
                    $filename = 'slide_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                        $image_path = 'uploads/hero/' . $filename;
                        if ($database->insert(
                            "INSERT INTO hero_slides (image, is_active) VALUES (?, ?)",
                            [$image_path, $is_active]
                        )) {
                            $message = 'Slide created successfully.';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to create slide.';
                            $message_type = 'error';
                        }
                    } else {
                        $message = 'Failed to upload image.';
                        $message_type = 'error';
                    }
                }
            }
        } elseif ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $existing = $database->fetchOne("SELECT image FROM hero_slides WHERE slide_id = ?", [$id]);
            if (!$existing) {
                $message = 'Slide not found.';
                $message_type = 'error';
            } else {
                $image_path = $existing['image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $allowed)) {
                        $message = 'Invalid image type. Allowed: ' . implode(', ', $allowed);
                        $message_type = 'error';
                    } else {
                        $old_path = '../' . $existing['image'];
                        if (file_exists($old_path)) {
                            unlink($old_path);
                        }
                        $filename = 'slide_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                            $image_path = 'uploads/hero/' . $filename;
                        }
                    }
                }
                if (empty($message)) {
                    if ($database->execute(
                        "UPDATE hero_slides SET image = ?, is_active = ? WHERE slide_id = ?",
                        [$image_path, $is_active, $id]
                    )) {
                        $message = 'Slide updated successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update slide.';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
}

$slides = $database->fetchAll("SELECT * FROM hero_slides ORDER BY sort_order ASC, created_at DESC");
$edit_slide = null;
if (isset($_GET['edit'])) {
    $edit_slide = $database->fetchOne("SELECT * FROM hero_slides WHERE slide_id = ?", [(int) $_GET['edit']]);
}

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'hero-slides';
$dashboard_page_title = 'Manage Hero Slides';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hero Slides - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Hero Slides</h6>
        <div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#slideModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 18px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                <i class="fas fa-plus me-1"></i>New
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if ($csrf_error): ?>
            <div class="alert alert-danger py-2 px-3 mb-3 text-center" style="border-radius:10px;font-size:0.9rem;border:none;">
                <?php echo htmlspecialchars($csrf_error); ?>
            </div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> py-2 px-3 mb-3 text-center" style="border-radius:10px;font-size:0.9rem;border:none;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="width:120px;">Image</th>
                        <th style="width:80px;">Status</th>
                        <th style="width:200px;text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($slides)): ?>
                        <tr><td colspan="3" class="text-center py-4" style="color:var(--text-light);">No hero slides yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($slides as $s): ?>
                        <tr>
                            <td>
                                <img src="../<?php echo htmlspecialchars($s['image']); ?>" alt="Slide" style="width:100px;height:60px;object-fit:cover;border-radius:6px;">
                            </td>
                            <td>
                                <?php if ($s['is_active']): ?>
                                    <span class="text-success fw-semibold">Active</span>
                                <?php else: ?>
                                    <span class="text-muted fw-semibold">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="?edit=<?php echo $s['slide_id']; ?>" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle_active=<?php echo $s['slide_id']; ?>" class="btn btn-sm <?php echo $s['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;margin-left:4px;">
                                    <i class="fas <?php echo $s['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </a>
                                <a href="?delete=<?php echo $s['slide_id']; ?>" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;margin-left:4px;" onclick="return confirm('Delete this slide?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="slideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">
                    <i class="fas fa-images me-2" style="color:var(--primary-blue);"></i>
                    <?php echo $edit_slide ? 'Edit Slide' : 'New Slide'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit_slide ? 'update' : 'create'; ?>">
                <?php if ($edit_slide): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_slide['slide_id']; ?>">
                <?php endif; ?>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="image" style="font-size:0.85rem;color:var(--text-dark);">Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*" <?php echo $edit_slide ? '' : 'required'; ?> style="border-radius:10px;">
                        <?php if ($edit_slide && $edit_slide['image']): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($edit_slide['image']); ?>" alt="Current image" style="width:100%;max-height:200px;object-fit:cover;border-radius:6px;">
                                <small class="text-muted ms-2">Current image (leave empty to keep)</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" style="width:20px;height:20px;cursor:pointer;" <?php echo ($edit_slide['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="is_active" style="font-size:0.9rem;color:var(--text-dark);cursor:pointer;">
                            Active (visible on homepage)
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $edit_slide ? 'Update' : 'Create'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
<?php if ($edit_slide): ?>
<script>new bootstrap.Modal('#slideModal').show();</script>
<?php endif; ?>
</body>
</html>
