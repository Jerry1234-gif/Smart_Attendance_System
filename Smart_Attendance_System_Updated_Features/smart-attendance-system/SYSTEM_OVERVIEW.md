# System Overview

## 1. Project purpose

The Smart Attendance System is a responsive web application proposed for university classroom attendance. A lecturer opens a time-limited class session and displays a QR code. An enrolled student signs in and scans the code. A six-digit backup code is provided for cases where the camera cannot scan. Lecturers can review attendance and export a CSV report.

This code package turns the HCI proposal into a working PHP/MySQL project. It is not connected to Limkokwing's official systems.

## 2. Main users and permissions

| Role | Main tasks |
|---|---|
| Administrator | Create lecturer/student accounts, create courses, assign lecturers, enrol students, enable/disable accounts. |
| Lecturer | View assigned courses, open attendance sessions, display QR/manual code, regenerate credentials, close sessions, view records and export CSV. |
| Student | View enrolled courses and live sessions, scan QR, enter manual code, see confirmation and check attendance history. |

Every private page checks the signed-in user's role. A lecturer can only manage sessions connected to that lecturer's own courses. A student can only check in to a course in which that student is enrolled.

## 3. Attendance process

1. The lecturer signs in and selects an assigned course.
2. The lecturer creates a session with a title, optional room and duration.
3. The server creates a high-entropy QR token and a random six-digit attendance code. Only hashes of the token/code are stored in the database; the temporary display values are held in the lecturer's PHP session.
4. The QR code encodes a link to `checkin.php?token=...`.
5. The student must sign in. The server checks the QR token or manual code, open session, start/end time, course enrolment and duplicate attendance constraint.
6. A successful check-in creates one attendance record with the method (`qr` or `code`) and timestamp.
7. The lecturer sees the updated list and can export it as CSV or close the session.

## 4. HCI design decisions

- **Visibility of system status:** session status, closing time, success/error feedback and attendance count are visible.
- **Consistency:** forms, buttons, tables and navigation follow a shared layout.
- **Error prevention:** required fields, role checks, code validation and a unique database key prevent common mistakes.
- **Recognition rather than recall:** students see live sessions and a clearly labelled manual code field.
- **Accessibility:** labels are connected to form controls; colour is paired with text labels; the layout adapts to smaller screens.
- **Recovery:** students can use a manual code when camera scanning fails; users receive clear feedback for invalid/expired sessions.

## 5. Database tables

### users
Stores each user account, role, email, password hash, student number, active status and first-login password-change flag.

### courses
Stores the course code, course name and assigned lecturer.

### enrollments
Connects students to courses. A unique key prevents the same student being enrolled in the same course twice.

### attendance_sessions
Stores a lecturer's class session, time window, open/closed state and hashes of the current QR token and manual code.

### attendance_records
Stores each student's successful check-in, timestamp and method. A unique key on `(session_id, student_id)` prevents duplicate check-ins in the same session.

## 6. Folder structure

```text
Smart_Attendance_System_Source_Code/
├── index.php                       Public landing page
├── setup.php                       First-time installer and admin creator
├── login.php / logout.php          Authentication
├── change_password.php             First-login/password update
├── checkin.php                     QR/manual check-in validation
├── scan.php                        Browser camera scanning page
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── courses.php
│   └── enrollments.php
├── lecturer/
│   ├── dashboard.php
│   ├── create_session.php
│   ├── session.php
│   ├── reports.php
│   └── export_csv.php
├── student/
│   ├── dashboard.php
│   └── history.php
├── config/                         Database and application settings
├── database/schema.sql             MySQL schema
├── includes/                       Shared connection, auth, helpers, layout
└── assets/
    ├── css/style.css
    ├── js/app.js
    ├── images/
    └──
```

## 7. Important limitations and responsible use

- The QR generator and camera scanner JavaScript are loaded from public CDNs. Internet is required for these two features to load. Manual-code attendance is available as a fallback.
- QR attendance does not prove physical presence. A student could share an image of the QR code while it is valid. A time limit, signed-in student account, course enrolment and one-record rule reduce some misuse but do not eliminate it.
- The application does not use GPS, face recognition or biometrics. Location/biometric collection should not be added without a clear university-approved reason, privacy review and user consultation.
- The project does not currently send SMS/email notifications, sync with the official university timetable, process excused absences or calculate official attendance percentages.
- It does not have an institution-wide role approval process, password reset email service, multi-factor authentication, automated backups or a formal security audit.
- No example student or lecturer accounts are seeded. The administrator creates real test accounts through the UI.
- Before actual university use, obtain approval for attendance policy, data retention, privacy notices, account management, hosting, HTTPS and backups.

## 8. Security measures in this educational version

- Passwords are stored using PHP `password_hash()` and checked using `password_verify()`.
- Database access uses PDO prepared statements.
- Private pages require authentication and role permissions.
- Forms use a session-based CSRF token.
- A successful login regenerates the session ID.
- Session cookies are HTTP-only and use SameSite=Lax.
- QR tokens are random 256-bit values; their hashes are stored in the database.
- Manual codes are stored as HMAC-SHA-256 hashes using the application key.
- The unique attendance constraint prevents duplicate check-ins.
- CSV values beginning with formula characters are escaped to reduce spreadsheet formula injection risk.

These controls are appropriate for a coursework prototype, not a claim of production readiness.


## Limkokwing student ID validation

Student account creation requires the ID to begin with the five-digit prefix `90500`, followed by one or more digits (for example, `905001234`). HTML input validation improves the form experience, server-side PHP validation is authoritative for account creation, and the new database schema includes a CHECK constraint where the database version enforces CHECK constraints. Existing installations should follow the migration instructions in `INSTALLATION_GUIDE.md`.

## Added accessibility, AI and support modules

The shared page layout now includes an accessibility/display toolbar with browser-local preferences, optional text-to-speech and visual accessibility controls. The AI Assistant is a separate authenticated page with a server-side API route; it uses built-in answers when live AI is not configured and never queries attendance records. Help & Support stores user requests in a `support_requests` table and administrators can review them through `admin/support.php`. Existing account management and the 90500 student ID validation remain intact.
