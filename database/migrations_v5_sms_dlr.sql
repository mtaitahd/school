-- =====================================================
-- KONA YA HISABATI - Database Migration v5
-- SMS Delivery Report (DLR) support
-- =====================================================

USE kona_hisabati;

-- Extend sms_logs to store provider message id and delivery details
ALTER TABLE sms_logs
    ADD COLUMN IF NOT EXISTS message_id VARCHAR(100) NULL AFTER sms_id,
    ADD COLUMN IF NOT EXISTS delivery_status VARCHAR(30) NULL AFTER message_id,
    ADD COLUMN IF NOT EXISTS failure_reason TEXT NULL AFTER delivery_status,
    ADD COLUMN IF NOT EXISTS api_response_raw LONGTEXT NULL AFTER response;

-- Backfill response into api_response_raw if column is empty
UPDATE sms_logs
SET api_response_raw = response_raw
WHERE api_response_raw IS NULL;

