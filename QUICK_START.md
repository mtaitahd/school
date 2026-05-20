# Kona Ya Hisabati - Quick Start Guide

## Get Running in 5 Minutes

### Step 1: Initialize Database
1. Ensure your server is running (XAMPP)
2. Open browser and go to: **http://localhost/school/php/init_db.php**
3. You should see: `{"success":true,"message":"Database initialized successfully!"}`
4. ✅ Database is ready!

### Step 2: Test the System

#### For Children - Try the Learning Platform
1. Go to: **http://localhost/school/**
2. Click the large "Start Learning" button
3. Choose a learning module (Counting, Shapes, Addition, etc.)
4. Click on an activity
5. **Watch the audio instruction play**
6. **Answer the question** (multiple choice buttons)
7. Correct answer shows ⭐ stars and celebration!

#### For Teachers - Try the Dashboard
1. Go to: **http://localhost/school/login.php**
2. Use test account:
   - Username: `teacher1`
   - Password: `password123`
3. You'll see the Teacher Dashboard with:
   - Learner statistics
   - Module performance
   - Recent activity log
   - Quick action buttons

#### For Parents - Try Parent Portal
1. Go to: **http://localhost/school/login.php**
2. Use test account:
   - Username: `parent1`
   - Password: `password123`
3. You'll see the Parent Dashboard with:
   - Child progress overview
   - Activity history
   - Star and badge tracking
   - Home learning tips

### Step 3: Explore Features

#### Test Audio Instructions
- Click any activity "Repeat" button
- You'll hear the instruction read aloud
- Volume and rate are optimized for children

#### Try Different Activity Types
- **Counting:** Count objects and select correct number
- **Shapes:** Identify circles, squares, triangles
- **Addition:** 3 + 2 = ?
- **Subtraction:** 5 - 2 = ?
- **Matching:** Match colors and shapes

#### View Teaching Resources
- **Teacher Guide:** `/school/teacher-guide.php`
- **Parent Guide:** `/school/parent-guide.php`
- **About Page:** `/school/about.php`

### Step 4: Create Your Own Accounts

#### To Create a Teacher Account:
Use phpMyAdmin at `http://localhost/phpmyadmin` and run:
```sql
INSERT INTO users (username, email, password, role, first_name, last_name) 
VALUES ('yourteacher', 'you@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Your', 'Name');
```

#### To Create a Parent Account:
```sql
INSERT INTO users (username, email, password, role, first_name, last_name) 
VALUES ('yourparent', 'you@home.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Your', 'Name');
```

Default password for both: `password123` (Change after first login)

### Step 5: Key URLs to Remember

| Function | URL |
|----------|-----|
| **Home Page** | http://localhost/school/ |
| **Learner Entry** | http://localhost/school/learner/login.php |
| **Teacher/Parent Login** | http://localhost/school/login.php |
| **Teacher Dashboard** | http://localhost/school/teacher/dashboard.php |
| **Parent Dashboard** | http://localhost/school/parent/dashboard.php |
| **Parent Guide** | http://localhost/school/parent-guide.php |
| **Teacher Guide** | http://localhost/school/teacher-guide.php |
| **About Page** | http://localhost/school/about.php |

## Features to Try

### Must-Try Features:
1. ✅ Click "Start Learning" and do an activity
2. ✅ Answer correctly and see the ⭐ star animation
3. ✅ Click "Repeat" button to hear the instruction again
4. ✅ Try Teacher login with test account
5. ✅ Try Parent login with test account
6. ✅ Read the teaching guides

### Interactive Elements:
- 🔊 Audio instructions on all pages
- ⭐ Star animations for correct answers
- 🎨 Colorful module cards with hover effects
- 📱 Responsive design (try on different screen sizes)
- 🎯 Large buttons optimized for touch

## Troubleshooting

### "Database connection failed" Message
- Check if MySQL is running in XAMPP
- Run `http://localhost/school/php/init_db.php` again
- Verify database exists in phpMyAdmin

### Audio Not Playing
- Check browser console for errors (F12)
- Ensure Web Speech API is supported (most modern browsers)
- Grant browser permission for audio
- Try in Incognito/Private mode

### Login Not Working
- Verify username and password exactly (case-sensitive)
- Check database has the test accounts:
  - Go to phpMyAdmin
  - View `users` table
  - Verify `teacher1` and `parent1` exist

### Page Not Loading
- Clear browser cache (Ctrl+Shift+Delete)
- Check file paths are correct
- Verify all files are in `c:\xampp\htdocs\school\`
- Check XAMPP Apache is running

## System Navigation Flow

```
Entry Point
    ↓
┌───────────────────────────────────────┐
│ Home Page (index.php)                 │
│ - Choose: Start Learning              │
│           Teacher Login               │
│           Parent Login                │
└───────────────────────────────────────┘
    ↓
    ├─→ LEARNER PATH
    │   ├─ Enter Name (learner/login.php)
    │   ├─ Choose Module (learner/activities.php)
    │   ├─ Select Activity (learner/activity.php)
    │   └─ Complete Task & Earn Stars
    │
    ├─→ TEACHER PATH
    │   ├─ Login (login.php)
    │   ├─ Dashboard (teacher/dashboard.php)
    │   └─ View Progress & Resources
    │
    └─→ PARENT PATH
        ├─ Login (login.php)
        ├─ Dashboard (parent/dashboard.php)
        └─ View Child Progress
```

## Learning Modules Available

1. **Counting** - Count 1-10 objects
2. **Shapes** - Identify circles, squares, triangles
3. **Addition** - Single digit addition
4. **Subtraction** - Single digit subtraction
5. **Matching** - Match colors and shapes
6. **Games** - Interactive math games

Each module has 3-5 different activities to practice.

## Test Scenarios

### Scenario 1: Child Learning
1. Go to home page
2. Click "Start Learning" (or go to /learner/login.php)
3. Enter a name (e.g., "Tommy")
4. Select "Counting" module
5. Click on "Count Apples"
6. See 5 apples displayed
7. Click "5" to answer correctly
8. ⭐ Stars appear! Audio says "Good job!"

### Scenario 2: Teacher Monitoring
1. Login with teacher1/password123
2. See list of all learners
3. View module statistics
4. See recent activity feed
5. Check average scores

### Scenario 3: Parent Checking Progress
1. Login with parent1/password123
2. See linked children
3. View activity history
4. Check stars earned
5. Read home learning tips

## Important Notes

- All test accounts have the same password: `password123`
- Learners don't need passwords (name-entry only)
- Audio works best on Chrome, Firefox, Safari, Edge
- Mobile & tablet friendly design
- All learning data is session-based (can add database persistence)

## Getting Help

- **Detailed Setup:** Read `SETUP_INSTRUCTIONS.md`
- **Implementation Details:** Read `IMPLEMENTATION_SUMMARY.md`
- **Parent Resources:** Visit `/parent-guide.php`
- **Teacher Resources:** Visit `/teacher-guide.php`

---

**You're all set!** 🎉

Go to **http://localhost/school/** and start exploring Kona Ya Hisabati!

If you encounter any issues, check `SETUP_INSTRUCTIONS.md` for detailed troubleshooting.
