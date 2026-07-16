<?php
/**
 * Deduplicate Learners - Browser UI
 *
 * Shows all duplicate groups with checkboxes.
 * User reviews, then clicks "Delete Selected" to remove.
 * Keeps the OLDEST record in each group (earliest created_at).
 *
 * Safe: all foreign keys reference users(user_id) with ON DELETE CASCADE.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

require_once __DIR__ . '/../php/db_connection.php';

$action   = $_POST['action']   ?? $_GET['action']   ?? 'list';
$selected = $_POST['delete_ids'] ?? [];
$message  = '';
$msgType  = 'info';

/* ---- Handle auto-delete all ---- */
if ($action === 'delete_all') {
    $toRemove = $database->fetchAll("
        SELECT user_id FROM users u1
        WHERE role = 'learner'
          AND user_id != (
              SELECT MIN(u2.user_id) FROM users u2
              WHERE u2.role = 'learner'
                AND LOWER(TRIM(u2.first_name)) = LOWER(TRIM(u1.first_name))
                AND LOWER(TRIM(u2.last_name))  = LOWER(TRIM(u1.last_name))
          )
          AND EXISTS (
              SELECT 1 FROM users u3
              WHERE u3.role = 'learner'
                AND LOWER(TRIM(u3.first_name)) = LOWER(TRIM(u1.first_name))
                AND LOWER(TRIM(u3.last_name))  = LOWER(TRIM(u1.last_name))
                AND u3.user_id < u1.user_id
          )
    ");
    $ids = array_column($toRemove, 'user_id');
    if (!empty($ids)) {
        $pdo  = $database->getPdo();
        $deleted = 0;
        $errors  = [];
        try {
            $pdo->beginTransaction();
            $chunks = array_chunk($ids, 200);
            foreach ($chunks as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id IN ($placeholders)");
                $stmt->execute($chunk);
                $deleted += $stmt->rowCount();
            }
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
        $msgType = $deleted > 0 ? 'success' : 'error';
        $message = "Deleted {$deleted} duplicate(s)" . ($errors ? ". Error: " . htmlspecialchars(implode('; ', $errors)) : "");
    } else {
        $msgType = 'info';
        $message = "No duplicates found to delete.";
    }
}

/* ---- Handle delete ---- */
if ($action === 'delete' && !empty($selected)) {
    $pdo  = $database->getPdo();
    $ids  = array_map('intval', $selected);
    $ids  = array_filter($ids);
    $deleted = 0;
    $errors  = [];
    try {
        $pdo->beginTransaction();
        $chunks = array_chunk($ids, 200);
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id IN ($placeholders)");
            $stmt->execute($chunk);
            $deleted += $stmt->rowCount();
        }
        $pdo->commit();
    } catch (\Exception $e) {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
    $msgType = $deleted > 0 ? 'success' : 'error';
    $message = "Deleted {$deleted} duplicate(s)" . ($errors ? ". Error: " . htmlspecialchars(implode('; ', $errors)) : "");
}

/* ---- Fetch duplicate groups ---- */
$duplicates = $database->fetchAll("
    SELECT LOWER(TRIM(first_name)) AS fn, LOWER(TRIM(last_name)) AS ln,
           COUNT(*) AS cnt
    FROM users
    WHERE role = 'learner'
    GROUP BY fn, ln
    HAVING cnt > 1
    ORDER BY cnt DESC
");

$totalGroups  = count($duplicates);
$totalRecords = 0;

$groups = [];
foreach ($duplicates as $dup) {
    $learners = $database->fetchAll("
        SELECT user_id, username, first_name, last_name, parent_id, created_at
        FROM users
        WHERE role = 'learner'
          AND LOWER(TRIM(first_name)) = ?
          AND LOWER(TRIM(last_name)) = ?
        ORDER BY created_at ASC, user_id ASC
    ", [$dup['fn'], $dup['ln']]);

    if (count($learners) < 2) continue;

    $keep   = $learners[0];
    $remove = array_slice($learners, 1);
    $totalRecords += count($remove);

    $groups[] = [
        'keep'   => $keep,
        'remove' => $remove,
    ];
}

$grandTotal = $database->fetchOne("SELECT COUNT(*) AS c FROM users WHERE role = 'learner'")['c'] ?? 0;
$afterTotal = $grandTotal - $totalRecords;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deduplicate Learners</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f0f2f5;color:#333;padding:20px}
.container{max-width:960px;margin:0 auto}
h1{font-size:1.4em;margin-bottom:6px}
.subtitle{color:#666;margin-bottom:16px;font-size:.9em}
.stats{display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.stat{background:#fff;border-radius:8px;padding:12px 18px;box-shadow:0 1px 3px rgba(0,0,0,.1);text-align:center;min-width:140px}
.stat .num{font-size:1.8em;font-weight:700;line-height:1.1}
.stat .label{font-size:.75em;color:#888;text-transform:uppercase}
.stat.danger .num{color:#e74c3c}
.stat.ok .num{color:#27ae60}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-weight:500}
.alert.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert.error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
.alert.info{background:#d1ecf1;color:#0c5460;border:1px solid #bee5eb}
.actions-bar{display:flex;gap:10px;margin-bottom:16px;align-items:center;flex-wrap:wrap}
.btn{padding:8px 18px;border:none;border-radius:6px;font-size:.9em;cursor:pointer;font-weight:500;transition:opacity .15s}
.btn:hover{opacity:.85}
.btn-primary{background:#3498db;color:#fff}
.btn-danger{background:#e74c3c;color:#fff}
.btn-success{background:#27ae60;color:#fff}
.btn-outline{background:#fff;color:#333;border:1px solid #ccc}
select{padding:8px 12px;border:1px solid #ccc;border-radius:6px;font-size:.9em}
.group{background:#fff;border-radius:8px;margin-bottom:12px;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden}
.group-header{padding:12px 16px;background:#f8f9fa;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;cursor:pointer;user-select:none}
.group-header:hover{background:#eef}
.group-header .name{font-weight:600;font-size:1em}
.group-header .count{background:#e74c3c;color:#fff;padding:2px 8px;border-radius:10px;font-size:.75em;margin-left:8px}
.group-header .ids{color:#999;font-size:.8em}
.group-body{padding:0}
.row{display:grid;grid-template-columns:40px 1fr 1fr 1fr 1fr 60px;align-items:center;padding:10px 16px;border-bottom:1px solid #f0f0f0;font-size:.88em}
.row:last-child{border-bottom:none}
.row.header{background:#fafafa;font-weight:600;color:#555;font-size:.8em;text-transform:uppercase;letter-spacing:.5px}
.row.keep{background:#f0fff0}
.row.del{background:#fff5f5}
.row input[type=checkbox]{width:18px;height:18px;cursor:pointer}
.username{font-family:monospace;color:#555}
.parent{color:#888;font-size:.85em}
.date{color:#999;font-size:.85em}
.tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75em;font-weight:500}
.tag.keep-tag{background:#d4edda;color:#155724}
.tag.del-tag{background:#f8d7da;color:#721c24}
.no-dups{text-align:center;padding:40px;color:#27ae60;font-size:1.2em}
</style>
</head>
<body>
<div class="container">
    <h1>Deduplicate Learners</h1>
    <p class="subtitle">Finds learners with same first_name + last_name. Keeps oldest record (earliest created_at). Duplicate records and all linked data will be removed via CASCADE.</p>

    <?php if ($message): ?>
        <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat"><div class="num"><?= $grandTotal ?></div><div class="label">Total Learners</div></div>
        <div class="stat danger"><div class="num"><?= $totalGroups ?></div><div class="label">Duplicate Groups</div></div>
        <div class="stat danger"><div class="num"><?= $totalRecords ?></div><div class="label">To Remove</div></div>
        <div class="stat ok"><div class="num"><?= $afterTotal ?></div><div class="label">After Cleanup</div></div>
    </div>

    <?php if ($groups): ?>
    <form method="POST" id="dedupForm">
        <input type="hidden" name="action" value="delete">
        <div class="actions-bar">
            <button type="button" class="btn btn-outline" onclick="toggleAll()">Select / Deselect All</button>
            <button type="button" class="btn btn-outline" onclick="selectAllRemove()">Select All Duplicates</button>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete <?= $totalRecords ?> duplicate records? This cannot be undone.')">Delete Selected (<?= $totalRecords ?>)</button>
            <a href="?action=delete_all" class="btn btn-danger" onclick="return confirm('Delete ALL <?= $totalRecords ?> duplicates automatically? This cannot be undone.')" style="background:#c0392b">Delete All Duplicates</a>
            <a href="?action=list" class="btn btn-outline">Refresh</a>
        </div>

        <?php foreach ($groups as $gi => $g): ?>
        <div class="group" id="group-<?= $gi ?>">
            <div class="group-header" onclick="toggleGroup(<?= $gi ?>)">
                <div>
                    <span class="name"><?= htmlspecialchars($g['keep']['first_name'] . ' ' . $g['keep']['last_name']) ?></span>
                    <span class="count"><?= count($g['remove']) + 1 ?> records</span>
                </div>
                <div class="ids">Keep #<?= $g['keep']['user_id'] ?></div>
            </div>
            <div class="group-body">
                <div class="row header">
                    <div></div><div>Username</div><div>Name</div><div>Parent</div><div>Created</div><div>Action</div>
                </div>
                <div class="row keep">
                    <div></div>
                    <div class="username"><?= htmlspecialchars($g['keep']['username']) ?></div>
                    <div><?= htmlspecialchars($g['keep']['first_name'] . ' ' . $g['keep']['last_name']) ?></div>
                    <div class="parent"><?= $g['keep']['parent_id'] ? 'ID:' . $g['keep']['parent_id'] : '<em>none</em>' ?></div>
                    <div class="date"><?= $g['keep']['created_at'] ?></div>
                    <div><span class="tag keep-tag">KEEP</span></div>
                </div>
                <?php foreach ($g['remove'] as $r): ?>
                <div class="row del">
                    <div><input type="checkbox" name="delete_ids[]" value="<?= $r['user_id'] ?>" class="del-cb" data-group="<?= $gi ?>"></div>
                    <div class="username"><?= htmlspecialchars($r['username']) ?></div>
                    <div><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></div>
                    <div class="parent"><?= $r['parent_id'] ? 'ID:' . $r['parent_id'] : '<em>none</em>' ?></div>
                    <div class="date"><?= $r['created_at'] ?></div>
                    <div><span class="tag del-tag">DELETE</span></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </form>

    <?php else: ?>
    <div class="no-dups">No duplicate learners found. All clean!</div>
    <?php endif; ?>
</div>

<script>
function toggleAll() {
    const cbs = document.querySelectorAll('.del-cb');
    const allChecked = Array.from(cbs).every(cb => cb.checked);
    cbs.forEach(cb => cb.checked = !allChecked);
}
function selectAllRemove() {
    document.querySelectorAll('.del-cb').forEach(cb => cb.checked = true);
}
function toggleGroup(gi) {
    const body = document.querySelector('#group-' + gi + ' .group-body');
    body.style.display = body.style.display === 'none' ? 'grid' : 'none';
}
</script>
</body>
</html>
