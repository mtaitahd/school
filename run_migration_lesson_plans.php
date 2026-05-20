<?php
require_once 'php/db_connection.php';

$sql = "
CREATE TABLE IF NOT EXISTS lesson_plans (
    lesson_plan_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    lesson_date DATE NOT NULL,
    duration_minutes INT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    materials TEXT NULL,
    activities TEXT NULL,
    homework_instructions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE SET NULL
);
";

try {
    $database->getConnection()->exec($sql);
    echo "Lesson plans table created successfully!";
} catch (PDOException $e) {
    echo "Error creating lesson plans table: " . $e->getMessage();
}
?>



