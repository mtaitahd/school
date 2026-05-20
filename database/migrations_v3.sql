-- =====================================================
-- KONA YA HISABATI - Database Migration v3
-- Teacher-Student-Parent Linking & SMS Integration
-- =====================================================

USE kona_hisabati;

-- =====================================================
-- CLASSES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    class_description TEXT,
    grade_level VARCHAR(50) NULL,
    academic_year VARCHAR(20) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- =====================================================
-- STUDENT ACCESS CODES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS student_access_codes (
    code_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    access_code VARCHAR(20) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_access_code (access_code),
    INDEX idx_student (student_id),
    INDEX idx_teacher (teacher_id)
);

-- =====================================================
-- CLASS ENROLLMENT TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS class_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_class_student (class_id, student_id)
);

-- =====================================================
-- PARENT-STUDENT LINKS TABLE
-- =====================================================
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
);

-- =====================================================
-- ASSIGNMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assignment_type ENUM('homework', 'task', 'material', 'quiz') NOT NULL,
    due_date TIMESTAMP NULL,
    points INT NULL,
    attachment_url VARCHAR(255) NULL,
    is_published TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE SET NULL
);

-- =====================================================
-- ASSIGNMENT SUBMISSIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS assignment_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    attachment_url VARCHAR(255) NULL,
    score DECIMAL(5,2) NULL,
    feedback TEXT,
    submitted_at TIMESTAMP NULL,
    graded_at TIMESTAMP NULL,
    status ENUM('pending', 'submitted', 'graded', 'late') DEFAULT 'pending',
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment_student (assignment_id, student_id)
);

-- =====================================================
-- STUDENT ASSIGNMENT LINKS (for individual assignments)
-- =====================================================
CREATE TABLE IF NOT EXISTS student_assignments (
    student_assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment_student (assignment_id, student_id)
);

-- =====================================================
-- SMS LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS sms_logs (
    sms_id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    response TEXT NULL,
    error_message TEXT NULL,
    sms_type ENUM('assignment', 'performance', 'parent_link', 'fee_payment', 'general') NOT NULL,
    recipient_type ENUM('parent', 'student', 'teacher') NOT NULL,
    related_id INT NULL,
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_type (sms_type)
);

-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('assignment', 'submission', 'grade', 'parent_link', 'system') NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    related_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (notification_type)
);

-- =====================================================
-- USER PERMISSIONS TABLE (RBAC)
-- =====================================================
CREATE TABLE IF NOT EXISTS user_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_name VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50) NULL,
    resource_id INT NULL,
    can_create TINYINT(1) DEFAULT 0,
    can_read TINYINT(1) DEFAULT 1,
    can_update TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission (user_id, permission_name, resource_type, resource_id)
);

-- =====================================================
-- ADD PHONE COLUMN TO USERS TABLE
-- =====================================================
ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email;
ALTER TABLE users ADD INDEX idx_phone (phone);

-- =====================================================
-- UPDATE USERS TABLE FOR MULTIPLE PARENTS SUPPORT
-- =====================================================
-- Note: The parent_student_links table already supports multiple parents per student
-- No changes needed to users table structure

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_assignments_teacher ON assignments(teacher_id);
CREATE INDEX idx_assignments_class ON assignments(class_id);
CREATE INDEX idx_submissions_student ON assignment_submissions(student_id);
CREATE INDEX idx_submissions_assignment ON assignment_submissions(assignment_id);
CREATE INDEX idx_student_assignments_student ON student_assignments(student_id);
CREATE INDEX idx_notifications_user_created ON notifications(user_id, created_at);

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
