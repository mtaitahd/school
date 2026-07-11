-- =====================================================
-- KONA YA HISABATI — Migration v9: Foundation Numbers
-- =====================================================
-- Adds: domains, strands, topics, lessons tables
-- Adds: lesson_id FK to activities
-- Seeds: Mathematics domain, Number & Operations strand,
--        "Recognising and Counting Numbers 1–9" topic,
--        8 lessons, 80 placeholder activities (10 per lesson)
-- Each activity includes step_type for guided progression
-- =====================================================

-- -----------------------------------------------------
-- 1. DOMAINS TABLE (Level 1)
-- -----------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. STRANDS TABLE (Level 2)
-- -----------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. TOPICS TABLE (Level 3)
-- -----------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. LESSONS TABLE (Level 4)
-- -----------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. ADD lesson_id TO activities TABLE
-- -----------------------------------------------------
SET @has_lesson_id = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activities' AND COLUMN_NAME = 'lesson_id');
SET @ddl = IF(@has_lesson_id = 0,
    'ALTER TABLE activities ADD COLUMN lesson_id INT NULL AFTER module_id,
     ADD COLUMN step_type VARCHAR(30) NULL AFTER lesson_id,
     ADD COLUMN step_order INT DEFAULT 0 AFTER step_type,
     ADD INDEX idx_activity_lesson (lesson_id)',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 6. SEED: Domain — Mathematics
-- -----------------------------------------------------
INSERT IGNORE INTO domains (domain_name, domain_code, domain_icon, domain_color, description, order_index)
VALUES ('Mathematics', 'MATH', 'fa-calculator', '#4A90E2', 'Numbers, shapes, patterns, and early math skills', 1);

-- -----------------------------------------------------
-- 7. SEED: Strand — Number & Operations
-- -----------------------------------------------------
SET @math_domain_id = (SELECT domain_id FROM domains WHERE domain_code = 'MATH');
INSERT IGNORE INTO strands (domain_id, strand_name, strand_code, strand_icon, description, learning_hours, order_index)
VALUES (@math_domain_id, 'Number & Operations', 'NUM', 'fa-sort-numeric-up', 'Counting, number recognition, and early arithmetic', 40, 1);

-- -----------------------------------------------------
-- 8. SEED: Topic — Recognising and Counting Numbers 1–9
-- -----------------------------------------------------
SET @num_strand_id = (SELECT strand_id FROM strands WHERE strand_code = 'NUM');

INSERT IGNORE INTO modules (module_id, module_name, module_description, module_icon, module_color, audio_prompt, order_index)
VALUES (14, 'Recognising and Counting Numbers 1–9',
        'Learn to recognise, count, and write numbers from 1 to 9',
        'fa-sort-numeric-up', '#FF8C00',
        'Touch here for Recognising and Counting Numbers!', 14);

INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index)
VALUES (@num_strand_id, 14, 'Recognising and Counting Numbers 1–9', 'NUM-01', '4-5',
        'Recognise, count, trace, and compare numbers 1 through 9', 8, 1);

-- -----------------------------------------------------
-- 9. SEED: 8 Lessons with permanent curriculum codes
-- -----------------------------------------------------
SET @topic_id = (SELECT topic_id FROM topics WHERE topic_code = 'NUM-01');

INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, prerequisite_lesson_ids, order_index) VALUES
(@topic_id, 'NUM-01-L01', 'Numbers 1, 2, 3',
 'By the end of this lesson, the child can recognise, count, and trace numbers 1, 2, and 3.',
 'Child can identify each number (1, 2, 3) by name, count up to 3 objects accurately, and trace the digits 1, 2, and 3.',
 20, NULL, 1),

(@topic_id, 'NUM-01-L02', 'Numbers 4, 5',
 'By the end of this lesson, the child can recognise, count, and trace numbers 4 and 5, and count up to 5 objects.',
 'Child can identify numbers 4 and 5, count 4-5 objects with one-to-one correspondence, and trace both digits.',
 20, '["NUM-01-L01"]', 2),

(@topic_id, 'NUM-01-L03', 'Numbers 6, 7',
 'By the end of this lesson, the child can recognise, count, and trace numbers 6 and 7, and count up to 7 objects.',
 'Child can identify numbers 6 and 7, count 6-7 objects correctly, and trace both digits.',
 20, '["NUM-01-L02"]', 3),

(@topic_id, 'NUM-01-L04', 'Numbers 8, 9',
 'By the end of this lesson, the child can recognise, count, and trace numbers 8 and 9, and count up to 9 objects.',
 'Child can identify numbers 8 and 9, count 8-9 objects correctly, and trace both digits.',
 20, '["NUM-01-L03"]', 4),

(@topic_id, 'NUM-01-L05', 'Counting 1–9',
 'By the end of this lesson, the child can count from 1 to 9 in order and match each number to the correct quantity.',
 'Child can count from 1 to 9 without skipping, match numerals 1-9 to their quantities, and arrange numbers in order.',
 20, '["NUM-01-L04"]', 5),

(@topic_id, 'NUM-01-L06', 'Comparing Numbers 1–9',
 'By the end of this lesson, the child can compare quantities and identify which group has more or fewer.',
 'Child can correctly identify the group with more or fewer objects when comparing two groups within 1-9.',
 20, '["NUM-01-L05"]', 6),

(@topic_id, 'NUM-01-L07', 'Missing Numbers 1–9',
 'By the end of this lesson, the child can identify missing numbers in a sequence from 1 to 9.',
 'Child can find the missing number in a 1-9 sequence when one number is removed, and fill in the blank correctly.',
 20, '["NUM-01-L06"]', 7),

(@topic_id, 'NUM-01-L08', 'Revision: Numbers 1–9',
 'By the end of this lesson, the child can demonstrate mastery of recognising, counting, tracing, and comparing numbers 1-9 through a comprehensive revision and assessment.',
 'Child scores at least 80% on the lesson assessment covering number recognition, counting, sequencing, and comparison.',
 25, '["NUM-01-L07"]', 8);

-- -----------------------------------------------------
-- 10. SEED: 10 Placeholder Activities per Lesson (80 total)
-- Each activity maps to a lesson blueprint stage via step_type
-- -----------------------------------------------------

-- Helper: insert a placeholder activity with step metadata
-- step_type values:
--   0 = intro          (Lesson Introduction)
--   1 = warmup         (Warm-Up Practice)
--   2 = i_do           (I Do — Teacher Demonstrate)
--   3 = we_do          (We Do — Guided Practice)
--   4 = you_do         (You Do — Independent Practice)
--   5 = check          (Check for Understanding)
--   6 = game           (Interactive Game)
--   7 = assessment     (Quick Assessment)
--   8 = reward         (Reward & Celebration)
--   9 = next_steps     (Revision & Next Steps)

-- Lesson 1: NUM-01-L01 — Numbers 1, 2, 3
SET @l1 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L01');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l1, 'intro', 0, 'Intro: Numbers 1-3', 'Meet numbers 1, 2, and 3', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 3, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Let us learn numbers 1, 2, and 3!'),
(14, @l1, 'warmup', 1, 'Warm-Up: Count 1-3', 'Review counting from 1 to 3', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 3, "object": "apple", "mode": "count", "step_type": "warmup"}',
 'Let us warm up by counting apples!'),
(14, @l1, 'i_do', 2, 'I Do: Watch Me Count 1-3', 'Watch and listen as I count 1, 2, 3', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 3, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me count. One, two, three!'),
(14, @l1, 'we_do', 3, 'We Do: Count Together 1-3', 'Count together with hints', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 3, "object": "ball", "mode": "count", "step_type": "we_do"}',
 'Let us count together!'),
(14, @l1, 'you_do', 4, 'You Do: Count 1-3 Alone', 'Count 1, 2, 3 by yourself', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 3, "object": "fish", "mode": "count", "step_type": "you_do"}',
 'Now you count by yourself!'),
(14, @l1, 'check', 5, 'Check: Find Number 1, 2, or 3', 'Show me you know numbers 1-3', 'counting', 'easy',
 '{"engine": "number_identification", "difficulty": 1, "min": 1, "max": 3, "step_type": "check"}',
 'Find the number I say!'),
(14, @l1, 'game', 6, 'Game: Number Hunt 1-3', 'Find and tap numbers 1, 2, 3 in a fun game', 'game', 'easy',
 '{"engine": "number_identification", "difficulty": 1, "min": 1, "max": 3, "mode": "hunt", "step_type": "game"}',
 'Let us play a number game!'),
(14, @l1, 'assessment', 7, 'Quiz: Numbers 1-3', 'Show what you know about numbers 1-3', 'quiz', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 3, "mode": "quiz", "step_type": "assessment"}',
 'Let us see what you have learned!'),
(14, @l1, 'reward', 8, 'Reward: Great Work!', 'Celebrate your progress', 'game', 'easy',
 '{"engine": "math_game", "difficulty": 1, "step_type": "reward", "skip_finish": true}',
 'Amazing work! You earned your stars!'),
(14, @l1, 'next_steps', 9, 'Next Steps: Keep Practicing', 'See what to do next', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 3, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'Great job! You are ready for the next lesson!');

-- Lesson 2: NUM-01-L02 — Numbers 4, 5
SET @l2 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L02');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l2, 'intro', 0, 'Intro: Numbers 4-5', 'Meet numbers 4 and 5', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 4, "max": 5, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Now let us learn numbers 4 and 5!'),
(14, @l2, 'warmup', 1, 'Warm-Up: Count 1-5', 'Review counting 1 to 5', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 5, "object": "apple", "mode": "count", "step_type": "warmup"}',
 'Let us count from 1 to 5!'),
(14, @l2, 'i_do', 2, 'I Do: Watch Me Count 4-5', 'Watch and listen as I count 4 and 5', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 4, "max": 5, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me count four, five!'),
(14, @l2, 'we_do', 3, 'We Do: Count Together 4-5', 'Count together with hints', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 4, "max": 5, "object": "ball", "mode": "count", "step_type": "we_do"}',
 'Let us count together!'),
(14, @l2, 'you_do', 4, 'You Do: Count 4-5 Alone', 'Count 4 and 5 by yourself', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 4, "max": 5, "object": "fish", "mode": "count", "step_type": "you_do"}',
 'Now you count by yourself!'),
(14, @l2, 'check', 5, 'Check: Find Number 4 or 5', 'Show me you know numbers 4 and 5', 'counting', 'easy',
 '{"engine": "number_identification", "difficulty": 1, "min": 4, "max": 5, "step_type": "check"}',
 'Find the number I say!'),
(14, @l2, 'game', 6, 'Game: Number Hunt 4-5', 'Find and tap numbers 4 and 5', 'game', 'easy',
 '{"engine": "number_identification", "difficulty": 1, "min": 4, "max": 5, "mode": "hunt", "step_type": "game"}',
 'Let us play a number game!'),
(14, @l2, 'assessment', 7, 'Quiz: Numbers 4-5', 'Show what you know about numbers 4 and 5', 'quiz', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 4, "max": 5, "mode": "quiz", "step_type": "assessment"}',
 'Let us see what you have learned!'),
(14, @l2, 'reward', 8, 'Reward: Great Work!', 'Celebrate your progress', 'game', 'easy',
 '{"engine": "math_game", "difficulty": 1, "step_type": "reward", "skip_finish": true}',
 'Amazing work! You earned your stars!'),
(14, @l2, 'next_steps', 9, 'Next Steps: Keep Practicing', 'See what to do next', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 5, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'Great job! You are ready for the next lesson!');

-- Lesson 3: NUM-01-L03 — Numbers 6, 7
SET @l3 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L03');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l3, 'intro', 0, 'Intro: Numbers 6-7', 'Meet numbers 6 and 7', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 6, "max": 7, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Now let us learn numbers 6 and 7!'),
(14, @l3, 'warmup', 1, 'Warm-Up: Count 1-7', 'Review counting to 7', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 7, "object": "apple", "mode": "count", "step_type": "warmup"}',
 'Let us count from 1 to 7!'),
(14, @l3, 'i_do', 2, 'I Do: Watch Me Count 6-7', 'Watch and listen as I count 6 and 7', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 6, "max": 7, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me count six, seven!'),
(14, @l3, 'we_do', 3, 'We Do: Count Together 6-7', 'Count together with hints', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 6, "max": 7, "object": "ball", "mode": "count", "step_type": "we_do"}',
 'Let us count together!'),
(14, @l3, 'you_do', 4, 'You Do: Count 6-7 Alone', 'Count 6 and 7 by yourself', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 6, "max": 7, "object": "fish", "mode": "count", "step_type": "you_do"}',
 'Now you count by yourself!'),
(14, @l3, 'check', 5, 'Check: Find Number 6 or 7', 'Show me you know numbers 6 and 7', 'counting', 'easy',
 '{"engine": "number_identification", "difficulty": 2, "min": 6, "max": 7, "step_type": "check"}',
 'Find the number I say!'),
(14, @l3, 'game', 6, 'Game: Number Hunt 6-7', 'Find and tap numbers 6 and 7', 'game', 'easy',
 '{"engine": "number_identification", "difficulty": 2, "min": 6, "max": 7, "mode": "hunt", "step_type": "game"}',
 'Let us play a number game!'),
(14, @l3, 'assessment', 7, 'Quiz: Numbers 6-7', 'Show what you know about numbers 6 and 7', 'quiz', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 6, "max": 7, "mode": "quiz", "step_type": "assessment"}',
 'Let us see what you have learned!'),
(14, @l3, 'reward', 8, 'Reward: Great Work!', 'Celebrate your progress', 'game', 'easy',
 '{"engine": "math_game", "difficulty": 2, "step_type": "reward", "skip_finish": true}',
 'Amazing work! You earned your stars!'),
(14, @l3, 'next_steps', 9, 'Next Steps: Keep Practicing', 'See what to do next', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 1, "max": 7, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'Great job! You are ready for the next lesson!');

-- Lesson 4: NUM-01-L04 — Numbers 8, 9
SET @l4 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L04');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l4, 'intro', 0, 'Intro: Numbers 8-9', 'Meet numbers 8 and 9', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 8, "max": 9, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Now let us learn numbers 8 and 9!'),
(14, @l4, 'warmup', 1, 'Warm-Up: Count 1-9', 'Review counting 1 to 9', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 1, "max": 9, "object": "apple", "mode": "count", "step_type": "warmup"}',
 'Let us count from 1 to 9!'),
(14, @l4, 'i_do', 2, 'I Do: Watch Me Count 8-9', 'Watch and listen as I count 8 and 9', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 8, "max": 9, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me count eight, nine!'),
(14, @l4, 'we_do', 3, 'We Do: Count Together 8-9', 'Count together with hints', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 8, "max": 9, "object": "ball", "mode": "count", "step_type": "we_do"}',
 'Let us count together!'),
(14, @l4, 'you_do', 4, 'You Do: Count 8-9 Alone', 'Count 8 and 9 by yourself', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 8, "max": 9, "object": "bird", "mode": "count", "step_type": "you_do"}',
 'Now you count by yourself!'),
(14, @l4, 'check', 5, 'Check: Find Number 8 or 9', 'Show me you know numbers 8 and 9', 'counting', 'easy',
 '{"engine": "number_identification", "difficulty": 2, "min": 8, "max": 9, "step_type": "check"}',
 'Find the number I say!'),
(14, @l4, 'game', 6, 'Game: Number Hunt 8-9', 'Find and tap numbers 8 and 9', 'game', 'easy',
 '{"engine": "number_identification", "difficulty": 2, "min": 8, "max": 9, "mode": "hunt", "step_type": "game"}',
 'Let us play a number game!'),
(14, @l4, 'assessment', 7, 'Quiz: Numbers 8-9', 'Show what you know about numbers 8 and 9', 'quiz', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 8, "max": 9, "mode": "quiz", "step_type": "assessment"}',
 'Let us see what you have learned!'),
(14, @l4, 'reward', 8, 'Reward: Great Work!', 'Celebrate your progress', 'game', 'easy',
 '{"engine": "math_game", "difficulty": 2, "step_type": "reward", "skip_finish": true}',
 'Amazing work! You earned your stars!'),
(14, @l4, 'next_steps', 9, 'Next Steps: Keep Practicing', 'See what to do next', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 1, "max": 9, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'Great job! You are ready for the next lesson!');

-- Lesson 5: NUM-01-L05 — Counting 1-9
SET @l5 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L05');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l5, 'intro', 0, 'Intro: Counting 1-9', 'Today we will count all numbers 1 to 9', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Today let us count from 1 to 9!'),
(14, @l5, 'warmup', 1, 'Warm-Up: Count to 5', 'Let us review counting to 5', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 1, "min": 1, "max": 5, "object": "apple", "mode": "count", "step_type": "warmup"}',
 'Let us warm up by counting to 5!'),
(14, @l5, 'i_do', 2, 'I Do: Watch Me Count 1-9', 'Watch and listen as I count 1 to 9 in order', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me count from one to nine!'),
(14, @l5, 'we_do', 3, 'We Do: Count 1-9 Together', 'Count from 1 to 9 together', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "candy", "mode": "count", "step_type": "we_do"}',
 'Let us count from 1 to 9 together!'),
(14, @l5, 'you_do', 4, 'You Do: Count 1-9 Alone', 'Count all numbers 1 to 9 by yourself', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "candy", "mode": "count", "step_type": "you_do"}',
 'Now count from 1 to 9 by yourself!'),
(14, @l5, 'check', 5, 'Check: Match Numbers 1-9', 'Match each number to its quantity', 'matching', 'medium',
 '{"engine": "match_quantity", "difficulty": 3, "min": 1, "max": 9, "step_type": "check"}',
 'Match the number to the correct group!'),
(14, @l5, 'game', 6, 'Game: Number Order 1-9', 'Put numbers 1-9 in the right order', 'game', 'medium',
 '{"engine": "number_sequencing", "difficulty": 3, "min": 1, "max": 9, "step_type": "game"}',
 'Put the numbers in the right order!'),
(14, @l5, 'assessment', 7, 'Quiz: Counting 1-9', 'Show what you know about counting 1-9', 'quiz', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "mode": "quiz", "step_type": "assessment"}',
 'Let us see what you have learned!'),
(14, @l5, 'reward', 8, 'Reward: Great Work!', 'Celebrate your progress', 'game', 'medium',
 '{"engine": "math_game", "difficulty": 3, "step_type": "reward", "skip_finish": true}',
 'Amazing work! You earned your stars!'),
(14, @l5, 'next_steps', 9, 'Next Steps: Keep Practicing', 'See what to do next', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'Great job! You are ready for the next lesson!');

-- Lesson 6: NUM-01-L06 — Comparing Numbers 1-9
SET @l6 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L06');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l6, 'intro', 0, 'Intro: Comparing Numbers', 'Today we learn about more and less', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Today we learn which group has more!'),
(14, @l6, 'warmup', 1, 'Warm-Up: Count to 9', 'Review counting from 1 to 9', 'counting', 'easy',
 '{"engine": "mango_counting", "difficulty": 2, "min": 1, "max": 9, "object": "apple", "mode": "count", "step_type": "warmup"}',
 'Let us count from 1 to 9 together!'),
(14, @l6, 'i_do', 2, 'I Do: Watch Me Compare', 'Watch how I compare two groups', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me compare which group has more!'),
(14, @l6, 'we_do', 3, 'We Do: Compare Together', 'Compare groups with my help', 'game', 'medium',
 '{"engine": "number_identification", "difficulty": 3, "mode": "compare", "min": 1, "max": 9, "step_type": "we_do"}',
 'Which group has more? Let us find out together!'),
(14, @l6, 'you_do', 4, 'You Do: Compare Alone', 'Compare two groups by yourself', 'game', 'medium',
 '{"engine": "match_quantity", "difficulty": 3, "mode": "compare", "min": 1, "max": 9, "step_type": "you_do"}',
 'Tap the group with more objects!'),
(14, @l6, 'check', 5, 'Check: Equal Groups', 'Find groups with the same number', 'matching', 'medium',
 '{"engine": "match_quantity", "difficulty": 3, "mode": "equal", "min": 1, "max": 9, "step_type": "check"}',
 'Find the groups that are the same!'),
(14, @l6, 'game', 6, 'Game: More or Less', 'Identify which group has more in a fun game', 'game', 'medium',
 '{"engine": "number_identification", "difficulty": 3, "mode": "compare", "min": 1, "max": 9, "step_type": "game"}',
 'Let us play a comparing game!'),
(14, @l6, 'assessment', 7, 'Quiz: Comparing Numbers', 'Show what you know about comparing', 'quiz', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "mode": "quiz", "step_type": "assessment"}',
 'Let us see what you have learned!'),
(14, @l6, 'reward', 8, 'Reward: Great Work!', 'Celebrate your progress', 'game', 'medium',
 '{"engine": "math_game", "difficulty": 3, "step_type": "reward", "skip_finish": true}',
 'Amazing work! You earned your stars!'),
(14, @l6, 'next_steps', 9, 'Next Steps: Keep Practicing', 'See what to do next', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'Great job! You are ready for the next lesson!');

-- Lesson 7: NUM-01-L07 — Missing Numbers 1-9
SET @l7 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L07');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l7, 'intro', 0, 'Intro: Missing Numbers', 'Today we find missing numbers in a sequence', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Today we find numbers that are missing!'),
(14, @l7, 'warmup', 1, 'Warm-Up: Number Order 1-9', 'Review number order 1 to 9', 'counting', 'medium',
 '{"engine": "number_sequencing", "difficulty": 3, "min": 1, "max": 9, "step_type": "warmup"}',
 'Let us review the number order!'),
(14, @l7, 'i_do', 2, 'I Do: Watch Me Find Missing', 'Watch how I find the missing number', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me find the missing number!'),
(14, @l7, 'we_do', 3, 'We Do: Find Missing Together', 'Find the missing number with my help', 'counting', 'medium',
 '{"engine": "missing_numbers", "difficulty": 3, "min": 1, "max": 9, "step_type": "we_do"}',
 'Let us find the missing number together!'),
(14, @l7, 'you_do', 4, 'You Do: Find Missing Alone', 'Find the missing number by yourself', 'counting', 'medium',
 '{"engine": "missing_numbers", "difficulty": 3, "min": 1, "max": 9, "step_type": "you_do"}',
 'Find the missing number by yourself!'),
(14, @l7, 'check', 5, 'Check: Complete the Sequence', 'Fill in all missing numbers 1-9', 'counting', 'medium',
 '{"engine": "missing_numbers", "difficulty": 4, "min": 1, "max": 9, "mode": "multiple", "step_type": "check"}',
 'Fill in all the missing numbers!'),
(14, @l7, 'game', 6, 'Game: Dot-to-Dot 1-9', 'Connect the dots from 1 to 9', 'game', 'medium',
 '{"engine": "dot_to_dot", "difficulty": 3, "min": 1, "max": 9, "step_type": "game"}',
 'Connect the dots in order!'),
(14, @l7, 'assessment', 7, 'Quiz: Missing Numbers', 'Show what you know about missing numbers', 'quiz', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "mode": "quiz", "step_type": "assessment"}',
 'Let us see what you have learned!'),
(14, @l7, 'reward', 8, 'Reward: Great Work!', 'Celebrate your progress', 'game', 'medium',
 '{"engine": "math_game", "difficulty": 3, "step_type": "reward", "skip_finish": true}',
 'Amazing work! You earned your stars!'),
(14, @l7, 'next_steps', 9, 'Next Steps: Keep Practicing', 'See what to do next', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'Great job! You are ready for the next lesson!');

-- Lesson 8: NUM-01-L08 — Revision: Numbers 1-9
SET @l8 = (SELECT lesson_id FROM lessons WHERE lesson_code = 'NUM-01-L08');
INSERT IGNORE INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction) VALUES
(14, @l8, 'intro', 0, 'Intro: Let Us Review', 'Today we review everything about numbers 1-9', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "intro", "step_type": "intro", "skip_finish": true}',
 'Today we review numbers 1 to 9!'),
(14, @l8, 'warmup', 1, 'Warm-Up: Quick Count 1-9', 'Quick review counting 1 to 9', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "apple", "mode": "count", "step_type": "warmup"}',
 'Let us quickly count from 1 to 9!'),
(14, @l8, 'i_do', 2, 'I Do: Review All Numbers', 'Watch me demonstrate numbers 1-9', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 3, "min": 1, "max": 9, "object": "star", "mode": "demo", "step_type": "i_do", "skip_finish": true}',
 'Watch me review all numbers 1 to 9!'),
(14, @l8, 'we_do', 3, 'We Do: Count and Match', 'Practice counting and matching with help', 'counting', 'medium',
 '{"engine": "match_quantity", "difficulty": 4, "min": 1, "max": 9, "step_type": "we_do"}',
 'Let us count and match together!'),
(14, @l8, 'you_do', 4, 'You Do: Count and Match Alone', 'Count objects and match to numbers 1-9', 'counting', 'medium',
 '{"engine": "match_quantity", "difficulty": 4, "min": 1, "max": 9, "step_type": "you_do"}',
 'Count and match all the numbers by yourself!'),
(14, @l8, 'check', 5, 'Check: Find Any Number 1-9', 'Show me you know all numbers 1-9', 'counting', 'medium',
 '{"engine": "number_identification", "difficulty": 4, "min": 1, "max": 9, "step_type": "check"}',
 'Find the number I say!'),
(14, @l8, 'game', 6, 'Game: Number Hunt 1-9', 'Find and tap all numbers 1-9 in a fun game', 'game', 'medium',
 '{"engine": "number_identification", "difficulty": 4, "min": 1, "max": 9, "mode": "hunt", "step_type": "game"}',
 'Find all the numbers in this fun game!'),
(14, @l8, 'assessment', 7, 'Final Quiz: Numbers 1-9', 'Comprehensive quiz on numbers 1-9', 'quiz', 'medium',
 '{"engine": "mango_counting", "difficulty": 4, "min": 1, "max": 9, "mode": "quiz", "step_type": "assessment"}',
 'Let us see everything you have learned!'),
(14, @l8, 'reward', 8, 'Reward: Congratulations!', 'Celebrate completing Numbers 1-9', 'game', 'medium',
 '{"engine": "math_game", "difficulty": 4, "step_type": "reward", "skip_finish": true}',
 'Congratulations! You completed Numbers 1 to 9!'),
(14, @l8, 'next_steps', 9, 'Next Steps: What Is Next?', 'See your progress and the next topic', 'counting', 'medium',
 '{"engine": "mango_counting", "difficulty": 4, "min": 1, "max": 9, "mode": "intro", "step_type": "next_steps", "skip_finish": true}',
 'You did amazing! You are ready for more numbers!');
