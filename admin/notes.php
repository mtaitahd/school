<?php
require_once '__DIR__ . '/../php/includes/session.php';
require_once '__DIR__ . '/../php/includes/security.php';
require_once '__DIR__ . '/../php/includes/csrf.php';
require_once '__DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$upload_dir = __DIR__ . '/../uploads/notes/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

function generateNoteSlug($title, $database, $exclude_id = null) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') $slug = 'note';
    $base_slug = $slug;
    $counter = 1;
    $exclude = $exclude_id ? (int)$exclude_id : 0;
    while ($database->fetchOne("SELECT 1 FROM notes WHERE slug = ? AND id != ?", [$slug, $exclude])) {
        $slug = $base_slug . '-' . $counter++;
    }
    return $slug;
}

function handleNoteImage($file) {
    $upload_dir = __DIR__ . '/../uploads/notes/';
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return false;
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $upload_dir . $filename)
        ? 'uploads/notes/' . $filename
        : null;
}

function deleteNoteImage($image_path) {
    if ($image_path) {
        $full = __DIR__ . '/../' . $image_path;
        if (file_exists($full)) unlink($full);
    }
}

$message = '';
$message_type = '';

if (isset($_GET['toggle_status'])) {
    $id = (int) $_GET['toggle_status'];
    $note = $database->fetchOne("SELECT status FROM notes WHERE id = ?", [$id]);
    if ($note) {
        $new_status = $note['status'] === 'published' ? 'draft' : 'published';
        $database->execute("UPDATE notes SET status = ? WHERE id = ?", [$new_status, $id]);
        $message = 'Status changed to ' . $new_status . '.';
        $message_type = 'success';
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $note = $database->fetchOne("SELECT featured_image FROM notes WHERE id = ?", [$id]);
    if ($note) {
        deleteNoteImage($note['featured_image']);
        $database->execute("DELETE FROM notes WHERE id = ?", [$id]);
        $message = 'Note deleted successfully.';
        $message_type = 'success';
    } else {
        $message = 'Note not found.';
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $title = trim($_POST['title'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $full_content = trim($_POST['full_content'] ?? '');
    $publish_date = trim($_POST['publish_date'] ?? '');
    $status = $_POST['status'] ?? 'published';
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($title === '') {
        $message = 'Title is required.';
        $message_type = 'error';
    } else {
        $slug = generateNoteSlug($title, $database, $id ?: null);

        $has_new_image = isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK;
        $image_path = null;
        $image_error = false;

        if ($has_new_image) {
            $uploaded = handleNoteImage($_FILES['featured_image']);
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
                    "INSERT INTO notes (title, slug, featured_image, short_description, full_content, publish_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$title, $slug, $image_path, $short_description ?: null, $full_content ?: null, $publish_date ?: null, $status]
                );
                if ($inserted) {
                    $message = 'Note created successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to create note.';
                    $message_type = 'error';
                }
            } elseif ($action === 'update' && $id) {
                $existing = $database->fetchOne("SELECT featured_image FROM notes WHERE id = ?", [$id]);
                if ($has_new_image && $image_path) {
                    deleteNoteImage($existing['featured_image'] ?? '');
                } elseif (!$has_new_image) {
                    $image_path = $existing['featured_image'] ?? null;
                }
                $updated = $database->execute(
                    "UPDATE notes SET title = ?, slug = ?, featured_image = ?, short_description = ?, full_content = ?, publish_date = ?, status = ? WHERE id = ?",
                    [$title, $slug, $image_path, $short_description ?: null, $full_content ?: null, $publish_date ?: null, $status, $id]
                );
                if ($updated) {
                    $message = 'Note updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update note.';
                    $message_type = 'error';
                }
            }
        }
    }
}

$notes = $database->fetchAll("SELECT * FROM notes ORDER BY created_at DESC");

$edit_note = null;
if (isset($_GET['edit'])) {
    $edit_note = $database->fetchOne("SELECT * FROM notes WHERE id = ?", [(int)$_GET['edit']]);
}

require_once '__DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'notes';
$dashboard_page_title = 'Manage Notes';
$lang_page = 'notes.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notes - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Notes</h6>
        <div>
            <span class="text-muted me-3" style="font-size:0.85rem;"><?php echo count($notes); ?> total</span>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#noteModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 18px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                <i class="fas fa-plus me-1"></i>New
            </button>
        </div>
    </div>
    <div class="card-body">
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
                        <th>Publish Date</th>
                        <th>Created</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notes)): ?>
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-light);">No notes yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notes as $n): ?>
                        <tr>
                            <td>
                                <?php if ($n['featured_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($n['featured_image']); ?>" alt="Thumbnail" style="width:60px;height:40px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                    <div style="width:60px;height:40px;border:1px solid #eee;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:0.65rem;">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($n['title']); ?></td>
                            <td style="font-size:0.85rem;color:var(--text-light);font-family:monospace;"><?php echo htmlspecialchars($n['slug']); ?></td>
                            <td>
                                <?php if ($n['status'] === 'published'): ?>
                                    <span class="text-success fw-semibold">Published</span>
                                <?php else: ?>
                                    <span class="text-muted fw-semibold">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.9rem;"><?php echo $n['publish_date'] ? date('M j, Y', strtotime($n['publish_date'])) : '<span style="color:#ccc;">—</span>'; ?></td>
                            <td style="color:var(--text-light);font-size:0.9rem;"><?php echo date('M j, Y', strtotime($n['created_at'])); ?></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="?edit=<?php echo $n['id']; ?>" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle_status=<?php echo $n['id']; ?>" class="btn btn-info btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;margin-left:2px;" title="Toggle published/draft">
                                    <i class="fas <?php echo $n['status'] === 'published' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </a>
                                <a href="?delete=<?php echo $n['id']; ?>" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;margin-left:2px;" onclick="return confirm('Delete this note? This cannot be undone.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <a href="../notes/<?php echo htmlspecialchars($n['slug']); ?>" class="btn btn-secondary btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;margin-left:2px;" target="_blank" title="Preview">
                                    <i class="fas fa-external-link-alt"></i>
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
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">
                    <i class="fas fa-sticky-note me-2" style="color:var(--primary-blue);"></i>
                    <?php echo $edit_note ? 'Edit Note' : 'New Note'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_note ? 'update' : 'create'; ?>">
                <?php if ($edit_note): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_note['id']; ?>">
                <?php endif; ?>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="title" style="font-size:0.85rem;color:var(--text-dark);">Title *</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_note['title'] ?? ''); ?>" required maxlength="255" style="border-radius:10px;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="publish_date" style="font-size:0.85rem;color:var(--text-dark);">Publish Date</label>
                                <input type="date" id="publish_date" name="publish_date" class="form-control" value="<?php echo htmlspecialchars($edit_note['publish_date'] ?? ''); ?>" style="border-radius:10px;">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="short_description" style="font-size:0.85rem;color:var(--text-dark);">Short Description</label>
                        <textarea id="short_description" name="short_description" class="form-control" rows="2" maxlength="500" style="border-radius:10px;"><?php echo htmlspecialchars($edit_note['short_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="full_content" style="font-size:0.85rem;color:var(--text-dark);">Full Content</label>
                        <textarea id="full_content" name="full_content" class="form-control" rows="8" style="border-radius:10px;"><?php echo htmlspecialchars($edit_note['full_content'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="featured_image" style="font-size:0.85rem;color:var(--text-dark);">Featured Image</label>
                        <div class="d-flex align-items-center gap-3" style="gap:12px;">
                            <input type="file" id="featured_image" name="featured_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" style="border-radius:10px;">
                            <?php if ($edit_note && $edit_note['featured_image']): ?>
                                <div style="flex-shrink:0;">
                                    <img src="../<?php echo htmlspecialchars($edit_note['featured_image']); ?>" alt="Current image" style="width:100px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #e0e0e0;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text" style="font-size:0.75rem;">Allowed: JPG, PNG, GIF, WebP.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="status" style="font-size:0.85rem;color:var(--text-dark);">Status</label>
                                <select id="status" name="status" class="form-select" style="border-radius:10px;">
                                    <option value="published" <?php echo ($edit_note['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="draft" <?php echo ($edit_note['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $edit_note ? 'Update' : 'Publish'; ?>
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
<?php if ($edit_note): ?>
<script>new bootstrap.Modal('#noteModal').show();</script>
<?php endif; ?>
</body>
</html>
