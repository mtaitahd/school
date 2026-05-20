-- =====================================================
-- KONA YA HISABATI - Database Migration v6
-- Add activity_id to assignments table for reliable linking
-- =====================================================

USE kona_hisabati;

-- =====================================================
-- ADD ACTIVITY_ID COLUMN TO ASSIGNMENTS TABLE
-- =====================================================
ALTER TABLE assignments ADD COLUMN activity_id INT NULL AFTER assignment_type;
ALTER TABLE assignments ADD INDEX idx_activity_id (activity_id);
ALTER TABLE assignments ADD FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE SET NULL;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
