<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (isset($_POST['action'])) {
        // Create new activity
        if ($_POST['action'] === 'create') {
            $module_id = intval($_POST['module_id']);
            $activity_name = trim($_POST['activity_name']);
            $activity_description = trim($_POST['activity_description']);
            $activity_type = trim($_POST['activity_type']);
            $difficulty_level = trim($_POST['difficulty_level']);
            $audio_instruction = trim($_POST['audio_instruction']);
            $audio_success = trim($_POST['audio_success']);
            $audio_error = trim($_POST['audio_error']);
            $order_index = intval($_POST['order_index']);

            $engine = trim($_POST['engine'] ?? '');
            $level = intval($_POST['level'] ?? 1);
            $activity_object = trim($_POST['activity_object'] ?? 'apple');
            $activity_data = [];
            if ($engine !== '') {
                $activity_data['engine'] = $engine;
                $activity_data['min'] = $level === 1 ? 1 : 10;
                $activity_data['max'] = $level === 1 ? 9 : 20;
                if ($activity_object !== '') {
                    $activity_data['object'] = $activity_object;
                }
            }
            $activity_data_json = !empty($activity_data) ? json_encode($activity_data) : null;
            
            $sql = "INSERT INTO activities (module_id, activity_name, activity_description, activity_type, difficulty_level, audio_instruction, audio_success, audio_error, order_index, activity_data) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$module_id, $activity_name, $activity_description, $activity_type, $difficulty_level, $audio_instruction, $audio_success, $audio_error, $order_index, $activity_data_json];
            
            if ($database->insert($sql, $params)) {
                $success = "Activity created successfully!";
            } else {
                $error = "Failed to create activity.";
            }
        }
        
        // Update activity
        if ($_POST['action'] === 'update') {
            $activity_id = intval($_POST['activity_id']);
            $module_id = intval($_POST['module_id']);
            $activity_name = trim($_POST['activity_name']);
            $activity_description = trim($_POST['activity_description']);
            $activity_type = trim($_POST['activity_type']);
            $difficulty_level = trim($_POST['difficulty_level']);
            $audio_instruction = trim($_POST['audio_instruction']);
            $audio_success = trim($_POST['audio_success']);
            $audio_error = trim($_POST['audio_error']);
            $order_index = intval($_POST['order_index']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $engine = trim($_POST['engine'] ?? '');
            $level = intval($_POST['level'] ?? 1);
            $activity_object = trim($_POST['activity_object'] ?? 'apple');
            $activity_data = [];
            if ($engine !== '') {
                $activity_data['engine'] = $engine;
                $activity_data['min'] = $level === 1 ? 1 : 10;
                $activity_data['max'] = $level === 1 ? 9 : 20;
                if ($activity_object !== '') {
                    $activity_data['object'] = $activity_object;
                }
            }
            $activity_data_json = !empty($activity_data) ? json_encode($activity_data) : null;
            
            $sql = "UPDATE activities SET module_id = ?, activity_name = ?, activity_description = ?, activity_type = ?, 
                    difficulty_level = ?, audio_instruction = ?, audio_success = ?, audio_error = ?, order_index = ?, is_active = ?, activity_data = ? 
                    WHERE activity_id = ?";
            $params = [$module_id, $activity_name, $activity_description, $activity_type, $difficulty_level, $audio_instruction, $audio_success, $audio_error, $order_index, $is_active, $activity_data_json, $activity_id];
            
            if ($database->execute($sql, $params)) {
                $success = "Activity updated successfully!";
            } else {
                $error = "Failed to update activity.";
            }
        }
        
        // Delete activity
        if ($_POST['action'] === 'delete') {
            $activity_id = intval($_POST['activity_id']);
            
            $sql = "DELETE FROM activities WHERE activity_id = ?";
            $params = [$activity_id];
            
            if ($database->execute($sql, $params)) {
                $success = "Activity deleted successfully!";
            } else {
                $error = "Failed to delete activity.";
            }
        }
    }
}

// Fetch all modules for dropdown
$modules = $database->fetchAll("SELECT * FROM modules ORDER BY order_index ASC");

// Fetch all activities
$activities = $database->fetchAll("
    SELECT a.*, m.module_name 
    FROM activities a 
    JOIN modules m ON a.module_id = m.module_id 
    ORDER BY a.order_index ASC
");

// Fetch activity details if editing
$editing_activity = null;
$editing_adata = [];
if (isset($_GET['edit'])) {
    $activity_id = intval($_GET['edit']);
    $editing_activity = $database->fetchOne("
        SELECT * FROM activities WHERE activity_id = ?
    ", [$activity_id]);
    if ($editing_activity && $editing_activity['activity_data']) {
        $editing_adata = json_decode($editing_activity['activity_data'], true) ?: [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Library - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once __DIR__ . '/../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'activity-library';
    $lang_page = 'activity-library.php';
    include '../php/includes/dashboard-start.php';
    ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
            
            <div>
                <h1 class="activity-title mb-0">Activity Library</h1>
                <p class="activity-instruction mb-0">Create and manage learning activities</p>
            </div>
            <button type="button" class="btn-child btn-child-primary" onclick="openModal('createActivityModal')">
                <i class="fas fa-plus-circle me-2"></i>Add Activity
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert-child alert-child-success mb-30">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert-child alert-child-error mb-30">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Activity Statistics -->
        <div class="row-child mb-30">
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                            <i class="fas fa-th-large"></i>
                        </div>
                        <h3 class="dashboard-card-title">Total Activities</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-blue); margin: 0;">
                        <?php echo count($activities); ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-green);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="dashboard-card-title">Active Activities</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-green); margin: 0;">
                        <?php 
                        $active_count = count(array_filter($activities, fn($a) => $a['is_active']));
                        echo $active_count;
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-yellow);">
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <h3 class="dashboard-card-title">Quiz Type</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-yellow); margin: 0;">
                        <?php 
                        $quiz_count = count(array_filter($activities, fn($a) => $a['activity_type'] === 'quiz'));
                        echo $quiz_count;
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-purple);">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <h3 class="dashboard-card-title">Game Type</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-purple); margin: 0;">
                        <?php 
                        $game_count = count(array_filter($activities, fn($a) => $a['activity_type'] === 'game'));
                        echo $game_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Activity Form (hidden) -->
        <div class="dashboard-card mb-30" style="display:none;" aria-hidden="true">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                    <i class="fas fa-<?php echo $editing_activity ? 'edit' : 'plus'; ?>"></i>
                </div>
                <h3 class="dashboard-card-title"><?php echo $editing_activity ? 'Edit Activity' : 'Create New Activity'; ?></h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $editing_activity ? 'update' : 'create'; ?>">
                <?php if ($editing_activity): ?>
                    <input type="hidden" name="activity_id" value="<?php echo $editing_activity['activity_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group-child">
                    <label class="form-label-child">Module</label>
                    <select class="form-control-child" name="module_id" required>
                        <option value="">-- Select Module --</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module['module_id']; ?>"
                                    <?php echo $editing_activity && $editing_activity['module_id'] == $module['module_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($module['module_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group-child">
                    <label class="form-label-child">Activity Name</label>
                    <input type="text" class="form-control-child" name="activity_name" 
                           value="<?php echo $editing_activity ? htmlspecialchars($editing_activity['activity_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group-child">
                    <label class="form-label-child">Description</label>
                    <textarea class="form-control-child" name="activity_description" rows="3"><?php echo $editing_activity ? htmlspecialchars($editing_activity['activity_description']) : ''; ?></textarea>
                </div>
                
                <div class="row-child">
                    <div class="col-child-2">
                        <div class="form-group-child">
                            <label class="form-label-child">Activity Type</label>
                            <select class="form-control-child" name="activity_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="counting" <?php echo $editing_activity && $editing_activity['activity_type'] == 'counting' ? 'selected' : ''; ?>>Counting</option>
                                <option value="shapes" <?php echo $editing_activity && $editing_activity['activity_type'] == 'shapes' ? 'selected' : ''; ?>>Shapes</option>
                                <option value="addition" <?php echo $editing_activity && $editing_activity['activity_type'] == 'addition' ? 'selected' : ''; ?>>Addition</option>
                                <option value="subtraction" <?php echo $editing_activity && $editing_activity['activity_type'] == 'subtraction' ? 'selected' : ''; ?>>Subtraction</option>
                                <option value="matching" <?php echo $editing_activity && $editing_activity['activity_type'] == 'matching' ? 'selected' : ''; ?>>Matching</option>
                                <option value="game" <?php echo $editing_activity && $editing_activity['activity_type'] == 'game' ? 'selected' : ''; ?>>Game</option>
                                <option value="measurement" <?php echo $editing_activity && $editing_activity['activity_type'] == 'measurement' ? 'selected' : ''; ?>>Measurement</option>
                                <option value="time" <?php echo $editing_activity && $editing_activity['activity_type'] == 'time' ? 'selected' : ''; ?>>Time</option>
                                <option value="money" <?php echo $editing_activity && $editing_activity['activity_type'] == 'money' ? 'selected' : ''; ?>>Money</option>
                                <option value="quiz" <?php echo $editing_activity && $editing_activity['activity_type'] == 'quiz' ? 'selected' : ''; ?>>Quiz</option>
                                <option value="song" <?php echo $editing_activity && $editing_activity['activity_type'] == 'song' ? 'selected' : ''; ?>>Song</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-child-2">
                        <div class="form-group-child">
                            <label class="form-label-child">Difficulty Level</label>
                            <select class="form-control-child" name="difficulty_level" required>
                                <option value="easy" <?php echo $editing_activity && $editing_activity['difficulty_level'] == 'easy' ? 'selected' : ''; ?>>Easy</option>
                                <option value="medium" <?php echo $editing_activity && $editing_activity['difficulty_level'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="hard" <?php echo $editing_activity && $editing_activity['difficulty_level'] == 'hard' ? 'selected' : ''; ?>>Hard</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group-child">
                    <label class="form-label-child">Order Index</label>
                    <input type="number" class="form-control-child" name="order_index" 
                           value="<?php echo $editing_activity ? htmlspecialchars($editing_activity['order_index']) : '0'; ?>" min="0">
                </div>
                
                <div class="form-group-child">
                    <label class="form-label-child">Audio Instruction (optional)</label>
                    <input type="text" class="form-control-child" name="audio_instruction" 
                           value="<?php echo $editing_activity ? htmlspecialchars($editing_activity['audio_instruction']) : ''; ?>" placeholder="Audio file path or URL">
                </div>
                
                <div class="form-group-child">
                    <label class="form-label-child">Audio Success (optional)</label>
                    <input type="text" class="form-control-child" name="audio_success" 
                           value="<?php echo $editing_activity ? htmlspecialchars($editing_activity['audio_success']) : ''; ?>" placeholder="Audio file path or URL">
                </div>
                
                <div class="form-group-child">
                    <label class="form-label-child">Audio Error (optional)</label>
                    <input type="text" class="form-control-child" name="audio_error" 
                           value="<?php echo $editing_activity ? htmlspecialchars($editing_activity['audio_error']) : ''; ?>" placeholder="Audio file path or URL">
                </div>
                
                <?php if ($editing_activity): ?>
                    <div class="form-group-child">
                        <label class="form-label-child">
                            <input type="checkbox" name="is_active" <?php echo $editing_activity['is_active'] ? 'checked' : ''; ?>>
                            Active Activity
                        </label>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-30">
                    <button type="submit" class="btn-child btn-child-primary btn-child-large">
                        <i class="fas fa-<?php echo $editing_activity ? 'save' : 'plus-circle'; ?> me-2"></i>
                        <?php echo $editing_activity ? 'Update Activity' : 'Create Activity'; ?>
                    </button>
                    <?php if ($editing_activity): ?>
                        <a href="activity-library" class="btn-child btn-child-secondary btn-child-large" style="margin-left: 10px;">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Activity Management Section -->
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                    <i class="fas fa-cog"></i>
                </div>
                <h3 class="dashboard-card-title">Activity Management</h3>
            </div>
            <p style="color: var(--text-light); margin-bottom: 20px;">
                Use the button below to create new activities. To view or edit existing activities, visit the <a href="all-activities" style="color: var(--primary-blue);">All Activities</a> page.
            </p>
            <button type="button" class="btn-child btn-child-primary" onclick="openModal('createActivityModal')">
                <i class="fas fa-plus-circle me-2"></i>Create New Activity
            </button>
        </div>

    
    <div id="createActivityModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal kona-modal-lg" role="dialog">
            
            <div class="kona-modal-header">
                <h3><i class="fas fa-plus-circle me-2"></i><?php echo $editing_activity ? 'Edit Activity' : 'Create Activity'; ?></h3>
                <button type="button" class="kona-modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $editing_activity ? 'update' : 'create'; ?>">
                <?php if ($editing_activity): ?><input type="hidden" name="activity_id" value="<?php echo (int)$editing_activity['activity_id']; ?>"><?php endif; ?>
                <div class="kona-modal-body">
                    <div class="form-group-child">
                        <label class="form-label-child">Module *</label>
                        <select class="form-control-child" name="module_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($modules as $module): ?>
                            <option value="<?php echo (int)$module['module_id']; ?>" <?php echo ($editing_activity && $editing_activity['module_id'] == $module['module_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($module['module_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Name *</label>
                        <input type="text" class="form-control-child" name="activity_name" required value="<?php echo $editing_activity ? htmlspecialchars($editing_activity['activity_name']) : ''; ?>">
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Description</label>
                        <textarea class="form-control-child" name="activity_description" rows="2"><?php echo $editing_activity ? htmlspecialchars($editing_activity['activity_description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row-child">
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Type *</label>
                                <select class="form-control-child" name="activity_type" required>
                                    <?php foreach (['counting','shapes','addition','subtraction','matching','game','quiz'] as $tp): ?>
                                    <option value="<?php echo $tp; ?>" <?php echo ($editing_activity && $editing_activity['activity_type'] === $tp) ? 'selected' : ''; ?>><?php echo ucfirst($tp); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Difficulty</label>
                                <select class="form-control-child" name="difficulty_level">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Order</label>
                        <input type="number" class="form-control-child" name="order_index" min="0" value="<?php echo $editing_activity ? (int)$editing_activity['order_index'] : 0; ?>">
                    </div>

                    <!-- ===== Interactive Engine Config ===== -->
                    <hr class="my-3" style="border-color:#e2e8f0;">
                    <p class="fw-bold text-muted small mb-2"><i class="fas fa-robot me-1"></i> Interactive Engine (optional)</p>

                    <div class="row-child">
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Engine Type</label>
                                <select class="form-control-child" name="engine" id="engineSelect" onchange="toggleEngineConfig()">
                                    <option value="">-- None (use type) --</option>
                                    <option value="math_game" <?php echo ($editing_adata['engine'] ?? '') === 'math_game' ? 'selected' : ''; ?>>Math Game (mix)</option>
                                    <option value="mango_counting" <?php echo ($editing_adata['engine'] ?? '') === 'mango_counting' ? 'selected' : ''; ?>>Counting Objects</option>
                                    <option value="number_identification" <?php echo ($editing_adata['engine'] ?? '') === 'number_identification' ? 'selected' : ''; ?>>Number Identification</option>
                                    <option value="number_sequencing" <?php echo ($editing_adata['engine'] ?? '') === 'number_sequencing' ? 'selected' : ''; ?>>Number Sequencing</option>
                                    <option value="missing_numbers" <?php echo ($editing_adata['engine'] ?? '') === 'missing_numbers' ? 'selected' : ''; ?>>Missing Numbers</option>
                                    <option value="match_quantity" <?php echo ($editing_adata['engine'] ?? '') === 'match_quantity' ? 'selected' : ''; ?>>Match Quantity</option>
                                    <option value="identify_shapes" <?php echo ($editing_adata['engine'] ?? '') === 'identify_shapes' ? 'selected' : ''; ?>>Identify Shapes</option>
                                    <option value="complete_pattern" <?php echo ($editing_adata['engine'] ?? '') === 'complete_pattern' ? 'selected' : ''; ?>>Complete Pattern</option>
                                    <option value="drag_addition" <?php echo ($editing_adata['engine'] ?? '') === 'drag_addition' ? 'selected' : ''; ?>>Drag Addition</option>
                                    <option value="visual_subtraction" <?php echo ($editing_adata['engine'] ?? '') === 'visual_subtraction' ? 'selected' : ''; ?>>Visual Subtraction</option>
                                    <option value="object_recognition" <?php echo ($editing_adata['engine'] ?? '') === 'object_recognition' ? 'selected' : ''; ?>>Object Recognition</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Level</label>
                                <div class="d-flex gap-2" id="levelGroup">
                                    <label class="btn-child <?php echo ($editing_adata['min'] ?? 1) === 1 ? 'btn-child-primary' : 'btn-child-secondary'; ?>" id="level1Label" style="cursor:pointer;flex:1;text-align:center;padding:8px 12px;font-size:0.85rem;">
                                        <input type="radio" name="level" value="1" <?php echo ($editing_adata['min'] ?? 1) === 1 ? 'checked' : ''; ?> onchange="selectLevel(1)" style="display:none;">
                                        Level 1 (1–9)
                                    </label>
                                    <label class="btn-child <?php echo ($editing_adata['min'] ?? 1) === 10 ? 'btn-child-primary' : 'btn-child-secondary'; ?>" id="level2Label" style="cursor:pointer;flex:1;text-align:center;padding:8px 12px;font-size:0.85rem;">
                                        <input type="radio" name="level" value="2" <?php echo ($editing_adata['min'] ?? 1) === 10 ? 'checked' : ''; ?> onchange="selectLevel(2)" style="display:none;">
                                        Level 2 (10–20)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-child" id="emojiPickerGroup">
                        <label class="form-label-child">Activity Object / Emoji</label>
                        <div class="d-flex align-items-center gap-3">
                            <div id="selectedEmojiDisplay" style="font-size:2.5rem;line-height:1;width:56px;height:56px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;border-radius:12px;border:2px solid #e2e8f0;">
                                <?php
                                $emojiMap = [
                                    'apple'=>'🍎','mango'=>'🥭','ball'=>'⚽','car'=>'🚗','cup'=>'🥤','duck'=>'🦆','star'=>'⭐','fish'=>'🐟',
                                    'dog'=>'🐶','cat'=>'🐱','bird'=>'🐦','bunny'=>'🐰','flower'=>'🌸','balloon'=>'🎈','book'=>'📚','cake'=>'🎂',
                                    'candy'=>'🍬','cookie'=>'🍪','elephant'=>'🐘','frog'=>'🐸','grapes'=>'🍇','icecream'=>'🍦','juice'=>'🧃',
                                    'kite'=>'🪁','lion'=>'🦁','monkey'=>'🐵','orange'=>'🍊','penguin'=>'🐧','robot'=>'🤖','sun'=>'☀️',
                                    'truck'=>'🛻','watermelon'=>'🍉','zebra'=>'🦓'
                                ];
                                $curObj = $editing_adata['object'] ?? 'apple';
                                echo $emojiMap[$curObj] ?? '🍎';
                                ?>
                            </div>
                            <input type="hidden" name="activity_object" id="activityObjectInput" value="<?php echo htmlspecialchars($editing_adata['object'] ?? 'apple'); ?>">
                            <button type="button" class="btn-child btn-child-secondary" onclick="openEmojiPicker()">
                                <i class="fas fa-icons me-1"></i> Choose Icon
                            </button>
                        </div>
                    </div>
                    <div id="engineConfigHint" class="small text-muted <?php echo ($editing_adata['engine'] ?? '') === '' ? '' : 'd-none'; ?>">
                        <i class="fas fa-info-circle me-1"></i> Leave engine empty to use the default based on activity type.
                    </div>

                    <?php if ($editing_activity): ?>
                    <div class="form-group-child">
                        <label><input type="checkbox" name="is_active" <?php echo $editing_activity['is_active'] ? 'checked' : ''; ?>> Active</label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-primary"><i class="fas fa-save me-2"></i>Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Emoji Picker Modal ===== -->
    <div class="kona-modal-overlay" id="emojiPickerModal" aria-hidden="true">
        <div class="kona-modal kona-modal-lg" role="dialog" style="max-width:600px;">
            <div class="kona-modal-header">
                <h3><i class="fas fa-icons me-2"></i>Choose an Icon</h3>
                <button type="button" class="kona-modal-close" onclick="closeEmojiPicker()">&times;</button>
            </div>
            <div class="kona-modal-body">
                <div class="emoji-grid" id="emojiGrid">
                    <?php
                    $allEmojis = [
                        'apple'=>'🍎','mango'=>'🥭','ball'=>'⚽','car'=>'🚗','cup'=>'🥤','duck'=>'🦆','star'=>'⭐','fish'=>'🐟',
                        'dog'=>'🐶','cat'=>'🐱','bird'=>'🐦','bunny'=>'🐰','flower'=>'🌸','balloon'=>'🎈','book'=>'📚','cake'=>'🎂',
                        'candy'=>'🍬','cookie'=>'🍪','elephant'=>'🐘','frog'=>'🐸','grapes'=>'🍇','icecream'=>'🍦','juice'=>'🧃',
                        'kite'=>'🪁','lion'=>'🦁','monkey'=>'🐵','orange'=>'🍊','penguin'=>'🐧','robot'=>'🤖','sun'=>'☀️',
                        'truck'=>'🛻','watermelon'=>'🍉','zebra'=>'🦓','hat'=>'🎩','umbrella'=>'☂️','bike'=>'🚲','van'=>'🚐',
                        'yarn'=>'🧶','toy'=>'🧸','xylophone'=>'🔔','queen'=>'👑','tree'=>'🌳','mushroom'=>'🍄','pineapple'=>'🍍',
                        'cherry'=>'🍒','banana'=>'🍌','peach'=>'🍑','lemon'=>'🍋','avocado'=>'🥑','tomato'=>'🍅','corn'=>'🌽',
                        'bread'=>'🍞','pizza'=>'🍕','burger'=>'🍔','fries'=>'🍟','egg'=>'🥚','pancake'=>'🥞','donut'=>'🍩',
                        'cookies'=>'🍪','chocolate'=>'🍫','milk'=>'🥛','coffee'=>'☕','tea'=>'🍵','horse'=>'🐴','cow'=>'🐮',
                        'pig'=>'🐷','mouse'=>'🐭','bear'=>'🐻','panda'=>'🐼','koala'=>'🐨','tiger'=>'🐯','rabbit'=>'🐇',
                        'chicken'=>'🐔','owl'=>'🦉','butterfly'=>'🦋','snail'=>'🐌','bee'=>'🐝','ant'=>'🐜','ladybug'=>'🐞',
                        'whale'=>'🐳','dolphin'=>'🐬','octopus'=>'🐙','turtle'=>'🐢','crab'=>'🦀','shark'=>'🦈','rocket'=>'🚀',
                        'globe'=>'🌍','moon'=>'🌙','rainbow'=>'🌈','cloud'=>'☁️','snowflake'=>'❄️','fire'=>'🔥','water'=>'💧',
                        'heart'=>'❤️','smile'=>'😊','clap'=>'👏','thumbsup'=>'👍','pencil'=>'✏️','scissors'=>'✂️','lock'=>'🔒',
                        'key'=>'🔑','bell'=>'🔔','gift'=>'🎁','crown'=>'👑','magic'=>'🪄','diamond'=>'💎','money'=>'💰',
                    ];
                    foreach ($allEmojis as $key => $emoji):
                        $sel = ($editing_adata['object'] ?? 'apple') === $key ? ' selected' : '';
                    ?>
                    <div class="emoji-item<?= $sel ?>" data-key="<?= htmlspecialchars($key) ?>" data-emoji="<?= htmlspecialchars($emoji) ?>" onclick="pickEmoji(this)">
                        <?= $emoji ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>

<style>
.emoji-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(48px, 1fr));
    gap: 6px;
    max-height: 380px;
    overflow-y: auto;
    padding: 4px;
}
.emoji-item {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s;
    border: 2px solid transparent;
}
.emoji-item:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    transform: scale(1.15);
}
.emoji-item.selected {
    background: #dbeafe;
    border-color: #2563eb;
    transform: scale(1.15);
}
</style>

<script>
// ===== Engine Config Toggle =====
function toggleEngineConfig() {
    const val = document.getElementById('engineSelect').value;
    const hint = document.getElementById('engineConfigHint');
    if (val) {
        hint.classList.add('d-none');
    } else {
        hint.classList.remove('d-none');
    }
}

// ===== Level Selection =====
function selectLevel(lvl) {
    const base = 'btn-child';
    document.getElementById('level1Label').className = base + (lvl === 1 ? ' btn-child-primary' : ' btn-child-secondary');
    document.getElementById('level2Label').className = base + (lvl === 2 ? ' btn-child-primary' : ' btn-child-secondary');
}

// ===== Emoji Picker =====
function openEmojiPicker() {
    document.getElementById('emojiPickerModal').setAttribute('aria-hidden', 'false');
    document.getElementById('emojiPickerModal').style.display = 'flex';
}

function closeEmojiPicker() {
    document.getElementById('emojiPickerModal').setAttribute('aria-hidden', 'true');
    document.getElementById('emojiPickerModal').style.display = 'none';
}

function pickEmoji(el) {
    document.querySelectorAll('.emoji-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedEmojiDisplay').textContent = el.dataset.emoji;
    document.getElementById('activityObjectInput').value = el.dataset.key;
    closeEmojiPicker();
}

// Close picker on overlay click
document.getElementById('emojiPickerModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEmojiPicker();
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/modals.js"></script>
    <script src="../js/dashboard.js"></script>
    <?php if ($editing_activity): ?><script>document.addEventListener('DOMContentLoaded',function(){openModal('createActivityModal');toggleEngineConfig();});</script><?php endif; ?>
</body>
</html>




