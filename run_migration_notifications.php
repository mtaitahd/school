<?php
require_once 'php/db_connection.php';

$sql = "
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
);

CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created ON notifications(created_at);
";

try {
    $database->getConnection()->exec($sql);
    echo "Notifications table created successfully!";
} catch (PDOException $e) {
    echo "Error creating notifications table: " . $e->getMessage();
}
?>



