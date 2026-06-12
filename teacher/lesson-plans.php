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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $title = trim($_POST['title'] ?? '');
    $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
    $category = trim($_POST['category'] ?? '');
    $lesson_date = trim($_POST['lesson_date'] ?? '');
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 0);
    $status = trim($_POST['status'] ?? 'draft');
    
    // Handle multiple materials
    $materials_array = $_POST['materials'] ?? [];
    $materials = implode(', ', array_filter($materials_array));
    
    // Handle multiple activities
    $activities_array = $_POST['activities'] ?? [];
    $activities = implode(', ', array_filter($activities_array));
    
    $homework_instructions = trim($_POST['homework_instructions'] ?? '');
    $teacher_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($lesson_date) && $duration_minutes > 0) {
        // Insert new lesson plan into database
        $result = $database->execute(
            "INSERT INTO lesson_plans (teacher_id, class_id, title, category, lesson_date, duration_minutes, status, materials, activities, homework_instructions, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$teacher_id, $class_id, $title, $category, $lesson_date, $duration_minutes, $status, $materials, $activities, $homework_instructions]
        );
        
        if ($result) {
            header('Location: lesson-plans?success=lesson_plan_created');
            exit;
        } else {
            $error = "Failed to create lesson plan. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch teacher's classes
$classes = $database->fetchAll("
    SELECT class_id, class_name, grade_level 
    FROM classes 
    WHERE teacher_id = ? 
    ORDER BY class_name ASC
", [$_SESSION['user_id']]);

// Fetch teacher's lesson plans
$lesson_plans = $database->fetchAll("
    SELECT lp.*, c.class_name, c.grade_level 
    FROM lesson_plans lp
    LEFT JOIN classes c ON lp.class_id = c.class_id
    WHERE lp.teacher_id = ?
    ORDER BY lp.lesson_date DESC, lp.created_at DESC
", [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Plans - Kona Ya Hisabati</title>
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
    $sidebar_active = 'lesson-plans';
    $lang_page = 'lesson-plans.php';
    include '../php/includes/dashboard-start.php';
    ?>

        
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
            <div>
                <h1 class="activity-title mb-0">Lesson Plans</h1>
                <p class="activity-instruction mb-0">Create and manage classroom lesson plans</p>
            </div>
            <button type="button" class="btn-child btn-child-primary" onclick="openModal('lessonPlanModal')">
                <i class="fas fa-plus-circle me-2"></i>Create Lesson Plan
            </button>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'lesson_plan_created'): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>Lesson plan created successfully!
            </div>
        <?php endif; ?>

        <!-- Legacy inline form hidden -- use modal -->
        
        <div class="dashboard-card mb-30" style="display:none;" aria-hidden="true">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3 class="dashboard-card-title">Create Lesson Plan</h3>
            </div>
            <p style="color: var(--text-light); margin-bottom: 20px;">Set up a new lesson plan for your class with activities and materials.</p>
            
            <form method="POST" action="">
                <div class="mb-20">
                    <label for="title" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                        <i class="fas fa-heading" style="margin-right: 8px;"></i>Title
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="title" 
                           name="title" 
                           placeholder="e.g. Introduction to Addition"
                           required
                           style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                </div>

                <div class="row-child mb-20">
                    <div class="col-child-2">
                        <label for="class_id" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                            <i class="fas fa-users" style="margin-right: 8px;"></i>Select class
                        </label>
                        <select class="form-select" 
                                id="class_id" 
                                name="class_id" 
                                style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">-- Select Class (Optional) --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['grade_level']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-child-2">
                        <label for="category" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                            <i class="fas fa-tag" style="margin-right: 8px;"></i>Select category (optional)
                        </label>
                        <select class="form-select" 
                                id="category" 
                                name="category" 
                                style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">-- Select Category --</option>
                            <option value="Counting">Counting</option>
                            <option value="Shapes">Shapes</option>
                            <option value="Addition">Addition</option>
                            <option value="Subtraction">Subtraction</option>
                            <option value="Patterns">Patterns</option>
                            <option value="Measurement">Measurement</option>
                            <option value="Time">Time</option>
                            <option value="Money">Money</option>
                            <option value="Number Concepts">Number Concepts</option>
                            <option value="Sorting">Sorting</option>
                        </select>
                    </div>
                </div>

                <div class="row-child mb-20">
                    <div class="col-child-2">
                        <label for="lesson_date" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                            <i class="fas fa-calendar" style="margin-right: 8px;"></i>Date
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="lesson_date" 
                               name="lesson_date" 
                               required
                               style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                    </div>
                    <div class="col-child-2">
                        <label for="duration_minutes" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                            <i class="fas fa-clock" style="margin-right: 8px;"></i>Duration (minutes)
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="duration_minutes" 
                               name="duration_minutes" 
                               placeholder="e.g. 45"
                               min="1"
                               max="180"
                               required
                               style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                    </div>
                </div>

                <div class="mb-20">
                    <label for="status" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                        <i class="fas fa-file-alt" style="margin-right: 8px;"></i>Status
                    </label>
                    <select class="form-select" 
                            id="status" 
                            name="status" 
                            required
                            style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>

                <div class="mb-20">
                    <label class="form-label" style="font-weight: 600; color: var(--text-dark);">
                        <i class="fas fa-box" style="margin-right: 8px;"></i>Materials
                    </label>
                    <div id="materials-container">
                        <div class="material-item mb-10" style="display: flex; gap: 10px;">
                            <input type="text" 
                                   class="form-control material-input" 
                                   name="materials[]" 
                                   placeholder="e.g. Counters"
                                   style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; flex: 1;">
                            <button type="button" 
                                    class="btn-child btn-child-secondary remove-material" 
                                    style="padding: 12px; min-width: 40px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" 
                            id="add-material" 
                            class="btn-child btn-child-secondary mt-10" 
                            style="padding: 8px 16px; font-size: 14px;">
                        <i class="fas fa-plus me-2"></i>Add Material
                    </button>
                </div>

                <div class="mb-20">
                    <label class="form-label" style="font-weight: 600; color: var(--text-dark);">
                        <i class="fas fa-tasks" style="margin-right: 8px;"></i>Activities
                    </label>
                    <div id="activities-container">
                        <div class="activity-item mb-10" style="display: flex; gap: 10px;">
                            <input type="text" 
                                   class="form-control activity-input" 
                                   name="activities[]" 
                                   placeholder="e.g. Counting practice 1-10"
                                   style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; flex: 1;">
                            <button type="button" 
                                    class="btn-child btn-child-secondary remove-activity" 
                                    style="padding: 12px; min-width: 40px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" 
                            id="add-activity" 
                            class="btn-child btn-child-secondary mt-10" 
                            style="padding: 8px 16px; font-size: 14px;">
                        <i class="fas fa-plus me-2"></i>Add Activity
                    </button>
                </div>

                <div class="mb-20">
                    <label for="homework_instructions" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                        <i class="fas fa-clipboard-list" style="margin-right: 8px;"></i>Homework Instructions
                    </label>
                    <textarea class="form-control" 
                              id="homework_instructions" 
                              name="homework_instructions" 
                              rows="3"
                              placeholder="Instructions for homework or follow-up activities..."
                              style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;"></textarea>
                </div>

                <div class="d-flex gap-10 mt-30">
                    <button type="button" 
                            class="btn-child btn-child-secondary" 
                            onclick="window.location.href='dashboard'"
                            style="flex: 1; padding: 12px 24px; font-size: 16px;">
                        <i class="fas fa-times" style="margin-right: 8px;"></i>Cancel
                    </button>
                    <button type="submit" 
                            class="btn-child btn-child-primary" 
                            style="flex: 1; padding: 12px 24px; font-size: 16px;">
                        <i class="fas fa-plus-circle" style="margin-right: 8px;"></i>Create Plan
                    </button>
                </div>
            </form>
        </div>

        <!-- My Lesson Plans Display -->
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3 class="dashboard-card-title">My Lesson Plans</h3>
            </div>
            
            <?php if (empty($lesson_plans)): ?>
                <div class="text-center py-30">
                    <i class="fas fa-folder-open" style="font-size: 48px; color: var(--text-light); margin-bottom: 15px;"></i>
                    <p style="color: var(--text-light);">No lesson plans created yet. Create your first lesson plan above!</p>
                </div>
            <?php else: ?>
                <div class="row-child">
                    <?php foreach ($lesson_plans as $plan): ?>
                        <div class="col-child-3">
                            <div class="dashboard-card">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <h4 style="color: var(--primary-blue); margin-bottom: 0; flex: 1;">
                                        <?php echo htmlspecialchars($plan['title']); ?>
                                    </h4>
                                    <span class="badge" style="background: <?php echo $plan['status'] === 'published' ? 'var(--primary-green)' : 'var(--text-light)'; ?>; color: white; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px;">
                                        <?php echo ucfirst($plan['status']); ?>
                                    </span>
                                </div>
                                <?php if ($plan['class_name']): ?>
                                    <p style="color: var(--text-light); font-size: 0.85rem; margin-bottom: 8px;">
                                        <i class="fas fa-users me-1"></i><?php echo htmlspecialchars($plan['class_name'] . ' - ' . $plan['grade_level']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($plan['category']): ?>
                                    <p style="color: var(--text-light); font-size: 0.85rem; margin-bottom: 8px;">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($plan['category']); ?>
                                    </p>
                                <?php endif; ?>
                                <p style="color: var(--text-light); font-size: 0.85rem; margin-bottom: 8px;">
                                    <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($plan['lesson_date'])); ?>
                                </p>
                                <p style="color: var(--text-light); font-size: 0.85rem; margin-bottom: 15px;">
                                    <i class="fas fa-clock me-1"></i><?php echo $plan['duration_minutes']; ?> minutes
                                </p>
                                <?php if ($plan['materials']): ?>
                                    <p style="color: var(--text-dark); font-size: 0.85rem; margin-bottom: 8px; font-weight: 600;">
                                        Materials:
                                    </p>
                                    <p style="color: var(--text-light); font-size: 0.8rem; margin-bottom: 10px;">
                                        <?php echo htmlspecialchars(substr($plan['materials'], 0, 100)) . (strlen($plan['materials']) > 100 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($plan['activities']): ?>
                                    <p style="color: var(--text-dark); font-size: 0.85rem; margin-bottom: 8px; font-weight: 600;">
                                        Activities:
                                    </p>
                                    <p style="color: var(--text-light); font-size: 0.8rem; margin-bottom: 10px;">
                                        <?php echo htmlspecialchars(substr($plan['activities'], 0, 100)) . (strlen($plan['activities']) > 100 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>
                                <div style="display: flex; gap: 8px;">
                                    <button class="btn-child btn-child-primary" style="flex: 1; min-height: 35px; font-size: 0.85rem;">
                                        <i class="fas fa-eye me-1"></i>View
                                    </button>
                                    <button class="btn-child btn-child-secondary" style="flex: 1; min-height: 35px; font-size: 0.85rem;">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    
    <div id="lessonPlanModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal kona-modal-lg" role="dialog">
            <div class="kona-modal-header">
                <h3><i class="fas fa-book-open me-2"></i>Create Lesson Plan</h3>
                <button type="button" class="kona-modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="">
                <div class="kona-modal-body">
                    <div class="form-group-child">
                        <label class="form-label-child">Title *</label>
                        <input type="text" class="form-control-child" name="title" required placeholder="e.g. Introduction to Addition">
                    </div>
                    <div class="row-child">
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Class</label>
                                <select class="form-control-child" name="class_id">
                                    <option value="">-- Optional --</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo (int)$class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Category</label>
                                <select class="form-control-child" name="category">
                                    <option value="">-- Optional --</option>
                                    <option value="Counting">Counting</option>
                                    <option value="Shapes">Shapes</option>
                                    <option value="Addition">Addition</option>
                                    <option value="Subtraction">Subtraction</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row-child">
                        
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Date *</label>
                                <input type="date" class="form-control-child" name="lesson_date" required>
                            </div>
                        </div>
                        <div class="col-child-2">
                            <div class="form-group-child">
                                <label class="form-label-child">Duration (min) *</label>
                                <input type="number" class="form-control-child" name="duration_minutes" min="1" max="180" required placeholder="45">
                            </div>
                        </div>
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Status</label>
                        <select class="form-control-child" name="status">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Materials (comma separated)</label>
                        <input type="text" class="form-control-child" name="materials[]" placeholder="Counters, number cards">
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Activities (comma separated)</label>
                        <input type="text" class="form-control-child" name="activities[]" placeholder="Counting 1-10">
                    </div>
                    <div class="form-group-child">
                        <label class="form-label-child">Homework</label>
                        <textarea class="form-control-child" name="homework_instructions" rows="2"></textarea>
                    </div>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-primary"><i class="fas fa-save me-2"></i>Create Plan</button>
                </div>
            </form>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/modals.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        // Add material field
        document.getElementById('add-material').addEventListener('click', function() {
            const container = document.getElementById('materials-container');
            const materialItem = document.createElement('div');
            materialItem.className = 'material-item mb-10';
            materialItem.style.display = 'flex';
            materialItem.style.gap = '10px';
            materialItem.innerHTML = `
                <input type="text" 
                       class="form-control material-input" 
                       name="materials[]" 
                       placeholder="e.g. Number cards"
                       style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; flex: 1;">
                <button type="button" 
                        class="btn-child btn-child-secondary remove-material" 
                        style="padding: 12px; min-width: 40px;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(materialItem);
        });

        // Remove material field
        document.getElementById('materials-container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-material')) {
                const container = document.getElementById('materials-container');
                if (container.children.length > 1) {
                    e.target.closest('.material-item').remove();
                }
            }
        });

        // Add activity field
        document.getElementById('add-activity').addEventListener('click', function() {
            const container = document.getElementById('activities-container');
            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item mb-10';
            activityItem.style.display = 'flex';
            activityItem.style.gap = '10px';
            activityItem.innerHTML = `
                <input type="text" 
                       class="form-control activity-input" 
                       name="activities[]" 
                       placeholder="e.g. Addition practice"
                       style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; flex: 1;">
                <button type="button" 
                        class="btn-child btn-child-secondary remove-activity" 
                        style="padding: 12px; min-width: 40px;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(activityItem);
        });

        // Remove activity field
        document.getElementById('activities-container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-activity')) {
                const container = document.getElementById('activities-container');
                if (container.children.length > 1) {
                    e.target.closest('.activity-item').remove();
                }
            }
        });
    </script>
</body>
</html>



