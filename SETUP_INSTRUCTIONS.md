# Kona Ya Hisabati - Setup Instructions

## Quick Start Guide

Follow these steps to get Kona Ya Hisabati running on your system.

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB database
- XAMPP or similar local server environment
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Installation Steps

#### 1. File Placement
Ensure all files are in: `c:\xampp\htdocs\school\`

The project structure should be:
```
school/
├── index.php                 (Home page)
├── login.php                 (Login for teacher/parent)
├── logout.php               (Logout)
├── about.php                (About page)
├── contact.php              (Contact page)
├── terms.php                (Terms page)
├── parent-guide.php         (Parent resources)
├── teacher-guide.php        (Teacher resources)
├── database.sql             (Database schema)
├── php/
│   ├── db_connection.php    (Database class)
│   └── init_db.php          (Database setup script)
├── css/
│   └── style.css            (Styling)
├── js/
│   └── main.js              (JavaScript functionality)
├── learner/
│   ├── login.php            (Learner login)
│   ├── activities.php       (Activity selection)
│   └── activity.php         (Activity execution)
├── teacher/
│   ├── dashboard.php        (Teacher dashboard)
│   ├── logout.php           (Teacher logout)
│   └── ...
├── parent/
│   ├── dashboard.php        (Parent dashboard)
│   ├── add-child.php        (Add learner)
│   └── ...
├── admin/
│   ├── dashboard.php        (Admin panel)
│   └── ...
└── assets/
    ├── audio/              (Audio files)
    ├── images/            (Images)
    └── ...
```

#### 2. Database Setup

**Option A: Automatic Setup (Recommended)**
1. Open your browser and navigate to: `http://localhost/school/php/init_db.php`
2. The database will be automatically created with sample data
3. You should see a success message

**Option B: Manual Setup**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named: `kona_hisabati`
3. Import the `database.sql` file
4. Database credentials (in `php/db_connection.php`):
   - Host: localhost
   - Database: kona_hisabati
   - Username: root
   - Password: (empty)

#### 3. Create Sample User Accounts

Run these SQL queries in phpMyAdmin:

```sql
-- Create a teacher account
INSERT INTO users (username, email, password, role, first_name, last_name) 
VALUES ('teacher1', 'teacher@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'John', 'Smith');

-- Create a parent account
INSERT INTO users (username, email, password, role, first_name, last_name) 
VALUES ('parent1', 'parent@home.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Jane', 'Doe');

-- Create a learner account
INSERT INTO users (username, email, password, role, first_name, last_name, parent_id) 
VALUES ('learner1', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'learner', 'Tommy', 'Doe', 2);
```

**Default password for all accounts:** `password123`

#### 4. Start Using the Platform

1. **Home Page**: `http://localhost/school/index.php`

2. **For Children (Learners)**:
   - Click "Learner" button
   - Enter a name
   - Choose a learning module
   - Complete activities and earn stars

3. **For Teachers**:
   - Click "Teacher" button
   - Username: `teacher1`
   - Password: `password123`
   - Access dashboard to view learner progress

4. **For Parents**:
   - Click "Parent" button
   - Username: `parent1`
   - Password: `password123`
   - View child's progress and learning resources

### Features & Modules

#### Learning Modules (15 total)
1. **Counting** - Numbers 1-10
2. **Shapes** - Circle, square, triangle, etc.
3. **Addition** - Single-digit addition
4. **Subtraction** - Single-digit subtraction
5. **Matching** - Color and shape matching
6. **Games** - Interactive math games
7. And 9 more specialized modules

#### Activity Types
- Counting activities
- Shape recognition
- Addition & subtraction problems
- Matching and sorting
- Interactive games
- Quizzes and assessments
- Songs and rhymes

#### User Roles

**Learner (Child)**
- Access interactive learning activities
- Earn stars for correct answers
- Receive audio-guided instructions
- Child-friendly interface

**Teacher**
- Monitor class progress
- Track individual learner performance
- Download worksheets and resources
- Generate progress reports

**Parent**
- View child's learning progress
- Access home learning guides
- See activity history
- Receive progress updates

**Admin**
- Upload and manage content
- Create and assign activities
- View system analytics

### Troubleshooting

#### Database Connection Issues
- Ensure MySQL is running
- Verify credentials in `php/db_connection.php`
- Check that database `kona_hisabati` exists

#### File Not Found Errors
- Ensure files are in correct directory: `c:\xampp\htdocs\school\`
- Check file names (case-sensitive on Linux servers)
- Verify all directories exist

#### Audio Not Playing
- Ensure Web Speech API is supported in browser
- Check browser permissions for audio
- Verify audio-related JavaScript is loaded

#### Login Issues
- Verify database users exist
- Check password is correct (case-sensitive)
- Clear browser cookies and try again
- Use Incognito/Private mode

### Key Files & Their Functions

| File | Purpose |
|------|---------|
| `php/db_connection.php` | Database connection class |
| `php/init_db.php` | Database initialization script |
| `index.php` | Home/landing page |
| `learner/activities.php` | Activity selection for children |
| `learner/activity.php` | Activity execution |
| `teacher/dashboard.php` | Teacher management interface |
| `parent/dashboard.php` | Parent view of child progress |
| `css/style.css` | All styling (child-friendly design) |
| `js/main.js` | Interactive functionality & audio |

### Customization

#### Add New Activity
1. Add module in database: `modules` table
2. Add activity: `activities` table with JSON data
3. JavaScript automatically handles display

#### Change Colors
- Edit `css/style.css` CSS variables section
- Update `module_color` in database

#### Add New Audio
- Modify `playAudio()` function in `js/main.js`
- Add audio files to `assets/audio/`

### Performance Optimization

- Enable browser caching
- Compress images in `assets/`
- Use CDN for Bootstrap/FontAwesome
- Enable GZIP compression in server

### Security Notes

- Change default database password
- Use HTTPS in production
- Validate all user inputs
- Keep dependencies updated
- Never expose database credentials in code

### Support & Contact

For issues or questions:
- Check the About page: `about.php`
- Review Teacher Guide: `teacher-guide.php`
- Check Parent Guide: `parent-guide.php`
- Contact: info@konahisabati.com

---

**Version:** 1.0
**Last Updated:** May 2026
**License:** Educational Use
