-- Migration: create parent_notes table
USE kona_hisabati;

CREATE TABLE IF NOT EXISTS parent_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    teacher_id INT NOT NULL,
    child_id INT NOT NULL,
    activity_id INT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE SET NULL
);
