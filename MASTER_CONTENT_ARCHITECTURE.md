# Master Content Architecture — Kona Ya Hisabati

> **Status:** Approved — Constitutional Document
> **Version:** 1.0
> **Scope:** All current and future educational content
> **Applies to:** Numbers, Letters, Colors, Shapes, Animals, Fruits, Body Parts, Time, Money, Science, Social Skills, and any future curriculum expansions

---

## Table of Contents

1. [Content Hierarchy](#1-content-hierarchy)
2. [Lesson Blueprint](#2-lesson-blueprint)
3. [Activity Blueprint](#3-activity-blueprint)
4. [Difficulty Framework](#4-difficulty-framework)
5. [Reusable Activity Engines](#5-reusable-activity-engines)
6. [Asset Blueprint](#6-asset-blueprint)
7. [Assessment Blueprint](#7-assessment-blueprint)
8. [Reward Blueprint](#8-reward-blueprint)
9. [Revision Blueprint](#9-revision-blueprint)
10. [Expansion Rules](#10-expansion-rules)

---

## 1. Content Hierarchy

The entire learning platform is organized into 8 hierarchical levels. Every piece of content lives at exactly one level. Nothing is added outside this structure.

```
Level 1:  DOMAIN         The broadest learning area (e.g., Mathematics)
Level 2:  STRAND         A major branch within a domain (e.g., Number & Operations)
Level 3:  TOPIC          A specific skill cluster within a strand (e.g., Numbers 1-10)
Level 4:  LESSON         A single teachable session with a clear objective
Level 5:  ACTIVITY       An interactive learning task within a lesson
Level 6:  CHALLENGE      A harder variant of an activity for deeper practice
Level 7:  ASSESSMENT     A checkpoint to measure mastery
Level 8:  REWARD         A celebration milestone for completed work
```

### Level 1 — Domain

The highest organizational category. Represents a complete academic subject area.

- **Examples:** Mathematics, Language & Communication, Science, Creative Development
- **Properties:** domain_id, domain_name, domain_icon, domain_color, order_index
- **Cardinality:** A platform can have 1-10 domains. Each domain is independent.

### Level 2 — Strand

A major branch of learning within a domain. Each strand represents a coherent body of knowledge.

- **Examples (Mathematics):** Number & Operations, Geometry, Measurement, Data Handling
- **Examples (Language):** Pre-Reading, Writing Readiness, Oral Communication
- **Properties:** strand_id, domain_id (FK), strand_name, strand_code (e.g., "MATH-NUM"), strand_icon, order_index, learning_hours (estimated total hours)
- **Cardinality:** 3-12 strands per domain. Each strand belongs to exactly one domain.

### Level 3 — Topic

A focused skill cluster within a strand. Topics are the smallest complete unit of curriculum planning — each topic teaches one specific set of closely related skills.

- **Examples:** Numbers 1-5, Numbers 6-10, Counting Objects, Number Recognition, 2D Shapes, Color Sorting, Size Comparison, Coin Identification
- **Properties:** topic_id, strand_id (FK), topic_name, topic_code, age_range (e.g., "3-4"), prerequisites (list of topic_ids), order_index, estimated_sessions
- **Cardinality:** 4-15 topics per strand. Topics are sequential and may have prerequisites.

### Level 4 — Lesson

A single teachable session with one clear learning objective. A lesson groups 3-7 activities around a specific goal. A learner typically completes one lesson per session (15-30 minutes).

- **Examples:** "Count Objects to 5", "Identify the Circle Shape", "Add Within 5 Using Fingers", "Recognize the Letter A"
- **Properties:** lesson_id, topic_id (FK), lesson_number, lesson_name, learning_objective (one precise sentence), success_criteria (what the child can do after), estimated_minutes, prerequisite_lesson_ids
- **Cardinality:** 3-8 lessons per topic. Each lesson builds on the previous one.

### Level 5 — Activity

An interactive learning task that practices one specific sub-skill within a lesson. Each activity has a single interaction mode (tap, drag, trace, match, etc.) and lasts 1-5 minutes.

- **Examples:** "Tap each apple as you count to 5", "Drag the number 3 to the group of 3 stars", "Trace the number 2 with your finger"
- **Properties:** activity_id, lesson_id (FK), activity_number, activity_name, activity_type (one of the 12 blueprints), engine (which reuseable engine powers it), difficulty_level (1-6), activity_params (JSON — engine-specific configuration), assets_required (JSON — list of asset references), passing_score (0-100), max_attempts (default 3)
- **Cardinality:** 3-7 activities per lesson. Activities follow the lesson blueprint sequence (see Section 2).

### Level 6 — Challenge

A harder variant of an activity, triggered when a learner achieves mastery on the base activity. Challenges use the same engine but with increased difficulty (larger numbers, faster pace, fewer hints, distractor items).

- **Examples:** "Count to 10" (Challenge: Count to 15 with mixed objects), "Identify Square" (Challenge: Square among similar shapes like rectangle/diamond)
- **Properties:** challenge_id, activity_id (FK), challenge_number, difficulty_increase (specific parameter changes from base activity), unlock_condition (must achieve X% on base activity)
- **Cardinality:** 0-2 challenges per activity. Not all activities need challenges.

### Level 7 — Assessment

A formal checkpoint to verify mastery. Assessments pull from the engines used in a lesson but present items in a randomized, timed, or reduced-support format. Results determine whether the learner passes, retries, or revises.

- **Types:** Quick Check (1 min, 2 questions), Lesson Check (3-5 min, 5 questions), Topic Test (10 min, 10 questions), Strand Exam (20 min, 20 questions)
- **Properties:** assessment_id, parent_type (lesson/topic/strand), parent_id (FK), assessment_name, question_count, time_limit_minutes, passing_percent, retry_delay_hours
- **Cardinality:** One Quick Check per activity group, one Lesson Check per lesson, one Topic Test per topic, one Strand Exam per strand.

### Level 8 — Reward

A celebration milestone awarded for completing a set of content. Rewards are not content themselves but are triggered by content completion.

- **Types:** Star (per activity), Badge (per milestone), Certificate (per strand/domain)
- **Properties:** reward_id, reward_type, trigger_type (activity_complete/lesson_complete/topic_complete/strand_complete), trigger_value (e.g., 100% score), reward_icon, reward_animation

### Hierarchy Rules

1. **No skipping levels.** Every item must fit at its designated level. You cannot have an Activity that is not inside a Lesson.
2. **Foreign key integrity.** Every level references its parent via a foreign key. The hierarchy is enforced at the database level.
3. **Order matters.** Within each level, items have an explicit order_index. The system relies on this for progression.
4. **Prerequisites.** A topic may list prerequisite topic IDs. A lesson may list prerequisite lesson IDs within the same topic.
5. **Uniqueness.** Content codes are unique across all levels. Use a prefix convention: DOM-STR-TPC-LSN-ACT (e.g., MATH-NUM-1A-L02-A03).

---

## 2. Lesson Blueprint

Every lesson in the system follows exactly this 10-step sequence. This sequence is grounded in educational research: it moves from concrete to abstract, from teacher-supported to independent, from practice to assessment to celebration.

```
Step 1:  LESSON INTRODUCTION        (1 min)
Step 2:  WARM-UP PRACTICE           (2 min)
Step 3:  I DO (Teacher Demonstrate)  (2 min)
Step 4:  WE DO (Guided Practice)    (3 min)
Step 5:  YOU DO (Independent)       (3 min)
Step 6:  CHECK FOR UNDERSTANDING    (2 min)
Step 7:  INTERACTIVE GAME           (3 min)
Step 8:  QUICK ASSESSMENT           (2 min)
Step 9:  REWARD & CELEBRATION       (1 min)
Step 10: REVISION & NEXT STEPS      (1 min)
         ─────────────────────────────────
         TOTAL: ~20 minutes per lesson
```

### Step 1 — Lesson Introduction

**Purpose:** Set the context. Tell the child what they will learn today and why it matters.

- **Activity Template:** INTRO
- **What happens:** An animated title card appears with the lesson name. Audio plays: "Today we will learn to count to 5! Let's start!"
- **Assets:** Lesson title graphic, audio narration, background music (fade in)
- **Engine:** None (static display)
- **Exit condition:** Child taps "Start" button or 3 seconds auto-advance

### Step 2 — Warm-Up Practice

**Purpose:** Activate prior knowledge. Review prerequisite skills so the child is ready for new learning.

- **Activity Template:** TAP
- **What happens:** A quick (30-60 second) review of the previous lesson's key skill. 1-2 easy questions drawn from the prerequisite lesson's content.
- **Engine:** Any engine that matches the prerequisite skill
- **Difficulty:** Always Level 1 (easiest)
- **Error handling:** No penalty. Correct answer shown with guidance if wrong.
- **Exit condition:** Child answers correctly (with hints if needed) or after 3 attempts

### Step 3 — I Do (Teacher Demonstration)

**Purpose:** Model the new skill. The system demonstrates the correct approach step by step. The child watches and listens.

- **Activity Template:** INTRO (demonstration variant)
- **What happens:** An animation plays showing exactly how to do the skill. Audio explains each step. For example: "Watch me count. One, two, three, four, five. There are five apples!"
- **Assets:** Demonstration animation (pre-rendered or sequenced), detailed voice-over
- **Engine:** None (playback only)
- **Child interaction:** None required — passive watching
- **Exit condition:** Animation ends + child taps "I understand" or auto-advance after 3 seconds

### Step 4 — We Do (Guided Practice)

**Purpose:** Practice together. The child attempts the skill with hints, visual cues, and audio prompts available.

- **Activity Template:** TAP or MULTI-TAP (guided)
- **What happens:** The child solves 1-2 problems with the system providing hints (highlight correct option after 5 seconds, play count-along audio, reduce choices)
- **Engine:** TAP_ENGINE or MULTI_TAP_ENGINE (with guided mode flag)
- **Difficulty:** Level 1
- **Error handling:** On first incorrect attempt, a hint is shown. On second incorrect, the system shows the correct answer and explains why.
- **Success criteria:** Complete with any number of attempts (learning, not testing)
- **Exit condition:** Both problems completed

### Step 5 — You Do (Independent Practice)

**Purpose:** Apply independently. The child solves problems without hints.

- **Activity Template:** TAP, MULTI-TAP, DRAG, or MATCH
- **What happens:** 2-3 problems at Level 1-2 difficulty. No hints. Minimal audio support (only the initial instruction).
- **Engine:** Appropriate engine per activity type (see Section 5)
- **Difficulty:** Level 1-2
- **Error handling:** Incorrect answer marks the problem wrong but does not show the answer. The child can retry once. After 2 failures, the system notes this for revision and moves on.
- **Success criteria:** Score tracked. Used for formative assessment data.

### Step 6 — Check for Understanding

**Purpose:** Verify the child grasped the concept before moving to the game.

- **Activity Template:** MATCH or COMPLETE
- **What happens:** A single targeted question that tests the lesson objective directly. If the child gets it wrong, the lesson branches to remedial practice (Step 4 variant) before continuing.
- **Engine:** MATCH_ENGINE
- **Difficulty:** Level 2
- **Branching logic:** Correct → proceed to Step 7. Incorrect → return to Step 4 with 2 additional guided problems. After remedial, retry Check. If still incorrect, flag for revision (see Section 9).
- **Critical rule:** A lesson should NOT proceed to the game if the child has not grasped the core concept.

### Step 7 — Interactive Game

**Purpose:** Reinforce through play. A fun, low-stakes game that uses the same skill in a playful context.

- **Activity Template:** GAME
- **What happens:** A game mode (memory match, timed tap, drag-race, or simple puzzle) using the lesson's skill. Time pressure is minimal or absent.
- **Engine:** GAME_ENGINE (with lesson-specific parameters)
- **Difficulty:** Level 2-3
- **Scoring:** Points are earned but there is no failure state. The game always ends positively.
- **Exit condition:** Game ends after a set time (60-90 seconds) or 3-5 rounds.

### Step 8 — Quick Assessment

**Purpose:** Measure learning outcomes for this session.

- **Activity Template:** QUIZ
- **What happens:** 2-3 questions in quiz format (no hints, timed per question — 15 seconds max). Questions randomly selected from the lesson's skill set.
- **Engine:** QUIZ_ENGINE
- **Difficulty:** Level 1-2 (same as lesson content)
- **Passing score:** 70%
- **Scoring logic:** 2-3 questions, each correct = 1 point. Score = (correct / total) x 100.
- **Pass:** Show reward (Step 9), mark lesson complete.
- **Fail:** Show encouragement message, suggest retry after 1 hour, flag for revision.

### Step 9 — Reward & Celebration

**Purpose:** Celebrate effort and progress.

- **Activity Template:** REWARD_SCREEN
- **What happens:** A celebration screen with animations (stars, confetti), positive audio feedback, and the reward earned (stars, badge progress indicator, or certificate).
- **Assets:** Star animation, confetti effect, audio applause, reward icon display
- **Engine:** None (static celebration)
- **Exit condition:** Child taps "Continue" or auto-advance after 5 seconds

### Step 10 — Revision & Next Steps

**Purpose:** Reinforce retention and guide the learner forward.

- **What happens:** A screen showing the child's progress summary. A "Revise" button if the Quick Assessment was below 70%. A "Next Lesson" button if passed.
- **Activities suggested:** If assessment was borderline (70-79%), suggest 1 revision activity before next lesson. If assessment was <70%, require revision (return to Step 4-5 activities).
- **Exit condition:** Child taps "Next Lesson", "Try Again", or "Revise"

### Educational Rationale

This 10-step sequence follows established pedagogical frameworks:

| Step | Pedagogy | Source |
|------|----------|--------|
| Intro | Advance organizer | Ausubel (1960) |
| Warm-Up | Activation of prior knowledge | Piaget, Vygotsky |
| I Do | Direct instruction / Modeling | Rosenshine (2012) |
| We Do | Scaffolded / Guided practice | Vygotsky's ZPD |
| You Do | Independent practice | Bloom's Mastery Learning |
| Check | Formative assessment | Black & Wiliam (1998) |
| Game | Play-based learning | Piaget, Froebel |
| Assess | Summative check | Bloom |
| Reward | Positive reinforcement | Skinner |
| Revision | Spaced repetition | Ebbinghaus |

### Lesson Blueprint Rules

1. Every lesson MUST follow these 10 steps in order. No skipping or reordering.
2. Steps 3-5 (I Do → We Do → You Do) represent the "gradual release of responsibility" model. This must never be violated.
3. Step 6 (Check) is a branching point. The system MUST branch to remediation if the check fails.
4. Step 8 (Quick Assessment) determines the reward and revision path. The assessment is non-optional.
5. A lesson may combine multiple activities within a single step (e.g., Step 5 could have 2-3 independent practice activities), but the step itself must be present.

---

## 3. Activity Blueprint

Every interactive learning task in the system conforms to exactly one of 12 activity blueprints. Each blueprint defines:

| Field | Description |
|-------|-------------|
| **Blueprint Name** | Unique identifier for this activity type |
| **Purpose** | What this activity type achieves for learning |
| **Learning Objective** | What the child will be able to do after this activity |
| **Child Interaction** | What the child does physically (tap, drag, trace, etc.) |
| **Input Method** | The technical input used (click, touch, drag, draw, etc.) |
| **Expected Output** | What the child produces (a selection, an arrangement, a drawing) |
| **Completion Criteria** | When the activity is considered done |
| **Success Feedback** | What happens when the child answers correctly |
| **Error Feedback** | What happens when the child answers incorrectly |
| **Retry Logic** | How many attempts, what hints are given, what happens on exhaustion |

### Blueprint 1: INTRO (Introduction / Demonstration)

| Field | Value |
|-------|-------|
| **Purpose** | Present new information or demonstrate a skill. The child watches and listens. No active response required. |
| **Learning Objective** | Child understands what they will learn and sees the correct process modeled |
| **Child Interaction** | None (passive viewing). Tap to advance after animation ends. |
| **Input Method** | Tap (for continue button only) |
| **Expected Output** | None. Child acknowledges understanding. |
| **Completion Criteria** | Animation/audio finishes AND child taps "I understand" or 3 seconds elapsed |
| **Success Feedback** | Transition animation to next step |
| **Error Feedback** | N/A — no errors possible |
| **Retry Logic** | N/A — child can replay the demonstration by tapping a "Watch Again" button |

### Blueprint 2: TAP (Single Tap Selection)

| Field | Value |
|-------|-------|
| **Purpose** | Select one correct answer from a set of options. The simplest interactive response. |
| **Learning Objective** | Child identifies the correct item by tapping it |
| **Child Interaction** | Taps one button/tile among 2-6 choices |
| **Input Method** | Click or touch on a button element |
| **Expected Output** | A single selection |
| **Completion Criteria** | Correct selection made (or max attempts exhausted) |
| **Success Feedback** | Button turns green, star animation, audio praise |
| **Error Feedback** | Button turns red briefly, audio: "Try again", hint highlight after 2nd wrong |
| **Retry Logic** | Max 3 attempts per round. After 3rd wrong, correct answer is highlighted and explained. Next round proceeds. |

**Supports subjects:** Numbers, Letters, Colors, Shapes, Animals, Fruits, Body Parts, Time, Money

### Blueprint 3: MULTI-TAP (Sequential Tap)

| Field | Value |
|-------|-------|
| **Purpose** | Tap multiple items in sequence (count objects, tap letters in order, tap body parts in sequence). |
| **Learning Objective** | Child demonstrates one-to-one correspondence or sequential ordering |
| **Child Interaction** | Taps each item one by one. Each tap advances a counter. |
| **Input Method** | Click or touch on multiple elements in sequence |
| **Expected Output** | Number of taps = number of items. Or taps in correct order. |
| **Completion Criteria** | All items tapped correctly. Or all items tapped in correct sequence. |
| **Success Feedback** | Each tap: item highlights, counter increments with audio. Final tap: celebration. |
| **Error Feedback** | Tap on already-tapped item: ignored. Tap in wrong sequence: item shakes, audio says which item to tap next. |
| **Retry Logic** | If sequence is wrong, the child must restart from the beginning (but can try indefinitely). If counting: retap resets, child tries again. |

**Supports subjects:** Numbers (counting), Letters (alphabet order), Body Parts (point and name), Time (sequence events)

### Blueprint 4: DRAG (Drag and Drop)

| Field | Value |
|-------|-------|
| **Purpose** | Move an item from one position to another. Teaches matching, sorting, and positioning. |
| **Learning Objective** | Child correctly places items into target zones |
| **Child Interaction** | Drags an item (or taps source then taps target for accessibility fallback) |
| **Input Method** | Pointer drag (or tap-to-select, tap-to-place) |
| **Expected Output** | Items correctly placed in their target zones |
| **Completion Criteria** | All items placed in correct zones |
| **Success Feedback** | Item snaps into place, audio confirms the item name, progress indicator updates |
| **Error Feedback** | Item snaps back to original position, audio: "Try a different spot" |
| **Retry Logic** | Unlimited retries per item. Child can try placing in different zones until correct. |

**Supports subjects:** Numbers (count to numeral), Shapes (match shape to name), Colors (sort by color), Animals (match to habitat), Fruits (sort by type), Body Parts (label diagram), Time (sequence cards), Money (drop coins in piggy bank)

### Blueprint 5: TRACE (Finger Tracing)

| Field | Value |
|-------|-------|
| **Purpose** | Trace a path, shape, letter, or number with the finger or pointer. Builds fine motor skills and shape/number/letter formation memory. |
| **Learning Objective** | Child reproduces the shape/letter/number by tracing its outline |
| **Child Interaction** | Draws along a guided path (on touchscreen) or follows with mouse |
| **Input Method** | Pointer movement along a path (touch drag or mouse drag) |
| **Expected Output** | A traced path that follows the template within tolerance |
| **Completion Criteria** | Path coverage ≥ 70% within the guide area |
| **Success Feedback** | Path glows, completion animation plays the shape/letter/number, audio names it |
| **Error Feedback** | If pointer leaves guide: gentle vibration, audio: "Stay on the line" |
| **Retry Logic** | Unlimited. Child can tap "Erase" and start over. After 3 failed attempts, auto-trace demonstration plays. |

**Supports subjects:** Numbers (digit formation), Letters (alphabet strokes), Shapes (outline drawing), Lines (pre-writing patterns)

### Blueprint 6: WRITE (Free Writing)

| Field | Value |
|-------|-------|
| **Purpose** | Draw, write, or form characters without a guided template. Tests recall of shape/number/letter formation. |
| **Learning Objective** | Child independently produces the correct symbol |
| **Child Interaction** | Draws freely on a canvas |
| **Input Method** | Freehand drawing (touch or mouse) |
| **Expected Output** | A drawing that matches the target symbol (assessed by shape matching or teacher review) |
| **Completion Criteria** | Automatic: shape recognition matches ≥ 60%. Teacher: manual approval. |
| **Success Feedback** | Drawing transforms into neat template, audio names it |
| **Error Feedback** | "Let's try again. Watch first." — auto-demonstration plays, canvas clears |
| **Retry Logic** | 3 attempts before auto-demo plays. Then 3 more attempts. After all exhausted, skip and flag for revision. |

**Supports subjects:** Numbers (write digits), Letters (write letters), Shapes (draw shapes), Writing readiness

### Blueprint 7: MATCH (Pair Matching)

| Field | Value |
|-------|-------|
| **Purpose** | Match items that belong together: numeral to quantity, letter to sound, shape to name, animal to sound, etc. |
| **Learning Objective** | Child correctly pairs related items |
| **Child Interaction** | Taps one item, then taps its matching partner |
| **Input Method** | Two sequential taps (tap item A, then tap item B) |
| **Expected Output** | Correct pairs formed |
| **Completion Criteria** | All pairs matched correctly |
| **Success Feedback** | Matched pair highlights with the same color, audio confirms the pairing, pair disappears or stays revealed |
| **Error Feedback** | Both items shake, unmatched items return to neutral, audio: "These do not match. Try again." |
| **Retry Logic** | Unlimited. Pairs already matched stay matched. Unmatched pairs can be retried indefinitely. After 3 wrong attempts on the same pair: hint glow on the correct match. |

**Supports subjects:** Numbers (numeral ↔ quantity), Letters (uppercase ↔ lowercase, letter ↔ sound), Colors (color ↔ name), Shapes (shape ↔ name), Animals (animal ↔ sound), Fruits (fruit ↔ name), Body Parts (part ↔ name), Time (clock ↔ time), Money (coin ↔ value)

### Blueprint 8: ORDER (Sequence Ordering)

| Field | Value |
|-------|-------|
| **Purpose** | Arrange items in a specific sequence: smallest to largest, first to last, earliest to latest. |
| **Learning Objective** | Child correctly sequences items according to a rule |
| **Child Interaction** | Rearranges items into correct positions (tap item, then tap target slot) or drags to reorder |
| **Input Method** | Tap-to-select then tap-to-place, or drag reorder |
| **Expected Output** | Items in correct sequential order |
| **Completion Criteria** | All items placed in correct sequence positions |
| **Success Feedback** | Each correct placement: slot fills, audio confirms. Final placement: celebration. |
| **Error Feedback** | Item placed in wrong slot: bounces back, audio: "Try a different spot" |
| **Retry Logic** | Unlimited. Items already correctly placed stay locked. Incorrect items can be retried. |

**Supports subjects:** Numbers (1-10 sequences, ascending/descending), Size (small to large), Time (daily routine sequence, morning→night), Money (coin value order)

### Blueprint 9: SORT (Categorization)

| Field | Value |
|-------|-------|
| **Purpose** | Group items into categories based on a shared attribute. |
| **Learning Objective** | Child correctly classifies items by a given rule |
| **Child Interaction** | Drags or taps items into category bins |
| **Input Method** | Drag (or tap item, then tap bin) |
| **Expected Output** | All items in their correct category bins |
| **Completion Criteria** | All items sorted into correct categories |
| **Success Feedback** | Each correct sort: item drops into bin, bin counter increases. All sorted: celebration. |
| **Error Feedback** | Wrong bin: item returns, bin shakes, audio: "This one goes in a different group" |
| **Retry Logic** | Unlimited. Child can retry any unsorted item. After 3 wrong attempts for the same item: highlight the correct bin. |

**Supports subjects:** Colors (sort by color), Shapes (sort by shape type), Size (sort by size), Animals (land/water/air), Fruits (color or type), Food (healthy/unhealthy)

### Blueprint 10: COMPLETE (Fill Missing / Complete Pattern)

| Field | Value |
|-------|-------|
| **Purpose** | Complete a partially filled sequence or pattern by selecting the missing element. |
| **Learning Objective** | Child identifies the missing item or next item in a pattern/sequence |
| **Child Interaction** | Taps the correct item from options to fill the gap |
| **Input Method** | Single tap from choices |
| **Expected Output** | Correct missing item selected |
| **Completion Criteria** | All gaps filled correctly |
| **Success Feedback** | Gap fills with selected item, audio confirms pattern, next gap appears (or celebration if done) |
| **Error Feedback** | Wrong selection: item shakes, audio: "Look at the pattern again" |
| **Retry Logic** | 3 attempts per gap. After 3rd wrong: correct answer fills in with explanation. Move to next gap. |

**Supports subjects:** Patterns (ABB, ABC, AABB), Numbers (missing number in sequence), Letters (missing letter in alphabet), Shapes (pattern completion)

### Blueprint 11: GAME (Play-Based Practice)

| Field | Value |
|-------|-------|
| **Purpose** | Practice skills in a fun, low-stakes game context. Time pressure is minimal. Failure is not penalized. |
| **Learning Objective** | Child applies the lesson skill in a playful context |
| **Child Interaction** | Varies by game type (tap, drag, match, sequence) — all within a game theme |
| **Input Method** | Depends on game mechanic |
| **Expected Output** | Points earned through correct answers |
| **Completion Criteria** | Game ends after time limit (60-90s) or rounds completed (3-5) |
| **Success Feedback** | Points counter increments, positive audio, mini celebrations every 3 correct answers |
| **Error Feedback** | No penalty. Correct answer is briefly shown, game continues. |
| **Retry Logic** | N/A — game always ends positively regardless of score. No retry of individual items. |

**Game variants:**
- **Memory Match:** Turn over cards to find matching pairs using lesson content
- **Speed Tap:** Tap correct answers as they appear (encourages quick recognition)
- **Drag Race:** Drag items to correct zones against a gentle timer
- **Bubble Pop:** Pop bubbles containing the correct answer
- **Treasure Hunt:** Find hidden items by answering questions

**Supports subjects:** All subjects. The game mechanic is generic; the content is injected via parameters.

### Blueprint 12: QUIZ (Formal Assessment)

| Field | Value |
|-------|-------|
| **Purpose** | Measure mastery of lesson/topic/strand content. No hints, timed per question, scored strictly. |
| **Learning Objective** | Child demonstrates mastery of the content area |
| **Child Interaction** | Answers multiple-choice or direct-input questions |
| **Input Method** | Tap (MC), drag (matching), or write (free input on higher levels) |
| **Expected Output** | Correct selections for each question |
| **Completion Criteria** | All questions answered |
| **Success Feedback** | Correct answer marks green, score updates, moves to next question |
| **Error Feedback** | Incorrect answer marks red, correct answer is NOT shown (preserves validity), moves to next question |
| **Retry Logic** | No retry on individual items. The assessment itself can be retaken after a delay (1 hour for Quick Check, 24 hours for Topic Test, 7 days for Strand Exam). |

**Assessment types (reuses QUIZ blueprint with different lengths):**
| Variant | Questions | Time | Pass % | Retry Delay |
|---------|-----------|------|--------|-------------|
| Quick Check | 2 | 30s | 70% | 1 hour |
| Lesson Check | 5 | 3 min | 70% | 4 hours |
| Topic Test | 10 | 10 min | 80% | 24 hours |
| Strand Exam | 20 | 20 min | 80% | 7 days |

---

## 4. Difficulty Framework

All activities progress through 6 difficulty levels. This framework applies uniformly across all subjects. A Level 1 activity in Numbers uses the same cognitive demand as a Level 1 activity in Shapes or Letters.

```
LEVEL 1:  EXPLORE      See and recognize
LEVEL 2:  IDENTIFY     Point to or name
LEVEL 3:  MATCH        Pair related items
LEVEL 4:  ORDER        Arrange by rule
LEVEL 5:  APPLY        Use in new context
LEVEL 6:  CREATE       Produce independently
```

### Level 1 — Explore

| Aspect | Description |
|--------|-------------|
| **Cognitive demand** | Passive exposure. See, hear, notice. |
| **Child action** | Watch an animation, listen to narration, tap to continue |
| **Activity types** | INTRO, TAP (2 choices, large targets) |
| **Number range** | 1-3 |
| **Distractors** | None or 1 obvious distractor |
| **Hints** | Full audio support, visual highlights |
| **Examples** | Watch a counting demo. See a shape and hear its name. Tap the circle when there are only 2 choices. |

### Level 2 — Identify

| Aspect | Description |
|--------|-------------|
| **Cognitive demand** | Recognition. Point to the correct answer from a set. |
| **Child action** | Tap the correct item among 2-4 choices |
| **Activity types** | TAP, MULTI-TAP |
| **Number range** | 1-5 |
| **Distractors** | 2-3 obvious distractors |
| **Hints** | Audio prompt, repeat button |
| **Examples** | "Find the number 3" from 4 numbers. "Which one is a triangle?" from 4 shapes. "Tap the red apple" from 3 fruits. |

### Level 3 — Match

| Aspect | Description |
|--------|-------------|
| **Cognitive demand** | Association. Connect two related items. |
| **Child action** | Pair items that go together (tap A then B, or drag) |
| **Activity types** | MATCH, DRAG, COMPLETE |
| **Number range** | 1-5 |
| **Distractors** | Related but incorrect items |
| **Hints** | Visual cue on the first pair |
| **Examples** | Match numeral 3 to a group of 3 stars. Match the letter "A" to its picture. Match the shape to its name. |

### Level 4 — Order

| Aspect | Description |
|--------|-------------|
| **Cognitive demand** | Sequencing. Arrange items in a logical order. |
| **Child action** | Drag or tap-to-place items in sequence |
| **Activity types** | ORDER, DRAG, COMPLETE |
| **Number range** | 1-10 |
| **Distractors** | Out-of-order items |
| **Hints** | Highlight the first item in the sequence |
| **Examples** | Arrange numbers 1-5 in order. Sequence daily activities (wake up → eat breakfast → go to school). Order objects from smallest to largest. |

### Level 5 — Apply

| Aspect | Description |
|--------|-------------|
| **Cognitive demand** | Application. Use the skill in a new or slightly unfamiliar context. |
| **Child action** | Solve problems that require combining skills, or apply a skill to a new situation |
| **Activity types** | TAP, DRAG, MATCH, GAME |
| **Number range** | 1-20 (or age-appropriate) |
| **Distractors** | Plausible wrong answers |
| **Hints** | Minimal — encouragement only |
| **Examples** | "You have 3 apples. I give you 2 more. How many now?" (addition). "Sort these animals by how many legs they have." |

### Level 6 — Create

| Aspect | Description |
|--------|-------------|
| **Cognitive demand** | Production. Independently produce the correct answer without choices. |
| **Child action** | Write, draw, speak, or build the answer |
| **Activity types** | WRITE, TRACE, ORDER |
| **Number range** | 1-20+ |
| **Distractors** | N/A — no choices given |
| **Hints** | None. Answer is evaluated after completion. |
| **Examples** | Write the number 5. Draw a triangle. Write the first letter of "Apple". Count aloud to 10. |

### Difficulty Mapping per Subject

| Level | Numbers | Letters | Shapes | Colors | Animals | Time | Money |
|-------|---------|---------|--------|--------|---------|------|-------|
| 1 Explore | See numbers 1-3 | See letter A | See circle | See red | See cow | See sun/moon | See coin |
| 2 Identify | Tap number 3 | Tap letter A | Tap circle | Tap red item | Tap the cow | Tap "day" | Tap coin worth 1 |
| 3 Match | 3 ↔ three stars | A ↔ apple | Circle ↔ name | Red ↔ red block | Cow ↔ "cow" | Sun ↔ daytime | 1 coin ↔ 1 value |
| 4 Order | 1,2,3,4,5 | A,B,C | Small→large circle | Light→dark | Calf→cow (grow) | Morning→night | 1,5,10,20 coins |
| 5 Apply | Add 2+3 | First letter of "ball" | Count shape sides | Mix to make new | Sort by habitat | Read clock (hour) | Buy item with coins |
| 6 Create | Write number 5 | Write letter B | Draw triangle | Paint red object | Draw a cow | Draw clock face | Count money total |

### Difficulty Rules

1. **Every activity must declare its difficulty level** (1-6) in its activity_params.
2. **Lessons start at Level 1-2** and progress to Level 3-4 by the end.
3. **Challenge activities (Level 6)** are unlocked only after the base activity is mastered.
4. **Assessments** are typically at the same level as the lesson's content (Level 1-4). They never exceed Level 4.
5. **Difficulty and age are correlated but not identical.** A Level 1 activity for a 5-year-old is different from a Level 1 activity for a 3-year-old. The difficulty level refers to cognitive demand, not age appropriateness. Age parameters are set at the Topic level.

---

## 5. Reusable Activity Engines

The system uses exactly 12 reusable engines. Each engine can power multiple activity types across multiple subjects by accepting different parameters. No duplicated logic: if a game mechanic is the same, it uses the same engine.

### Engine 1: TAP_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Present a question with multiple choices. Child taps one correct answer. |
| **How it works** | Displays a prompt and 2-6 choice buttons. Waits for a tap. Validates against the correct answer. |
| **Input** | `{ prompt, choices: [{ label, value, emoji? }], correctValue, difficulty, subject }` |
| **Output** | `{ selectedValue, isCorrect, attempts }` |

**Supports these activity blueprints:** TAP, MULTI-TAP (sequential mode)

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | "Find number 5" — choices: 3, 5, 7, 2 |
| Letters | "Find letter A" — choices: A, B, C, D |
| Colors | "Which is red?" — choices: red, blue, green, yellow |
| Shapes | "Find the circle" — choices: circle, square, triangle |
| Animals | "Find the cow" — choices: cow, dog, cat, bird |
| Fruits | "Find the apple" — choices: apple, banana, orange, grapes |
| Body Parts | "Find the nose" — choices: nose, eye, ear, mouth |
| Time | "Which shows morning?" — choices: sun, moon, stars |
| Money | "Which coin is worth 1?" — choices: 1, 5, 10, 20 |

### Engine 2: MULTI_TAP_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Tap multiple items in a defined sequence (counting, ordering, spelling). |
| **How it works** | Displays an array of tappable items. Each tap increments a counter or checks sequence position. |
| **Input** | `{ items: [{ id, label, emoji }], mode: 'count' | 'sequence', correctSequence?: [], targetCount?: number }` |
| **Output** | `{ tapCount, sequence: [], isComplete }` |

**Supports these activity blueprints:** MULTI-TAP

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | Tap each star while counting to 5 |
| Letters | Tap letters A, B, C in order |
| Body Parts | Tap head, shoulders, knees, toes in song order |
| Time | Tap the sequence: wake up, eat breakfast, go to school |

### Engine 3: DRAG_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Drag items to target positions. |
| **How it works** | Displays draggable items and target zones. When an item is dropped on a zone, it snaps if correct or bounces back if incorrect. |
| **Input** | `{ items: [{ id, label, emoji, targetZoneId }], zones: [{ id, label, acceptsItems }], mode: 'snap' | 'free' }` |
| **Output** | `{ placedItems: [], remainingItems: [], isComplete }` |

**Supports these activity blueprints:** DRAG, SORT, ORDER, MATCH

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | Drag 3 apples to the box labeled "3" |
| Shapes | Drag shapes into matching outline slots |
| Colors | Drag colored items into color bins |
| Animals | Drag animals to their habitats (land, water, sky) |
| Fruits | Drag fruits into their color groups |
| Body Parts | Drag labels to body parts on a diagram |
| Time | Drag daily activities into sequence slots |
| Money | Drag coins into piggy bank (counting money) |
| Letters | Drag letters to form a word |

### Engine 4: TRACE_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Guide the child to trace a shape, letter, or number along a path. |
| **How it works** | Displays a stencil/template. Child draws on or near the path. Path coverage is calculated. |
| **Input** | `{ templateType: 'shape' | 'letter' | 'number' | 'line', templateValue: string, strokeWidth: number, guideOpacity: number }` |
| **Output** | `{ coverage: number (0-100), averageDeviation: number, isComplete }` |

**Supports these activity blueprints:** TRACE

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | Trace the number 2 |
| Letters | Trace the letter A |
| Shapes | Trace a circle |
| Lines | Trace straight, curved, and zigzag lines (pre-writing) |

### Engine 5: WRITE_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Freehand drawing area for producing numbers, letters, or shapes without a guide. |
| **How it works** | Shows a blank canvas with a prompt. Child draws. The drawing is compared to the target via a lightweight shape-matching algorithm. |
| **Input** | `{ expectedType: 'number' | 'letter' | 'shape', expectedValue: string, matchingThreshold: number (default 60) }` |
| **Output** | `{ matchPercentage: number, drawnImageData: string (base64), isComplete }` |

**Supports these activity blueprints:** WRITE

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | Write the number 5 from memory |
| Letters | Write the letter B from memory |
| Shapes | Draw a triangle from memory |

### Engine 6: MATCH_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Match pairs of related items. |
| **How it works** | Two columns of items (or a memory grid). Child selects one item then its match. Correct pairs lock. |
| **Input** | `{ pairs: [{ itemA: { id, label, emoji }, itemB: { id, label, emoji } }], mode: 'column' | 'grid' | 'memory' }` |
| **Output** | `{ matchedPairs: [], attempts: number, isComplete }` |

**Supports these activity blueprints:** MATCH, COMPLETE (matching mode)

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | Match numeral 3 to three stars |
| Letters | Match uppercase A to lowercase a |
| Colors | Match color swatch to color name |
| Shapes | Match shape image to shape name |
| Animals | Match animal to its sound |
| Fruits | Match fruit image to fruit name |
| Body Parts | Match body part to its function |
| Time | Match clock face to written time |
| Money | Match coin to its value |

### Engine 7: ORDER_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Arrange items in a specified sequence. |
| **How it works** | Displays unsorted items and an equal number of slots. Child places items into slots in the correct order. |
| **Input** | `{ items: [{ id, label, emoji, correctPosition }], sequenceRule: 'ascending' | 'descending' | 'custom', hintFirst?: boolean }` |
| **Output** | `{ placements: [{ itemId, position, correct }], isComplete }` |

**Supports these activity blueprints:** ORDER

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | Order 1, 2, 3, 4, 5 |
| Size | Order objects from smallest to largest |
| Time | Order daily activities |
| Money | Order coins from lowest to highest value |
| Letters | Order letters A, B, C, D |

### Engine 8: SORT_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Categorize items into groups based on an attribute. |
| **How it works** | Shows items and category bins. Child drags/apportions items to bins. |
| **Input** | `{ items: [{ id, label, emoji, category }], categories: [{ id, label, emoji, color }], mode: 'drag' | 'tap' }` |
| **Output** | `{ sorted: [{ itemId, categoryId, correct }], isComplete }` |

**Supports these activity blueprints:** SORT

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Colors | Sort red/blue/yellow objects |
| Shapes | Sort circles/squares/triangles |
| Size | Sort small/medium/large |
| Animals | Sort land animals vs water animals |
| Food | Sort healthy vs unhealthy food |
| Letters | Sort vowels vs consonants |

### Engine 9: COMPLETE_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Fill in a missing element in a pattern, sequence, or equation. |
| **How it works** | Shows a sequence/pattern with one or more gaps. Child selects the correct item from options to fill each gap. |
| **Input** | `{ pattern: string[], gaps: number[], choices: string[], correctChoice: string, patternType: 'repeat' | 'sequence' | 'equation' }` |
| **Output** | `{ filledGaps: [{ position, selectedValue, correct }], isComplete }` |

**Supports these activity blueprints:** COMPLETE

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Patterns | Complete ABAB pattern: ○□○□? |
| Numbers | Fill missing number: 1, 2, ?, 4, 5 |
| Letters | Fill missing letter: A, B, ?, D |
| Operations | Fill answer: 2 + 3 = ? |

### Engine 10: GAME_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Wrap any skill in a game context for playful reinforcement. |
| **How it works** | Reads game type parameter and delegates to the correct sub-mode (memory, speed tap, drag race, bubble pop, treasure hunt). Injects lesson content as the game content. |
| **Input** | `{ gameType: 'memory' | 'speedtap' | 'dragrace' | 'bubblepop' | 'treasure', contentConfig: items from lesson, timeLimit: number, roundCount: number }` |
| **Output** | `{ score: number, roundsComplete: number, itemsCorrect: number }` |

**Supports these activity blueprints:** GAME

**Can teach these subjects:** All subjects. The game is agnostic; content is parameterized.

### Engine 11: OPS_ENGINE (Operations Engine)

| Field | Value |
|-------|-------|
| **Purpose** | Present arithmetic or logic operations (add, subtract, compare, etc.) with visual support. |
| **How it works** | Shows an operation with visual objects (apples, stars, blocks). Child manipulates objects or selects the answer. |
| **Input** | `{ operation: 'add' | 'subtract' | 'compare' | 'equate', operandA: number, operandB: number, visualObject: string, mode: 'manipulate' | 'select' }` |
| **Output** | `{ userAnswer, correctAnswer, attempts, isComplete }` |

**Supports these activity blueprints:** TAP, DRAG (with operation context)

**Can teach these subjects:**

| Subject | Example |
|---------|---------|
| Numbers | 3 + 2 = ? (with apple visuals) |
| Comparison | Which is more? 5 apples or 3 apples |
| Money | 1 coin + 2 coins = ? |
| Measurement | Which is taller? (compare two objects) |

### Engine 12: QUIZ_ENGINE

| Field | Value |
|-------|-------|
| **Purpose** | Present a series of assessment questions. No hints. Timed. Scored. |
| **How it works** | Reads a question bank (from lesson/topic/strand). Presents one question at a time. Records answers. Calculates score. |
| **Input** | `{ questions: [{ prompt, type: 'mc' | 'match' | 'order', choices: [], correctAnswer, timeLimit }], shuffleQuestions: boolean, shuffleChoices: boolean }` |
| **Output** | `{ score: number, total: number, answers: [{ questionId, selected, correct, timeSpent }], percentage: number, passed: boolean }` |

**Supports these activity blueprints:** QUIZ

**Can teach these subjects:** All subjects. Questions are parameterized per assessment type.

### Engine Summary Table

| # | Engine | Blueprints | Subjects | Reuse Factor |
|---|--------|-----------|----------|-------------|
| 1 | TAP_ENGINE | TAP | All | High |
| 2 | MULTI_TAP_ENGINE | MULTI-TAP | Numbers, Letters, Body, Time | Medium |
| 3 | DRAG_ENGINE | DRAG, SORT, ORDER, MATCH | All | Very High |
| 4 | TRACE_ENGINE | TRACE | Numbers, Letters, Shapes | Medium |
| 5 | WRITE_ENGINE | WRITE | Numbers, Letters, Shapes | Medium |
| 6 | MATCH_ENGINE | MATCH, COMPLETE | All | Very High |
| 7 | ORDER_ENGINE | ORDER | Numbers, Size, Time, Money | Medium |
| 8 | SORT_ENGINE | SORT | Colors, Shapes, Animals, Food | Medium |
| 9 | COMPLETE_ENGINE | COMPLETE | Patterns, Numbers, Letters | Medium |
| 10 | GAME_ENGINE | GAME | All | Very High |
| 11 | OPS_ENGINE | TAP, DRAG | Numbers, Money, Comparison | Medium |
| 12 | QUIZ_ENGINE | QUIZ | All | High |

**Reuse rules:**
1. No two engines may implement the same core mechanic.
2. If a new subject is added, it must use existing engines before creating new ones.
3. New engines are only created when an entirely new interaction mode is required (e.g., voice recording, camera-based recognition).
4. Engines are configured entirely via their input parameters. No hardcoded subject logic lives inside an engine.

---

## 6. Asset Blueprint

Every activity requires a specific set of assets. This section defines what assets are needed per activity type, how they are organized, and the naming conventions.

### Asset Types

| Type | Code | Format | Description |
|------|------|--------|-------------|
| Image | `img` | SVG, PNG, WebP | Illustrations, icons, backgrounds |
| Audio Instruction | `aud_instr` | MP3, OGG | Voice-over explaining the activity |
| Audio Feedback | `aud_fb` | MP3, OGG | Positive/negative response sounds |
| Audio Narration | `aud_narr` | MP3, OGG | Story/narration for demonstrations |
| Sound Effect | `sfx` | MP3, WAV | Button taps, completion, transitions |
| Background Music | `bgm` | MP3, OGG | Looping background music per theme |
| Animation | `anim` | Lottie JSON, CSS, SVG | Movement, transitions, celebrations |
| Illustration | `illus` | SVG, PNG | Scene illustrations (farm, market, classroom) |
| Reward Asset | `reward` | SVG, PNG | Stars, badges, certificates, coins |

### Assets Required per Activity Blueprint

| Blueprint | img | aud_instr | aud_fb | aud_narr | sfx | bgm | anim | illus | reward |
|-----------|-----|-----------|--------|----------|-----|-----|------|-------|--------|
| **INTRO** | Required | Required | Optional | Required | Optional | Optional | Required | Required | Optional |
| **TAP** | Required | Required | Required | Optional | Required | Optional | Optional | Optional | At end |
| **MULTI-TAP** | Required | Required | Required | Optional | Required | Optional | Optional | Optional | At end |
| **DRAG** | Required | Required | Required | Optional | Required | Optional | Optional | Optional | At end |
| **TRACE** | Required | Required | Required | Optional | Required | Optional | Required | Optional | At end |
| **WRITE** | Required | Required | Required | Optional | Required | Optional | Optional | Optional | At end |
| **MATCH** | Required | Required | Required | Optional | Required | Optional | Optional | Required | At end |
| **ORDER** | Required | Required | Required | Optional | Required | Optional | Optional | Optional | At end |
| **SORT** | Required | Required | Required | Optional | Required | Optional | Optional | Required | At end |
| **COMPLETE** | Required | Required | Required | Optional | Required | Optional | Optional | Optional | At end |
| **GAME** | Required | Required | Required | Optional | Required | Required | Required | Required | At end |
| **QUIZ** | Optional | Required | Required | Optional | Required | Optional | Optional | Optional | At end |

### Asset Naming Convention

Every asset filename follows this structure:

```
{language}_{subject}_{strandCode}_{topicCode}_{lessonCode}_{activityCode}_{type}_{variant}.{ext}
```

**Example:**

```
en_number_NUM-01_1A-L02-A03_img_circle.png
en_number_NUM-01_1A-L02-A03_aud_instr.mp3
sw_number_NUM-01_1A-L02-A03_aud_instr.mp3
```

**Breakdown:**

| Segment | Example | Notes |
|---------|---------|-------|
| language | `en`, `sw` | ISO 639-1 code |
| subject | `number`, `letter`, `shape`, `color` | Lowercase subject name |
| strandCode | `NUM-01` | From hierarchy Level 2 |
| topicCode | `1A` | From hierarchy Level 3 |
| lessonCode | `L02` | From hierarchy Level 4 |
| activityCode | `A03` | From hierarchy Level 5 |
| type | `img`, `aud_instr`, `sfx` | From asset types table |
| variant | `circle`, `correct`, `bg-farm` | Specific to this asset |

### Asset Organization

```
assets/
├── en/                          # English assets
│   └── number/
│       └── NUM-01/
│           └── 1A/
│               └── L02/
│                   ├── A03/
│                   │   ├── img_circle.svg
│                   │   ├── aud_instr.mp3
│                   │   ├── sfx_tap.mp3
│                   │   └── anim_complete.json
│                   └── A04/
│                       └── ...
├── sw/                          # Swahili assets (audio only for same visuals)
│   └── number/
│       └── NUM-01/
│           └── 1A/
│               └── L02/
│                   └── A03/
│                       ├── aud_instr.mp3
│                       └── aud_fb_correct.mp3
├── shared/                      # Shared across languages
│   ├── images/
│   │   ├── backgrounds/
│   │   ├── characters/
│   │   └── rewards/
│   ├── audio/
│   │   ├── sfx/                 # Sound effects (language-independent)
│   │   └── bgm/                 # Background music
│   └── animations/
│       ├── confetti.json
│       ├── star.json
│       └── celebration.json
└── rewards/
    ├── stars/
    ├── badges/
    └── certificates/
```

### Asset Creation Rules

1. **Visual assets first in SVG** where possible (scalable, small file size). PNG/WebP for photographs or complex illustrations.
2. **Audio assets in both EN and SW** for all instruction and narration. Feedback sounds (correct/incorrect) can use the same audio in both languages or language-specific.
3. **Shared assets never duplicated.** If the same image is used in multiple lessons, it goes under `shared/images/` and is referenced by name.
4. **Background music per theme domain.** One BGM per domain (e.g., Mathematics gets a playful counting melody, Language gets an alphabet song instrumental).
5. **SFX are consistent.** The same correct-answer sound, incorrect-answer sound, and completion fanfare are used everywhere.
6. **Each asset file must be under 500KB.** Larger files must be compressed or split.
7. **Lottie JSON** for complex animations (celebrations, demonstrations). CSS animations for simple effects (bounce, fade).

---

## 7. Assessment Blueprint

Assessment is built into the content hierarchy at 4 levels. It is not an afterthought — every lesson, topic, and strand has a defined assessment component.

### Assessment Types

| Type | Code | When | Duration | Questions | Format | Pass % | Retry Delay |
|------|------|------|----------|-----------|--------|--------|-------------|
| Quick Check | `QC` | After activity group (every 2-3 activities within a lesson) | 30s | 2 | MC (2 choices) | 70% | 1 hour |
| Lesson Check | `LC` | End of each lesson | 3 min | 5 | MC (2-3 choices) | 70% | 4 hours |
| Topic Test | `TT` | End of each topic | 10 min | 10 | MC + Match + Order | 80% | 24 hours |
| Strand Exam | `SE` | End of each strand | 20 min | 20 | All formats | 80% | 7 days |

### When Assessment Happens

```
LESSON FLOW:
  Activity 1 → Activity 2 → [QUICK CHECK] → Activity 3 → Activity 4 → [QUICK CHECK] → [LESSON CHECK]
  
TOPIC FLOW:
  Lesson 1 → Lesson 2 → ... → Lesson N → [TOPIC TEST]
  
STRAND FLOW:
  Topic 1 → Topic 2 → ... → Topic N → [STRAND EXAM]
```

- **Quick Check** occurs after every 2-3 activities within a lesson. If the lesson has 6 activities (e.g., Steps 2-5 in the lesson blueprint), there are 2 Quick Checks.
- **Lesson Check** is Step 8 of the lesson blueprint. It is mandatory.
- **Topic Test** is accessed from the topic completion screen. It pools questions from all lessons in that topic.
- **Strand Exam** is accessed from the strand completion screen. It pools questions from all topics in that strand.

### Assessment Format Rules

| Assessment | MC (2 choices) | MC (3 choices) | Match | Order | Time per Q |
|------------|---------------|---------------|-------|-------|-----------|
| Quick Check | 100% | 0% | 0% | 0% | 10 seconds |
| Lesson Check | 60% | 40% | 0% | 0% | 15 seconds |
| Topic Test | 30% | 40% | 20% | 10% | 20 seconds |
| Strand Exam | 20% | 30% | 30% | 20% | 30 seconds |

### Mastery Criteria

| Level | Mastery | Pass | Needs Revision |
|-------|---------|------|----------------|
| Quick Check | 100% (both correct) | 70%+ (1+ correct) | < 70% (0 correct) |
| Lesson Check | 100% (all 5 correct) | 70%+ (4+ correct) | < 70% (0-3 correct) |
| Topic Test | 90%+ | 80%+ | < 80% |
| Strand Exam | 90%+ | 80%+ | < 80% |

### What Happens at Each Outcome

**Mastery (child scored ≥ 90% or all correct):**
- Reward animation (big celebration)
- Unlock next content
- Record "mastered" in progress
- Schedule revision in 30 days (spaced repetition, Level 9)

**Pass (child scored between pass % and mastery):**
- Reward animation (standard celebration)
- Unlock next content
- Record "passed" in progress
- Schedule revision in 7 days

**Needs Revision (child scored below pass %):**
- Encouragement message: "Great effort! Let's practice a bit more."
- Do NOT unlock next content
- Return to remediation (guided practice activities from the same lesson)
- Offer "Try Again" on the assessment after 1 hour (Quick Check) / 4 hours (Lesson Check)
- After 3 failed attempts: recommend teacher/parent intervention
- Record "needs_revision" in progress

### Progress Tracking

The progress table records per-activity, per-assessment, and per-revision data:

```
progress:
  user_id          → learner
  content_type     → 'activity' | 'assessment' | 'revision'
  content_id       → activity_id / assessment_id / lesson_id
  status           → 'not_started' | 'in_progress' | 'passed' | 'mastered' | 'needs_revision'
  score            → 0-100
  attempts         → number of attempts
  max_score        → highest score achieved
  time_spent_sec   → total seconds
  last_attempt_at  → timestamp
  next_revision_at → timestamp (for spaced repetition)
  completed_at     → timestamp
```

### Assessment Security Rules

1. **Questions are randomized.** The order of questions and choices must be shuffled per attempt.
2. **Answers are never revealed during the assessment.** Incorrect answers are marked red but the correct answer is not shown. This preserves the validity of future attempts.
3. **Results are shown only after submission.**
4. **Timer is enforced per question.** If time expires, the question is marked incorrect and the next question loads.
5. **No retry on individual items.** The entire assessment must be retaken.
6. **Retry delay is enforced server-side.** The child cannot bypass the delay by refreshing or logging out.

---

## 8. Reward Blueprint

Rewards follow a 3-tier hierarchy: Stars → Badges → Certificates. Every activity, lesson, topic, and strand completion triggers the appropriate reward.

### Tier 1: Stars

| Aspect | Detail |
|--------|--------|
| **Source** | Earned per activity completion |
| **Scale** | 1 star (passed: 70-89%), 2 stars (excellent: 90-99%), 3 stars (perfect: 100%) |
| **Visual** | Small gold star icon with sparkle animation |
| **Audio** | "You earned a star!" or number callout for multiple stars |
| **Persistence** | Total star count displayed on learner dashboard |
| **Primary purpose** | Immediate, frequent positive reinforcement |

**Star accumulation rules:**
- Stars never decrease (no penalty for retries)
- Stars are additive across all activities
- Total stars = sum of all stars earned across all activities
- Star milestones: 10, 25, 50, 100, 200, 500 (triggers badge at each milestone)

### Tier 2: Badges

| Aspect | Detail |
|--------|--------|
| **Source** | Earned for achieving milestones |
| **Scale** | Fixed — each badge is earned once |
| **Visual** | Full-color badge icon on a medal/ribbon background |
| **Audio** | Fanfare sound, "Congratulations! You earned the [Badge Name] badge!" |
| **Animation** | Badge flips and shines on first award |
| **Persistence** | Displayed on learner profile and parent dashboard |

**Badge types:**

| Badge | Trigger | Icon |
|-------|---------|------|
| First Star | Earn first star | ⭐ |
| Star Collector | Earn 10 stars | 🌟 |
| Star Champion | Earn 50 stars | 🏅 |
| Perfect Score | Perfect (3 stars) on any activity | 💎 |
| Lesson Star | Mastery on any Lesson Check | 🌠 |
| Topic Master | Pass all lessons in a topic + Topic Test | 📜 |
| Strand Champion | Complete all topics in a strand + Strand Exam | 🏆 |
| Counting Star | Complete all counting activities | 🔢 |
| Shape Wizard | Complete all shape activities | 🔷 |
| Math Explorer | Complete any 3 topics | 🗺️ |
| Math Genius | Complete all Mathematics content | 👑 |
| Night Owl | Learn after 6 PM (encourages flexible schedules) | 🦉 |
| Streak Master | Log in 7 days in a row | 🔥 |
| Helper Badge | Parent/teacher marks "helped sibling/friend" | 🤝 |

**Badge display rules:**
- Unearned badges are shown as silhouettes (greyed out) to motivate
- Newly earned badges get a "NEW!" badge overlay for 48 hours
- Badges are always visible to the learner and parent

### Tier 3: Certificates

| Aspect | Detail |
|--------|--------|
| **Source** | Earned for completing entire strands |
| **Scale** | One per strand completed with Mastery |
| **Visual** | Full-page printable certificate with: child's name, strand name, date, star count, teacher/parent signature line |
| **Audio** | Extended celebration, "You completed [Strand Name]! Amazing work!" |
| **Animation** | Certificate unfurls on screen |
| **Persistence** | Downloadable PDF, displayed in learner portfolio |

**Certificate types:**

| Certificate | Requirement | Includes |
|-------------|-------------|----------|
| Foundation Numbers | Complete NUM strand with ≥80% overall | Number range, star count, date |
| Shape Explorer | Complete GEO strand with ≥80% overall | Shape list, star count, date |
| Early Math Champion | Complete ALL Mathematics strands | All topics, total stars, final score |
| Future certificates | Per strand/domain as platform expands | Custom per domain |

### Reward Animations

Every reward triggers a layered celebration:

| Reward Level | Animation | Audio | Duration |
|-------------|-----------|-------|----------|
| 1 star | Small sparkle, star rises from center | "Good job!" | 1.5s |
| 2 stars | Star burst, 2 stars spiral out | "Excellent work!" | 2s |
| 3 stars | Full confetti, 3 stars with glow | "Perfect! Amazing!" | 3s |
| Badge | Badge flip animation, ribbon unfurl | Fanfare + badge name | 4s |
| Certificate | Certificate unfurls, sparkle border | Extended fanfare | 5s |

### Currency System (Coins)

Optional secondary reward for motivation:

| Aspect | Detail |
|--------|--------|
| **Source** | 1 coin per activity completion, 5 coins per lesson pass, 10 coins per perfect score |
| **Use** | Future: spend on customizing avatar, unlocking bonus games, or "surprise" rewards |
| **Visual** | Gold coin with denomination |
| **Display** | Coin purse icon on dashboard |

Coins are secondary to stars/badges/certificates. They exist purely as additional motivation for older learners (Std 1-2). Young learners (Pre-Primary) primarily use stars and badges.

### Reward Rules

1. **Every completed activity earns at least 1 star.** No activity is rewardless.
2. **Rewards are immediate.** The celebration plays right after the activity/assessment ends.
3. **Rewards are cumulative.** Stars add up. Badges and certificates are permanent.
4. **No reward reduction.** Stars are never deducted, even on retry.
5. **Badges are earned once.** Repeating content cannot re-earn a badge.
6. **Certificates are re-printable.** If a child improves their score, a new certificate with the higher score can be generated.

---

## 9. Revision Blueprint

Revision is an automatic, data-driven process. The system does not wait for the child or teacher to decide when to revise — it uses performance data to schedule and trigger revision automatically.

### When Revision is Triggered

| Trigger | Condition | Action |
|---------|-----------|--------|
| **Assessment Fail** | Quick Check < 70% | Immediate remediation (return to guided practice within the lesson) |
| **Lesson Fail** | Lesson Check < 70% | Block next lesson. Offer retry after 4 hours. Require revision activities. |
| **Topic Fail** | Topic Test < 80% | Block next topic. Schedule revision session (all lessons in topic). |
| **Strand Fail** | Strand Exam < 80% | Block next strand (if any). Comprehensive revision plan. |
| **Low Score** | Activity score < 70% | Flag activity for revision. Suggest replay. |
| **Scheduled Revision** | Time elapsed since last attempt | Automatic revision prompt based on spaced repetition schedule. |

### Spaced Repetition Schedule

When a child achieves Pass or Mastery on any content, the system schedules a future revision using this schedule:

| Attempt | Interval | Format |
|---------|----------|--------|
| Initial learning | Day 0 | Full lesson (10 steps) |
| First revision | +1 day | Quick Check + 2 revision activities |
| Second revision | +3 days | Quick Check + 1 revision activity |
| Third revision | +7 days | Lesson Check only |
| Fourth revision | +14 days | 3 random questions |
| Fifth revision | +30 days | 2 random questions |
| Maintenance | +90 days | 1 random question |

**If any revision attempt scores < 70%, the schedule resets to Day 0** (full lesson redo).

**If all revision attempts score ≥ 80%, the content is marked as "mastered permanently"** and enters maintenance mode (1 question every 90 days).

### Revision Activities

When revision is triggered, the system selects appropriate activities:

| Situation | Activities |
|-----------|-----------|
| Quick Check fail | Repeat the 2 activities before the Quick Check |
| Lesson Check fail | Repeat Step 4 (We Do) and Step 5 (You Do) activities from the lesson |
| Topic Test fail | One representative activity from each lesson in the topic |
| Scheduled revision | One random activity from the content being revised |

### How Revision is Presented

Revision is never presented as punishment. The language is always positive:

- "Let's practice again to make it even better!"
- "You're doing great! Let's try one more time."
- "I know you can do this! Let's try together."

**Revision screen:** Shows a friendly character saying "Ready to practice more?" with options:
- "Yes, let's practice" → starts revision activities
- "Not now" → returns to dashboard, schedules reminder for next session

### Remediation vs Revision

| Aspect | Remediation | Revision |
|--------|-------------|----------|
| **When** | Immediately after assessment fail | Scheduled later (spaced repetition) |
| **Goal** | Fix misunderstanding right now | Reinforce long-term retention |
| **Format** | Guided (hints available) | Independent (no hints) |
| **Duration** | 2-5 minutes | 1-3 minutes |
| **Trigger** | Assessment score < pass% | Time elapsed since last successful attempt |

### Mastery Reinforcement

When a child achieves Mastery (≥90%) on an assessment:

1. The content is entered into the spaced repetition schedule
2. The first revision is scheduled for +7 days (not +1 day, since mastery is higher)
3. The child is offered a **Challenge** (Level 5-6 activity variant) for deeper learning
4. If the child completes the Challenge successfully, the first revision is pushed to +14 days

### Data-Driven Revision

The revision system uses these data points:

| Data Point | Source | Used For |
|-----------|--------|----------|
| Score per activity | progress table | Identify weak activities |
| Score per assessment | progress table | Identify weak lessons/topics |
| Attempts | progress table | Identify persistently difficult content |
| Time spent | progress table | Identify rushed vs careful attempts |
| Pattern of errors | assessment answer log | Identify specific skill gaps |
| Streak | login/log table | Identify disengaged learners |

### Revision Rules

1. **Revision never introduces new content.** It only revisits previously taught material.
2. **Revision is mandatory after assessment failure.** The child cannot bypass it.
3. **Scheduled revision is optional.** The system prompts, but the child can postpone.
4. **Revision activities are shorter** than the original activities (fewer items, no demonstration).
5. **Revision is always free.** Even on subscription platforms, revision activities are not behind a paywall.

---

## 10. Expansion Rules

This section defines strict rules for adding new content in the future. Every new activity, lesson, topic, strand, or domain must follow these rules. No content is added outside this framework.

### Rule 1: Hierarchy Compliance

**Any new content must fit into exactly one level of the hierarchy.** It cannot span levels or sit outside the hierarchy.

- A new domain → Level 1
- A new strand → Level 2, under an existing domain
- A new topic → Level 3, under an existing strand
- A new lesson → Level 4, under an existing topic
- A new activity → Level 5, inside an existing lesson

**Violation:** Creating an "Addition & Shapes" activity that is neither in the Number strand nor the Geometry strand.

### Rule 2: Lesson Blueprint Compliance

**Every new lesson must follow the 10-step lesson blueprint.** No shortcuts. No skipped steps.

- If creating a "Color Mixing" lesson for Science, it must still follow: Intro → Warm-Up → I Do → We Do → You Do → Check → Game → Assess → Reward → Revision.
- The content changes, the structure does not.

### Rule 3: Activity Blueprint Compliance

**Every new activity must conform to one of the 12 activity blueprints.** No custom activity types outside the blueprints.

- If a "Voice Recording" activity is needed, it must either use an existing blueprint (e.g., TAP for selection + voice response) or a new blueprint must be proposed and approved through Rule 7.
- **Violation:** Creating a "Spin the Wheel" activity that does not fit TAP, MULTI-TAP, DRAG, TRACE, WRITE, MATCH, ORDER, SORT, COMPLETE, GAME, QUIZ, or INTRO.

### Rule 4: Engine Reuse First

**Before creating a new engine, prove that existing engines cannot support the new content.**

- New content in "Social Skills" (e.g., "Identify the happy face") → use TAP_ENGINE (choices of emoji faces)
- New content in "Science" (e.g., "Sort animals by habitat") → use SORT_ENGINE
- If TAP_ENGINE with emoji choices works, do NOT create a "Social Skills Engine"

**Process for new content:**
1. Define the activity using one of the 12 blueprints
2. Check if an existing engine can be configured to support it (via parameters)
3. If yes, use the existing engine
4. If no, propose a new engine through the Engine Exception process (Rule 7)

### Rule 5: Difficulty Progression

**New content must follow the 6-level difficulty framework.** Every activity must be assigned a difficulty level (1-6).

- Lesson 1 in a new topic: Level 1-2 activities
- Lesson 3 in a new topic: Level 3 activities
- Lesson 5 in a new topic: Level 4 activities
- Challenges: Level 5-6

### Rule 6: Assessment Integration

**Every new lesson must include at least one Quick Check and one Lesson Check.** Every new topic must include a Topic Test. Every new strand must include a Strand Exam.

- No lesson is complete without assessment.
- The assessment must use the QUIZ_ENGINE (no custom assessment pages).

### Rule 7: The 3-Gate Exception Process

For any content that genuinely cannot fit into the existing framework:

**Gate 1 — Hierarchy Exception:**
If a new content item does not fit within the 8-level hierarchy, propose a modification to the hierarchy that also accommodates all existing content. The modification must not break existing hierarchy relationships.

**Gate 2 — Blueprint Exception:**
If a new interaction mode is needed that does not match any of the 12 blueprints, create a detailed proposal that includes:
- The new blueprint's full spec (all 10 fields from Section 3)
- At least 3 different subjects it would apply to
- Why existing blueprints cannot be configured to achieve the same effect

**Gate 3 — Engine Exception:**
If a new engine must be created:
- It must support at least 3 different subjects
- It must not overlap with any existing engine's mechanics
- Full documentation must be written before any code

**Important:** Exceptions are rare. If you propose an exception, you must demonstrate that you have exhausted all options within the existing framework first.

### Rule 8: Naming Convention

**All new content must follow the established naming and code conventions.**

- Topic code: `{StrandCode}-{Number}{Letter}` (e.g., `NUM-01-1A`)
- Lesson code: `L{two-digit-number}` (e.g., `L02`)
- Activity code: `A{two-digit-number}` (e.g., `A03`)
- Asset filenames: `{language}_{subject}_{strandCode}_{topicCode}_{lessonCode}_{activityCode}_{type}_{variant}.{ext}`

### Rule 9: Age Appropriateness

**New content must declare its target age range at the topic level.**

| Age Range | Label | Example Topics |
|-----------|-------|----------------|
| 3-4 years | Early Pre-Primary | Numbers 1-3, Basic Colors, Simple Shapes |
| 4-5 years | Pre-Primary | Numbers 1-10, Letter Sounds, Sorting |
| 5-6 years | Pre-Primary Advanced | Numbers 1-20, Addition, Simple Reading |
| 6-7 years | Standard 1 | Numbers to 100, Subtraction, Time |
| 7-8 years | Standard 2 | Operations, Money, Measurement |

- Activities within a topic should match the age range's cognitive abilities
- A Level 1 activity for age 3 is VERY different from a Level 1 activity for age 7
- The difficulty level (1-6) is about cognitive demand within the age range, not absolute complexity

### Rule 10: Backward Compatibility

**New content must not break existing content.**

- Adding a new strand must not change the order or structure of existing strands
- Adding a new topic must not change the numbering of existing topics
- Adding a new lesson must not change the sequence of existing lessons
- Asset references must use new filenames; existing asset names must never be reused for different content
- Existing progress data must remain valid — old learners' progress should map correctly to the new hierarchy

### Expansion Checklist

When adding anything new, verify each item:

```
□ 1. Hierarchy level assigned (Domain/Strand/Topic/Lesson/Activity)
□ 2. Lesson blueprint followed (10 steps)
□ 3. Activity blueprint assigned (one of 12)
□ 4. Engine assigned from existing 12 (or 3-gate exception)
□ 5. Difficulty level assigned (1-6)
□ 6. Assessment points defined (QC/LC/TT/SE)
□ 7. Assets created per asset blueprint
□ 8. Reward triggers defined (stars/badges/certificate)
□ 9. Revision schedule set (spaced repetition)
□ 10. All naming conventions followed
□ 11. Age range declared
□ 12. Backward compatibility verified
□ 13. Prerequisites defined (if any)
□ 14. Languages supported (EN + SW at minimum)
```

This checklist must be completed and documented before any code is written for new content.

---

> **This Master Content Architecture is the governing document for all educational content in Kona Ya Hisabati.**
>
> Every developer, content creator, and AI system working on this platform must read and follow this document.
>
> Any deviation from this architecture must go through the 3-Gate Exception Process (Rule 7) and be documented as an amendment to this document.
>
> **Version:** 1.0
> **Date:** July 2026
> **Status:** Approved
