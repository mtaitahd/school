-- Migration v4: Per-number lessons (Number 1-9) with child-friendly activities
-- Run on live server if ensure_schema_v4 doesn't auto-run.

SET @module_id = (SELECT module_id FROM modules WHERE module_name LIKE '%Recognising%Counting%Numbers%9%' AND is_active = 1 LIMIT 1);
SET @strand_id = COALESCE((SELECT strand_id FROM strands WHERE strand_code = 'NUM' LIMIT 1), (SELECT strand_id FROM strands LIMIT 1));

INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, order_index, is_active)
VALUES (@strand_id, @module_id, 'Recognising and Counting Numbers 1-9', 'NUM-01', '3-5', 'Learn to recognise, count, and write numbers from 1 to 9', 1, 1);

SET @topic_id = (SELECT topic_id FROM topics WHERE module_id = @module_id LIMIT 1);

-- Create 9 individual number lessons
INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, order_index, is_active) VALUES
(@topic_id, 'NUM-N1', 'Number 1', 'Learn to recognise, count, trace, and match number 1', 'Can identify number 1, count 1 objects, and trace the digit.', 15, 1, 1),
(@topic_id, 'NUM-N2', 'Number 2', 'Learn to recognise, count, trace, and match number 2', 'Can identify number 2, count 2 objects, and trace the digit.', 15, 2, 1),
(@topic_id, 'NUM-N3', 'Number 3', 'Learn to recognise, count, trace, and match number 3', 'Can identify number 3, count 3 objects, and trace the digit.', 15, 3, 1),
(@topic_id, 'NUM-N4', 'Number 4', 'Learn to recognise, count, trace, and match number 4', 'Can identify number 4, count 4 objects, and trace the digit.', 15, 4, 1),
(@topic_id, 'NUM-N5', 'Number 5', 'Learn to recognise, count, trace, and match number 5', 'Can identify number 5, count 5 objects, and trace the digit.', 15, 5, 1),
(@topic_id, 'NUM-N6', 'Number 6', 'Learn to recognise, count, trace, and match number 6', 'Can identify number 6, count 6 objects, and trace the digit.', 15, 6, 1),
(@topic_id, 'NUM-N7', 'Number 7', 'Learn to recognise, count, trace, and match number 7', 'Can identify number 7, count 7 objects, and trace the digit.', 20, 7, 1),
(@topic_id, 'NUM-N8', 'Number 8', 'Learn to recognise, count, trace, and match number 8', 'Can identify number 8, count 8 objects, and trace the digit.', 20, 8, 1),
(@topic_id, 'NUM-N9', 'Number 9', 'Learn to recognise, count, trace, and match number 9', 'Can identify number 9, count 9 objects, and trace the digit.', 20, 9, 1);

-- Deactivate old grouped lessons
UPDATE lessons SET is_active = 0 WHERE lesson_code IN ('NUM-01-L01','NUM-01-L02','NUM-01-L03','NUM-01-L04');

-- NOTE: Activities should be seeded via the PHP migration (ensure_schema_v4_number_groups)
-- which runs automatically on page load. For manual seeding, see the PHP function in migrate.php.
