-- =====================================================
-- KONA YA HISABATI - Database Migration v4
-- Parent Claim Code System
-- =====================================================

USE kona_hisabati;

-- =====================================================
-- ADD CLAIM CODE FIELDS TO USERS TABLE
-- =====================================================
ALTER TABLE users 
ADD COLUMN parent_phone VARCHAR(20) NULL AFTER phone,
ADD COLUMN claim_code VARCHAR(20) NULL AFTER parent_phone,
ADD COLUMN parent_claimed TINYINT(1) DEFAULT 0 AFTER claim_code,
ADD COLUMN claim_code_created_at TIMESTAMP NULL AFTER parent_claimed,
ADD INDEX idx_claim_code (claim_code),
ADD INDEX idx_parent_claimed (parent_claimed);

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
