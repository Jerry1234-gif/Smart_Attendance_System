# User Guide

## A. Administrator

The administrator prepares the system. Sign in with the admin account created during setup.

### Create lecturer and student accounts

1. Open **Users**.
2. Enter the person's full name and email.
3. Choose **Lecturer** or **Student**.
4. For a student, enter the official Limkokwing student ID. It must start with `90500` and contain digits only after that prefix (example: `905001234`). IDs with another prefix, letters or spaces are rejected. Each ID must be unique.
5. Enter a temporary password with at least 8 characters.
6. Create the account and give the temporary credentials to that user privately.
7. The user must change the password at first sign-in.

The 90500 student ID rule is checked by the browser and again by the PHP server; changing or bypassing the browser form will not allow a different prefix. An administrator can disable or re-enable a student or lecturer account. Disabling an account prevents future sign-ins but does not erase its attendance history.

### Create a course

1. Open **Courses**.
2. Enter the unique course code and course name.
3. Select the active lecturer responsible for the course.
4. Create the course.

### Enrol students

1. Open **Manage enrolments**.
2. Select the course.
3. Select an active student and click **Enrol student**.
4. Repeat for the other students in the course.

Only enrolled students can check in to that course's attendance sessions.

## B. Lecturer

### Open a QR attendance session

1. Sign in using the lecturer account.
2. Find the course assigned to you.
3. Click **Open attendance session**.
4. Enter a useful title such as `Week 3 Lecture`.
5. Enter the room if needed.
6. Select the attendance window (5 to 120 minutes).
7. Create the session.

The session page displays:

- A unique QR code containing a random check-in link.
- A six-digit manual attendance code.
- The closing time.
- The list of students who have checked in.

The QR link and manual code are valid only while the session is open and within its attendance time window. A student must be signed in and enrolled. Each student can record only one attendance entry for that session.

### Regenerate the QR code and code

Choose **Regenerate QR and code**. The new token/code pair replaces the previous pair. Do this if the code has been shared outside the class. The old pair will stop working.

### Close a session

Choose **Close session** when attendance should stop before the scheduled closing time. The old QR and code will no longer be accepted.

### Review and export

Open **Reports** or the session page. Download a CSV file to open in Excel. It contains course, session, student name/number, email, check-in time and method.

## C. Student

### Sign in

Use the email and temporary password supplied by the administrator. Change the temporary password when prompted.

### Check in by QR code

1. Open **Scan QR**.
2. Allow camera permission.
3. Point the camera at the QR code displayed by the lecturer.
4. Wait for the check-in result.
5. Return to the dashboard or open **My history**.

The phone should open the QR URL on the same server used by the lecturer. For local demonstrations, see the phone setup instructions in INSTALLATION_GUIDE.md.

### Check in with the manual code

1. Ask the lecturer for the current six-digit code.
2. Enter it in **Enter attendance code** on the student dashboard or Scan QR page.
3. Submit the code.

The code is valid only for an active session and a student enrolled in the course.

### View attendance history

Open **My history** to see the successful check-ins recorded for your student account.

## D. Attendance outcomes

- **Successful:** the check-in is saved and the student sees a confirmation.
- **Already recorded:** the student has already checked into this session.
- **Invalid/expired:** the session is closed, the time window has ended, or the code is incorrect.
- **Not enrolled:** the student account is not enrolled in the course. The administrator must correct enrolment.
- **Account disabled:** the user must contact the administrator.

## Additional display, accessibility and support features

Use the Accessibility & display bar beneath the top navigation to increase text size, change the accent colour, switch light/dark mode, enable high contrast, use a highly readable font, underline links, reduce animation or use a larger cursor. The Read page aloud button uses browser speech; Stop reading cancels it.

The AI Assistant provides system guidance. Without an API key it uses its built-in help answers; live AI requires an administrator to configure a private server-side API key. Do not enter passwords or private student information into the assistant.

Use Help & Support for FAQs and to submit a support request. Administrators can review requests from Support inbox. The existing administrator Users page remains the place to create student and lecturer accounts.
