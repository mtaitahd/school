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
