# Testing Checklist

Use sample test accounts and non-real attendance data. Record the result (Pass/Fail) and evidence for each test.

You can also run the built-in student ID validator tests from the project folder with `php tests/student_id_validation_test.php`. All test cases should report PASS.

| No. | Test | Expected result | Pass/Fail |
|---:|---|---|---|
| 1 | Open setup.php before installation | Installation form appears. | |
| 2 | Create first administrator | Database tables and admin account are created. | |
| 3 | Sign in as administrator | Admin is directed to change the initial password. | |
| 4 | Try a wrong password | Sign-in is rejected with a clear message. | |
| 5 | Create a lecturer account | Lecturer appears in the user list. | |
| 6 | Create a student account with ID `905001234` (use a test ID) | Student appears with the valid 90500 prefix and unique number. | |
| 6a | Try student ID `12345999` | Account creation is rejected because the ID does not start with 90500. | |
| 6b | Try student ID `90500ABC` or `90500 123` | Account creation is rejected because characters after the prefix must be digits only. | |
| 6c | Send a direct POST request with an invalid student ID | Server-side PHP validation rejects the request even if browser validation is bypassed. | |
| 7 | Create a course and assign lecturer | Course appears with the selected lecturer. | |
| 8 | Enrol student in course | Student appears in the course enrolment list. | |
| 9 | Create a session as assigned lecturer | QR code and six-digit code are displayed. | |
| 10 | Try to create a session as a lecturer not assigned to that course | Course should not be offered/accepted. | |
| 11 | Check in as enrolled student using valid QR | Attendance is recorded and confirmation appears. | |
| 12 | Check in using correct six-digit code | Attendance is recorded with method CODE. | |
| 13 | Check in twice to the same session | Second attempt is rejected as already recorded. | |
| 14 | Try a wrong or expired code | Check-in is rejected. | |
| 15 | Try to check in to a course without enrolment | Check-in is rejected. | |
| 16 | Close the lecturer session | Old QR and code stop working. | |
| 17 | View student attendance history | The successful check-in appears. | |
| 18 | View lecturer records | Student, student number, time and method appear. | |
| 19 | Download attendance CSV | A CSV containing the session's records downloads. | |
| 20 | Disable a student account | That account can no longer sign in. | |
| 21 | Open the layout on a phone-sized screen | Navigation, forms and tables remain usable. | |
| 22 | Block camera permission or disable internet | Manual six-digit code path remains available. | |

## Test evidence to capture

- Admin dashboard after creating the accounts and course.
- Course enrolment page showing the sample student.
- Lecturer's QR code and manual code (hide the code if sharing publicly).
- Student's successful check-in confirmation.
- Lecturer's attendance records table.
- Student's attendance history.
- Downloaded CSV opened in Excel or another spreadsheet application.

Do not invent test results in the project report. Carry out the tests and write down the actual outcomes.
