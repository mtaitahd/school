# SMS Debug & Fix - TODO

## Step 1: Investigation (done)
- [x] Located SMS integration code in `php/sms_service.php`
- [x] Located `sms_logs` schema in `database/migrations_v3.sql`

## Step 2: Code hardening (next)
- [ ] Strictly enforce Tanzania phone formats: `2557XXXXXXXX` / `2556XXXXXXXX` and normalize to `+255...`
- [ ] Replace permissive success detection with strict Webline response parsing (message_id/status/rejected reasons)
- [ ] Fix SMS log lifecycle: queued/sent/pending/delivered/failed/rejected (no immediate “delivered/success”) ✅

## Step 3: Delivery verification (next)
- [ ] Implement Webline DLR retrieval (polling) OR webhook endpoint depending on available API spec/endpoint
- [ ] Add a sync routine to update `sms_logs.status` based on DLR

## Step 4: Database improvements
- [ ] Extend `sms_logs` table with `message_id`, `api_response`, `delivery_status`, `failure_reason` if missing

## Step 5: Logging + retry
- [ ] Save full raw API request payload and full API response into logs/DB
- [ ] Retry only retryable failures; do not retry explicit rejections/auth/sender issues

## Step 6: Admin UX
- [ ] Add SMS test page (enter phone + message; show raw response and delivery status)
- [ ] Add SMS log viewer and dashboard counts by status + failure reasons

## Step 7: Testing
- [ ] Send test SMS and verify status transitions: pending -> sent/pending -> delivered/failed
- [ ] Validate phone rejection for invalid numbers

