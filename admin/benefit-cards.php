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

$message = '';
$message_type = '';

$upload_dir = '../uploads/benefits/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle toggle active
if (isset($_GET['toggle_active'])) {
    $id = (int) $_GET['toggle_active'];
    $card = $database->fetchOne("SELECT is_active FROM benefit_cards WHERE id = ?", [$id]);
    if ($card) {
        $new = $card['is_active'] ? 0 : 1;
        $database->execute("UPDATE benefit_cards SET is_active = ? WHERE id = ?", [$new, $id]);
        $message = 'Card status updated.';
        $message_type = 'success';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $card = $database->fetchOne("SELECT bg_image FROM benefit_cards WHERE id = ?", [$id]);
    if ($card) {
        if ($card['bg_image']) {
            $img_path = '../' . $card['bg_image'];
            if (file_exists($img_path)) unlink($img_path);
        }
        $database->execute("DELETE FROM benefit_cards WHERE id = ?", [$id]);
        $message = 'Card deleted successfully.';
        $message_type = 'success';
    }
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $action = $_POST['action'];

    $icon = trim($_POST['icon'] ?? 'fa-star');
    $title_en = trim($_POST['title_en'] ?? '');
    $title_sw = trim($_POST['title_sw'] ?? '');
    $desc_en = trim($_POST['description_en'] ?? '');
    $desc_sw = trim($_POST['description_sw'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title_en) || empty($title_sw) || empty($desc_en) || empty($desc_sw)) {
        $message = 'All title and description fields are required.';
        $message_type = 'error';
    } elseif ($action === 'create') {
        $bg_image = '';
        if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'benefit_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $upload_dir . $filename)) {
                    $bg_image = 'uploads/benefits/' . $filename;
                }
            } else {
                $message = 'Invalid image type.';
                $message_type = 'error';
            }
        }
        if (empty($message)) {
            $database->insert(
                "INSERT INTO benefit_cards (icon, bg_image, title_en, title_sw, description_en, description_sw, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$icon, $bg_image ?: null, $title_en, $title_sw, $desc_en, $desc_sw, $is_active, $sort_order]
            );
            $message = 'Card created successfully.';
            $message_type = 'success';
        }
    } elseif ($action === 'update' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $existing = $database->fetchOne("SELECT bg_image FROM benefit_cards WHERE id = ?", [$id]);
        if (!$existing) {
            $message = 'Card not found.';
            $message_type = 'error';
        } else {
            $bg_image = $existing['bg_image'];
            if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    if ($existing['bg_image']) {
                        $old_path = '../' . $existing['bg_image'];
                        if (file_exists($old_path)) unlink($old_path);
                    }
                    $filename = 'benefit_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $upload_dir . $filename)) {
                        $bg_image = 'uploads/benefits/' . $filename;
                    }
                } else {
                    $message = 'Invalid image type.';
                    $message_type = 'error';
                }
            }
            if (empty($message)) {
                $database->execute(
                    "UPDATE benefit_cards SET icon = ?, bg_image = ?, title_en = ?, title_sw = ?, description_en = ?, description_sw = ?, is_active = ?, sort_order = ? WHERE id = ?",
                    [$icon, $bg_image, $title_en, $title_sw, $desc_en, $desc_sw, $is_active, $sort_order, $id]
                );
                $message = 'Card updated successfully.';
                $message_type = 'success';
            }
        }
    }
}

$cards = $database->fetchAll("SELECT * FROM benefit_cards ORDER BY sort_order ASC, id ASC");
$edit_card = null;
if (isset($_GET['edit'])) {
    $edit_card = $database->fetchOne("SELECT * FROM benefit_cards WHERE id = ?", [(int) $_GET['edit']]);
}

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'benefit-cards';
$dashboard_page_title = 'Benefit Cards';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benefit Cards - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Benefit Cards</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cardModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 22px;font-weight:600;font-size:0.85rem;">
        <i class="fas fa-plus me-1"></i>New Card
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> py-2 px-3 mb-3 text-center" style="border-radius:10px;font-size:0.9rem;border:none;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Benefit Cards</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="width:60px;">Icon</th>
                        <th style="width:120px;">Image</th>
                        <th>Title (EN)</th>
                        <th>Title (SW)</th>
                        <th style="width:80px;">Order</th>
                        <th style="width:80px;">Status</th>
                        <th style="width:200px;text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cards)): ?>
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-light);">No benefit cards yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cards as $c): ?>
                        <tr>
                            <td class="text-center"><i class="fas <?php echo htmlspecialchars($c['icon']); ?>" style="font-size:1.3rem;color:var(--primary-blue);"></i></td>
                            <td>
                                <?php if ($c['bg_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($c['bg_image']); ?>" alt="" style="width:100px;height:60px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($c['title_en']); ?></td>
                            <td><?php echo htmlspecialchars($c['title_sw']); ?></td>
                            <td class="text-center"><?php echo (int) $c['sort_order']; ?></td>
                            <td>
                                <?php if ($c['is_active']): ?>
                                    <span class="text-success fw-semibold">Active</span>
                                <?php else: ?>
                                    <span class="text-muted fw-semibold">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle_active=<?php echo $c['id']; ?>" class="btn btn-sm <?php echo $c['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;margin-left:4px;">
                                    <i class="fas <?php echo $c['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </a>
                                <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;margin-left:4px;" data-confirm="Delete this card?" data-confirm-title="Delete Card" data-confirm-ok="Delete">
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
<div class="modal fade" id="cardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">
                    <i class="fas fa-credit-card me-2" style="color:var(--primary-blue);"></i>
                    <?php echo $edit_card ? 'Edit Card' : 'New Card'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit_card ? 'update' : 'create'; ?>">
                <?php if ($edit_card): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_card['id']; ?>">
                <?php endif; ?>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Title (EN)</label>
                            <input type="text" name="title_en" class="form-control" required style="border-radius:10px;" value="<?php echo htmlspecialchars($edit_card['title_en'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Title (SW)</label>
                            <input type="text" name="title_sw" class="form-control" required style="border-radius:10px;" value="<?php echo htmlspecialchars($edit_card['title_sw'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Description (EN)</label>
                            <textarea name="description_en" class="form-control" required rows="2" style="border-radius:10px;"><?php echo htmlspecialchars($edit_card['description_en'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Description (SW)</label>
                            <textarea name="description_sw" class="form-control" required rows="2" style="border-radius:10px;"><?php echo htmlspecialchars($edit_card['description_sw'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">FontAwesome Icon</label>
                            <input type="text" name="icon" class="form-control" required style="border-radius:10px;" value="<?php echo htmlspecialchars($edit_card['icon'] ?? 'fa-star'); ?>" placeholder="fa-shield-alt">
                            <small class="text-muted">e.g., fa-shield-alt, fa-hand-pointer</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" style="border-radius:10px;" value="<?php echo (int) ($edit_card['sort_order'] ?? 0); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="card_is_active" style="width:20px;height:20px;cursor:pointer;" <?php echo ($edit_card['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="card_is_active" style="font-size:0.9rem;cursor:pointer;">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Background Image</label>
                            <input type="file" name="bg_image" class="form-control" accept="image/*" style="border-radius:10px;">
                            <?php if ($edit_card && $edit_card['bg_image']): ?>
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <img src="../<?php echo htmlspecialchars($edit_card['bg_image']); ?>" alt="" style="width:80px;height:50px;object-fit:cover;border-radius:6px;">
                                    <small class="text-muted">Current image (leave empty to keep)</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $edit_card ? 'Update' : 'Create'; ?>
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
<?php if ($edit_card): ?>
<script>new bootstrap.Modal('#cardModal').show();</script>
<?php endif; ?>
</body>
</html>
