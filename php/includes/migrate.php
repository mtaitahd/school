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

    // Announcement Ticker table
    $database->execute("
        CREATE TABLE IF NOT EXISTS announcement_ticker (
            ticker_id INT AUTO_INCREMENT PRIMARY KEY,
            message VARCHAR(500) NOT NULL,
            url VARCHAR(500) NULL,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            start_date DATE NULL,
            end_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

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

    // Student assignments table (migrations_v3)
    $database->execute("
        CREATE TABLE IF NOT EXISTS student_assignments (
            student_assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            assignment_id INT NOT NULL,
            status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
            score INT NULL,
            notes TEXT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            submitted_at TIMESTAMP NULL,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Notifications table (migrations_v4_notifications)
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
    $idxCheck = $database->fetchAll("SHOW INDEX FROM notifications WHERE Key_name = 'idx_notifications_user_read'");
    if (empty($idxCheck)) {
        $database->execute("CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read)");
    }
    $idxCheck = $database->fetchAll("SHOW INDEX FROM notifications WHERE Key_name = 'idx_notifications_created'");
    if (empty($idxCheck)) {
        $database->execute("CREATE INDEX idx_notifications_created ON notifications(created_at)");
    }

    $done = true;
}
