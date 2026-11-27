# Installation Guide - Smart Attendance System

This project is a browser-based application written in PHP, HTML, CSS and JavaScript. It stores accounts, courses, sessions and attendance records in MySQL.

## 1. Requirements

- Windows computer with XAMPP installed.
- Apache and MySQL/MariaDB from the XAMPP Control Panel.
- PHP 8.1 or newer (required by the source code).
- PHP `PDO` and `PDO MySQL` extensions enabled.
- A modern browser such as Chrome or Edge.
- Internet access when loading the QR-generation and camera-scanning libraries from their CDNs.

## 2. Put the project in XAMPP

1. Extract the ZIP file.
2. Copy the folder named `Smart_Attendance_System_Source_Code` into `C:\xampp\htdocs\`.
3. Rename that copied folder to `smart-attendance-system`.
4. The folder should now be `C:\xampp\htdocs\smart-attendance-system\`.
5. Open that folder in Visual Studio Code. Open the complete folder, not just one PHP file.

The main page should be at:

`C:\xampp\htdocs\smart-attendance-system\index.php`

## 3. Start XAMPP

1. Open the XAMPP Control Panel.
2. Click **Start** beside Apache.
3. Click **Start** beside MySQL.
4. Wait until both services show that they are running.

If Apache or MySQL does not start, resolve the XAMPP port/service issue before opening the application.

## 4. Check database settings

Open `config/database.php` in VS Code. The default values are for a common XAMPP installation:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `smart_attendance_system`
- Username: `root`
- Password: blank

If your MySQL root account has a password or uses another port, change the values in that file and save it.

Open `config/app.php`. It contains a long application secret (`app_key`). A unique random key is already supplied in this copy. Do not replace it with a short word. Keep the same key after installation; changing it makes existing manual attendance-code hashes fail validation.

## 5. Run the installation wizard

1. Open Chrome or Edge.
2. Enter `http://localhost/smart-attendance-system/setup.php`.
3. Enter the administrator's full name, email address and a password of at least 8 characters.
4. Click **Create database and administrator**.
5. When the success message appears, click **Go to sign in**.

The wizard creates the database (if the configured MySQL user has permission), creates all application tables, and adds the first administrator account. No fake students or lecturers are created.

If setup reports that it cannot create the database, use phpMyAdmin to create a database named `smart_attendance_system`, confirm `config/database.php` uses the same name, and run the setup wizard again. The configured MySQL account must have permission to create tables and insert records.

## 6. First sign-in

1. Sign in using the administrator email and password you created.
2. The application requires the administrator to change the initial password.
3. Enter the current password, choose a new password, confirm it, and save.
4. You will then arrive at the administrator dashboard.

There is no shared default password in the source code.

## 7. Prepare the system for a class

Follow these steps in order:

1. Open **Users** and create at least one lecturer account.
2. Create student accounts. Give every student a unique student number.
3. Open **Courses** and create the course. Assign the correct lecturer.
4. Open **Manage enrolments** and select the course.
5. Add the students who are registered for that course.
6. Give each person their email and temporary password privately.
7. Each new user must change their temporary password after signing in.

A student cannot check in to a course unless the administrator has enrolled that student in it.


## 7.1 Limkokwing student ID rule (90500 prefix)

The application requires every student number to match this format: `90500` followed by one or more digits. Example: `905001234`. It rejects IDs with another prefix, letters, spaces, punctuation, or no digits after `90500`. The check is performed in the browser for convenience and again in PHP on the server, so bypassing the browser form does not bypass the rule. The new-install database schema also includes a CHECK constraint on database versions that enforce CHECK constraints.

**For an existing installation:**

1. Back up the `smart_attendance_system` database in phpMyAdmin before changing it.
2. Replace the existing application files with this updated project copy (keep your own `config/database.php` and `config/app.php` values).
3. In phpMyAdmin, select the application's database and open the **SQL** tab. Run this query to find any existing student numbers that do not follow the new format:

   ```sql
   SELECT id, full_name, student_number
   FROM users
   WHERE role = 'student'
     AND (student_number IS NULL OR student_number NOT REGEXP '^90500[0-9]+$');
   ```

4. If the query returns any rows, confirm the correct official IDs with the university and correct those records before continuing. Do not invent or automatically add digits to a student's ID.
5. Open `database/migrations/001_require_90500_student_id_prefix.sql`, copy the `ALTER TABLE` statement at the end, and run it once in phpMyAdmin to add the database-level rule. If the constraint already exists, do not add it again.
6. From then on, student accounts created through **Admin → Users** must use the 90500 prefix.

The PHP validation applies even if you do not run the database migration. The migration adds an extra database-level check for MySQL/MariaDB versions that enforce CHECK constraints.

## 8. Demonstrate attendance

1. Sign out from the administrator account.
2. Sign in as the lecturer assigned to the course.
3. On the lecturer dashboard, click **Open attendance session** for the course.
4. Add a session title, optional room and attendance window.
5. Click **Create session and QR code**.
6. Keep the lecturer's session page open. It displays a QR code and a six-digit backup code.
7. Sign in as an enrolled student using another browser/device.
8. Choose **Scan QR**, allow camera access, and scan the QR displayed by the lecturer. Or enter the six-digit backup code in the student dashboard.
9. The student should see a confirmation message. The lecturer's attendance list should show the student's name and check-in time.
10. Try the same code again with the same student. The app should reject the duplicate check-in.
11. From the lecturer session page, download the CSV report or close the session.

## 9. Use a phone during a local demonstration

The QR URL is built from the address the lecturer used to open the application. If the lecturer opens `localhost` on the laptop, a phone scanning that QR will try to open the phone's own `localhost`, which will not reach the laptop.

For a same-Wi-Fi classroom demonstration:

1. Connect the lecturer's laptop and the student's phone to the same Wi-Fi network.
2. Find the laptop's IPv4 address (for example, `192.168.1.25`) using Windows Command Prompt: `ipconfig`.
3. Ensure Apache is allowed through Windows Firewall on the private network.
4. On the laptop, open `http://192.168.1.25/smart-attendance-system/` instead of `localhost`.
5. The lecturer must create the attendance session from that IP-based address. Its QR code will then contain the same network address.
6. The student opens that link on the phone and signs in.

A browser's camera API generally requires HTTPS on real devices. If camera access is blocked by the local HTTP connection, use a trusted HTTPS test server or demonstrate the six-digit attendance-code fallback. Do not expose the XAMPP server to the public internet just to test QR attendance.

## 10. If the application cannot connect to MySQL

- Confirm MySQL is running in XAMPP.
- Confirm host, port, database name, username and password in `config/database.php`.
- Confirm PHP's `pdo_mysql` extension is enabled in the XAMPP `php.ini` file. In XAMPP this extension is commonly enabled by default. If you change `php.ini`, restart Apache.
- Open `http://localhost/phpmyadmin/` to check whether MySQL is reachable.
- Run `setup.php` again only if no administrator account has been created. After installation, the setup page reports that the system is already installed.

## 11. Back up the database

Use phpMyAdmin: select `smart_attendance_system`, choose **Export**, select SQL format, and download the backup. Keep the backup somewhere safe. The PHP source folder alone does not contain the live attendance records; those are stored in MySQL.

## 12. Common errors

| Problem | What to check |
|---|---|
| Page not found | Folder name and URL must match. Check that the project is under `htdocs`. |
| PHP code downloads or displays as text | Open through Apache at `http://localhost/...`, not by double-clicking the file. |
| Database connection failed | MySQL service, port, credentials and `pdo_mysql` extension. |
| Student cannot check in | Confirm the student is enrolled in the course and the session is still open. |
| QR library cannot load | Check internet access. Use the six-digit manual code if the CDN is unavailable. |
| Camera does not start | Grant camera permission, use HTTPS on a phone, and check browser compatibility. |
| QR opens the wrong address on phone | Open the lecturer portal using the laptop's local IP address, not `localhost`. |
| Code has expired | Ask the lecturer to create a new session or display the current active code. |

## Important

This is a student HCI project implementation. Test with sample accounts before entering real student information. Ask the university to approve its attendance policy, privacy handling and security before any official deployment.

## Lecturer-requested features update

After copying the updated project files over the existing project folder, keep your current `config/database.php` settings. Do not delete or recreate your existing database. The support request table is created automatically the first time Help & Support or the administrator Support inbox is opened. See `FEATURES_ADDED_GUIDE.md` for details.

The AI Assistant has a built-in help mode and does not require an API key for the preset guidance. To enable live generative AI answers, follow the optional instructions in `FEATURES_ADDED_GUIDE.md`. Never put a real API key in a ZIP file that you share.
