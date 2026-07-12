<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/migrate.php';
ensure_schema_v2($database);

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$message = '';
$message_type = '';

$upload_dir = __DIR__ . '/../uploads/governance/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$border_colors = [
    'blue'   => '#007bff',
    'green'  => '#28a745',
    'red'    => '#dc3545',
    'yellow' => '#ffc107',
    'purple' => '#6f42c1',
];

$border_tints = [
    'blue'   => 'rgba(0, 123, 255, 0.05)',
    'green'  => 'rgba(40, 167, 69, 0.05)',
    'red'    => 'rgba(220, 53, 69, 0.05)',
    'yellow' => 'rgba(255, 193, 7, 0.08)',
    'purple' => 'rgba(111, 66, 193, 0.05)',
];

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $leader = $database->fetchOne("SELECT image_path FROM governance WHERE id = ?", [$id]);
    if ($leader) {
        if ($leader['image_path']) {
            $full = __DIR__ . '/../' . $leader['image_path'];
            if (file_exists($full)) unlink($full);
        }
        $database->execute("DELETE FROM governance WHERE id = ?", [$id]);
        $message = 'Leader deleted successfully.';
        $message_type = 'success';
    }
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $profile_link = trim($_POST['profile_link'] ?? '');
    $border_color = trim($_POST['border_color'] ?? 'blue');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '' || $title === '') {
        $message = 'Name and Title are required.';
        $message_type = 'error';
    } elseif ($_POST['action'] === 'create') {
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                $message = 'Invalid image type. Allowed: ' . implode(', ', $allowed);
                $message_type = 'error';
            } else {
                $filename = 'leader_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'uploads/governance/' . $filename;
                }
            }
        }
        if (empty($message)) {
            if ($database->insert(
                "INSERT INTO governance (name, title, image_path, profile_link, border_color, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
                [$name, $title, $image_path ?: null, $profile_link ?: null, $border_color, $sort_order]
            )) {
                $message = 'Leader created successfully.';
                $message_type = 'success';
            } else {
                $message = 'Failed to create leader.';
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $existing = $database->fetchOne("SELECT image_path FROM governance WHERE id = ?", [$id]);
        if (!$existing) {
            $message = 'Leader not found.';
            $message_type = 'error';
        } else {
            $image_path = $existing['image_path'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    $message = 'Invalid image type. Allowed: ' . implode(', ', $allowed);
                    $message_type = 'error';
                } else {
                    if ($existing['image_path']) {
                        $old = __DIR__ . '/../' . $existing['image_path'];
                        if (file_exists($old)) unlink($old);
                    }
                    $filename = 'leader_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                        $image_path = 'uploads/governance/' . $filename;
                    }
                }
            }
            if (empty($message)) {
                if ($database->execute(
                    "UPDATE governance SET name = ?, title = ?, image_path = ?, profile_link = ?, border_color = ?, sort_order = ? WHERE id = ?",
                    [$name, $title, $image_path, $profile_link ?: null, $border_color, $sort_order, $id]
                )) {
                    $message = 'Leader updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update leader.';
                    $message_type = 'error';
                }
            }
        }
    }
}

$leaders = $database->fetchAll("SELECT * FROM governance ORDER BY sort_order ASC, id ASC");
$edit_leader = null;
if (isset($_GET['edit'])) {
    $edit_leader = $database->fetchOne("SELECT * FROM governance WHERE id = ?", [(int) $_GET['edit']]);
}

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'governance';
$dashboard_page_title = 'Manage Governance & Leadership';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Governance - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Leaders</h6>
        <div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leaderModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 18px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
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
                        <th style="width:100px;">Photo</th>
                        <th>Name</th>
                        <th>Title</th>
                        <th style="width:100px;">Border</th>
                        <th style="width:80px;">Order</th>
                        <th style="width:180px;text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaders)): ?>
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-light);">No leaders yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leaders as $l): ?>
                        <tr>
                            <td>
                                <?php if ($l['image_path']): ?>
                                    <img src="../<?php echo htmlspecialchars($l['image_path']); ?>" alt="<?php echo htmlspecialchars($l['name']); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%;">
                                <?php else: ?>
                                    <span style="display:inline-flex;width:60px;height:60px;border-radius:50%;border:1px solid #e2e8f0;align-items:center;justify-content:center;color:#94a3b8;"><i class="fas fa-user"></i></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($l['name']); ?></td>
                            <td style="color:var(--text-light);"><?php echo htmlspecialchars($l['title']); ?></td>
                            <td>
                                <span style="display:inline-block;width:24px;height:24px;border-radius:4px;border:2px solid <?php echo $border_colors[$l['border_color']] ?? '#007bff'; ?>;"></span>
                                <small class="ms-1"><?php echo htmlspecialchars($l['border_color']); ?></small>
                            </td>
                            <td style="text-align:center;"><?php echo (int) $l['sort_order']; ?></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="?edit=<?php echo $l['id']; ?>" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $l['id']; ?>" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;margin-left:4px;" data-confirm="Delete this leader?" data-confirm-title="Delete Leader" data-confirm-ok="Delete">
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
<div class="modal fade" id="leaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">
                    <i class="fas fa-users-cog me-2" style="color:var(--primary-blue);"></i>
                    <?php echo $edit_leader ? 'Edit Leader' : 'New Leader'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit_leader ? 'update' : 'create'; ?>">
                <?php if ($edit_leader): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_leader['id']; ?>">
                <?php endif; ?>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="name" style="font-size:0.85rem;color:var(--text-dark);">Full Name <span style="color:#e74a3b;">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars($edit_leader['name'] ?? ''); ?>" style="border-radius:10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="title" style="font-size:0.85rem;color:var(--text-dark);">Title / Role <span style="color:#e74a3b;">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars($edit_leader['title'] ?? ''); ?>" style="border-radius:10px;">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="profile_link" style="font-size:0.85rem;color:var(--text-dark);">Profile Link <small style="color:var(--text-light);font-weight:400;">(optional)</small></label>
                            <input type="url" id="profile_link" name="profile_link" class="form-control" value="<?php echo htmlspecialchars($edit_leader['profile_link'] ?? ''); ?>" placeholder="https://" style="border-radius:10px;">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold" for="border_color" style="font-size:0.85rem;color:var(--text-dark);">Border Accent</label>
                            <select id="border_color" name="border_color" class="form-control" style="border-radius:10px;">
                                <?php foreach ($border_colors as $key => $hex): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($edit_leader['border_color'] ?? 'blue') === $key ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($key); ?> (<?php echo $hex; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold" for="sort_order" style="font-size:0.85rem;color:var(--text-dark);">Sort Order</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?php echo (int) ($edit_leader['sort_order'] ?? 0); ?>" min="0" style="border-radius:10px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="image" style="font-size:0.85rem;color:var(--text-dark);">Portrait Photo</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*" <?php echo $edit_leader ? '' : ''; ?> style="border-radius:10px;">
                        <?php if ($edit_leader && $edit_leader['image_path']): ?>
                            <div class="mt-2 d-flex align-items-center gap-3">
                                <img src="../<?php echo htmlspecialchars($edit_leader['image_path']); ?>" alt="Current photo" style="width:60px;height:60px;object-fit:cover;border-radius:50%;">
                                <small class="text-muted">Current image (leave empty to keep)</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $edit_leader ? 'Update' : 'Save'; ?>
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
<?php if ($edit_leader): ?>
<script>new bootstrap.Modal('#leaderModal').show();</script>
<?php endif; ?>
</body>
</html>
