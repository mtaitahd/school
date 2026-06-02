<?php
session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($database->execute("DELETE FROM announcements WHERE announcement_id = ?", [$id])) {
        $message = 'Announcement deleted successfully.';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete announcement.';
        $message_type = 'error';
    }
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;

    if ($title === '' || $content === '') {
        $message = 'Title and content are required.';
        $message_type = 'error';
    } elseif ($_POST['action'] === 'create') {
        if ($database->insert(
            "INSERT INTO announcements (title, content, is_urgent) VALUES (?, ?, ?)",
            [$title, $content, $is_urgent]
        )) {
            $message = 'Announcement created successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to create announcement.';
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        if ($database->execute(
            "UPDATE announcements SET title = ?, content = ?, is_urgent = ? WHERE announcement_id = ?",
            [$title, $content, $is_urgent, $id]
        )) {
            $message = 'Announcement updated successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to update announcement.';
            $message_type = 'error';
        }
    }
}

$announcements = $database->fetchAll("SELECT * FROM announcements ORDER BY created_at DESC");
$edit_announcement = null;
if (isset($_GET['edit'])) {
    $edit_announcement = $database->fetchOne("SELECT * FROM announcements WHERE announcement_id = ?", [(int) $_GET['edit']]);
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
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
    <button type="button" class="btn-child btn-child-primary" onclick="openModal('announcementModal')">
        <i class="fas fa-plus me-2"></i>New Announcement
    </button>
</div>

<?php if ($message): ?>
    <div class="alert-child alert-child-<?php echo $message_type === 'success' ? 'success' : 'error'; ?> text-center mb-30">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-icon" style="background: var(--primary-blue);"><i class="fas fa-bullhorn"></i></div>
        <h3 class="dashboard-card-title">All Announcements</h3>
    </div>
    <div style="overflow-x: auto;">
        <table style="width:100%;border-collapse:collapse;font-family:'Nunito',sans-serif;">
            <thead>
                <tr style="background:var(--background-light);text-align:left;">
                    <th style="padding:12px 16px;font-weight:700;">Title</th>
                    <th style="padding:12px 16px;font-weight:700;">Type</th>
                    <th style="padding:12px 16px;font-weight:700;">Created</th>
                    <th style="padding:12px 16px;font-weight:700;text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                    <tr><td colspan="4" style="padding:30px;text-align:center;color:var(--text-light);">No announcements yet. Click "New Announcement" to create one.</td></tr>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px 16px;font-weight:600;"><?php echo htmlspecialchars($a['title']); ?></td>
                        <td style="padding:12px 16px;">
                            <?php if ($a['is_urgent']): ?>
                                <span style="display:inline-block;padding:3px 12px;border-radius:999px;background:var(--primary-red);color:#fff;font-size:0.8rem;font-weight:600;">Urgent</span>
                            <?php else: ?>
                                <span style="display:inline-block;padding:3px 12px;border-radius:999px;background:var(--primary-blue);color:#fff;font-size:0.8rem;font-weight:600;">Standard</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 16px;color:var(--text-light);font-size:0.9rem;"><?php echo date('M j, Y', strtotime($a['created_at'])); ?></td>
                        <td style="padding:12px 16px;text-align:center;">
                            <a href="?edit=<?php echo $a['announcement_id']; ?>" class="btn-child btn-child-yellow" style="padding:6px 14px;min-height:auto;font-size:0.85rem;text-decoration:none;display:inline-flex;gap:5px;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?delete=<?php echo $a['announcement_id']; ?>" class="btn-child btn-child-red" style="padding:6px 14px;min-height:auto;font-size:0.85rem;text-decoration:none;display:inline-flex;gap:5px;margin-left:6px;" onclick="return confirm('Delete this announcement?');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="announcementModal" class="kona-modal-overlay" aria-hidden="true" style="<?php echo $edit_announcement ? 'display:flex;' : ''; ?>">
    <div class="kona-modal" role="dialog" style="max-width:640px;">
        <div class="kona-modal-header">
            <h3><?php echo $edit_announcement ? 'Edit Announcement' : 'New Announcement'; ?></h3>
            <button type="button" class="kona-modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?php echo $edit_announcement ? 'update' : 'create'; ?>">
            <?php if ($edit_announcement): ?>
                <input type="hidden" name="id" value="<?php echo $edit_announcement['announcement_id']; ?>">
            <?php endif; ?>
            <div class="kona-modal-body">
                <div class="form-group-child">
                    <label class="form-label-child" for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control-child" value="<?php echo htmlspecialchars($edit_announcement['title'] ?? ''); ?>" required maxlength="255">
                </div>
                <div class="form-group-child">
                    <label class="form-label-child" for="content">Content</label>
                    <textarea id="content" name="content" class="form-control-child" rows="6" required><?php echo htmlspecialchars($edit_announcement['content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group-child">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-family:'Poppins',sans-serif;font-weight:600;font-size:1.1rem;">
                        <input type="checkbox" name="is_urgent" value="1" style="width:20px;height:20px;cursor:pointer;" <?php echo ($edit_announcement['is_urgent'] ?? 0) ? 'checked' : ''; ?>>
                        Mark as Urgent (appears in top bar)
                    </label>
                </div>
            </div>
            <div class="kona-modal-footer">
                <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn-child btn-child-primary">
                    <i class="fas fa-save me-2"></i><?php echo $edit_announcement ? 'Update' : 'Publish'; ?>
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
