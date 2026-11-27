# Smart Attendance System – Lecturer-Requested Feature Update

This update adds the requested features while retaining the existing Smart Attendance website pages, design, PHP/MySQL attendance flow and Limkokwing student ID rule.

## Features included

1. **Increase text size** – choose Normal, Large or Extra large in the Accessibility & display bar.
2. **AI integration** – the AI Assistant page has a built-in help mode and can connect to the OpenAI Responses API when a private API key is configured on the PHP server.
3. **Add users** – this feature already existed in `admin/users.php`; it is retained and remains available to administrators from the Users navigation link.
4. **Change colours** – choose the original green, teal, blue, purple or orange accent theme.
5. **Dark/light mode** – switch between light mode and dark mode.
6. **Disability/accessibility use** – high contrast, a highly readable font, underlined links, reduced motion, larger cursor and a skip-to-content link.
7. **Text to speech** – use Read page aloud / Stop reading. This uses the browser's built-in Web Speech API; a recent browser with speech support is required.
8. **Help and support** – FAQs and a form for sending a support request. Administrators can review and update request status in Support inbox.

## Using the features

- Sign in and use **Accessibility & display** below the main navigation for text size, colour, mode and accessibility options.
- Use **Read page aloud** to hear the main content on the current page. Use **Stop reading** to stop speech.
- Open **AI Assistant** for guided system help.
- Open **Help & Support** to read FAQs or submit a request.
- Administrators use **Users** to create student and lecturer accounts. The existing account creation feature has not been replaced.
- Administrators use **Support inbox** to review support requests and set them to Open, In progress or Resolved.

Display preferences are stored in local browser storage on the device being used. They are not stored in the attendance database and do not affect other users' devices.

## Setting up live AI (optional)

The AI page works immediately in built-in help mode without an API key. For live generative AI answers, an administrator must configure an API key on the PHP server. API usage may incur charges depending on the API account and its limits.

1. Create an API key in the OpenAI Platform.
2. In the project `config` folder, copy `ai.local.example.php` and rename the copy to `ai.local.php`.
3. Open `config/ai.local.php` and replace `PASTE_YOUR_PRIVATE_API_KEY_HERE` with the private API key. Keep the key on the server only.
4. Save the file and refresh the AI Assistant page. If needed, restart Apache in XAMPP.
5. Live AI requests require PHP cURL. In XAMPP, open Apache's `php.ini`, make sure the cURL extension line is enabled (for example `extension=curl` rather than `;extension=curl`), save and restart Apache.
6. Do not upload, email or share `ai.local.php` or a ZIP containing your real key.

The application also accepts the `OPENAI_API_KEY` and `OPENAI_MODEL` server environment variables. The default model setting is `gpt-6.2-astra`; it can be changed in `ai.local.php` if the API account uses another model. The API key is sent only from the PHP server, not from browser JavaScript. See [OpenAI's API key safety guidance](https://help.openai.com/en/articles/5112595-best-practices-for-api-key-safety) and the [Responses API quickstart](https://platform.openai.com/docs/overview).

**Important AI limits:** the assistant only receives the question typed into the chat. It does not connect to student records, read attendance history, make account changes or mark attendance. Do not type passwords, API keys or private student details into the AI chat.

## Database update

- New installations receive the `support_requests` table from `database/schema.sql`.
- For existing installations, opening `help_support.php` or the administrator Support inbox creates the table automatically with `CREATE TABLE IF NOT EXISTS`.
- The migration is also included at `database/migrations/002_add_support_requests.sql` if the administrator prefers to apply it manually in phpMyAdmin.
- No attendance records or existing user accounts are deleted by this update.

## Preserved behaviour

- The existing PHP/MySQL application and its page structure are retained.
- Existing student, lecturer and administrator roles remain.
- The Users page continues to create student and lecturer accounts.
- Student IDs must still match `90500` followed by one or more digits, for example `905001234`. The browser and server validation were not removed or weakened.
- Existing QR attendance sessions, attendance checks, history and reports remain in place.

## Testing checklist

- [ ] Sign in as a student, lecturer and administrator and confirm each role still opens its correct dashboard.
- [ ] Open Accessibility & display and try each text size, colour theme and mode.
- [ ] Enable and disable each accessibility option; refresh to confirm preferences are retained on that device.
- [ ] Use Read page aloud and Stop reading in a supported browser.
- [ ] Open AI Assistant and test its built-in help mode. If an API key is configured, test a live question.
- [ ] As administrator, create a student with an ID beginning `90500`; confirm an ID with another prefix is rejected.
- [ ] Submit a test Help & Support request and confirm it appears in the administrator Support inbox.
- [ ] Update the support request status and confirm it changes.
- [ ] Test the existing QR session and student attendance flow.
