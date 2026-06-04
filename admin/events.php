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

$message = '';
$message_type = '';

if (isset($_GET['toggle_status'])) {
    $id = (int) $_GET['toggle_status'];
    $ev = $database->fetchOne("SELECT status FROM events WHERE id = ?", [$id]);
    if ($ev) {
        $new_status = $ev['status'] === 'published' ? 'draft' : 'published';
        $database->execute("UPDATE events SET status = ? WHERE id = ?", [$new_status, $id]);
        $message = 'Status changed to ' . $new_status . '.';
        $message_type = 'success';
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $ev = $database->fetchOne("SELECT id FROM events WHERE id = ?", [$id]);
    if ($ev) {
        $database->execute("DELETE FROM events WHERE id = ?", [$id]);
        $message = 'Event deleted successfully.';
        $message_type = 'success';
    } else {
        $message = 'Event not found.';
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $event_title = trim($_POST['event_title'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $event_time = trim($_POST['event_time'] ?? '');
    $event_description = trim($_POST['event_description'] ?? '');
    $status = $_POST['status'] ?? 'published';
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($event_title === '' || $event_date === '') {
        $message = 'Event title and date are required.';
        $message_type = 'error';
    } else {
        if ($action === 'create') {
            $inserted = $database->insert(
                "INSERT INTO events (event_title, event_date, event_time, event_description, status) VALUES (?, ?, ?, ?, ?)",
                [$event_title, $event_date, $event_time ?: null, $event_description ?: null, $status]
            );
            if ($inserted) {
                $message = 'Event created successfully.';
                $message_type = 'success';
            } else {
                $message = 'Failed to create event.';
                $message_type = 'error';
            }
        } elseif ($action === 'update' && $id) {
            $updated = $database->execute(
                "UPDATE events SET event_title = ?, event_date = ?, event_time = ?, event_description = ?, status = ? WHERE id = ?",
                [$event_title, $event_date, $event_time ?: null, $event_description ?: null, $status, $id]
            );
            if ($updated) {
                $message = 'Event updated successfully.';
                $message_type = 'success';
            } else {
                $message = 'Failed to update event.';
                $message_type = 'error';
            }
        }
    }
}

$events = $database->fetchAll("SELECT * FROM events ORDER BY event_date DESC");

$edit_event = null;
if (isset($_GET['edit'])) {
    $edit_event = $database->fetchOne("SELECT * FROM events WHERE id = ?", [(int)$_GET['edit']]);
}

require_once '__DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'events';
$dashboard_page_title = 'Manage Events';
$lang_page = 'events.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Events</h6>
        <div>
            <span class="text-muted me-3" style="font-size:0.85rem;"><?php echo count($events); ?> total</span>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#eventModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 18px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
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
                        <th>Event Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-light);">No events yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $e): ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($e['event_title']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($e['event_date'])); ?></td>
                            <td style="color:var(--text-light);"><?php echo htmlspecialchars($e['event_time'] ?? '—'); ?></td>
                            <td>
                                <?php if ($e['status'] === 'published'): ?>
                                    <span class="text-success fw-semibold">Published</span>
                                <?php else: ?>
                                    <span class="text-muted fw-semibold">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--text-light);font-size:0.9rem;"><?php echo date('M j, Y', strtotime($e['created_at'])); ?></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="?edit=<?php echo $e['id']; ?>" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle_status=<?php echo $e['id']; ?>" class="btn btn-info btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;margin-left:2px;" title="Toggle published/draft">
                                    <i class="fas <?php echo $e['status'] === 'published' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </a>
                                <a href="?delete=<?php echo $e['id']; ?>" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;font-weight:600;margin-left:2px;" onclick="return confirm('Delete this event? This cannot be undone.');">
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
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">
                    <i class="fas fa-calendar-plus me-2" style="color:var(--primary-blue);"></i>
                    <?php echo $edit_event ? 'Edit Event' : 'New Event'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit_event ? 'update' : 'create'; ?>">
                <?php if ($edit_event): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_event['id']; ?>">
                <?php endif; ?>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="event_title" style="font-size:0.85rem;color:var(--text-dark);">Event Title *</label>
                        <input type="text" id="event_title" name="event_title" class="form-control" value="<?php echo htmlspecialchars($edit_event['event_title'] ?? ''); ?>" required maxlength="255" style="border-radius:10px;">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="event_date" style="font-size:0.85rem;color:var(--text-dark);">Event Date *</label>
                                <input type="date" id="event_date" name="event_date" class="form-control" value="<?php echo htmlspecialchars($edit_event['event_date'] ?? ''); ?>" required style="border-radius:10px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="event_time" style="font-size:0.85rem;color:var(--text-dark);">Event Time</label>
                                <input type="text" id="event_time" name="event_time" class="form-control" value="<?php echo htmlspecialchars($edit_event['event_time'] ?? ''); ?>" placeholder="e.g. 8:00 AM - 5:00 PM" style="border-radius:10px;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="event_description" style="font-size:0.85rem;color:var(--text-dark);">Description</label>
                        <textarea id="event_description" name="event_description" class="form-control" rows="3" style="border-radius:10px;"><?php echo htmlspecialchars($edit_event['event_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="status" style="font-size:0.85rem;color:var(--text-dark);">Status</label>
                        <select id="status" name="status" class="form-select" style="border-radius:10px;">
                            <option value="published" <?php echo ($edit_event['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo ($edit_event['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $edit_event ? 'Update' : 'Publish'; ?>
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
<?php if ($edit_event): ?>
<script>new bootstrap.Modal('#eventModal').show();</script>
<?php endif; ?>
</body>
</html>
