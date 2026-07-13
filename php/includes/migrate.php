<?php
/**
 * Ensures v2 tables exist (safe to call on each dashboard load)
 */
function ensure_schema_v2($database): void {
    static $done = false;
    if ($done) {
        return;
    }

    $database->execute("
        CREATE TABLE IF NOT EXISTS activity_assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            learner_id INT NOT NULL,
            activity_id INT NOT NULL,
            notes TEXT NULL,
            due_date DATE NULL,
            status ENUM('assigned', 'in_progress', 'completed') DEFAULT 'assigned',
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (learner_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE CASCADE,
            UNIQUE KEY unique_teacher_learner_activity (teacher_id, learner_id, activity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $database->execute("
        CREATE TABLE IF NOT EXISTS content_uploads (
            upload_id INT AUTO_INCREMENT PRIMARY KEY,
            uploaded_by INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            related_type ENUM('module', 'activity', 'worksheet', 'audio', 'image') NOT NULL,
            related_id INT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Enhanced announcements table (v3)
    $database->execute("
        CREATE TABLE IF NOT EXISTS announcements (
            announcement_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            short_description VARCHAR(300) NULL,
            content TEXT NOT NULL,
            image VARCHAR(500) NULL,
            event_date DATE NULL,
            status ENUM('draft', 'published') DEFAULT 'published',
            is_urgent TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Migrate old announcements: add columns silently if missing
    $cols = $database->fetchAll("SHOW COLUMNS FROM announcements");
    $has = array_column($cols, 'Field');
    if (!in_array('slug', $has)) {
        $database->execute("ALTER TABLE announcements ADD COLUMN slug VARCHAR(255) NOT NULL DEFAULT '' AFTER title");
    }
    if (!in_array('short_description', $has)) {
        $database->execute("ALTER TABLE announcements ADD COLUMN short_description VARCHAR(300) NULL AFTER slug");
    }
    if (!in_array('image', $has)) {
        $database->execute("ALTER TABLE announcements ADD COLUMN image VARCHAR(500) NULL AFTER content");
    }
    if (!in_array('event_date', $has)) {
        $database->execute("ALTER TABLE announcements ADD COLUMN event_date DATE NULL AFTER image");
    }
    if (!in_array('status', $has)) {
        $database->execute("ALTER TABLE announcements ADD COLUMN status ENUM('draft','published') DEFAULT 'published' AFTER event_date");
    }
    if (!in_array('updated_at', $has)) {
        $database->execute("ALTER TABLE announcements ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }
    if (!in_array('is_urgent', $has)) {
        $database->execute("ALTER TABLE announcements ADD COLUMN is_urgent TINYINT(1) DEFAULT 0 AFTER status");
    }

    // Announcement Ticker table
    $database->execute("
        CREATE TABLE IF NOT EXISTS announcement_ticker (
            ticker_id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            url VARCHAR(500) NULL,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            start_date DATE NULL,
            end_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // Migrate old VARCHAR(500) to TEXT if needed
    $tickerCols = $database->fetchAll("SHOW COLUMNS FROM announcement_ticker");
    $tickerHas = array_column($tickerCols, 'Field');
    if (in_array('message', $tickerHas)) {
        $tickerType = '';
        foreach ($tickerCols as $col) {
            if ($col['Field'] === 'message') {
                $tickerType = $col['Type'];
                break;
            }
        }
        if ($tickerType && strpos($tickerType, 'varchar') !== false) {
            $database->execute("ALTER TABLE announcement_ticker MODIFY message TEXT NOT NULL");
        }
    }

    // Hero Slides table
    $database->execute("
        CREATE TABLE IF NOT EXISTS hero_slides (
            slide_id INT AUTO_INCREMENT PRIMARY KEY,
            image VARCHAR(500) NOT NULL,
            title VARCHAR(255) NULL,
            subtitle VARCHAR(500) NULL,
            link VARCHAR(500) NULL,
            btn_text VARCHAR(100) DEFAULT 'Learn More',
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Notes Board table
    $database->execute("
        CREATE TABLE IF NOT EXISTS notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            featured_image VARCHAR(500) NULL,
            short_description TEXT NULL,
            full_content TEXT NULL,
            publish_date DATE NULL,
            status ENUM('draft','published') DEFAULT 'published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Events Calendar table
    $database->execute("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_title VARCHAR(255) NOT NULL,
            event_date DATE NOT NULL,
            event_time VARCHAR(100) NULL,
            event_description TEXT NULL,
            status ENUM('draft','published') DEFAULT 'published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Governance & Leadership table
    $database->execute("
        CREATE TABLE IF NOT EXISTS governance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            image_path VARCHAR(500) NULL,
            profile_link VARCHAR(500) NULL,
            border_color VARCHAR(50) DEFAULT 'blue',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Parent-student links table (migrations_v3)
    $database->execute("
        CREATE TABLE IF NOT EXISTS parent_student_links (
            link_id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NOT NULL,
            student_id INT NOT NULL,
            access_code VARCHAR(20) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_parent_student (parent_id, student_id),
            INDEX idx_access_code (access_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Assignments table (migrations_v3)
    $database->execute("
        CREATE TABLE IF NOT EXISTS assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            due_date DATE NULL,
            assignment_type ENUM('activity', 'worksheet', 'quiz', 'custom') DEFAULT 'activity',
            created_by INT NOT NULL,
            activity_id INT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_activity (activity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Ensure assignments table has teacher_id and activity_id columns
    // (handle schema mismatch between migrations_v3.sql and this PHP migration)
    $assignCols = $database->fetchAll("SHOW COLUMNS FROM assignments");
    $aFields = array_column($assignCols, 'Field');
    if (!in_array('teacher_id', $aFields)) {
        if (in_array('created_by', $aFields)) {
            $database->execute("ALTER TABLE assignments CHANGE created_by teacher_id INT NOT NULL");
        } else {
            $database->execute("ALTER TABLE assignments ADD COLUMN teacher_id INT NOT NULL AFTER title, ADD FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE");
        }
    }
    if (!in_array('activity_id', $aFields)) {
        $database->execute("ALTER TABLE assignments ADD COLUMN activity_id INT NULL AFTER assignment_type, ADD INDEX idx_activity (activity_id)");
    }
    if (!in_array('due_date', $aFields) && in_array('created_at', $aFields)) {
        $database->execute("ALTER TABLE assignments ADD COLUMN due_date DATE NULL AFTER description");
    }

    // Student assignments table (migrations_v3)
    $database->execute("
        CREATE TABLE IF NOT EXISTS student_assignments (
            student_assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            assignment_id INT NOT NULL,
            status ENUM('pending', 'in_progress', 'submitted', 'graded', 'completed') DEFAULT 'pending',
            score INT NULL,
            notes TEXT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            submitted_at TIMESTAMP NULL,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // Ensure student_assignments has score, submitted_at columns (missing from v3.sql)
    $saCols = $database->fetchAll("SHOW COLUMNS FROM student_assignments");
    $saFields = array_column($saCols, 'Field');
    if (!in_array('score', $saFields)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN score INT NULL AFTER status");
    }
    if (!in_array('submitted_at', $saFields)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN submitted_at TIMESTAMP NULL AFTER assigned_at");
    }
    if (!in_array('notes', $saFields)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN notes TEXT NULL AFTER score");
    }

    // Migrate existing ENUM if needed
    $enumCheck = $database->fetchAll("SHOW COLUMNS FROM student_assignments WHERE Field = 'status'");
    if (!empty($enumCheck)) {
        $type = $enumCheck[0]['Type'] ?? '';
        if (strpos($type, 'completed') === false) {
            $database->execute("ALTER TABLE student_assignments MODIFY COLUMN status ENUM('pending','in_progress','submitted','graded','completed') DEFAULT 'pending'");
        }
    }

    // Notifications table (migrations_v4_notifications) — migrate from v3 if needed
    $notifExists = $database->fetchOne("SHOW TABLES LIKE 'notifications'");
    if (!$notifExists) {
        $database->execute("
            CREATE TABLE IF NOT EXISTS notifications (
                notification_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                related_user_id INT NULL,
                notification_type ENUM('assignment', 'completion', 'badge', 'reminder', 'system') NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (related_user_id) REFERENCES users(user_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } else {
        // Migrate v3 schema: add related_user_id if missing
        $cols = $database->fetchAll("SHOW COLUMNS FROM notifications");
        $colNames = array_column($cols, 'Field');
        if (!in_array('related_user_id', $colNames)) {
            $database->execute("ALTER TABLE notifications ADD COLUMN related_user_id INT NULL AFTER user_id");
        }
        if (!in_array('notification_type', $colNames)) {
            $database->execute("ALTER TABLE notifications ADD COLUMN notification_type ENUM('assignment', 'completion', 'badge', 'reminder', 'system') NOT NULL DEFAULT 'system' AFTER related_user_id");
        }
        if (!in_array('link', $colNames)) {
            $database->execute("ALTER TABLE notifications ADD COLUMN link VARCHAR(255) NULL AFTER message");
        }
    }
    $idxCheck = $database->fetchAll("SHOW INDEX FROM notifications WHERE Key_name = 'idx_notifications_user_read'");
    if (empty($idxCheck)) {
        $database->execute("CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read)");
    }
    $idxCheck = $database->fetchAll("SHOW INDEX FROM notifications WHERE Key_name = 'idx_notifications_created'");
    if (empty($idxCheck)) {
        $database->execute("CREATE INDEX idx_notifications_created ON notifications(created_at)");
    }

    // === Assignment Engine v2: question tracking, timer, answers ===
    $saCols2 = $database->fetchAll("SHOW COLUMNS FROM student_assignments");
    $saFields2 = array_column($saCols2, 'Field');
    if (!in_array('total_questions', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN total_questions INT DEFAULT 0 AFTER status");
    }
    if (!in_array('answered_questions', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN answered_questions INT DEFAULT 0 AFTER total_questions");
    }
    if (!in_array('skipped_questions', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN skipped_questions INT DEFAULT 0 AFTER answered_questions");
    }
    if (!in_array('completed_questions', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN completed_questions INT DEFAULT 0 AFTER skipped_questions");
    }
    if (!in_array('progress_percentage', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN progress_percentage DECIMAL(5,2) DEFAULT 0.00 AFTER completed_questions");
    }
    if (!in_array('duration_minutes', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN duration_minutes INT DEFAULT NULL AFTER progress_percentage");
    }
    if (!in_array('started_at', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN started_at TIMESTAMP NULL AFTER assigned_at");
    }
    if (!in_array('completed_at', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN completed_at TIMESTAMP NULL AFTER started_at");
    }
    if (!in_array('submission_type', $saFields2)) {
        $database->execute("ALTER TABLE student_assignments ADD COLUMN submission_type ENUM('manual','automatic','time_expired') DEFAULT 'manual' AFTER completed_at");
    }

    // Fix ENUM to add new statuses
    $enumCheck2 = $database->fetchAll("SHOW COLUMNS FROM student_assignments WHERE Field = 'status'");
    if (!empty($enumCheck2)) {
        $type2 = $enumCheck2[0]['Type'] ?? '';
        if (strpos($type2, 'expired') === false || strpos($type2, 'auto_submitted') === false) {
            $database->execute("ALTER TABLE student_assignments MODIFY COLUMN status ENUM('pending','in_progress','completed','expired','auto_submitted') DEFAULT 'pending'");
        }
    }

    // Assignment questions table
    $database->execute("
        CREATE TABLE IF NOT EXISTS assignment_questions (
            question_id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT NOT NULL,
            question_text TEXT NOT NULL,
            question_type ENUM('multiple_choice','true_false','short_answer','fill_blank') DEFAULT 'multiple_choice',
            options JSON DEFAULT NULL,
            correct_answer TEXT NOT NULL,
            points INT DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
            INDEX idx_assignment (assignment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Assignment answers table
    $database->execute("
        CREATE TABLE IF NOT EXISTS assignment_answers (
            answer_id INT AUTO_INCREMENT PRIMARY KEY,
            student_assignment_id INT NOT NULL,
            question_id INT NOT NULL,
            student_id INT NOT NULL,
            given_answer TEXT DEFAULT NULL,
            is_correct TINYINT(1) DEFAULT 0,
            points_earned INT DEFAULT 0,
            answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_assignment_id) REFERENCES student_assignments(student_assignment_id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES assignment_questions(question_id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_question (student_assignment_id, question_id),
            INDEX idx_student_assignment (student_assignment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Add age and gender columns to users table
    $userCols = $database->fetchAll("SHOW COLUMNS FROM users");
    $uFields = array_column($userCols, 'Field');
    if (!in_array('age', $uFields)) {
        $database->execute("ALTER TABLE users ADD COLUMN age INT NULL AFTER last_name");
    }
    if (!in_array('gender', $uFields)) {
        $database->execute("ALTER TABLE users ADD COLUMN gender VARCHAR(10) NULL AFTER age");
    }
    if (!in_array('parent_id', $uFields)) {
        $database->execute("ALTER TABLE users ADD COLUMN parent_id INT NULL AFTER last_name");
    }

    // Password resets table (forgot password feature)
    $database->execute("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $done = true;
}

/**
 * Ensures v3 tables exist (domains, strands, topics, lessons, lesson_id FK)
 * Safe to call on every dashboard load.
 */
function ensure_schema_v3($database): void {
    static $done = false;
    if ($done) {
        return;
    }

    $database->execute("
        CREATE TABLE IF NOT EXISTS domains (
            domain_id INT AUTO_INCREMENT PRIMARY KEY,
            domain_name VARCHAR(100) NOT NULL,
            domain_code VARCHAR(20) NOT NULL UNIQUE,
            domain_icon VARCHAR(255) NOT NULL DEFAULT 'fa-book',
            domain_color VARCHAR(50) DEFAULT '#4A90E2',
            description TEXT,
            order_index INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $database->execute("
        CREATE TABLE IF NOT EXISTS strands (
            strand_id INT AUTO_INCREMENT PRIMARY KEY,
            domain_id INT NOT NULL,
            strand_name VARCHAR(100) NOT NULL,
            strand_code VARCHAR(20) NOT NULL UNIQUE,
            strand_icon VARCHAR(255) DEFAULT 'fa-star',
            description TEXT,
            learning_hours DECIMAL(4,1) DEFAULT 0,
            order_index INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (domain_id) REFERENCES domains(domain_id) ON DELETE CASCADE,
            INDEX idx_strand_domain (domain_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $database->execute("
        CREATE TABLE IF NOT EXISTS topics (
            topic_id INT AUTO_INCREMENT PRIMARY KEY,
            strand_id INT NOT NULL,
            module_id INT NULL,
            topic_name VARCHAR(100) NOT NULL,
            topic_code VARCHAR(20) NOT NULL UNIQUE,
            age_range VARCHAR(20) DEFAULT '4-5',
            description TEXT,
            prerequisites JSON NULL,
            estimated_sessions INT DEFAULT 1,
            order_index INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (strand_id) REFERENCES strands(strand_id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE SET NULL,
            INDEX idx_topic_strand (strand_id),
            INDEX idx_topic_module (module_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $database->execute("
        CREATE TABLE IF NOT EXISTS lessons (
            lesson_id INT AUTO_INCREMENT PRIMARY KEY,
            topic_id INT NOT NULL,
            lesson_code VARCHAR(20) NOT NULL UNIQUE,
            lesson_name VARCHAR(100) NOT NULL,
            learning_objective TEXT NOT NULL,
            success_criteria TEXT NOT NULL,
            estimated_minutes INT DEFAULT 20,
            prerequisite_lesson_ids JSON NULL,
            order_index INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
            INDEX idx_lesson_topic (topic_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Add lesson_id column to activities if missing
    $actCols = $database->fetchAll("SHOW COLUMNS FROM activities");
    $actFields = array_column($actCols, 'Field');
    if (!in_array('lesson_id', $actFields)) {
        $database->execute("ALTER TABLE activities ADD COLUMN lesson_id INT NULL AFTER module_id, ADD INDEX idx_activity_lesson (lesson_id)");
    }

    $done = true;
}

/**
 * Creates two modules from the old "Recognising and Counting Numbers 1-9":
 *   1. "Recognising Numbers 1-9" — colour, shape, trace, find, quiz, reward, next
 *   2. "Counting Numbers 1-9" — count, match, game
 * Each number (1-9) gets activities split between the two modules.
 */
function ensure_schema_v4_number_groups($database): void {
    static $done = false;
    if ($done) return;

    // Find original module
    $origModule = $database->fetchOne(
        "SELECT module_id, module_name FROM modules WHERE module_name LIKE '%Recognising%Counting%Numbers%9%' AND is_active = 1"
    );
    if (!$origModule) { $done = true; return; }
    $origModuleId = (int)$origModule['module_id'];

    // --- Module 1: Recognising Numbers 1-9 (keep original module) ---
    $recogniseId = $origModuleId;
    $database->execute(
        "UPDATE modules SET module_name = 'Recognising Numbers 1-9', module_description = 'Learn to recognise, trace, and find numbers 1 to 9', audio_prompt = 'Touch here for Recognising Numbers!' WHERE module_id = ?",
        [$origModuleId]
    );

    // --- Module 2: Counting Numbers 1-9 (create new module) ---
    $countingModule = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Counting Numbers 1-9'");
    if (!$countingModule) {
        $maxOrder = (int)($database->fetchOne("SELECT COALESCE(MAX(order_index),0) as mx FROM modules")['mx'] ?? 0);
        $database->execute(
            "INSERT INTO modules (module_name, module_description, module_icon, module_color, audio_prompt, order_index, is_active)
             VALUES ('Counting Numbers 1-9', 'Count objects, match groups, and play counting games with numbers 1 to 9', 'fa-calculator', '#10B981', 'Touch here for Counting Numbers!', ?, 1)",
            [$maxOrder + 1]
        );
        $countingModule = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Counting Numbers 1-9'");
    }
    $countingId = (int)$countingModule['module_id'];

    // Create topic for counting module
    $countTopic = $database->fetchOne("SELECT topic_id FROM topics WHERE module_id = ? LIMIT 1", [$countingId]);
    if (!$countTopic) {
        $strand = $database->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM' LIMIT 1");
        if (!$strand) { $strand = $database->fetchOne("SELECT strand_id FROM strands LIMIT 1"); }
        if ($strand) {
            $database->execute(
                "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, order_index, is_active)
                 VALUES (?, ?, 'Counting Numbers 1-9', 'NUM-C1', '3-5', 'Count objects and match numbers 1 to 9', 1, 1)",
                [$strand['strand_id'], $countingId]
            );
        }
    }
    $countTopic = $database->fetchOne("SELECT topic_id FROM topics WHERE module_id = ? LIMIT 1", [$countingId]);
    $countTopicId = $countTopic ? (int)$countTopic['topic_id'] : 0;

    // --- Create per-number lessons for Counting module ---
    for ($num = 1; $num <= 9; $num++) {
        $lessonCode = 'COUNT-N' . $num;
        $database->execute(
            "INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, order_index, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
            [$countTopicId, $lessonCode, 'Number ' . $num,
             'Count ' . $num . ' objects and match to number ' . $num,
             'Can count ' . $num . ' objects and match them to the number.',
             ($num >= 7) ? 20 : 15, $num]
        );
    }

    // --- Old lessons deactivation ---
    $recogTopic = $database->fetchOne("SELECT topic_id FROM topics WHERE module_id = ? LIMIT 1", [$recogniseId]);
    if ($recogTopic) {
        $database->execute(
            "UPDATE lessons SET is_active = 0 WHERE topic_id = ? AND lesson_code NOT LIKE 'NUM-N%'",
            [(int)$recogTopic['topic_id']]
        );
    }
    if ($countTopicId) {
        $database->execute(
            "UPDATE lessons SET is_active = 0 WHERE topic_id = ? AND lesson_code NOT LIKE 'COUNT-N%'",
            [$countTopicId]
        );
    }

    // --- Check if counting activities already exist ---
    $countN1 = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = 'COUNT-N1'");
    if ($countN1) {
        $cnt = $database->fetchOne("SELECT COUNT(*) as c FROM activities WHERE lesson_id = ? AND is_active = 1", [$countN1['lesson_id']]);
        if ((int)($cnt['c'] ?? 0) >= 3) { $done = true; return; }
    }

    // --- Shared data ---
    $objects = [1=>'pencil',2=>'table',3=>'desk',4=>'chair',5=>'butterfly',6=>'rabbit',7=>'book',8=>'eraser',9=>'chicken'];

    // =============================================
    // RECOGNISING activities (for each number)
    // =============================================
    $wordMap = ['','one','two','three','four','five','six','seven','eight','nine'];
    $shapes = [
        1=>'straight like a pencil', 2=>'like a swan swimming', 3=>'like two bumps on a hill',
        4=>'like a chair with legs', 5=>'like a hat and a belly', 6=>'like a spiral',
        7=>'like a cane to lean on', 8=>'like two circles stacked', 9=>'like a balloon on a string',
    ];

    for ($num = 1; $num <= 9; $num++) {
        $lesson = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", ['NUM-N' . $num]);
        if (!$lesson) continue;
        $lessonId = (int)$lesson['lesson_id'];
        $shapeDesc = $shapes[$num];
        $nextNum = ($num < 9) ? $num + 1 : null;
        $numWord = $wordMap[$num];
        $maxCount = ($num === 9) ? 9 : $num + 2;

        $acts = [
            ['intro', 0, 'colouring', "Colour Number $num",
             "Let us learn about number $num! Colour the big number $num.",
             '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$maxCount.',"object":"star","mode":"intro","step_type":"intro","skip_finish":true}',
             "Let us learn about number $num!"],
            ['shape', 1, 'tracing', "Shape of Number $num",
             "Look at the shape of number $num. It looks $shapeDesc.",
             '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$num.',"object":"star","mode":"intro","step_type":"shape","skip_finish":true}',
             "Number $num looks $shapeDesc."],
            ['tracing', 2, 'tracing', "Trace Number $num",
             "Use your finger to trace the number $num. Follow the dotted lines.",
             '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$num.',"object":"star","mode":"trace","step_type":"tracing","skip_finish":true}',
             "Trace number $num with your finger!"],
            ['find', 3, 'identification', "Find Number $num",
             "Find and tap the number $num among all the numbers!",
             '{"engine":"number_identification","difficulty":1,"min":1,"max":'.$maxCount.',"step_type":"find"}',
             "Can you find number $num?"],
            ['assessment', 4, 'quiz', "Quiz: Number $num",
             "Show what you know about number $num!",
             '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$maxCount.',"mode":"quiz","step_type":"assessment"}',
             "Let us see what you learned about number $num!"],
            ['reward', 5, 'game', 'Great Work!',
             "Amazing work! You recognised number $num!",
             '{"engine":"math_game","difficulty":1,"step_type":"reward","skip_finish":true}',
             "Amazing work! You earned your stars!"],
        ];

        if ($nextNum) {
            $acts[] = ['next_steps', 6, 'counting', "Next: Number $nextNum",
                "Great job! Ready for number $nextNum?",
                '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$maxCount.',"mode":"intro","step_type":"next_steps","skip_finish":true}',
                "Great job! You are ready for number $nextNum!"];
        } else {
            $acts[] = ['next_steps', 6, 'counting', 'What is Next?',
                "Congratulations! You recognised numbers 1 to 9!",
                '{"engine":"mango_counting","difficulty":1,"min":1,"max":9,"mode":"intro","step_type":"next_steps","skip_finish":true}',
                "Congratulations! You completed all numbers from 1 to 9!"];
        }

        foreach ($acts as [$stepType, $stepOrder, $actType, $name, $desc, $data, $audio]) {
            $exists = $database->fetchOne(
                "SELECT activity_id FROM activities WHERE lesson_id = ? AND step_type = ? LIMIT 1",
                [$lessonId, $stepType]
            );
            if ($exists) continue;
            $database->execute(
                "INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, order_index, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'easy', ?, ?, 1)",
                [$recogniseId, $lessonId, $stepType, $stepOrder, $stepOrder, $name, $desc, $actType, $data, $audio]
            );
        }
    }

    // =============================================
    // COUNTING activities (for each number)
    // =============================================
    for ($num = 1; $num <= 9; $num++) {
        $lesson = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", ['COUNT-N' . $num]);
        if (!$lesson) continue;
        $lessonId = (int)$lesson['lesson_id'];
        $obj = $objects[$num];
        $numWord = $wordMap[$num];
        $maxCount = ($num === 9) ? 9 : $num + 2;

        $acts = [
            ['warmup', 0, 'counting', "Count $num " . ucfirst($obj) . ($num > 1 ? 's' : ''),
             "Count the $obj" . ($num > 1 ? 's' : '') . " on the screen. Tap each one!",
             '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$num.',"object":"'.$obj.'","mode":"count","step_type":"warmup"}',
             "Count the $obj" . ($num > 1 ? 's' : '') . " with me!"],
            ['match', 1, 'matching', "Match " . ucfirst($numWord) . ' Object' . ($num > 1 ? 's' : ''),
             "Match the number $num to the group with $num $obj" . ($num > 1 ? 's' : '') . ".",
             '{"engine":"match_quantity","difficulty":1,"min":1,"max":'.$maxCount.',"step_type":"match"}',
             "Find the group with $num $obj" . ($num > 1 ? 's' : '') . "!"],
            ['game', 2, 'game', "Number Game: Find $num",
             "Play a fun game! Find number $num as fast as you can!",
             '{"engine":"number_identification","difficulty":1,"min":1,"max":'.$maxCount.',"mode":"hunt","step_type":"game"}',
             "Let us play a game! Find number $num!"],
            ['assessment', 3, 'quiz', "Counting Quiz: Number $num",
             "Show how well you can count to $num!",
             '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$maxCount.',"mode":"quiz","step_type":"assessment"}',
             "Let us see how well you can count!"],
            ['reward', 4, 'game', 'Great Counting!',
             "Amazing! You can count to $num!",
             '{"engine":"math_game","difficulty":1,"step_type":"reward","skip_finish":true}',
             "Amazing counting! You earned your stars!"],
        ];

        $nextNum = ($num < 9) ? $num + 1 : null;
        if ($nextNum) {
            $acts[] = ['next_steps', 5, 'counting', "Next: Count to $nextNum",
                "Great counting! Ready to count to $nextNum?",
                '{"engine":"mango_counting","difficulty":1,"min":1,"max":'.$maxCount.',"mode":"intro","step_type":"next_steps","skip_finish":true}',
                "Great counting! Ready for the next number!"];
        } else {
            $acts[] = ['next_steps', 5, 'counting', 'Congratulations!',
                "You can count all the way from 1 to 9!",
                '{"engine":"mango_counting","difficulty":1,"min":1,"max":9,"mode":"intro","step_type":"next_steps","skip_finish":true}',
                "Congratulations! You can count from 1 to 9!"];
        }

        foreach ($acts as [$stepType, $stepOrder, $actType, $name, $desc, $data, $audio]) {
            $exists = $database->fetchOne(
                "SELECT activity_id FROM activities WHERE lesson_id = ? AND step_type = ? LIMIT 1",
                [$lessonId, $stepType]
            );
            if ($exists) continue;
            $database->execute(
                "INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, order_index, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'easy', ?, ?, 1)",
                [$countingId, $lessonId, $stepType, $stepOrder, $stepOrder, $name, $desc, $actType, $data, $audio]
            );
        }
    }

    $done = true;
}
