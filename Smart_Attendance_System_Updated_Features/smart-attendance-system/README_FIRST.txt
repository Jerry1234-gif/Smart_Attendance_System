SMART ATTENDANCE SYSTEM - COMPLETE SOURCE CODE
HCI Group Project | DTECH206 | Limkokwing University of Creative Technology

START HERE
1. Read INSTALLATION_GUIDE.md before running the application.
2. Extract this folder into C:\xampp\htdocs\smart-attendance-system\
3. Start Apache and MySQL in XAMPP.
4. Check config/database.php and config/app.php.
5. Open http://localhost/smart-attendance-system/setup.php
6. Create the first administrator account in the installation form.
7. Sign in and follow USER_GUIDE.md to create lecturers, students, courses and enrolments.

WHAT THIS ZIP CONTAINS
- Full PHP application source code.
- MySQL database structure (database/schema.sql).
- First-time database and administrator setup page (setup.php).
- Administrator tools for users, courses and enrolments. Student IDs are restricted to the Limkokwing 90500 prefix, followed by digits only.
- Lecturer tools for QR attendance sessions, six-digit codes, reports and CSV export.
- Student dashboard, QR scanner, manual code check-in and attendance history.
- Shared accessibility and display controls: text size, colour themes, light/dark mode, high contrast, readable font, underlined links, reduced motion and larger cursor.
- Browser text-to-speech with Read page aloud and Stop reading controls.
- AI Assistant with built-in help mode and optional server-side live AI integration.
- Help & Support FAQs, a support request form and administrator support inbox.
- CSS styling, JavaScript, classroom image and HCI diagrams/mockup references.
- Installation, feature, user, testing, security and troubleshooting guides.

IMPORTANT TECHNICAL NOTES
- This is a PHP 8.1+ and MySQL web application intended for XAMPP/Apache.
- Do not open PHP pages by double-clicking them. Run them through http://localhost/...
- The new support-request table is created automatically on an existing installation when Help & Support or the administrator Support inbox is opened; see FEATURES_ADDED_GUIDE.md.
- The AI Assistant works in built-in help mode without credentials. Live AI is optional and requires an administrator to configure a private server-side API key; never share a ZIP containing a real key.
- The QR display and browser camera scanner load QRCode.js and html5-qrcode from their public CDNs. Internet is required for those two library files. The six-digit manual attendance code remains available as a fallback.
- A phone camera needs a secure connection (HTTPS) for in-browser scanning, except on localhost. For a local classroom demo, connect the laptop and phone to the same Wi-Fi and open the application using the laptop's local network IP address. See INSTALLATION_GUIDE.md.
- The system does not connect to Limkokwing's official student database. Add accounts and course enrolments through the administrator account.
- The QR code is time-limited, but it cannot prove a student's physical location. Do not treat it as foolproof anti-proxy attendance.

FIRST ADMINISTRATOR
There is no hard-coded default username or password. You create the administrator details during setup.php.
The administrator must change the initial password after first sign-in.

LIMKOKWING STUDENT ID RULE
- Every student ID must begin with 90500 and have one or more digits after it (example: 905001234).
- The rule is enforced in the HTML form and by server-side PHP validation.
- Fresh databases also receive a database CHECK constraint. For an existing installation, back up the database and follow the migration steps in INSTALLATION_GUIDE.md.

READ NEXT
- INSTALLATION_GUIDE.md - XAMPP setup from start to finish.
- USER_GUIDE.md - how each role uses the system.
- TESTING_CHECKLIST.md - how to test the full attendance workflow.
- SYSTEM_OVERVIEW.md - pages, database tables and project limitations.
- FEATURES_ADDED_GUIDE.md - details and setup for the lecturer-requested new features.
