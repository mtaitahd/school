KONA YA HISABATI
FULL SYSTEM FLOWCHART -TEXT-BASED DIAGRAM
1. MASTER SYSTEM FLOW
 ┌─────────────────────┐
│ HOME PAGE │
└─────────┬───────────┘
 │
 ┌────────────────────────────┼────────────────────────────┐
 │ │ │
┌───▼───┐ ┌─────▼─────┐ ┌──────▼──────┐
│ START │ │ TEACHER │ │ PARENT │
│LEARNING│ │ DASHBOARD │ │ GUIDE │
└───┬───┘ └─────┬─────┘ └──────┬──────┘
 │ │ │
 ▼ ▼ ▼
┌────────────┐ ┌────────────┐ ┌─────────────┐
│ LEARNING │ │ TEACHER │ │ PARENT │
│ CATEGORIES │ │ TOOLS │ │ RESOURCES │
└────┬───────┘ └────┬───────┘ └──────┬──────┘
 │ │ │
 ▼ ▼ ▼
(Flows Continue...) (Flows Continue...) (Flows Continue...)
2. CHILD USER FLOW DIAGRAM
 ┌─────────────────────────┐
│ HOME PAGE │
└─────────────┬───────────┘
 │
 ┌─────▼─────┐
│ START │
│ LEARNING │
└─────┬─────┘
 │
 ┌──────▼──────┐
│ LEARNING │
│ CATEGORIES │
└──────┬──────┘
 ┌──────────────────┼───────────────────┐
 │ │ │
 ▼ ▼ ▼
 ┌────────────┐ ┌────────────┐ ┌────────────┐
 │ COUNTING & │ │ SHAPES & │ │ ADDITION & │
 │ NUMBERS │ │ PATTERNS │ │ SUBTRACTION│
 └─────┬──────┘ └──────┬─────┘ └──────┬─────┘
 │ │ │
 ▼ ▼ ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ Interactive │ │ Interactive │ │ Interactive │
│ Activities │ │ Activities │ │ Activities │
└──────┬──────────┘ └──────┬──────────┘ └──────┬──────────┘
 │ │ │
 ▼ ▼ ▼
┌──────────────┐ ┌──────────────┐ ┌───────────────┐
│ Feedback & │ │ Feedback & │ │ Feedback & │
│ Rewards │ │ Rewards │ │ Rewards │
└──────┬───────┘ └──────┬───────┘ └──────┬────────┘
 │ │ │
 ▼ ▼ ▼
┌──────────────┐ ┌──────────────┐ ┌───────────────┐
│ Next Activity│ │ Next Activity│ │ Next Activity │
└──────────────┘ └──────────────┘ └───────────────┘
3. TEACHER FLOW DIAGRAM
 ┌──────────────────────────┐
│ HOME PAGE │
└──────────────┬───────────┘
 │
 ┌──────▼──────┐
│ TEACHER │
 │ LOGIN │
 └──────┬──────┘
 │ (Auth success)
 ┌──────▼────────┐
│ TEACHER │
│ DASHBOARD │
└──────┬────────┘
 ┌───────────────────────────┼──────────────────────────┐
 │ │ │
 ▼ ▼ ▼
┌───────────────┐ ┌─────────────────┐ ┌──────────────────┐
│ Curriculum & │ │ Track Learner │ │ Downloadable │
│ Lesson Plans │ │ Progress │ Worksheets/Tools │
└───────┬──────┘ └──────────┬──────┘ └──────────┬────────┘
 │ │ │
 ▼ ▼ ▼
┌───────────────┐ ┌─────────────────┐ ┌────────────────────┐
│ Select Lesson │ │ Class/Student │ │ PDF/Flashcards
│
│ or Activity │ │ Progress View │ │ Lesson Guides
│
└───────────────┘ └─────────────────┘ └────────────────────┘
 Final Action → Log Out / Continue Teaching
4. PARENT/CAREGIVER FLOW
DIAGRAM
 ┌─────────────────────────┐
│ HOME PAGE │
└─────────────┬───────────┘
 │
 ┌───────▼────────┐
│ PARENT GUIDE │
└───────┬────────┘
 │
 ┌────────────────────────┼────────────────────────┐
 │ │ │
 ▼ ▼ ▼
┌────────────────┐ ┌─────────────────┐ ┌──────────────────┐
│ Home Numeracy │ │ Video Tutorials │ │ Daily Practice │
│ Guides │ │ │ │ Activities
└──────┬─────────┘ └────────┬────────┘ └────────┬─────────┘
 │ │ │
 ▼ ▼ ▼
┌────────────────┐ ┌─────────────────┐ ┌──────────────────┐
│ Step-by-step │ │ Watch & Learn │ │ Activities that │
│ Support Tips │ │ │ │ need no devices │
└────────────────┘ └─────────────────┘ └──────────────────┘
 Final Action → Support child → Return anytime
5. DETAILED SYSTEM FLOW – ALL
MODULES CONNECTED
HOME PAGE
 │
 ├──► START LEARNING (Child Path)
 │ │
 │ └──► Learning Categories
 │ │
 │ ├──► Counting
 │ ├──► Shapes
 │ ├──► Addition/Subtraction
 │ ├──► Math Games
 │ ├──► Quizzes
 │ └──► Songs & Videos
 │
 ├──► TEACHER LOGIN → Dashboard
 │ │
 │ ├──► Lesson Plans
 │ ├──► Learner Progress
 │ ├──► Activity Library
 │ └──► Downloads
 │
 └──► PARENT GUIDE
 │
 ├──► Home Numeracy Tips
 ├──► Videos
 └──► Practice Activities




 KONA YA HISABATI – WEB DEVELOPMENT GUIDELINE & FLOW-CHART
A. DEVELOPMENT GUIDELINE
1.0 Background Summary for Developers
Kona Ya Hisabati is a digital version of the classroom Mathematics Learning Corner. It aims to address
the lack of well-equipped math corners in many schools by providing online, child-friendly,
interactive numeracy activities accessible anytime, at school or home.
Target Users:
 Pre-Primary pupils
 Standard One and two pupils
 Teachers
 Parents/Caregivers
Platform Goals:
 Improve numeracy skills through interactive practice.
 Provide continuous access to curriculum-aligned math resources.
 Strengthen school–home collaboration.
 Boost children’s interest, engagement, and self-confidence in mathematics.
2.0 Functional Requirements
2.1 User Categories
1. Learner (Pupil)
 Access games and activities.
 Receive feedback (audio + visual).
 Navigate using icons (minimal text).
2. Teacher
 Select activities for class.
 Monitor learner progress.
 Download simple worksheets.
3. Parent
 Monitor child’s performance.
 Support home practice.
4. Admin
 Upload content.
 Manage accounts/permissions.
 Review analytics.
3.0 Platform Structure & Modules
A. Home Page
 Child-friendly interface
 Colorful icons
 Audio-assisted navigation eg Tap here for Counting
B. Main Learning Modules
1. Number Concepts
Counting, number recognition, sequencing, tracing, missing numbers.
2. Shapes & Patterns & Spatial Awareness
Identify, sort, complete patterns, left/right, above/below.
3. Basic Operations (Addition/Subtraction)
Drag & drop, number lines, visual operations, word problems.
4. Sorting, Matching, Classifying
Sort by color/size, categorize objects, match numbers.
5. Measurement Basics
Big/small, tall/short, heavy/light, ordering sizes.
6. Time & Daily Routine
Day/night, morning/evening, sequencing activities.
7. Money Concepts
Identify coins/notes, match values, simple buying/selling.
8. Math Games (Play Zone)
Puzzles, matching games, memory cards, number hunts.
9. Math Songs & Rhymes
Counting songs, animations, shape songs.
10. Quizzes & Assessment
Auto-graded quizzes, badges, progress reports.
4.0 Technical Requirements
4.1 Front-End
 Framework: React.js / Vue.js (recommended)
 Responsive design for tablets and phones
 High-contrast colors and large buttons for children
4.2 Back-End
 Admin dashboard for content uploading
4.3 Accessibility
 Audio instructions for non-readers
 Minimal text
 Visual cues (arrows, icons)
 Offline mode (optional)
4.4 Security
 Child-safe authentication (no email needed for pupils)
 Teacher/parent accounts secured by password
 No personal data collection from children
5.0 Content Development Guidelines
1. Curriculum Alignment
 Align with pre-primary and Grade 1 numeracy standards in Tanzania.
2. Activity Design
 Simple instructions with voice support.
 Use bright colors, familiar objects (animals, fruits, everyday items).
 Immediate positive feedback (sounds, stars, animations).
3. Game Mechanics
 Drag-and-drop
 Matching
 Tap-to-select
 Audio prompts
4. Assessment
 Short quizzes after each topic
 Automatic scoring
 Generate simple reports for parents/teachers
6.0 Development Phases
Phase 1: Planning & Analysis
 Review curriculum
 Define modules
 Draft UI/UX wireframes
Phase 2: Content & Activity Design
 Prepare all visuals (icons, shapes, objects)
 Script audio instructions
 Prepare animations
Phase 3: System Development
 Build front-end interface
 Set up back-end, API, database
 Integrate content
Phase 4: Testing
 Child usability testing
 Teacher feedback
 Bug fixing
Phase 5: Deployment
 Launch web platform
 Set up analytics
Phase 6: Continuous Improvement
 Add new games
 Update songs & quizzes
 Teacher feedback integration
7.0 Flow Chart Diagram
 ┌──────────────────────┐
 │ START │
 └─────────┬────────────┘
 ▼
 ┌────────────────────────────┐
 │ User Accesses Platform │
 └─────────┬──────────────────┘
 ▼
 ┌──────────────────┼───────────────────┐
 ▼ ▼ ▼
┌──────────────┐ ┌────────────────┐ ┌────────────────┐
 │ Learner │ │ Teacher │ │ Parent │
└─────┬────────┘ └──────┬─────────┘ └──────┬─────────┘
 ▼ ▼ ▼
┌───────────────┐ ┌────────────────┐ ┌──────────────────┐
│Select Activity │ │Choose Class/ │ │ View Child │
│(Games/Numbers/ │ │Assign Activity │ │ Progress │
│Shapes, etc.) │ └──────┬─────────┘ └──────────────────┘
└─────┬─────────┘ │
 │ │
 ▼ ▼
┌───────────────┐ ┌────────────────┐
│Play Activity/ │ │ Monitor Progress│
│Receive Feedback│ │ View Reports │
└─────┬─────────┘ └──────┬──────────┘
 │ │
 ▼ ▼
┌───────────────┐ ┌────────────────┐
│Take Quiz/ │ │ Adjust Teaching│
│Assessment │ │ based on data │
└─────┬─────────┘ └──────┬──────────┘
 │ │
 └──────────┬────────┘
 ▼
 ┌──────────────────────┐
 │ Save Progress │
 └─────────┬────────────┘
 ▼
 ┌──────────────────────┐
 │ END │
 └──────────────────────┘
8.0 Deliverables for Developers
 Functional platform with full modules listed.
 UX optimized for children (3–7 years).
 Audio-assisted instructions.
 Teacher & parent dashboards.
 Secure, scalable architecture.
 Admin for adding new content.


Kona Ya Hisabati
Complete UI/UX Design and Development Guideline
1. UI/UX Concept Overview
1.1 Purpose of the Platform
Kona Ya Hisabati is a web-based interactive mathematics learning platform designed to
digitize the traditional Mathematics Learning Corner used in Tanzanian early grade
classrooms. The platform aims to:
 Improve numeracy learning for Pre-Primary and Standard One to Two learners.
 Provide a child-friendly environment for interactive math practice.
 Equip teachers with structured digital activities and progress tracking tools.
 Support parents in guiding home-based practice numeracy learning.
 Ensure inclusive, accessible learning following UDL principles.
1.2 Target Users
1. Children (Pre-Primary & Standard One-Two)
o Limited reading ability
o Require visual, intuitive, highly interactive interfaces
o Prefer audio guidance and large buttons
2. Teachers
o Require structured lesson-aligned content
o Need classroom resources, analytics, and printable materials
o Require tools aligned with Tanzania’s curriculum
3. Parents / Caregivers
o Require simple navigation and explanations
o Need practical home numeracy activities
o Require video guides and low literacy support
1.3 Key Learning Principles
 Early Childhood Education: More visuals, less text; color-rich, playful interactions.
 Universal Design for Learning (UDL): Multiple means of engagement,
representation, and expression.
 Accessibility: High contrast colors, simple navigation, audio instruction controls,
alternative text, offline support.

## Project Structure

school/
├── css/
│   └── style.css              # Global styles and child-friendly design
├── js/
│   └── main.js               # JavaScript functionality and audio prompts
├── php/
│   └── db_connection.php     # Database connection using PDO
├── assets/
│   ├── audio/                # Audio files (optional)
│   └── images/               # Images and icons
├── admin/
│   └── dashboard.php         # Admin dashboard for content management
├── teacher/
│   └── dashboard.php         # Teacher dashboard with lesson plans and progress
├── parent/
│   ├── dashboard.php         # Parent dashboard for tracking children
│   ├── add-child.php         # Add child account
│   ├── guide.php             # Parent guide with tips and activities
│   └── child-progress.php    # Detailed child progress view
├── learner/
│   ├── login.php             # Child-safe learner login
│   ├── activities.php        # Activities list for a module
│   └── activity.php          # Interactive activity template
├── index.php                 # Home page with learning categories
├── login.php                 # Login page for teachers/parents
├── register.php              # Registration page
├── logout.php                # Logout handler
├── about.php                 # About page
├── contact.php               # Contact page
├── terms.php                 # Terms of use page
├── database.sql              # Database schema and sample data
└── README.md                 # This file

2.2 Sitemap (Hierarchical)
Home Page
 Start Learning
 Teacher Login
 Parent Guide
 About Kona Ya Hisabati
 Language Selector
Children Learning Section
 Counting & Number Recognition
o Count Objects
o Match Numbers
o Number Ordering
 Shapes & Patterns
o Identify Shapes
o Create Patterns
o Shape Sorting
 Addition & Subtraction
o Single-digit addition
o Single-digit subtraction
o Story problems
 Math Games
o Memory game
o Matching game
o Drag-and-drop games
o Math Tag of war (mfano) https://www.instagram.com/reels/DUsl_8VATIr/
 Quizzes
o Short assessments
o Certificate printing
 Math Songs & Videos
o Counting songs
o Shape songs
o Animated story videos
Teacher Section/ Dashboard
 Lesson Plans
 Activity Library
 Learner Progress
 Printable Worksheets
 Classroom Tips
Parent Section
 How to Support Learning at Home
 Simple daily math tasks
 Video guides
 Progress view (optional)
2.3 User Flows
Child User Flow
Home → Start Learning → Choose Category → Interactive Activity → Reward/Progress →
Continue or Exit
Teacher User Flow
Home → Teacher Login → Dashboard → Select Tools → Download/Track → Logout
Parent User Flow
Home → Parent Guide → View Tips → Watch Video → Access Home Activities
3. Detailed UI Descriptions for Each Page
3.1 Home Page
Purpose
Welcome page for all users; easy entry to learning activities.
Layout
 Top header: Logo, language toggle, teacher login
 Center: Main illustration (children learning)
 Large “Start Learning” button
 Secondary buttons: Teacher Area, Parent Guide
 Footer: About, Contact, Terms
Components
 Large rounded buttons
 High-resolution illustrations
 Top navigation bar
Sample Labels
 “Start Learning ”
 “Teacher Dashboard”
 “Parent Guide”
Interaction
 Hover: Light shadow expansion
 Click: Soft bounce animation
 Audio option: “Touch here to start learning!”
3.2 Learning Categories Page
Purpose
Provide children with icon based choices of math activities.
Layout
 Grid of colorful icons (6 main categories)
 Each icon shows an illustration (shapes, numbers, games)
Components
 Icon card (child friendly)
 Category title
 Simple audio prompts
Sample Labels
 “Counting,” “Shapes,” “Games,” “Quizzes”
Interactions
 Hover: Glow effect
 Click: Page transition with animation
3.3 Activity Page Template (All Learning Activities)
Purpose
Interactive page where children solve math tasks.
Layout
 Top bar: Home, Back, Audio
 Middle: Interactive component (drag-and-drop, buttons, animations)
 Bottom: Next Activity button
Components
 Large interactive element
 Object illustrations (fruit, toys, animals)
 Reward animations (stars)
Sample Text
 “Count the apples”
 “Touch the correct number”
Interaction Behavior
 Click feedback: Color change
 Drag feedback: Snap-to-place
 Correct answer: Star animation and voice “Good job!”
4. Learning Activity UI Templates
4.1 Counting & Number Recognition
 Drag objects to match numbers
 Select correct number from 1–10
 Moving number line
4.2 Shapes & Patterns
 Identify shapes by tapping
 Complete patterns using drag-and-drop
 Shape matching memory game
4.3 Addition & Subtraction
 Visual math (apples added/removed)
 Simple equations (3 + 2 = ?)
4.4 Math Games
 Maze game (collect numbers)
 Shape-hunt adventure
4.5 Quizzes
 Timed tasks
 Star-rating scorecards
4.6 Math Songs & Videos
 Video player with large controls
 Playlist of math songs
5. Teacher Dashboard UI Requirements
5.1 Content Access
 Clear folder-like structure
 Curriculum-aligned activities
5.2 Progress Tracking
 Charts (bar/line)
 Class-level summaries
 Downloadable reports
5.3 Materials
 PDF worksheets
 Flashcards
 Lesson guides
5.4 Classroom Tools
 Tips for differentiation
 UDL strategies
 Remedial plans
6. Parent/Caregiver Support UI
Key Elements
 Step-by-step guides
 Simple video tutorials
 Printable home activities
 Low-literacy-friendly icons
 Large fonts
7. Style Guide / Design System
7.1 Color Palette
 Primary Blue:
 Primary Yellow:
 Secondary Green:
 Orange (high interest)
 Background Light:
7.2 Typography
 Headings: Poppins
 Body text: Nunito
7.3 Buttons
 Rounded corners
 Height: 70–90px for children
 Bold labels
7.4 Icons
 Flat, colorful, child friendly
 Use culturally appropriate visuals
7.5 Spacing
 16px baseline grid
 Clear padding around elements
8. Accessibility & Inclusivity Requirements
 High contrast colors
 Alt-text for images
 Audio instruction button on all activities
 Touch-friendly 1.5cm minimum tap-target
 Simple language (Kiswahili & English)
 Dyslexia-friendly mode (optional)
9. Flow Diagrams (Text-Based)
10.1 Child Flow
Home → Start → Select Category → Activity → Reward → Next Activity → Exit
10.2 Teacher Flow
Home → Login → Dashboard → Select Tool → Track/Download → Logout
10.3 Parent Flow
Home → Parent Guide → Watch Video → Try Home Activity → Repeat#   s c h o o l  
 