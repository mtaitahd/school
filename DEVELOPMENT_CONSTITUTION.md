# Development Constitution — Kona Ya Hisabati

> **Status:** Binding — All development must comply
> **Version:** 1.0
> **Scope:** Every developer, every AI, every code change
> **Enforcement:** Check these rules BEFORE generating any code

---

## Table of Contents

1. [Architecture Rules](#1-architecture-rules)
2. [Coding Standards](#2-coding-standards)
3. [UI/UX Rules](#3-uiux-rules)
4. [Curriculum Rules](#4-curriculum-rules)
5. [Lesson Rules](#5-lesson-rules)
6. [Activity Rules](#6-activity-rules)
7. [Engine Rules](#7-engine-rules)
8. [Database Rules](#8-database-rules)
9. [Asset Rules](#9-asset-rules)
10. [Performance Rules](#10-performance-rules)
11. [Accessibility Rules](#11-accessibility-rules)
12. [Mobile-First Rules](#12-mobile-first-rules)
13. [Expansion Rules](#13-expansion-rules)
14. [Quality Assurance Rules](#14-quality-assurance-rules)

---

## 1. Architecture Rules

### 1.1 File Structure

The project follows this exact directory layout. Every file must live in its designated location.

```
school/                          # Web root
├── index.php                    # Home page (entry point)
├── login.php                    # Teacher/Parent login
├── register.php                 # Registration
├── logout.php                   # Logout
├── .env                         # Environment variables (never committed)
├── .htaccess                    # Apache security rules
│
├── php/                         # Server-side logic
│   ├── db_connection.php        # Database singleton class
│   ├── init_db.php              # Database initialization script
│   ├── sms_service.php          # SMS service class
│   ├── claim_code_generator.php # Claim code generation
│   └── includes/                # Shared PHP modules
│       ├── session.php          # Session management functions
│       ├── auth.php             # Authentication functions
│       ├── csrf.php             # CSRF protection functions
│       ├── security.php         # Headers, rate limiting, error handling
│       ├── validator.php        # Validator static class
│       ├── lang.php             # Bilingual string functions
│       ├── helpers.php          # General helper functions
│       ├── url_helpers.php      # URL generation helpers
│       ├── subscription.php     # Subscription functions
│       ├── settings.php         # DB-backed settings
│       ├── migrate.php          # Auto-migration functions
│       ├── dashboard-start.php  # Dashboard layout opener
│       ├── dashboard-end.php    # Dashboard layout closer
│       ├── auth-split-start.php # Auth layout opener
│       ├── auth-split-end.php   # Auth layout closer
│       ├── activity-topbar.php  # Activity navigation bar
│       ├── header.php           # Public header
│       └── SubscriptionMiddleware.php  # Subscription class
│
├── js/                          # Client-side logic
│   ├── main.js                  # Global utilities, audio, navigation
│   ├── activity-runner.js       # Activity bootstrap + legacy quiz
│   ├── auth-ui.js               # Auth page UI helpers
│   ├── customizer.js            # Font/theme switcher
│   ├── dashboard.js             # Sidebar interaction
│   ├── modals.js                # Custom modal management
│   └── activities/              # Activity engine system
│       ├── core.js              # ActivityCore shared utilities
│       ├── engines.js           # ActivityEngines (12 engines)
│       └── registry.js          # ActivityRegistry (key→engine mapping)
│
├── css/                         # Stylesheets
│   ├── style.css                # Global design system (7,464 lines)
│   └── activities.css           # Activity-specific components (882 lines)
│
├── learner/                     # Learner-facing pages
│   ├── login.php                # Username-only login
│   ├── dashboard.php            # Learner home
│   ├── categories.php           # Module selection
│   ├── activities.php           # Activity listing per module
│   ├── activity.php             # Activity execution page
│   ├── finish.php               # Activity completion
│   └── profile.php              # Learner profile
│
├── teacher/                     # Teacher-facing pages
│   ├── dashboard.php            # Teacher home
│   ├── learners.php             # Learner management
│   ├── progress.php             # Progress tracking
│   ├── reports.php              # Reports
│   ├── classes.php              # Class management
│   └── ...                      # (16 files total)
│
├── parent/                      # Parent-facing pages
│   ├── dashboard.php            # Parent home
│   ├── claim-child.php          # Claim child by code
│   ├── add-child.php            # Add child
│   ├── guide.php                # Home learning guide
│   └── ...                      # (11 files total)
│
├── admin/                       # Admin-facing pages
│   ├── dashboard.php            # Admin home
│   ├── users.php                # User management
│   ├── modules.php              # Module management
│   ├── activities.php           # Activity management
│   ├── upload-content.php       # Content upload
│   └── ...                      # (22 files total)
│
├── assets/                      # Static resources
│   ├── images/                  # Image files
│   ├── audio/                   # Audio files
│   ├── uploads/                 # User-uploaded files (.htaccess blocks PHP)
│   └── vendor/                  # Third-party libraries
│       └── jquery/              # jQuery (for Bootstrap dependency only)
│
├── api/                         # JSON API endpoints
│   └── save-progress.php        # Activity progress saving
│
├── database/                    # Schema files
│   ├── schema.sql               # Current full schema
│   └── migrations_v*.sql        # Versioned migrations
│
├── logs/                        # Runtime logs (gitignored)
│   └── ratelimit/               # Rate limit lock files
│
├── Documents/                   # Design documents
│
└── docs/                        # Architecture documents (optional future)
```

### 1.2 Architectural Principles

1. **Hybrid PHP architecture.** Use procedural functions for page controllers (dashboards, forms). Use OOP classes for services (Database, SmsService, Validator, SubscriptionMiddleware). No PHP frameworks. No Composer.

2. **Database is the backbone.** All state lives in MySQL. No file-based storage for user data.

3. **JSON data flow.** PHP renders page-level config into `window.ACTIVITY_CONFIG` via `json_encode()`. The JS engine system reads this config and renders the UI. For saving, JS POSTs JSON to `api/*.php` endpoints.

4. **No autoloader.** Use `require_once __DIR__ . '/../php/includes/file.php'` for all includes. Paths are relative from `__DIR__`.

5. **Session-based auth.** No JWT, no OAuth. Sessions managed by `sec_session_start()` and `auth_*` functions.

6. **CSRF on every POST.** Every form and AJAX POST must include a CSRF token validated by `csrf_require()` or `csrf_verify()`.

7. **Rate limiting on sensitive endpoints.** Login: 5 attempts per 15 minutes. General: 120 requests per 60 seconds.

8. **No hardcoded secrets.** All API keys, tokens, and DB credentials go in `.env` file, loaded via `sec_env()`.

9. **New tables use `{entity}_id` primary keys.** The bare `id` pattern is deprecated. All new tables must use `{entity}_id`.

10. **Migration pattern.** Use versioned SQL files (`database/migrations_v{N}.sql`) with PHP runner scripts. The auto-migration in `migrate.php` uses `SHOW COLUMNS` checks and `static $done` guards.

---

## 2. Coding Standards

### 2.1 PHP Standards

| Rule | Standard | Example |
|------|----------|---------|
| **Style** | Hybrid procedural + OOP. Page controllers are procedural. Services are OOP classes. | `procedural_function()` vs `$service->method()` |
| **Indentation** | 4 spaces. No tabs. | |
| **Braces** | K&R style (opening brace on same line) | `function foo(): void {` |
| **Naming — functions** | `module_prefix_snake_case()` | `auth_login()`, `sec_session_start()`, `csrf_require()` |
| **Naming — function prefixes** | Use a 2-5 letter module prefix | `auth_`, `sec_`, `csrf_`, `sub_`, `json_`, `app_` |
| **Naming — classes** | PascalCase | `Database`, `Validator`, `SmsService` |
| **Naming — class methods** | camelCase (static or instance) | `$database->fetchOne()`, `Validator::email()` |
| **Naming — local variables** | snake_case | `$user_id`, `$first_name`, `$activity_id` |
| **Naming — session keys** | snake_case | `$_SESSION['user_id']`, `$_SESSION['role']` |
| **Naming — session private** | UPPERCASE with underscore prefix | `$_SESSION['_CREATED']`, `$_SESSION['_csrf_token']` |
| **Naming — POST/GET params** | snake_case | `$_POST['first_name']`, `$_GET['module_id']` |
| **Naming — constants** | UPPER_SNAKE_CASE | `RATE_LIMIT_DIR`, `LOGIN_MAX_ATTEMPTS` |
| **Naming — JSON keys** | snake_case | `['ok' => true, 'message' => '...']` |
| **Type declarations** | Use `: void`, `: bool`, `: string`, `: ?array` on all new functions | |
| **String quoting** | Single quotes for short strings. Double quotes only for interpolation. | `'Hello'`, `"Hello $name"` |
| **Default values** | Use null coalescing: `$_POST['action'] ?? ''` | |
| **Type casting** | Explicit (int) for numeric input | `(int) $_POST['user_id']` |

### 2.2 PHP Include Order

Every PHP file must include dependencies in this exact order:

**Page files (dashboards, forms):**
```php
<?php
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/session.php';    // if needed
require_once __DIR__ . '/../php/includes/auth.php';       // if auth needed
require_once __DIR__ . '/../php/includes/csrf.php';       // if form handling
require_once __DIR__ . '/../php/includes/lang.php';       // if bilingual
require_once __DIR__ . '/../php/includes/helpers.php';    // if helpers needed
// ... page-specific includes ...
```

**API endpoints:**
```php
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}
// ... endpoint logic ...
```

**Action handlers (POST redirects):**
```php
<?php
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    // ... process ...
    header('Location: ...');
    exit;
}
header('Location: ...');
exit;
```

### 2.3 PHP Error Handling

1. **Guard clauses first.** Validate input and exit early with 400/redirect.
2. **JSON API endpoints** must use the envelope pattern: `['ok' => true/false, 'message' => '...']`.
3. **Non-critical failures** (SMS, optional features) log the error and continue. They never block the main operation.
4. **CRITICAL failures** (DB connection, auth failure) must fail loudly via `RuntimeException` or immediate redirect.
5. **No `try-catch` around individual queries.** Let `PDO::ERRMODE_EXCEPTION` propagate to the global handlers.
6. **Use `try-catch` only at service boundaries** (API calls, file operations, third-party services).

### 2.4 JavaScript Standards

| Rule | Standard | Example |
|------|----------|---------|
| **Style** | Vanilla JS only. No jQuery in custom code. No ES modules, no bundlers. | |
| **Naming — functions** | camelCase | `playAudio()`, `showStarAnimation()`, `initNavbar()` |
| **Naming — variables** | camelCase | `currentQuestion`, `totalQuestions` |
| **Naming — constants** | UPPER_SNAKE_CASE | `ACTIVITY_CONFIG`, `OBJECT_EMOJIS` |
| **Naming — namespace objects** | PascalCase | `ActivityCore`, `ActivityEngines`, `ActivityRegistry` |
| **Naming — localStorage keys** | kebab-case with prefix | `kona-dashboard-sidebar-collapsed` |
| **Encapsulation** | Use IIFE for page-specific scripts | `(function () { ... })()` |
| **DOM ready** | Use `DOMContentLoaded` event listener | `document.addEventListener('DOMContentLoaded', function () { ... })` |
| **Event binding** | Use `addEventListener()`. No inline `onclick` in JS (inline HTML `onclick` is allowed for simple navigation). | |
| **Config passing** | PHP writes to `window.ACTIVITY_CONFIG` | `window.ACTIVITY_CONFIG = <?php echo json_encode($data); ?>` |
| **No `var`** | Use `const` and `let`. Never `var`. | |

### 2.5 HTML Standards

1. **Indentation:** 4 spaces. Indent all nested elements.
2. **Use semantic HTML5:** `<main>`, `<nav>`, `<article>`, `<section>`, `<header>`, `<footer>`.
3. **Always set `<html lang="...">`** based on the current language.
4. **All inline styles are forbidden.** Use CSS classes exclusively.
5. **Use `role` attributes** on interactive elements: `role="button"`, `role="toolbar"`, `role="dialog"`.
6. **Use `aria-hidden="true"`** on all decorative icons (`<i class="fas fa-star" aria-hidden="true"></i>`).
7. **Use `aria-label`** on all icon-only buttons.

### 2.6 SQL Standards

1. **SQL keywords in UPPERCASE:** `SELECT`, `FROM`, `WHERE`, `JOIN`, `INSERT`, `UPDATE`, `DELETE`.
2. **Always use prepared statements** with `?` placeholders through the `$database` wrapper.
3. **Never concatenate values into SQL strings.**
4. **Multi-line queries** indent with SQL aligned:
   ```php
   $sql = "SELECT u.*, p.score
           FROM users u
           JOIN progress p ON u.user_id = p.user_id
           WHERE u.role = 'learner'
           ORDER BY u.last_name ASC";
   ```
5. **Dynamic IN clauses** use `array_fill()`:
   ```php
   $placeholders = implode(',', array_fill(0, count($ids), '?'));
   ```
6. **Use `$database->fetchOne()` for single row**, `$database->fetchAll()` for multiple rows, `$database->insert()` for inserts, `$database->execute()` for updates/deletes.

### 2.7 File Headers

Every new PHP file must start with:
```php
<?php
/**
 * Kona Ya Hisabati — {Brief description of what this file does}
 */
```

Existing files without headers may be left as-is unless modified. All NEW files must include this header.

---

## 3. UI/UX Rules

### 3.1 Design System

**The design system is defined entirely in `css/style.css` CSS variables.** Never hardcode colors, radii, shadows, or fonts.

```css
:root {
    --primary-blue: #4A90E2;
    --primary-blue-dark: #357ABD;
    --primary-yellow: #FFD700;
    --primary-yellow-dark: #FFC107;
    --primary-green: #50C878;
    --primary-green-dark: #3CB371;
    --primary-red: #FF6B6B;
    --primary-red-dark: #EE5A5A;
    --primary-purple: #9B59B6;
    --primary-purple-dark: #8E44AD;
    --primary-orange: #FF8C00;
    --primary-orange-dark: #FF7F00;
    --text-dark: #333333;
    --text-light: #666666;
    --background-light: #f5f5f5;
    --background-white: #ffffff;
    --navbar-dark: #0b2d89;
    --navbar-glow: rgba(255, 215, 0, 0.3);
    --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.1);
    --shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
    --border-radius-lg: 20px;
    --border-radius-md: 15px;
    --border-radius-sm: 10px;
    --dashboard-sidebar-width: 260px;
    --dashboard-sidebar-collapsed-width: 72px;
    --dashboard-topbar-height: 64px;
    --kyh-font-family: Poppins, sans-serif;
}
```

### 3.2 Button System

| Class | Purpose | Min-Height | Font |
|-------|---------|------------|------|
| `.btn-child` | Base child-friendly button | 70px | Poppins, uppercase, weight 700 |
| `.btn-child-large` | Larger action button | 90px | Same |
| `.btn-child-primary` | Blue button (primary action) | 70px | Same |
| `.btn-child-yellow` | Yellow button (reward/celebration) | 70px | Same |
| `.btn-child-green` | Green button (success/next) | 70px | Same |
| `.btn-child-red` | Red button (stop/delete) | 70px | Same |
| `.btn-child-purple` | Purple button (special) | 70px | Same |
| `.btn-child-orange` | Orange button (highlight) | 70px | Same |
| `.btn-child-secondary` | White/blue outline (secondary action) | 70px | Same |
| `.btn-child-small` | Small variant | 50px | Same, weight 600 |
| `.topbar-btn` | Activity top navigation | 57px | Icon only |
| `.audio-btn` | Circular audio play | 50x50px | Icon only |

**Hover:** All buttons lift with `translateY(-3px)` and increased shadow `var(--shadow-hover)`.
**Active/Click:** Scale bounce via `btnBounce` class.
**Disabled:** Greyed out, no hover effects.

### 3.3 Card System

| Class | Purpose | Min-Height |
|-------|---------|------------|
| `.module-card` | Learning module selection | 250-280px |
| `.dashboard-card` | Dashboard stat/data card | Variable |
| `.benefit-card` | Feature/benefit showcase | 300px |

All cards share: gradient background, `::before` floating radial gradient, `::after` glow on hover, `cardFadeIn` entrance animation, centered layout.

### 3.4 Typography

| Element | Font | Weight | Size |
|---------|------|--------|------|
| Headings (h1) | Poppins | 700-800 | clamp(2rem, 5vw, 3rem) |
| Headings (h2) | Poppins | 700 | clamp(1.5rem, 3vw, 2.2rem) |
| Activity title | Poppins | 800 | 3rem (mobile: 2rem) |
| Activity prompt | Poppins | 600 | 1.25rem |
| Body text | Nunito | 400 | 1rem |
| Button text | Poppins | 700 | 1rem, uppercase |
| Module subtitle | Nunito | 400 | clamp(1.1rem, 2.5vw, 1.35rem) |

### 3.5 Layout

1. **Learner-facing pages** use the `.container-child` / `.row-child` / `.col-child-{1-4}` custom grid system.
2. **Dashboard pages** (admin, teacher, parent) use Bootstrap 5 grid (`row`, `col-*`, `g-4`).
3. **Auth pages** use the `.auth-split-page` layout.
4. **Activity pages** use the fixed template defined in `learner/activity.php`.

### 3.6 Activity Page Template

Every activity page must follow this exact structure:

```
<body class="page-child">
  <header> (conditional)
  <main class="container-child mt-30 page-enter">
    <div class="activity-container">
      TOPBAR:     .activity-topbar (Home | Back | Audio)
      HEADER:     .activity-header (title, instruction)
      PROGRESS:   .progress-bar-child (conditional)
      DISPLAY:    #activityDisplay
      OPTIONS:    #answerOptions
      SCORE:      #scoreSection (conditional)
      FOOTER:     #nextActivityBar (hidden until needed)
    </div>
  </main>
  ACCESSIBILITY: .a11y-toolbar
  AUDIO:         <audio id="audioPlayer">
  SCRIPTS:       Bootstrap → main.js → core.js → engines.js → registry.js → activity-runner.js
</body>
```

### 3.7 State Classes

| Class | Purpose |
|-------|---------|
| `.correct` | Green highlight for correct answer |
| `.incorrect` | Red highlight + shake for incorrect answer |
| `.tapped` | Mark an item as tapped/counted |
| `.filled` | Mark a slot as occupied |
| `.selected-correct` | Correct selection highlight |
| `.selected-wrong` | Wrong selection highlight |
| `.hint-flash` | Green ring pulse hint |
| `.highlight-remaining` | Yellow highlight for remaining items |
| `.removed` | Faded/gone for removed items |
| `.dragging` | Active drag state |
| `.in-basket` | Item placed in basket |
| `.sorted` | Item has been sorted |
| `.high-contrast` | Body class — high contrast mode |
| `.dyslexia-mode` | Body class — dyslexia friendly mode |
| `.btn-bounce` | Click bounce animation |
| `.page-enter` | Fade-in-up entrance animation |

### 3.8 UX Principles

1. **Every interaction has feedback.** Visual + audio. Correct = positive. Incorrect = gentle + hint.
2. **No dead ends.** Every screen has a "Continue", "Back", or "Home" option.
3. **Audio-first.** Primary navigation and all activity instructions are spoken via Web Speech API.
4. **Minimal text.** Use icons + audio + large text. Young learners (3-5) should not need to read.
5. **Generous tap targets.** Minimum 70x70px for child buttons. Minimum 57x57px for topbar.
6. **No scrolling during activities.** The entire activity must fit within the viewport without scrolling.
7. **Immediate celebration.** Correct answers trigger star animation + confetti within 200ms.
8. **Error is never punishment.** Incorrect answers use "Try again!", "Almost!", never "Wrong!".
9. **Consistent navigation.** Topbar with Home, Back, and Audio Repeat on every activity page.
10. **Bilingual by default.** Every page supports `?lang=en` and `?lang=sw`. All user-facing strings use the `$t[]` array or inline ternary.

---

## 4. Curriculum Rules

### 4.1 Content Hierarchy Compliance

All content must conform to the 8-level hierarchy defined in the Master Content Architecture:

```
Level 1: DOMAIN      (e.g., Mathematics)
Level 2: STRAND      (e.g., Number & Operations)
Level 3: TOPIC       (e.g., Numbers 1-10)
Level 4: LESSON      (e.g., Count Objects to 5)
Level 5: ACTIVITY    (e.g., Tap each apple as you count)
Level 6: CHALLENGE   (e.g., Count to 15 with mixed objects)
Level 7: ASSESSMENT  (e.g., Quick Check, Lesson Check)
Level 8: REWARD      (e.g., Stars, Badges, Certificates)
```

**Rules:**
1. No content exists outside this hierarchy.
2. Every item references its parent via foreign key.
3. Order is explicit via `order_index` at every level.
4. Prerequisites are declared at the Topic and Lesson levels.

### 4.2 Content Coding Convention

Every content item has a unique code:

| Level | Code Pattern | Example |
|-------|-------------|---------|
| Domain | `{abbreviation}` | `MATH` |
| Strand | `{DOM}-{abbreviation}` | `MATH-NUM` |
| Topic | `{STR}-{number}{letter}` | `MATH-NUM-01A` |
| Lesson | `{TOP}-L{two-digit}` | `MATH-NUM-01A-L02` |
| Activity | `{LESSON}-A{two-digit}` | `MATH-NUM-01A-L02-A03` |

### 4.3 Curriculum Expansion

1. New domains must be proposed and approved before any content is created.
2. New strands must fit within an existing domain.
3. New topics must fit within an existing strand and list their prerequisites.
4. No topic can be inserted between existing topics without updating the order_index of all following topics.

---

## 5. Lesson Rules

### 5.1 The 10-Step Lesson Blueprint

Every lesson MUST follow this exact sequence:

```
Step 1:  LESSON INTRODUCTION        (1 min)
Step 2:  WARM-UP PRACTICE           (2 min)
Step 3:  I DO (Teacher Demonstrate) (2 min)
Step 4:  WE DO (Guided Practice)    (3 min)
Step 5:  YOU DO (Independent)       (3 min)
Step 6:  CHECK FOR UNDERSTANDING    (2 min)
Step 7:  INTERACTIVE GAME           (3 min)
Step 8:  QUICK ASSESSMENT           (2 min)
Step 9:  REWARD & CELEBRATION       (1 min)
Step 10: REVISION & NEXT STEPS      (1 min)
         TOTAL: ~20 minutes per lesson
```

**Rules:**
1. No steps may be reordered or skipped.
2. Steps 3-5 (I Do → We Do → You Do) represent gradual release of responsibility. Must never be violated.
3. Step 6 (Check for Understanding) is a branching point. If the child answers incorrectly, the lesson MUST branch to Step 4 remediation.
4. Step 8 (Quick Assessment) determines reward and revision path. Non-optional.
5. Each step maps to one of the 12 activity blueprints.

### 5.2 Lesson Properties

Every lesson in the database must have:
- `lesson_id` — unique identifier
- `topic_id` — foreign key to parent topic
- `lesson_number` — order within topic (1-based)
- `lesson_name` — display name
- `learning_objective` — one precise sentence: "By the end of this lesson, the child can {skill}."
- `success_criteria` — what the child can do after: "Child can {measurable outcome}."
- `estimated_minutes` — expected duration
- `prerequisite_lesson_ids` — JSON array of lesson IDs that must be completed first

### 5.3 Lesson Flow Implementation

In the database, each lesson references its activities via `activities.lesson_id`. The `activities.order_index` within a lesson determines the blueprint sequence:

| order_index | Blueprint | Lesson Step |
|-------------|-----------|-------------|
| 0 | INTRO | Lesson Introduction |
| 1 | TAP | Warm-Up Practice |
| 2 | INTRO (demo) | I Do |
| 3 | TAP/MULTI-TAP (guided) | We Do |
| 4 | TAP/MULTI-TAP/DRAG/MATCH | You Do |
| 5 | MATCH/COMPLETE | Check for Understanding |
| 6 | GAME | Interactive Game |
| 7 | QUIZ | Quick Assessment |
| 8 | REWARD_SCREEN | Reward & Celebration |
| 9 | NEXT_STEPS | Revision & Next Steps |

---

## 6. Activity Rules

### 6.1 The 12 Blueprints

Every interactive task must conform to exactly one of these 12 blueprints:

| # | Blueprint | Interaction | Purpose |
|---|-----------|-------------|---------|
| 1 | INTRO | Passive viewing | Present new information |
| 2 | TAP | Single tap | Select correct answer |
| 3 | MULTI-TAP | Sequential taps | Count or sequence |
| 4 | DRAG | Drag and drop | Place items in targets |
| 5 | TRACE | Finger tracing | Follow a guided path |
| 6 | WRITE | Free drawing | Produce symbol from memory |
| 7 | MATCH | Pair matching | Connect related items |
| 8 | ORDER | Sequence ordering | Arrange by rule |
| 9 | SORT | Categorization | Group by attribute |
| 10 | COMPLETE | Fill missing | Complete pattern/sequence |
| 11 | GAME | Playful practice | Reinforce through play |
| 12 | QUIZ | Formal assessment | Measure mastery |

**Rules:**
1. No activity may use a custom interaction mode outside these 12.
2. Each blueprint has exactly 10 specification fields (see MCA Section 3).
3. Every activity stores its blueprint type in `activity_type` column.

### 6.2 Activity Properties

Every activity in the database must have:
- `activity_id` — unique identifier
- `lesson_id` — foreign key to parent lesson
- `activity_number` — order within lesson
- `activity_name` — display name
- `activity_type` — one of the 12 blueprints
- `difficulty_level` — 1-6 (see MCA Section 4)
- `activity_data` — JSON object with engine-specific parameters
- `audio_instruction` — text spoken to introduce the activity
- `order_index` — position within lesson (maps to lesson step)
- `passing_score` — minimum score to pass (0-100)
- `max_attempts` — maximum retries (default 3)

### 6.3 activity_data JSON Convention

The `activity_data` JSON must follow this structure:

```json
{
    "engine": "tap_engine",
    "difficulty": 2,
    "prompt": "Find the number 5",
    "choices": [
        {"label": "3", "value": 3},
        {"label": "5", "value": 5},
        {"label": "7", "value": 7}
    ],
    "correctValue": 5,
    "subject": "numbers"
}
```

**Common keys across all engines:**
- `engine` — which engine to use (lowercase, from registry)
- `difficulty` — 1-6
- `subject` — lowercase subject name

**Engine-specific keys** are documented in each engine's specification.

### 6.4 Difficulty Rules

| Level | Name | Numbers | Choices | Hints |
|-------|------|---------|---------|-------|
| 1 | Explore | 1-3 | 2 | Full |
| 2 | Identify | 1-5 | 2-4 | Audio prompt |
| 3 | Match | 1-5 | Related pairs | Visual cue (first pair) |
| 4 | Order | 1-10 | Out-of-order | Highlight first item |
| 5 | Apply | 1-20 | Plausible | Minimal |
| 6 | Create | 1-20+ | None | None |

### 6.5 Feedback Rules

1. **Success:** Green highlight + star animation + audio praise within 200ms. Audio from `ActivityCore.ENCOURAGEMENTS`.
2. **Error:** Red highlight for 600ms + audio: "Try again" or "Almost!" + hint after 2 attempts.
3. **On last retry exhaust:** Show correct answer with explanation. Move to next item. Do NOT let the child get stuck.
4. **No negative language.** Never use "Wrong!", "Incorrect!", "Bad!". Use "Good try!", "Almost!", "Let's try again!".
5. **Audio feedback must be unique per answer type** — different sounds for success, error, completion, and encouragement.

---

## 7. Engine Rules

### 7.1 The 12 Engines

The system has exactly 12 reusable engines. Every activity uses one of these.

| # | Engine | Blueprints | Input Driven By |
|---|--------|-----------|-----------------|
| 1 | TAP_ENGINE | TAP | `prompt`, `choices`, `correctValue` |
| 2 | MULTI_TAP_ENGINE | MULTI-TAP | `items`, `mode`, `targetCount` |
| 3 | DRAG_ENGINE | DRAG, SORT, ORDER, MATCH | `items`, `zones`, `mode` |
| 4 | TRACE_ENGINE | TRACE | `templateType`, `templateValue` |
| 5 | WRITE_ENGINE | WRITE | `expectedType`, `expectedValue` |
| 6 | MATCH_ENGINE | MATCH, COMPLETE | `pairs`, `mode` |
| 7 | ORDER_ENGINE | ORDER | `items`, `sequenceRule` |
| 8 | SORT_ENGINE | SORT | `items`, `categories`, `mode` |
| 9 | COMPLETE_ENGINE | COMPLETE | `pattern`, `gaps`, `choices` |
| 10 | GAME_ENGINE | GAME | `gameType`, `contentConfig` |
| 11 | OPS_ENGINE | TAP, DRAG (with ops) | `operation`, `operandA`, `operandB` |
| 12 | QUIZ_ENGINE | QUIZ | `questions`, `shuffle` |

### 7.2 Engine Implementation Rules

1. **Every engine is a method on the `ActivityEngines` object** in `engines.js`.
2. **Every engine receives a single `config` parameter** — the `activity_data` JSON from the database.
3. **Every engine uses `ActivityCore` for all DOM manipulation** — never direct DOM access.
4. **Every engine must use these exact DOM hooks:**
   - `ActivityCore.getDisplay()` → `#activityDisplay`
   - `ActivityCore.getOptions()` → `#answerOptions`
   - `ActivityCore.clearStage()` → clears both
   - `ActivityCore.renderPrompt()` → creates `.activity-prompt` element
   - `ActivityCore.renderMC()` → creates `.answer-btn` buttons in options area
5. **Every engine must call `ActivityCore.finishActivity()`** on successful completion to trigger celebration + progress save.
6. **Every engine must call `ActivityCore.say()`** for audio instructions.
7. **Every engine must call `ActivityCore.bindTopbarAudio()`** to link the topbar repeat button.

### 7.3 Engine Registration Rules

1. Every engine key must be registered in `ActivityRegistry` in `registry.js`.
2. The registry maps string keys to engine functions: `mango_counting: (c) => ActivityEngines.mango_counting(c)`.
3. Activity keys in the registry can map to the same engine with different parameter defaults (e.g., `shape_sorting` maps to `identify_shapes` with `sort_by_size: true`).
4. The `resolveEngine()` function reads `config.engine` first, then falls back to `activityType`.

### 7.4 Engine Configuration

Interact with the engines ONLY through the `config` object passed in `activity_data`. Example for TAP_ENGINE:

```json
{
    "engine": "tap",
    "prompt": "Find the number 5",
    "choices": [
        {"label": "3", "value": 3, "emoji": null},
        {"label": "5", "value": 5, "emoji": null},
        {"label": "7", "value": 7, "emoji": null}
    ],
    "correctValue": 5,
    "difficulty": 2
}
```

---

## 8. Database Rules

### 8.1 Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Database name | lowercase_snake_case | `kona_hisabati` |
| Table names | PLURAL snake_case | `users`, `activities`, `progress` (exception) |
| Primary keys | `{entity}_id` (NOT bare `id`) | `user_id`, `activity_id` |
| Foreign keys | `{referenced_entity}_id` | `module_id`, `user_id` |
| Boolean columns | `is_{adjective}` | `is_active`, `is_read` |
| Timestamp columns | `{event}_at` | `created_at`, `completed_at` |
| Indexes | `idx_{column}` or `idx_{table}_{columns}` | `idx_user`, `idx_notifications_user_read` |
| Unique constraints | `unique_{table}_{columns}` | `unique_user_activity` |
| ENUM values | lowercase_snake_case | `'pending', 'in_progress', 'completed'` |

### 8.2 Table Design Rules

1. **Every table must have:** `{entity}_id INT AUTO_INCREMENT PRIMARY KEY`, `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`.
2. **Mutable tables also need:** `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`.
3. **All tables use InnoDB** engine and `utf8mb4_unicode_ci` collation.
4. **All foreign keys must be indexed.** Create an index for every FK column.
5. **Foreign key actions:** Use `ON DELETE CASCADE` for dependent child records. Use `ON DELETE SET NULL` for optional references.
6. **Use ENUM for status/type fields** with a fixed set of allowed values. Extend via ALTER TABLE in migrations.
7. **Use TINYINT(1) for booleans.**
8. **Use DECIMAL(5,2) for scores.** Use DECIMAL(10,2) for monetary amounts.
9. **Use JSON columns sparingly.** Only for semi-structured config data that varies per row (e.g., `activity_data`).
10. **No soft deletes.** Use hard deletes with CASCADE. If a "deleted" state is needed, use `is_active = 0`.

### 8.3 Migration Rules

1. **Versioned migration files:** `database/migrations_v{N}_{description}.sql`
2. **Each migration has a PHP runner:** `database/run_migration_v{N}.php`
3. **Migrations must be idempotent.** Use `CREATE TABLE IF NOT EXISTS` and check columns before ALTER.
4. **The auto-migration function** `ensure_schema_v2()` in `migrate.php` uses `static $done` guard and `SHOW COLUMNS` for column checks.
5. **ENUM extensions** use `ALTER TABLE ... MODIFY COLUMN ... ENUM(...)` after checking current values.

### 8.4 Query Rules

1. **Always use `$database` wrapper.** Never use raw PDO.
2. **Always use prepared statements with `?` placeholders.** Never concatenate values.
3. **Always cast IDs to int before querying:** `$database->fetchOne("SELECT ...", [(int) $id])`.
4. **Use LOWER() for case-insensitive lookups:** `WHERE LOWER(username) = LOWER(?)`.
5. **SQL keywords in UPPERCASE.**
6. **Limit and paginate all list queries.**

---

## 9. Asset Rules

### 9.1 Naming Convention

All asset filenames follow this exact pattern:

```
{language}_{subject}_{strandCode}_{topicCode}_{lessonCode}_{activityCode}_{type}_{variant}.{ext}
```

**Example:** `en_number_NUM-01_1A-L02-A03_img_circle.svg`

### 9.2 Asset Organization

```
assets/
├── en/                          # English audio assets
├── sw/                          # Swahili audio assets
├── shared/                      # Language-independent
│   ├── images/
│   │   ├── backgrounds/
│   │   ├── characters/
│   │   └── rewards/
│   ├── audio/
│   │   ├── sfx/
│   │   └── bgm/
│   └── animations/
└── rewards/
    ├── stars/
    ├── badges/
    └── certificates/
```

### 9.3 Format Rules

| Asset Type | Preferred Format | Fallback | Max Size |
|-----------|-----------------|----------|----------|
| Illustration | SVG | PNG/WebP | 200KB |
| Background | SVG | WebP | 300KB |
| Audio Instruction | MP3 (128kbps) | OGG | 500KB |
| Sound Effect | MP3 (192kbps) | WAV | 100KB |
| Background Music | MP3 (128kbps) | OGG | 1MB |
| Animation | Lottie JSON | CSS keyframes | 100KB |
| Photo | WebP | PNG | 500KB |
| Reward Icon | SVG | PNG | 50KB |
| Certificate | HTML+CSS | PDF | 200KB |

### 9.4 Asset Rules

1. **Shared assets are never duplicated.** If the same image is used in multiple lessons, it goes in `shared/images/`.
2. **Each language gets its own audio.** EN audio in `assets/en/`, SW audio in `assets/sw/`. Visual assets are shared.
3. **All uploaded files get random names.** `bin2hex(random_bytes(16))`. Never use user-provided filenames.
4. **Uploaded files are validated by MIME type** using `finfo::file()` (magic bytes), not `$_FILES['type']`.
5. **Upload directory has `.htaccess` blocking PHP execution.** `php_flag engine off`.
6. **Allowed upload types:** `.jpg`, `.jpeg`, `.png`, `.gif`, `.pdf`, `.doc`, `.docx`.
7. **Max upload size:** 5MB for content, 2MB for profile images.
8. **Audio format:** All instructional audio must use the Web Speech API (TTS). Pre-recorded audio files are only for sound effects, background music, and songs.

---

## 10. Performance Rules

### 10.1 Frontend Performance

1. **All scripts load synchronously** in order. No `async` or `defer` on dependencies. `defer` allowed only on independent scripts.
2. **CSS at the top** (in `<head>`), **JS at the bottom** (before `</body>`).
3. **Minify HTML** by removing unnecessary whitespace in production.
4. **Lazy-load images** with `loading="lazy"`.
5. **No unused CSS.** Every selector in `style.css` and `activities.css` must be used.
6. **Animations use CSS transforms and opacity only.** Never animate `width`, `height`, `top`, `left` (triggers layout).
7. **Reduce animation on `prefers-reduced-motion`.** Add `@media (prefers-reduced-motion: reduce)` that disables all non-essential animations.
8. **All SVG/PNG assets should be optimized.** Use SVGO for SVGs, TinyPNG for PNGs.

### 10.2 Backend Performance

1. **Database queries are the bottleneck.** Minimize queries per page load.
2. **Use `LIMIT` on all list queries.** Never SELECT from a table without limiting.
3. **Index every query column.** If you filter by `WHERE user_id = ?`, ensure `user_id` is indexed.
4. **Cache settings in memory** using `static` variables in functions (e.g., `setting_get()`).
5. **Use `static $done` guard** in migration functions to prevent repeated execution.
6. **No expensive operations in loops.** Move SQL queries outside loops.
7. **Optimize session writes.** Use `session_write_close()` after critical session data is written.
8. **Log errors, not debug info.** `error_log()` only for actual errors.

### 10.3 Database Performance

1. **All queries use indexed columns for WHERE, JOIN, and ORDER BY.**
2. **Composite indexes for multi-column queries:** `(user_id, created_at)` for user timeline queries.
3. **No `SELECT *` in production queries.** Specify columns explicitly.
4. **Use `EXPLAIN SELECT`** on any query that runs in a hot path.
5. **Archive old progress data** periodically (move completed records older than 1 year to archive tables).

---

## 11. Accessibility Rules

### 11.1 Mandatory Requirements

1. **Every `i` tag with an icon must have `aria-hidden="true"`**. Example: `<i class="fas fa-star" aria-hidden="true"></i>`
2. **Every icon-only button must have `aria-label`**. Example: `<button aria-label="Home"><i class="fas fa-home" aria-hidden="true"></i></button>`
3. **Every image must have `alt` text.** Decorative images get `alt=""`. Informative images get descriptive `alt`.
4. **Every clickable card must have `tabindex="0"`, `role="button"`, and Enter key handler.**
   ```html
   <article tabindex="0" role="button" onclick="selectModule(1)" onkeydown="if(event.key==='Enter')selectModule(1)">...</article>
   ```
5. **Every modal must have `role="dialog"` and `aria-labelledby`** pointing to the modal title element.
6. **Escape key must close all modals, dropdowns, and overlays.**
7. **Focus must be trapped inside open modals.** Tab should cycle through modal elements only.
8. **Focus must return to the trigger element when modal closes.**
9. **Skip navigation link** should be the first focusable element on every page.
10. **`<html lang>` must match the current language** (`en` or `sw`).

### 11.2 Color and Contrast

1. **All text must meet WCAG AA contrast ratio (4.5:1 for normal text, 3:1 for large text).**
2. **The `.high-contrast` mode must override all colors** to meet WCAG AAA (7:1) where possible.
3. **Never use color alone to convey information.** Always pair with icon, text, or pattern.
4. **Error states use red + icon + text.** Never red alone.
5. **Success states use green + icon + text + animation.** Never green alone.

### 11.3 Keyboard Navigation

1. **All interactive elements must be reachable via Tab.** No keyboard traps.
2. **`focus-visible` outlines must be visible** on all interactive elements: `outline: 3px solid var(--primary-orange); outline-offset: 3px`.
3. **Arrow keys** for navigation within components (carousels, tab panels, grids).
4. **Enter/Space** to activate buttons and links.
5. **Escape** to close modals, menus, and overlays.

### 11.4 Screen Reader Support

1. **Use `aria-live="polite"`** for dynamic content updates (score changes, feedback messages).
2. **Use `aria-live="assertive"`** for time-sensitive alerts (assessment timer warnings).
3. **Use `aria-describedby`** for additional context on complex elements.
4. **Use `aria-current="page"`** on the current page's navigation link.
5. **Use `aria-expanded`** on expandable elements (accordions, dropdowns).
6. **Use `aria-selected`** on tab panels and selection lists.

### 11.5 Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

This must be in `style.css`. All animations must gracefully degrade.

### 11.6 Accessibility Toolbar

Every page includes the accessibility toolbar (`.a11y-toolbar`):
- **High Contrast toggle** — toggles `body.high-contrast`
- **Dyslexia Mode toggle** — toggles `body.dyslexia-mode` (Comic Sans MS, increased letter-spacing and line-height)

Both modes persist via `localStorage`.

---

## 12. Mobile-First Rules

### 12.1 Breakpoints

The project uses desktop-first breakpoints for backward compatibility with existing pages. All NEW pages must be designed mobile-first using `min-width` breakpoints:

| Breakpoint | Name | Target |
|-----------|------|--------|
| Base | Mobile | All screens |
| `min-width: 576px` | sm | Large phones |
| `min-width: 768px` | md | Tablets |
| `min-width: 992px` | lg | Desktop |
| `min-width: 1200px` | xl | Large desktop |

**Existing CSS breakpoints (for reference when modifying old code):**
- `max-width: 1024px` — navbar logo resize
- `max-width: 992px` — sidebar collapse, grid adjustments
- `max-width: 768px` — primary mobile threshold (16+ occurrences)
- `max-width: 576px` — small phones

### 12.2 Touch Targets

1. **Minimum touch target: 44x44 CSS pixels** (WCAG requirement). Kona uses 70x70 for child buttons.
2. **Minimum spacing between touch targets: 8px.**
3. **No hover-dependent interactions.** All interactions must work on touch devices.
4. **`touch-action: manipulation`** on interactive elements to prevent double-tap zoom.
5. **`user-select: none`** on interactive elements to prevent text selection during rapid tapping.

### 12.3 Layout Rules

1. **Learner pages** use the custom grid: `.col-child-1` (100%) on mobile, `.col-child-2` (50%) on tablet, `.col-child-3` (33.3%) on desktop.
2. **Dashboard pages** use Bootstrap grid with responsive column classes: `col-12 col-md-6 col-lg-4`.
3. **Activity pages must work in portrait mode** on a 7-inch tablet without scrolling.
4. **Sidebar on dashboards** is off-canvas on mobile (slides in from left with backdrop), collapsed icons on desktop.

### 12.4 Font Scaling

All font sizes must use `clamp()` for responsive scaling:

| Element | clamp() Formula |
|---------|-----------------|
| h1 | `clamp(2rem, 5vw, 3rem)` |
| h2 | `clamp(1.5rem, 3vw, 2.2rem)` |
| Activity title | `clamp(1.8rem, 4vw, 3rem)` |
| Body | `clamp(0.9rem, 2.5vw, 1rem)` |
| Button | `clamp(0.85rem, 2vw, 1rem)` |

### 12.5 Responsive Images

1. **Use SVG** for icons and illustrations (scale infinitely).
2. **Use `max-width: 100%` and `height: auto`** on all `img` tags.
3. **Use `srcset`** for raster images when different resolutions are needed.

---

## 13. Expansion Rules

### 13.1 The 3-Gate Exception Process

Any content that cannot fit into the existing framework must pass through 3 gates:

**Gate 1 — Hierarchy Exception:**
Prove the content cannot fit into the 8-level hierarchy. Propose a modification that accommodates all existing content without breaking FK relationships.

**Gate 2 — Blueprint Exception:**
Prove that none of the 12 activity blueprints can support the required interaction. Provide a full spec (10 fields) for the new blueprint. Show at least 3 subjects it would apply to.

**Gate 3 — Engine Exception:**
Prove that the required engine logic is not covered by any existing engine. The new engine must support at least 3 subjects and must not overlap with any existing engine.

### 13.2 Expansion Checklist

Before adding ANY new content, verify every item:

```
□ 1. Hierarchy level assigned (Domain/Strand/Topic/Lesson/Activity)
□ 2. Lesson blueprint followed (10 steps)
□ 3. Activity blueprint assigned (one of 12)
□ 4. Engine assigned from existing 12
□ 5. Difficulty level assigned (1-6)
□ 6. Assessment points defined (QC/LC/TT/SE)
□ 7. Assets created per asset blueprint
□ 8. Reward triggers defined
□ 9. Revision schedule set
□ 10. Naming conventions followed
□ 11. Age range declared
□ 12. Backward compatibility verified
□ 13. Prerequisites defined
□ 14. Both languages supported (EN + SW)
```

### 13.3 Backward Compatibility Rules

1. Adding a new strand must not change the order of existing strands.
2. Adding a new topic must not change the numbering of existing topics.
3. Adding a new lesson must not change the sequence of existing lessons.
4. Asset filenames must never collide. New assets use new codes.
5. Existing progress data must remain valid. Old learners must see the same hierarchy.
6. Database migrations must not drop or rename existing columns.

---

## 14. Quality Assurance Rules

### 14.1 Pre-Commit Checklist

Before committing any code change:

```
□ PHP syntax check: `php -l filename.php`
□ No `var_dump()`, `print_r()`, `console.log()`, or `debugger` statements
□ No hardcoded secrets, passwords, or API keys
□ CSRF token present on all POST forms and AJAX endpoints
□ Rate limiting applied to auth endpoints
□ SQL uses prepared statements (no concatenation)
□ All user-facing strings have bilingual support (EN + SW)
□ Every `i` icon has `aria-hidden="true"`
□ Every icon-only button has `aria-label`
□ Every interactive card has `tabindex`, `role`, and `onkeydown`
□ New activities follow the 12 blueprints
□ New engines are registered in `registry.js`
```

### 14.2 Testing Rules

1. **Test on the 3 target devices:**
   - 7-inch tablet (portrait) — primary learner device
   - 10-inch tablet (landscape) — classroom projection
   - Desktop (1920x1080) — teacher/admin use
2. **Test in both languages** (English and Swahili).
3. **Test with audio on and off.** Ensure the activity works without audio.
4. **Test keyboard-only navigation.** Tab through every interactive element.
5. **Test with high contrast mode** and dyslexia mode enabled.
6. **Test with slow internet.** Simulate 3G to ensure activity loads within 5 seconds.

### 14.3 Browser Support

| Browser | Minimum Version | Must Support |
|---------|----------------|--------------|
| Chrome | 80+ | Web Speech API |
| Firefox | 75+ | Web Speech API |
| Safari | 13+ | Web Speech API |
| Edge | 80+ | Web Speech API |
| Samsung Internet | 12+ | Touch events |

### 14.4 Code Review Rules

Every code change must be checked for:

1. **Architecture compliance** — does it follow the file structure and include patterns?
2. **Coding standards** — naming, indentation, formatting?
3. **UI/UX rules** — correct button/card classes, variables, layout?
4. **Curriculum rules** — hierarchy, lesson blueprint, activity blueprint?
5. **Engine rules** — existing engine used? registered? core.js helpers used?
6. **Database rules** — naming, prepared statements, migration pattern?
7. **Performance** — excessive queries? missing indexes?
8. **Accessibility** — aria labels, keyboard, contrast, screen reader?
9. **Mobile** — touch targets, responsive, no hover dependency?
10. **Expansion** — checklist completed? backward compatible?

### 14.5 Documentation Requirements

1. **Every new engine** must have inline documentation of its config parameters.
2. **Every new database table** must be documented in the schema file.
3. **Every migration** must have a versioned SQL file and a PHP runner.
4. **No inline TODO comments.** Use the external TODO.md for pending tasks.
5. **Architecture documents** (MCA, Constitution, Blueprint) are updated when exceptions are granted.

---

> **This Development Constitution is binding for all code written for Kona Ya Hisabati.**
>
> Before generating any code, every developer — human or AI — must review the relevant section(s) of this constitution.
>
> Violations must be corrected before the code is committed.
>
> **Version:** 1.0
> **Date:** July 2026
> **Status:** Active
