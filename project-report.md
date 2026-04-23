# Design & Creativity Lab (CSCR1504)

## Project Report

**B.Tech 1st Year - Semester 2 (2025-2026)**

---

## Project Context

This report is based on the active project in the current workspace: **manager**, a Laravel + Livewire personal productivity system. The system is designed to help users manage timetable-driven routines and daily tasks through role-aware scheduling, automated prioritization, and a smart dashboard experience.

The project includes:

1. Secure authentication and account security flows.
2. Role onboarding (Student, Teacher, Corporate Worker).
3. Timetable upload and AI-assisted slot extraction.
4. Smart to-do organization and priority scoring.
5. Schedule-aware task suggestions and weekly planning support.

---

# 1. Project Title

**Manager: A Role-Aware Smart Scheduler and To-Do Prioritization System Using Laravel and Livewire**

---

# 2. Team / Group Formation

| S.No | Student Name | Roll Number | System ID | Role |
| ---- | ------------ | ----------- | --------- | ---- |
| 1 | Aung Min Hein | CSCR1504-021 | LAB-PC-07 | Full-Stack Developer (Research, Design, Development, Testing) |

---

# 3. Research and Empathizing

## 3.1 Understanding the Problem Area

Students and working professionals both struggle with the same invisible challenge: they often know what they need to do, but they do not have a practical system to decide what to do **next**. Most people use scattered tools like notes apps, reminders, screenshots of class timetables, and chat messages. This creates information overload rather than clarity.

During regular college weeks, a student may have class schedules, assignment deadlines, quizzes, and personal commitments at the same time. Teachers and corporate workers face similar pressure from meetings, preparation work, and deliverables. In all these cases, the problem is not just “lack of effort,” but **lack of a unified planning workflow**.

Three recurring pain points appeared clearly:

1. **Timetable friction**: Many users store timetable files as images or PDFs and manually copy data into their planners.
2. **Task ambiguity**: Users quickly add tasks but do not classify them, so they become one long unstructured list.
3. **Priority confusion**: People often choose tasks emotionally rather than logically, leading to urgent tasks being delayed.

Another major issue is context mismatch. A task list is useful only when linked to the day’s real schedule. If the system knows that a class or meeting is upcoming, it should suggest related work before that slot. Traditional to-do apps do not connect task decisions with time-table context deeply enough.

This is why the current project in the workspace focuses on a role-aware manager system rather than a generic task app. The project combines:

1. User role onboarding to personalize behavior.
2. Timetable upload and structured slot extraction.
3. Smart classification of tasks.
4. Priority scoring using role-based logic and AI assistance.
5. Visual scheduler dashboard that ties classes/slots and tasks together.

Laravel and Livewire are a strong fit because they provide secure backend patterns, clean relational data handling, queue-based background processing, and reactive UI workflows without heavy frontend complexity. The resulting system supports both usability and maintainability.

In short, the core problem area is time-management fragmentation. The project addresses it by creating one integrated manager experience where schedule data and task planning work together.

## 3.2 Secondary Research

Secondary research was conducted by reviewing popular productivity tools and scheduling patterns used by students and professionals. We compared Google Calendar workflows, Notion task boards, Todoist-like prioritization patterns, and timetable management habits commonly seen in educational environments.

### Observations from Existing Tools

1. **Calendar tools** are strong for time blocks but weak for dynamic task prioritization.
2. **To-do tools** are strong for capture and checklists but weak in schedule intelligence.
3. **Notes-based systems** are flexible but become inconsistent over time.
4. **Education-focused apps** often manage classes but do not intelligently prioritize tasks tied to those classes.

### Strengths and Limitations

**Google Calendar / Apple Calendar**

- Strength: Excellent timeline visibility and reminders.
- Limitation: Users still manually decide what task is most important in a free slot.

**Notion / Generic task boards**

- Strength: Flexible organization and custom databases.
- Limitation: Requires significant manual setup and maintenance discipline.

**Todoist / Basic priority apps**

- Strength: Fast task capture and due-date reminders.
- Limitation: Priority levels are mostly user-defined and not deeply contextual to schedule data.

**Campus timetable apps (common regional examples)**

- Strength: Display class routine quickly.
- Limitation: Rarely integrated with task planning and dynamic workload management.

### Research Insight

Current solutions usually optimize **either** schedule **or** tasks. Very few create a meaningful bridge between the two. This gap creates decision fatigue, where users repeatedly ask themselves:

- “What should I do now?”
- “Which task is actually urgent?”
- “How do I use this free slot effectively?”

This project’s direction emerged from that gap: build a manager system where timetable context directly influences task organization and prioritization.

### Why a Laravel + Livewire Approach is Reasonable

1. Secure account and authentication support is production-ready.
2. Queue jobs can handle heavier operations like timetable parsing without blocking UI.
3. Eloquent relationships make it practical to connect users, schedule slots, subjects, and todos.
4. Livewire allows interactive scheduler behavior while staying in a PHP-first stack.

Secondary research confirmed that the value is not in building another plain to-do list, but in building an integrated and context-aware manager.

## 3.3 Primary Research

Primary research was done using peer observations, short interviews, and scenario-based testing with students.

### Method

1. Observed how users currently track timetable and assignments.
2. Asked users to simulate a “busy day” planning session.
3. Noted where confusion and delays happened.

### Sample Insights from Interviews

1. “I write tasks quickly, but later I don’t know which one to start first.”
2. “My timetable is in a PDF, but my tasks are in another app, so I keep switching.”
3. “When deadlines come together, I panic and do random tasks first.”
4. “If a system suggests what to do before class, I would follow it.”

### Behavioral Findings

1. Users capture tasks fast but categorize them inconsistently.
2. Many users postpone medium-importance tasks until they become urgent.
3. Timetable data is often stored but not actively used for planning decisions.
4. Users appreciate visual dashboards but ignore overly complex analytics.

### Core Pain Points

1. High friction in converting timetable files into usable schedule data.
2. Manual prioritization is error-prone and inconsistent.
3. Lack of personalized planning based on role context.
4. Difficulty maintaining momentum over a week.

### Need Statement

Users need a simple but intelligent manager platform that turns timetable and task inputs into clear next actions. The system should reduce decision fatigue, keep priorities realistic, and provide role-specific planning support without adding operational complexity.

## 3.4 AEIOU Framework

| Dimension | Analysis for Manager Project |
| --------- | ---------------------------- |
| Activities | Upload timetable, review today timeline, add tasks, classify tasks, complete tasks, carry tasks to tomorrow, monitor pending workload. |
| Environment | College campus, hostel room, home study desk, office workspace, mobile-heavy and laptop-mixed usage contexts. |
| Interactions | User-system interaction through scheduler dashboard and to-do flows; indirect interaction with AI services for parsing and scoring; role-specific interaction patterns. |
| Objects | Timetable PDFs/images, schedule slots, subjects, todos, due dates, priority scores, dashboard indicators. |
| Users | Student, Teacher, Corporate Worker (role onboarding supported); system administrators as technical maintainers. |

## 3.5 5W & H Analysis

| Aspect | Analysis |
| ------ | -------- |
| Who | Students, teachers, and corporate workers who need structured time and task planning. |
| What | A role-aware manager system that merges schedule data and to-do prioritization. |
| When | Daily planning sessions, pre-class preparation windows, and weekly workload balancing periods. |
| Where | Web-based access from home, campus, office, and hybrid work/study environments. |
| Why | To reduce planning confusion, prevent missed deadlines, and improve execution consistency. |
| How | Through Laravel + Livewire workflows, timetable parsing, role-based scoring, and scheduler dashboard intelligence. |

## 3.6 5 Why Analysis

**Core Problem:** Users stay busy but still feel disorganized and behind schedule.

1. **Why 1:** Why do users feel disorganized?  
Because their tasks are not prioritized in a context-aware way.

2. **Why 2:** Why is prioritization weak?  
Because tasks are stored as flat lists without schedule linkage.

3. **Why 3:** Why are tasks not linked to schedules?  
Because timetable data is often trapped in PDFs/images and not parsed into structured slots.

4. **Why 4:** Why is timetable parsing not common in standard planners?  
Because it requires additional processing and workflow design beyond basic checklist apps.

5. **Why 5:** Why does that matter for productivity?  
Because without schedule context, users repeatedly make ad-hoc decisions and waste focus energy.

**Root Cause:** Current personal planning tools are separated by function (calendar vs tasks) and do not create an integrated decision system.

---

# 4. Problem Definition

## 4.1 Synthesizing Research Findings

The combined research findings show a clear pattern: users are not lacking tools, they are lacking **integration**. They already have calendars, reminders, and task apps, but these systems run in parallel instead of collaboration.

The first critical finding is that timetable context is underutilized. Users may have accurate routine information, but it is usually stored in static formats. Without structured slots inside the planning system, free windows and class transitions cannot be used intelligently.

The second finding is that task capture is easy, but task interpretation is hard. Users can quickly add a title like “finish DS assignment,” but they often skip category, urgency, and timing decisions. Over time, this creates a noisy task list where priority becomes emotional rather than logical.

The third finding is that role context matters significantly. A student, teacher, and corporate worker may write similar task titles, but urgency interpretation should differ by role. For example, “prepare slides” means different operational pressure for different users.

The fourth finding is that users prefer actionable summaries over heavy analytics. They want to know: pending tasks, due soon, overdue count, and what to focus on next. If dashboards are overloaded, users disengage.

The fifth finding is that background automation increases usability only when trust is maintained. AI support for slot parsing and scoring is helpful, but outputs must be normalized, validated, and safely integrated into user-controlled flows.

Therefore, the problem is defined as follows:

- Planning is fragmented.
- Priorities are inconsistent.
- Schedule data is not operationalized.
- Users need a lightweight but intelligent manager workflow.

The manager project addresses this by combining role onboarding, timetable extraction, subject-linked slots, smart task arrangement, and dashboard-led execution guidance.

## 4.2 How Might We (HMW)

1. How might we convert timetable files into actionable daily schedule slots with minimal user effort?
2. How might we prioritize tasks using both role-based logic and contextual schedule data?
3. How might we reduce decision fatigue by showing the most useful next actions instead of overwhelming lists?
4. How might we support different user roles while keeping one simple and consistent interface?
5. How might we use AI assistance in a safe, fallback-friendly way that still keeps users in control?

## 4.3 Final Problem Statement

People managing study or work responsibilities often use disconnected tools for schedules and tasks, leading to weak prioritization and execution confusion. A unified, role-aware manager system is needed to transform timetable data and to-do inputs into clear, context-based action planning with reliable prioritization and usable daily guidance.

---

# 5. Ideation

## 5.1 Brainstorming Techniques Used

To shape the final manager solution, multiple ideation techniques were used in sequence.

### Brainstorming

Initial brainstorming generated a wide set of ideas:

1. Manual timetable editor.
2. AI timetable scan from image/PDF.
3. Role-based task labels.
4. Smart urgency scoring.
5. “Carry to tomorrow” workflow.
6. Timeline view with free-slot detection.
7. Weekly productivity heatmap.
8. Auto-suggest next task based on class context.

This broad phase helped avoid premature narrowing and ensured both technical and user-experience perspectives were considered.

### SCAMPER

SCAMPER helped transform raw ideas into implementable features:

- **Substitute:** Replace manual timetable entry with upload + extraction workflow.
- **Combine:** Merge task list and schedule timeline in one dashboard.
- **Adapt:** Use role selection to adapt scoring behavior.
- **Modify:** Simplify analytics into sidebar summaries users check frequently.
- **Put to another use:** Use subject metadata to map tasks to nearest relevant class slots.
- **Eliminate:** Remove non-essential gamification from first release.
- **Reverse:** Start from “what is due soon” and then expose full task list.

### Mind Mapping

Mind mapping was used to align modules:

1. Authentication and security.
2. Role onboarding.
3. Timetable upload pipeline.
4. Slot normalization and subject linking.
5. Todo capture, categorization, arrangement, and prioritization.
6. Scheduler timeline and dashboard insights.

This clarified dependencies and development order.

## 5.2 Double Diamond Approach

### Discover

We explored real scheduling behavior and productivity pain points across student and work contexts.

### Define

We narrowed the challenge to one practical outcome: enable users to consistently know what to do next based on schedule and priority.

### Develop

We designed and compared flows for timetable ingestion, task arrangement, and dashboard visualization. Multiple alternatives were tested conceptually.

### Deliver

The workspace implementation delivers role onboarding, timetable upload and parsing, schedule slot sync, to-do arrangement jobs, and reactive scheduler screens in Laravel/Livewire.

The Double Diamond process prevented jumping straight into code without clarity and made the final solution more grounded.

## 5.3 Group Ideation

Although the project was built by one developer, ideation still included external feedback loops.

1. Classmates provided usability feedback on text clarity and dashboard readability.
2. Faculty input helped keep scope practical and academically relevant.
3. Self-review checkpoints were used to evaluate security, performance, and maintainability.

This lightweight collaboration model improved decision quality while preserving independent execution speed.

## 5.4 Shortlisting Ideas

The MoSCoW method was used to prioritize implementation.

### Must Have

1. Secure authentication and role onboarding.
2. Timetable upload and asynchronous processing.
3. Schedule slot storage and daily timeline view.
4. To-do capture and pending/completed state management.
5. Priority scoring and sorted pending list.

### Should Have

1. Category classification for tasks.
2. Subject-aware todo-to-slot matching.
3. Dashboard summaries (due today, due soon, overdue).

### Could Have

1. Weekly productivity visualizations.
2. Smart reminder nudges.
3. Exportable planning reports.

### Won’t Have (Current Scope)

1. Native mobile application.
2. Team collaboration boards.
3. Full offline support.

The selected final set balanced practical value, build feasibility, and reliability.

---

# 6. Prototyping and Testing

## 6.1 Prototype Development

### Low-Fidelity Prototype

Low-fidelity planning started with screen flow sketches for:

1. Role selection after login.
2. Timetable upload modal and processing state.
3. Scheduler dashboard blocks (current slot, next slot, timeline).
4. To-do input and summary sidebar.

Early wireflow thinking helped keep navigation focused and reduced later rework.

### High-Fidelity Prototype (Workspace Implementation)

The active project implementation includes:

1. **Role onboarding page** with Student/Teacher/Corporate Worker selection.
2. **Scheduler page** with timetable upload handling and status awareness.
3. **Background jobs** for timetable processing and todo arrangement.
4. **To-dos page** with add, complete, carry-forward, category breakdown, and focus list.
5. **Data models** linking users, timetable uploads, subjects, schedule slots, and todos.

The build emphasizes maintainable data flows and practical UX over unnecessary complexity.

## 6.2 Testing and Feedback

Usability and functional checks were performed with scenario-based flows.

### Functional Test Scenarios

1. Register/login and role selection redirect behavior.
2. Timetable upload (file type and size validation).
3. Queue-driven processing state transitions.
4. Todo creation with optional due date.
5. Completion and carry-to-tomorrow actions.
6. Priority ordering and sidebar summary consistency.

### User Feedback Highlights

1. The role onboarding flow felt clear and fast.
2. Users liked seeing pending, due today, and overdue indicators in one place.
3. Automatic arrangement was useful, but users wanted visibility into why a score is high.
4. Some users requested stronger weekly planning and reminder features.

### What Worked Well

1. Clear structure between scheduler and todo workspace.
2. Reduced friction in turning uploaded timetable into usable slots.
3. Better focus due to sorted pending tasks and concise dashboard stats.

### What Needed Refinement

1. Explanatory hints for AI-influenced prioritization.
2. Better handling for ambiguous timetable cells.
3. Additional guidance for first-time users.

## 6.3 Iteration and Refinement

Post-feedback refinement focused on clarity and reliability:

1. Improved normalization logic for timetable extraction outputs.
2. Enhanced schedule-slot matching robustness for todos.
3. Maintained fallback behavior when AI services are unavailable.
4. Refined dashboard text and visual priorities for faster scanning.
5. Kept workflows concise to prevent feature overload.

These iterations moved the project from “feature demo” to “usable daily workflow.”

---

# 7. Business Model and Implementation

## 7.1 Value Proposition Canvas

### Customer Segments

1. College students managing classes and assignments.
2. Teachers balancing lessons, grading, and prep work.
3. Early professionals handling schedules and deliverables.

### Customer Jobs

1. Plan day/week efficiently.
2. Avoid missing important deadlines.
3. Connect scheduled events with practical next tasks.
4. Reduce cognitive load from planning decisions.

### Pains

1. Fragmented tools and duplicated entries.
2. Poor prioritization when workload increases.
3. Difficulty using timetable data operationally.
4. Inconsistent follow-through on pending tasks.

### Gains

1. One integrated planning environment.
2. Smarter, role-aware prioritization.
3. Faster daily decision-making.
4. Better visibility into pending and overdue workload.

### Product Fit

Manager creates value by turning schedule data and task data into a connected planning system rather than separate lists.

## 7.2 Business Model Canvas

| Component | Description |
| --------- | ----------- |
| Key Partners | Educational institutions, productivity communities, cloud providers, AI API providers. |
| Key Activities | Product development, timetable parsing improvements, user onboarding, support, iterative UX refinement. |
| Key Resources | Laravel codebase, queue processing, database models, AI integration layer, UI components. |
| Value Proposition | Role-aware scheduler + to-do intelligence in one lightweight and practical platform. |
| Customer Relationships | Guided onboarding, in-app hints, feedback-driven improvements, periodic feature releases. |
| Channels | Web app deployment, institutional pilot programs, direct demos, developer portfolio distribution. |
| Customer Segments | Students, teachers, corporate workers, small academic groups. |
| Cost Structure | Hosting, development, maintenance, AI API usage, testing and quality assurance. |
| Revenue Streams | Freemium model, premium productivity analytics, institutional subscriptions. |

## 7.3 Market Feasibility

The market for productivity and planning tools is highly competitive but still open for focused niche products. Generic tools are abundant, yet users repeatedly seek systems tailored to their daily context.

This project has feasibility in education and early-professional segments because it addresses a practical gap:

1. Automatic timetable-to-task workflow.
2. Role-aware scoring behavior.
3. Simpler interface than large enterprise planning suites.

Compared to broad productivity platforms, manager’s advantage is contextual specificity. Instead of trying to replace every workflow, it solves a concrete and recurring planning problem with low setup friction.

From a technical perspective, Laravel/Livewire stack and queue architecture support scalable iteration. From an adoption perspective, the onboarding-first experience lowers learning barrier. Therefore, pilot deployment in academic environments is realistic.

## 7.4 Risk Analysis

| Risk Category | Risk Description | Impact | Mitigation Strategy |
| ------------- | ---------------- | ------ | ------------------- |
| Technical | AI extraction may misread ambiguous timetable cells. | Medium | Add normalization, validation, and safe fallback flows; allow manual correction in future iterations. |
| Technical | Queue delays under high load can reduce responsiveness. | Medium | Tune queue workers, monitor job retries, and optimize processing paths. |
| Product | Users may expect full automation and ignore manual review. | Medium | Provide clear messaging that suggestions are assistive, not authoritative. |
| Adoption | Users may not sustain daily usage after initial interest. | Medium | Focus on lightweight UX, reminders, and visible progress indicators. |
| Security | Unauthorized access to user schedules or tasks. | High | Enforce authentication, policy checks, and role-based route protection. |
| Privacy | Sensitive timetable/task details could be exposed. | High | Secure storage access, proper permission checks, and controlled file preview routes. |
| Dependency | Third-party AI service instability or rate limits. | Medium | Implement retries, fallback models, and graceful degradation when AI unavailable. |

---

# 8. Final Solution and Implementation

## 8.1 Final Prototype Description

The final prototype in this workspace is a role-aware manager platform with the following implemented core capabilities:

1. **Authentication and Account Security**
   - User authentication and protected routes.
   - Security support flows including two-factor ready user model fields.

2. **Role Onboarding Workflow**
   - New users select role (Student, Teacher, Corporate Worker).
   - Role selection influences prioritization behavior and planning language.

3. **Timetable Upload and Processing**
   - Users upload timetable files (PDF/JPG/JPEG/PNG/WEBP).
   - Uploads are queued for asynchronous parsing.
   - Slots are extracted and normalized into structured schedule data.

4. **Schedule Intelligence Layer**
   - Current slot and next slot detection.
   - Today timeline generation with class and free blocks.
   - Subject metadata linkage for better contextual decisions.

5. **To-Do Workspace**
   - Fast todo capture (title, description, due date).
   - Pending/completed state updates.
   - Carry-forward scheduling to tomorrow.

6. **Smart Arrangement and Prioritization**
   - Category classification (existing categories + AI suggestion fallback).
   - Slot association based on subject matching.
   - Combined role score + AI score to produce priority ordering.

7. **Dashboard Summaries**
   - Pending count, due today, due soon, overdue indicators.
   - Category breakdown and focus task view.

Overall, this is a practical productivity system that translates schedule awareness into action prioritization.

## 8.2 Deployment Steps

### Environment Setup

1. Set up Linux server with PHP, Composer, Node, database, and queue worker support.
2. Configure `.env` values for database, queue, app URL, and Gemini service keys (optional but recommended).
3. Ensure writable storage and cache directories.

### Application Setup

1. Install dependencies: `composer install` and `npm install`.
2. Generate app key: `php artisan key:generate`.
3. Run migrations: `php artisan migrate`.
4. Build assets: `npm run build`.

### Runtime Services

1. Run queue workers for background jobs (timetable processing and todo arrangement).
2. Configure web server document root to Laravel `public` directory.
3. Enable HTTPS for secure session handling.

### Verification Checklist

1. New user is redirected to role onboarding when role is missing.
2. Timetable upload validates format and queues processing correctly.
3. Schedule slots appear and update scheduler cards.
4. Todos are created, scored, and sorted as expected.
5. Sidebar summary metrics remain consistent after state changes.

### Maintenance

1. Monitor queue failures and retry behavior.
2. Track AI-related errors and fallback rates.
3. Review performance for timetable parsing and dashboard queries.

## 8.3 Real-world Impact

This project can improve day-to-day execution quality for students and professionals in a practical way.

### User Impact

1. Less time spent deciding “what to do next.”
2. Better alignment between available time slots and priority tasks.
3. Lower chance of missing critical deadlines.
4. Increased sense of control and planning clarity.

### Academic/Professional Impact

1. Students can manage assignments around class routines more effectively.
2. Teachers can balance prep/grading tasks with schedule constraints.
3. Corporate users can handle meeting-heavy days with better task sequencing.

### Scalability Potential

With further iteration, manager can evolve into:

1. Multi-user collaborative planning.
2. Team/department planning modules.
3. Institutional deployment for student support ecosystems.

---

# 9. Conclusion and Future Scope

## 9.1 Key Learnings

This project reinforced several high-value lessons.

### Technical Learnings

1. Queue-based background processing improves responsiveness for heavy tasks.
2. Data normalization is essential when integrating AI outputs into real workflows.
3. Strong relational modeling is critical for schedule-task linkage.

### Product Learnings

1. Simplicity is more valuable than feature count in daily productivity tools.
2. Users trust systems that provide clear, understandable suggestions.
3. Role-based contextual behavior improves relevance significantly.

### Personal Learnings

1. Building as a solo developer requires strict prioritization and disciplined iteration.
2. Early feedback loops save significant redesign time.
3. Human-centered framing leads to more usable implementation decisions.

## 9.2 Challenges Faced

1. **Challenge:** Converting semi-structured timetable files into reliable slot data.  
   **Resolution:** Added normalization and validation rules before persistence.

2. **Challenge:** Maintaining usable UX while adding intelligent behavior.  
   **Resolution:** Prioritized concise summaries and progressive detail.

3. **Challenge:** Balancing AI assistance with deterministic fallback logic.  
   **Resolution:** Implemented role score baseline and graceful fallback when AI is unavailable.

4. **Challenge:** Preserving architecture quality within semester timeline.  
   **Resolution:** Used phased delivery and MoSCoW-driven scope decisions.

## 9.3 Future Enhancements

1. Manual timetable correction UI for extracted slot review.
2. Weekly planner with drag-and-drop task placement.
3. Explainable priority panel (showing score factors clearly).
4. Reminder engine (email/push/WhatsApp style hooks).
5. Mobile-friendly companion app.
6. Calendar sync integrations.
7. Team collaboration and shared schedule boards.

---

# 10. References and Appendices

## 10.1 References

1. Laravel Documentation. (2026). https://laravel.com/docs
2. Laravel Livewire Documentation. (2026). https://livewire.laravel.com
3. Laravel Queue Documentation. (2026). https://laravel.com/docs/queues
4. Laravel Fortify Documentation. (2026). https://laravel.com/docs/fortify
5. Google AI / Gemini API Docs. https://ai.google.dev
6. Nielsen Norman Group articles on usability and cognitive load. https://www.nngroup.com
7. Basic productivity design patterns from established task and calendar products.

## 10.2 Appendices

### Appendix A: Sample User Interview Questions

1. How do you currently plan your day when you have classes/meetings plus tasks?
2. What is your biggest difficulty in managing deadlines?
3. Do you use one tool or multiple tools for schedule and to-dos?
4. Would role-specific suggestions improve your planning confidence?
5. What dashboard metrics do you check most frequently?

### Appendix B: Sample Testing Feedback Summary

| User ID | Role | Positive Feedback | Issue Reported | Improvement Suggestion |
| ------- | ---- | ----------------- | -------------- | ---------------------- |
| U1 | Student | Task list is easy to use | Wants clearer score explanation | Add “why this task is high priority” tooltip |
| U2 | Student | Upload flow is smooth | Ambiguous timetable row parsing | Add manual edit confirmation step |
| U3 | Teacher | Good due/overdue overview | Needs weekly planning board | Add week view panel |
| U4 | Corporate Worker | Role-aware language feels useful | Wants calendar sync | Integrate external calendar APIs |
| U5 | Student | Carry-to-tomorrow is practical | Needs reminder notifications | Add optional reminder settings |

### Appendix C: Data Structure Overview

- Users (id, name, email, role, security fields)
- TimetableUploads (status, mime_type, parsed payload, parsed_at)
- Subjects (subject_key, course_code, course_name, faculty_name, section, assignment)
- ScheduleSlots (day_of_week, starts_at, ends_at, subject linkage, room/block, source)
- Todos (title, category, due_at, scheduled_for, role_score, ai_score, priority_score, status)

### Appendix D: Suggested Screenshots for Final Submission

1. Role onboarding screen.
2. Scheduler dashboard with current/next slot.
3. Timetable upload modal and processing indicator.
4. To-do workspace with summary badges.
5. Category breakdown and focus tasks.

---

# 11. Signature

| Student Name | Signature | Faculty Name | Signature |
| ------------ | --------- | ------------ | --------- |
| Aung Min Hein | ____________ | ____________________ | ____________ |

---

## Submission Note

This report has been rewritten specifically for the active workspace project (**manager**). It is written in long-form, humanized academic style to support a 20-page class submission when exported with standard formatting (12pt Times New Roman, 1.5 line spacing, justified text) and appendix screenshots.
