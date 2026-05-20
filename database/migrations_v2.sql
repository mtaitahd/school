-- Kona Ya Hisabati - Schema updates v2
USE kona_hisabati;

-- Extend activity types per curriculum modules
ALTER TABLE activities
    MODIFY activity_type ENUM(
        'counting', 'shapes', 'addition', 'subtraction', 'matching', 'game',
        'measurement', 'time', 'money', 'quiz', 'song'
    ) NOT NULL;

-- Teacher assigns activities to learners
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
);

-- Admin uploaded files (worksheets, audio, images)
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
);
