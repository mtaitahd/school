-- =====================================================
-- KONA YA HISABATI - Database Schema
-- =====================================================

CREATE DATABASE IF NOT EXISTS kona_hisabati CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kona_hisabati;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'parent', 'learner') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    profile_image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    parent_id INT NULL,
    FOREIGN KEY (parent_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- =====================================================
-- MODULES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS modules (
    module_id INT AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL,
    module_description TEXT,
    module_icon VARCHAR(255) NOT NULL,
    module_color VARCHAR(50) DEFAULT '#4A90E2',
    audio_prompt VARCHAR(255) NULL,
    order_index INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- ACTIVITIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    activity_name VARCHAR(100) NOT NULL,
    activity_description TEXT,
    activity_type ENUM('counting', 'shapes', 'addition', 'subtraction', 'matching', 'game') NOT NULL,
    difficulty_level ENUM('easy', 'medium', 'hard') DEFAULT 'easy',
    activity_data JSON NULL,
    audio_instruction VARCHAR(255) NULL,
    audio_success VARCHAR(255) NULL,
    audio_error VARCHAR(255) NULL,
    order_index INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE
);

-- =====================================================
-- PROGRESS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_id INT NOT NULL,
    score INT DEFAULT 0,
    attempts INT DEFAULT 0,
    completed TINYINT(1) DEFAULT 0,
    stars_earned INT DEFAULT 0,
    badges_earned JSON NULL,
    completed_at TIMESTAMP NULL,
    last_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_activity (user_id, activity_id)
);

-- =====================================================
-- BADGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    badge_name VARCHAR(100) NOT NULL,
    badge_description TEXT,
    badge_icon VARCHAR(255) NOT NULL,
    badge_color VARCHAR(50) DEFAULT '#FFD700',
    requirement_type VARCHAR(50) NOT NULL,
    requirement_value INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- USER_BADGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS user_badges (
    user_badge_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id)
);

-- =====================================================
-- SESSIONS TABLE (for authentication)
-- =====================================================
CREATE TABLE IF NOT EXISTS sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    session_data TEXT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT SAMPLE DATA
-- =====================================================

-- Insert default admin user
INSERT INTO users (username, email, password, role, first_name, last_name) VALUES
('admin', 'admin@konahisabati.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User');

-- Insert sample modules
INSERT INTO modules (module_name, module_description, module_icon, module_color, audio_prompt, order_index) VALUES
('Counting', 'Learn to count numbers 1-10', 'fa-calculator', '#4A90E2', 'Touch here for Counting!', 1),
('Shapes', 'Learn about different shapes', 'fa-shapes', '#50C878', 'Touch here for Shapes!', 2),
('Addition', 'Learn basic addition', 'fa-plus', '#FFD700', 'Touch here for Addition!', 3),
('Subtraction', 'Learn basic subtraction', 'fa-minus', '#FF6B6B', 'Touch here for Subtraction!', 4),
('Number Concepts', 'Counting, recognition, sequencing, tracing, missing numbers', 'fa-sort-numeric-up', '#E74C3C', 'Touch here for Number Concepts!', 5),
('Patterns', 'Identify, sort, complete patterns, spatial awareness', 'fa-project-diagram', '#3498DB', 'Touch here for Patterns!', 6),
('Sorting', 'Sort by color/size, categorize objects, match numbers', 'fa-filter', '#1ABC9C', 'Touch here for Sorting!', 7),
('Measurement', 'Big/small, tall/short, heavy/light, ordering sizes', 'fa-ruler', '#F39C12', 'Touch here for Measurement!', 8),
('Time', 'Day/night, morning/evening, sequencing activities', 'fa-clock', '#9B59B6', 'Touch here for Time!', 9),
('Money', 'Identify coins/notes, match values, simple buying/selling', 'fa-coins', '#27AE60', 'Touch here for Money!', 10),
('Play Zone', 'Puzzles, matching games, memory cards, number hunts', 'fa-puzzle-piece', '#E91E63', 'Touch here for Play Zone!', 11),
('Songs & Rhymes', 'Counting songs, animations, shape songs', 'fa-music', '#00BCD4', 'Touch here for Songs!', 12),
('Quizzes', 'Short assessments, auto-graded, badges, progress reports', 'fa-clipboard-check', '#FF5722', 'Touch here for Quizzes!', 13);

-- Insert sample activities
INSERT INTO activities (module_id, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction, order_index) VALUES
(1, 'Count Apples', 'Count the apples and select the correct number', 'counting', 'easy', '{"min": 1, "max": 10, "object": "apple"}', 'How many apples do you see?', 1),
(1, 'Count Stars', 'Count the stars and select the correct number', 'counting', 'easy', '{"min": 1, "max": 10, "object": "star"}', 'How many stars do you see?', 2),
(2, 'Identify Circle', 'Identify the circle shape', 'shapes', 'easy', '{"shape": "circle"}', 'Which one is a circle?', 1),
(2, 'Identify Square', 'Identify the square shape', 'shapes', 'easy', '{"shape": "square"}', 'Which one is a square?', 2),
(3, 'Add Numbers 1-5', 'Add two numbers between 1 and 5', 'addition', 'easy', '{"min": 1, "max": 5}', 'Add these numbers together', 1),
(4, 'Subtract Numbers 1-5', 'Subtract two numbers between 1 and 5', 'subtraction', 'easy', '{"min": 1, "max": 5}', 'Subtract these numbers', 1),
-- Number Concepts activities
(5, 'Number Recognition', 'Identify numbers 1-10', 'counting', 'easy', '{"min": 1, "max": 10}', 'What number is this?', 1),
(5, 'Number Sequencing', 'Arrange numbers in order', 'counting', 'easy', '{"min": 1, "max": 10}', 'Put the numbers in order', 2),
(5, 'Missing Numbers', 'Find the missing number in sequence', 'counting', 'medium', '{"min": 1, "max": 10}', 'What number is missing?', 3),
-- Patterns activities
(6, 'Complete Pattern', 'Complete the pattern', 'shapes', 'easy', '{"pattern": "AB"}', 'What comes next?', 1),
(6, 'Shape Patterns', 'Identify and complete shape patterns', 'shapes', 'medium', '{"pattern": "ABC"}', 'Complete the pattern', 2),
(6, 'Spatial Awareness', 'Above/Below/Left/Right', 'shapes', 'easy', '{"concept": "spatial"}', 'Where is the object?', 3),
-- Sorting activities
(7, 'Sort by Color', 'Sort objects by color', 'matching', 'easy', '{"sort_by": "color"}', 'Sort by color', 1),
(7, 'Sort by Size', 'Sort objects by size', 'matching', 'easy', '{"sort_by": "size"}', 'Sort by size', 2),
(7, 'Categorize Objects', 'Group similar objects', 'matching', 'medium', '{"sort_by": "category"}', 'Group the objects', 3),
-- Measurement activities
(8, 'Big and Small', 'Identify big and small objects', 'matching', 'easy', '{"concept": "size"}', 'Which one is bigger?', 1),
(8, 'Tall and Short', 'Compare height', 'matching', 'easy', '{"concept": "height"}', 'Which one is taller?', 2),
(8, 'Heavy and Light', 'Compare weight', 'matching', 'medium', '{"concept": "weight"}', 'Which one is heavier?', 3),
-- Time activities
(9, 'Day and Night', 'Identify day and night', 'matching', 'easy', '{"concept": "time"}', 'Is it day or night?', 1),
(9, 'Daily Routine', 'Sequence daily activities', 'matching', 'easy', '{"concept": "routine"}', 'What do you do first?', 2),
(9, 'Morning and Evening', 'Identify morning and evening', 'matching', 'easy', '{"concept": "time_of_day"}', 'Is it morning or evening?', 3),
-- Money activities
(10, 'Identify Coins', 'Identify different coins', 'matching', 'easy', '{"concept": "coins"}', 'Which coin is this?', 1),
(10, 'Match Values', 'Match coins to values', 'matching', 'medium', '{"concept": "value"}', 'How much is this worth?', 2),
(10, 'Simple Buying', 'Simple buying scenarios', 'addition', 'medium', '{"min": 1, "max": 10}', 'How much do you need to pay?', 3),
-- Play Zone activities
(11, 'Memory Game', 'Match pairs of cards', 'game', 'easy', '{"game": "memory"}', 'Find the matching pair', 1),
(11, 'Number Hunt', 'Find hidden numbers', 'game', 'easy', '{"game": "hunt"}', 'Find the number', 2),
(11, 'Puzzle', 'Complete number puzzle', 'game', 'medium', '{"game": "puzzle"}', 'Complete the puzzle', 3),
-- Songs activities
(12, 'Counting Song', 'Learn counting through song', 'song', 'easy', '{"song": "counting"}', 'Sing along!', 1),
(12, 'Shape Song', 'Learn shapes through song', 'song', 'easy', '{"song": "shapes"}', 'Sing along!', 2),
(12, 'Number Rhyme', 'Number rhymes for kids', 'song', 'easy', '{"song": "rhyme"}', 'Say the rhyme!', 3),
-- Quiz activities
(13, 'Counting Quiz', 'Test counting skills', 'quiz', 'easy', '{"min": 1, "max": 10}', 'Quiz time!', 1),
(13, 'Shapes Quiz', 'Test shape recognition', 'quiz', 'easy', '{"quiz": "shapes"}', 'Quiz time!', 2),
(13, 'Math Assessment', 'Comprehensive math assessment', 'quiz', 'medium', '{"quiz": "comprehensive"}', 'Final assessment!', 3);

-- Insert sample badges
INSERT INTO badges (badge_name, badge_description, badge_icon, badge_color, requirement_type, requirement_value) VALUES
('First Star', 'Earned your first star', 'fa-star', '#FFD700', 'first_star', 1),
('Counting Champion', 'Complete all counting activities', 'fa-trophy', '#FFD700', 'complete_module', 1),
('Shape Master', 'Complete all shape activities', 'fa-shapes', '#50C878', 'complete_module', 2),
('Math Wizard', 'Complete all activities', 'fa-hat-wizard', '#9B59B6', 'complete_all', 0),
('Perfect Score', 'Get a perfect score on any activity', 'fa-gem', '#E74C3C', 'perfect_score', 100);
