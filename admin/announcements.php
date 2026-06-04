<?php
require_once '../php/includes/session.php';
require_once '../php/includes/security.php';
require_once '../php/includes/csrf.php';
require_once '../php/db_connection.php';

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$csrf_error = $_SESSION['_csrf_error'] ?? null;
unset($_SESSION['_csrf_error']);

$upload_dir = __DIR__ . '/../uploads/announcements/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

function generateSlug($title, $database, $exclude_id = null) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') $slug = 'announcement';
    $base_slug = $slug;
    $counter = 1;
    $exclude = $exclude_id ? (int)$exclude_id : 0;
    while ($database->fetchOne("SELECT 1 FROM announcements WHERE slug = ? AND announcement_id != ?", [$slug, $exclude])) {
        $slug = $base_slug . '-' . $counter++;
    }
    return $slug;
}

function handleImageUpload($file) {
    $upload_dir = __DIR__ . '/../uploads/announcements/';
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return false;
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $upload_dir . $filename)
        ? 'uploads/announcements/' . $filename
        : null;
}

function deleteImageFile($image_path) {
    if ($image_path) {
        $full = __DIR__ . '/../' . $image_path;
        if (file_exists($full)) unlink($full);
    }
}

$message = '';
$message_type = '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

if (isset($_GET['toggle_status'])) {
    $id = (int) $_GET['toggle_status'];
    $ann = $database->fetchOne("SELECT status FROM announcements WHERE announcement_id = ?", [$id]);
    if ($ann) {
        $new_status = $ann['status'] === 'published' ? 'draft' : 'published';
        $database->execute("UPDATE announcements SET status = ? WHERE announcement_id = ?", [$new_status, $id]);
        $message = 'Status changed to ' . $new_status . '.';
        $message_type = 'success';
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $ann = $database->fetchOne("SELECT image FROM announcements WHERE announcement_id = ?", [$id]);
    if ($ann) {
        deleteImageFile($ann['image']);
        $database->execute("DELETE FROM announcements WHERE announcement_id = ?", [$id]);
        $message = 'Announcement deleted successfully.';
        $message_type = 'success';
    } else {
        $message = 'Announcement not found.';
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $title = trim($_POST['title'] ?? '');
    $slug_input = trim($_POST['slug'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $status = $_POST['status'] ?? 'published';
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($title === '' || $content === '') {
        $message = 'Title and content are required.';
        $message_type = 'error';
    } else {
        if ($slug_input !== '') {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $slug_input)), '-'));
            $slug = preg_replace('/-+/', '-', $slug);
            if ($slug === '') $slug = generateSlug($title, $database, $id ?: null);
            $base_slug = $slug;
            $counter = 1;
            $exclude = $id ?: 0;
            while ($database->fetchOne("SELECT 1 FROM announcements WHERE slug = ? AND announcement_id != ?", [$slug, $exclude])) {
                $slug = $base_slug . '-' . $counter++;
            }
        } else {
            $slug = generateSlug($title, $database, $id ?: null);
        }

        $has_new_image = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
        $image_path = null;
        $image_error = false;

        if ($has_new_image) {
            $uploaded = handleImageUpload($_FILES['image']);
            if ($uploaded === false) {
                $message = 'Invalid image file. Allowed: JPG, PNG, GIF, WebP.';
                $message_type = 'error';
                $image_error = true;
            } elseif ($uploaded) {
                $image_path = $uploaded;
            }
        }

        if (!$image_error) {
            if ($action === 'create') {
                $inserted = $database->insert(
                    "INSERT INTO announcements (title, slug, short_description, content, image, event_date, status, is_urgent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$title, $slug, $short_description ?: null, $content, $image_path, $event_date ?: null, $status, $is_urgent]
                );
                if ($inserted) {
                    $message = 'Announcement created successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to create announcement.';
                    $message_type = 'error';
                }
            } elseif ($action === 'update' && $id) {
                $existing = $database->fetchOne("SELECT image FROM announcements WHERE announcement_id = ?", [$id]);
                if ($has_new_image && $image_path) {
                    deleteImageFile($existing['image'] ?? '');
                } elseif (!$has_new_image) {
                    $image_path = $existing['image'] ?? null;
                }
                $updated = $database->execute(
                    "UPDATE announcements SET title = ?, slug = ?, short_description = ?, content = ?, image = ?, event_date = ?, status = ?, is_urgent = ? WHERE announcement_id = ?",
                    [$title, $slug, $short_description ?: null, $content, $image_path, $event_date ?: null, $status, $is_urgent, $id]
                );
                if ($updated) {
                    $message = 'Announcement updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update announcement.';
                    $message_type = 'error';
                }
            }
        }
    }
}

$count_row = $database->fetchOne("SELECT COUNT(*) as total FROM announcements");
$total_items = $count_row['total'] ?? 0;
$total_pages = max(1, ceil($total_items / $per_page));
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $per_page; }

$announcements = $database->fetchAll("SELECT * FROM announcements ORDER BY created_at DESC LIMIT ? OFFSET ?", [$per_page, $offset]);

$edit_announcement = null;
if (isset($_GET['edit'])) {
    $edit_announcement = $database->fetchOne("SELECT * FROM announcements WHERE announcement_id = ?", [(int)$_GET['edit']]);
}

require_once '../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'announcements';
$dashboard_page_title = 'Manage Announcements';
$lang_page = 'announcements.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Announcements</h6>
        <div>
            <span class="text-muted me-3" style="font-size:0.85rem;"><?php echo $total_items; ?> total</span>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#announcementModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 18px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
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
                        <th style="width:70px;">Image</th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th style="text-align:center;">Urgent</th>
                        <th>Event Date</th>
                        <th>Created</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr><td colspan="8" class="text-center py-4" style="color:var(--text-light);">No announcements yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $a): ?>
                        <tr>
                            <td>
                                <?php if ($a['image']): ?>
                                    <img src="../<?php echo htmlspecialchars($a['image']); ?>" alt="Thumbnail" style="width:60px;height:40px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                    <div style="width:60px;height:40px;border:1px solid #eee;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:0.65rem;">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($a['title']); ?></td>
                            <td style="font-size:0.85rem;color:var(--text-light);font-family:monospace;"><?php echo htmlspecialchars($a['slug']); ?></td>
                            <td>
                                <?php if ($a['status'] === 'published'): ?>
                                    <span class="text-success fw-semibold">Published</span>
                                <?php else: ?>
                                    <span class="text-muted fw-semibold">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($a['is_urgent']): ?>
                                    <span class="text-danger fw-semibold">Urgent</span>
                                <?php else: ?>
                                    <span style="color:#ccc;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.9rem;"><?php echo $a['event_date'] ? date('M j, Y', strtotime($a['event_date'])) : '<span style="color:#ccc;">—</span>'; ?></td>
                            <td style="color:var(--text-light);font-size:0.9rem;"><?php echo date('M j, Y', strtotime($a['created_at'])); ?></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="?edit=<?php echo $a['announcement_id']; ?>" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle_status=<?php echo $a['announcement_id']; ?>" class="btn btn-info btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;margin-left:2px;" title="Toggle published/draft">
                                    <i class="fas <?php echo $a['status'] === 'published' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </a>
                                <a href="?delete=<?php echo $a['announcement_id']; ?>" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;margin-left:2px;" onclick="return confirm('Delete this announcement? This cannot be undone.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Announcements page navigation" class="mt-3">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">
                    <i class="fas fa-bullhorn me-2" style="color:var(--primary-blue);"></i>
                    <?php echo $edit_announcement ? 'Edit Announcement' : 'New Announcement'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit_announcement ? 'update' : 'create'; ?>">
                <?php if ($edit_announcement): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_announcement['announcement_id']; ?>">
                <?php endif; ?>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="title" style="font-size:0.85rem;color:var(--text-dark);">Title *</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_announcement['title'] ?? ''); ?>" required maxlength="255" style="border-radius:10px;" oninput="autoSlug(this.value)">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="event_date" style="font-size:0.85rem;color:var(--text-dark);">Event Date</label>
                                <input type="date" id="event_date" name="event_date" class="form-control" value="<?php echo htmlspecialchars($edit_announcement['event_date'] ?? ''); ?>" style="border-radius:10px;">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="slug" style="font-size:0.85rem;color:var(--text-dark);">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_announcement['slug'] ?? ''); ?>" maxlength="255" style="border-radius:10px;font-size:0.85rem;font-family:monospace;">
                        <div class="form-text" style="font-size:0.75rem;">Auto-generated from title. You may edit it manually.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="short_description" style="font-size:0.85rem;color:var(--text-dark);">Short Description</label>
                        <textarea id="short_description" name="short_description" class="form-control" rows="2" maxlength="300" style="border-radius:10px;"><?php echo htmlspecialchars($edit_announcement['short_description'] ?? ''); ?></textarea>
                        <div class="form-text" style="font-size:0.75rem;">Max 300 characters. Shown in announcement card previews.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="content" style="font-size:0.85rem;color:var(--text-dark);">Content *</label>
                        <textarea id="content" name="content" class="form-control" rows="6" required style="border-radius:10px;"><?php echo htmlspecialchars($edit_announcement['content'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="image" style="font-size:0.85rem;color:var(--text-dark);">Image</label>
                        <div class="d-flex align-items-center gap-3" style="gap:12px;">
                            <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" style="border-radius:10px;">
                            <?php if ($edit_announcement && $edit_announcement['image']): ?>
                                <div style="flex-shrink:0;">
                                    <img src="../<?php echo htmlspecialchars($edit_announcement['image']); ?>" alt="Current image" style="width:100px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #e0e0e0;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text" style="font-size:0.75rem;">Allowed: JPG, PNG, GIF, WebP. Leave empty to keep existing image when editing.</div>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="mb-3 mb-md-0">
                                <label class="form-label fw-semibold" for="status" style="font-size:0.85rem;color:var(--text-dark);">Status</label>
                                <select id="status" name="status" class="form-select" style="border-radius:10px;">
                                    <option value="published" <?php echo ($edit_announcement['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="draft" <?php echo ($edit_announcement['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-check">
                                <input type="checkbox" name="is_urgent" value="1" class="form-check-input" id="is_urgent" style="width:20px;height:20px;cursor:pointer;" <?php echo ($edit_announcement['is_urgent'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="is_urgent" style="font-size:0.9rem;color:var(--text-dark);cursor:pointer;">
                                    Mark as Urgent (appears in top bar)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $edit_announcement ? 'Update' : 'Publish'; ?>
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
<script>
function autoSlug(title) {
    var slugField = document.getElementById('slug');
    if (!slugField.dataset.manuallyEdited) {
        slugField.value = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}
document.getElementById('slug').addEventListener('input', function () {
    this.dataset.manuallyEdited = 'true';
});
</script>
<?php if ($edit_announcement): ?>
<script>new bootstrap.Modal('#announcementModal').show();</script>
<?php endif; ?>
</body>
</html>
