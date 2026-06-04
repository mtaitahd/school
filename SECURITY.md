# Security Implementation — Kona Ya Hisabati

## 1. Session Security — `php/includes/session.php`

| Feature | What it does |
|---------|-------------|
| `sec_session_start()` | Starts session with: `httponly`, `strict_mode`, `SameSite=Lax`, custom name, 30min timeout |
| `sec_session_regenerate()` | Regenerates session ID after login to prevent session fixation |
| `sec_session_destroy()` | Clears all session data + deletes cookie for clean logout |
| Session lifetime check | Auto-destroys expired sessions (>30 min idle) |

## 2. CSRF Protection — `php/includes/csrf.php`

| Feature | What it does |
|---------|-------------|
| `csrf_token()` | Generates a random 32-byte token stored in session |
| `csrf_field()` | Returns `<input type="hidden">` for use in all forms |
| `csrf_meta()` | Returns `<meta name="csrf-token">` for AJAX requests |
| `csrf_require()` | Validates token on every POST with `hash_equals()` (timing-attack safe) |
| `csrf_regenerate()` | Re-issues token after validation (prevents reuse) |

## 3. Input Validation — `php/includes/validator.php`

| Method | Validates |
|--------|----------|
| `Validator::username()` | 3-50 chars, alphanumeric + `._-` |
| `Validator::email()` | Standard email format |
| `Validator::password()` | Min 6 chars |
| `Validator::int()` | Integer within optional range |
| `Validator::phone()` | Digits, `+`, `-`, `(`, `)`, spaces |
| `Validator::slug()` | Lowercase alphanumeric + hyphens |
| `Validator::text()` | Stripped tags, max length |
| `Validator::url()` | Valid URL format |
| `Validator::inArray()` | Value in an allowlist |

## 4. Security Headers — `php/includes/security.php` + `.htaccess`

| Header | Value |
|--------|-------|
| `Content-Security-Policy` | Restricted sources for scripts, styles, fonts, images; `form-action 'self'`; `frame-ancestors 'self'`; `object-src 'none'` |
| `X-Frame-Options` | `SAMEORIGIN` (no clickjacking) |
| `X-Content-Type-Options` | `nosniff` (MIME-sniffing prevention) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | No camera, mic, geolocation |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (in production) |
| `X-Powered-By` | Removed (PHP version hidden) |
| `ServerSignature` | `Off` (Apache version hidden) |

## 5. Rate Limiting — `php/includes/security.php`

| Limiter | Threshold | Window | Scope |
|---------|-----------|--------|-------|
| Login attempts | **5 failures** | **15 minutes** | Per IP+username (preventing both IP-based bypass and user-targeting) |
| General requests | **120 requests** | **60 seconds** | Per IP |
| Admin lockout | Manual | 15 minutes | Per username, across all IPs |

On limit hit: login shows *"Too many login attempts. Please try again in 15 minutes."*; general returns HTTP 429.

## 6. Authentication — All login pages

| Feature | Where |
|---------|-------|
| Rate-limited login | `login.php`, `admin/index.php`, `teacher/login.php`, `parent/login.php`, `learner/login.php` |
| CSRF validation on POST | Same 5 login pages |
| `auth_login()` with session regeneration | Same 5 login pages |
| `is_active` check | Login queries only match `is_active = 1` |
| `password_verify()` | All password-based logins |
| Rate limit cleared on success | `sec_clear_login_rate_limit()` after successful login |

## 7. Admin User Management — `admin/user-actions.php` + `users.php`

| Action | Protected by |
|--------|-------------|
| Create user | CSRF + rate limit |
| Update user | CSRF + rate limit + self-deactivation prevented |
| Delete user | CSRF + rate limit + self-delete prevented |
| Toggle active/inactive | CSRF + rate limit |
| **Lock login (15 min)** | CSRF + rate limit — creates admin lockout file |
| **Unlock login** | CSRF + rate limit — clears lockout + all rate limit files |

## 8. File Upload Security — `admin/upload-content.php`, `update-profile.php`

| Check | Implementation |
|-------|---------------|
| MIME type | `finfo::file()` — reads actual file magic bytes, not `$_FILES['type']` |
| Extension allowlist | Only `.jpg, .jpeg, .png, .gif, .pdf, .doc, .docx` (uploads) / `.jpg, .jpeg, .png, .gif` (profile) |
| Max file size | 5MB (uploads) / 2MB (profile) |
| Filename renaming | `bin2hex(random_bytes(16))` — no user-controlled filenames |
| PHP execution blocked | `uploads/.htaccess` with `php_flag engine off` |
| Old file cleanup | Profile update deletes previous image |

## 9. Database — `php/db_connection.php`

| Feature | Detail |
|---------|--------|
| PDO prepared statements | All queries use `?` placeholders — no SQL injection |
| `.env` credentials | Reads `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` from `.env` |
| Backward-compatible fallback | Falls back to hardcoded credentials if `.env` not present |
| `RuntimeException` on failure | Fails loudly if DB connection cannot be established |

## 10. Error Handling — `php/includes/security.php`

| Feature | Detail |
|---------|--------|
| Custom error handler | Logs errors to `logs/php_errors.log` |
| Custom exception handler | Logs + shows generic 500 page in production |
| `display_errors` | Off in production |
| `log_errors` | On |

## 11. `.htaccess` — Server-Level Protections

| Rule | Effect |
|------|--------|
| `Options -Indexes` | No directory listing |
| `ServerSignature Off` | No Apache version in error pages |
| `<FilesMatch>` for `.env`, `*.sql`, `*.log`, `composer.*`, etc. | 40+ sensitive file patterns blocked (returns 403) |
| TRACE/TRACK method rejection | Prevents HTTP method tampering |
| `RewriteCond` for malicious query strings | Blocks `cmd=`, `exec=`, `UNION SELECT`, `etc/passwd`, etc. |
| `mod_headers` | Sends all 6 security headers for static assets |
| `mod_expires` | Caching headers for static resources |

## 12. Active Logging

| Log | Location |
|-----|----------|
| PHP errors | `logs/php_errors.log` |
| Rate limit data | `logs/ratelimit/*.lock` |
| Admin lockout files | `logs/ratelimit/lockout_*.lock` |

---

## Summary: Attack Vectors vs Mitigations

| Attack | Mitigated by |
|--------|-------------|
| SQL injection | PDO prepared statements everywhere |
| XSS | `htmlspecialchars()` on all output + CSP header |
| CSRF | Token on every POST, validated with `hash_equals()` |
| Session hijack | httponly+secure cookies, strict mode, regeneration |
| Brute force | 5 attempts / 15 min per IP+username |
| File upload RCE | MIME check + extension allowlist + random name + uploads PHP blocked |
| Clickjacking | `X-Frame-Options: SAMEORIGIN` |
| Info disclosure | ServerSignature Off, X-Powered-By removed, CSP, no directory listing |
| Directory traversal | Input validation + .htaccess file access blocks |
| HTTP method abuse | TRACE/TRACK rejected |
| Account lockout bypass | Rate key = IP+username (not just IP) |
| Session fixation | `session_regenerate_id()` on login |
| Timing attacks | `hash_equals()` for CSRF comparison |
| Malicious requests | .htaccess blocks common attack payloads in query strings |
