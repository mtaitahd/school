<?php
/**
 * Kona Ya Hisabati — Admin: Lesson Management
 */
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();

auth_require_role(['admin'], 'index');

$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'lessons';
$dashboard_page_title = 'Lesson Management';
$lang_page = 'lessons.php';

$topics = $database->fetchAll(
    "SELECT t.*, s.strand_name, d.domain_name
     FROM topics t
     JOIN strands s ON t.strand_id = s.strand_id
     JOIN domains d ON s.domain_id = d.domain_id
     ORDER BY d.order_index, s.order_index, t.order_index"
);

$lessons = $database->fetchAll(
    "SELECT l.*, t.topic_name, t.topic_code, m.module_name
     FROM lessons l
     JOIN topics t ON l.topic_id = t.topic_id
     LEFT JOIN modules m ON t.module_id = m.module_id
     ORDER BY t.order_index, l.order_index"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Management - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-1 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Lesson Management</h1>
                <p class="text-muted mb-0" style="font-size:0.9rem;">View lessons across all topics</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Lessons</h6>
            </div>
            <div class="card-body">
                <?php if (empty($lessons)): ?>
                    <p class="text-muted mb-0" style="font-size:0.9rem;">No lessons found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Lesson Code</th>
                                    <th>Lesson Name</th>
                                    <th>Topic</th>
                                    <th>Module</th>
                                    <th>Est. Min</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $ls): ?>
                                <tr>
                                    <td style="font-family:monospace;font-size:0.85rem;"><?php echo htmlspecialchars($ls['lesson_code']); ?></td>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($ls['lesson_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ls['topic_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ls['module_name'] ?? '—'); ?></td>
                                    <td><?php echo (int)$ls['estimated_minutes']; ?></td>
                                    <td><?php echo (int)$ls['order_index']; ?></td>
                                    <td>
                                        <?php if ($ls['is_active']): ?>
                                            <span class="text-success fw-semibold">Active</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-semibold">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">Topics</h6>
            </div>
            <div class="card-body">
                <?php if (empty($topics)): ?>
                    <p class="text-muted mb-0" style="font-size:0.9rem;">No topics found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Topic Code</th>
                                    <th>Topic Name</th>
                                    <th>Domain</th>
                                    <th>Strand</th>
                                    <th>Age Range</th>
                                    <th>Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topics as $t): ?>
                                <tr>
                                    <td style="font-family:monospace;font-size:0.85rem;"><?php echo htmlspecialchars($t['topic_code']); ?></td>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($t['topic_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['domain_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['strand_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['age_range']); ?></td>
                                    <td><?php echo (int)$t['order_index']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
