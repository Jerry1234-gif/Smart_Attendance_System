# Dependencies and licenses

The application-specific backend and user interface code in this folder is written for this project and does not require Composer or npm.

Two browser libraries are loaded from public CDNs:

1. **QRCode.js 1.0.0** - used to render the lecturer's check-in link as a QR code. Source: https://github.com/davidshimjs/qrcodejs . The upstream project is distributed under the MIT license.
2. **html5-qrcode 2.3.8** - used to read a QR code through the student's browser camera. Source: https://github.com/mebjas/html5-qrcode . The upstream project is distributed under the Apache License 2.0.

The corresponding CDN URLs are set in `config/app.php`. If you change the CDN or library version, verify the upstream license and compatibility. A network connection is required to download these scripts into the browser. The manual attendance-code method does not need camera access.

Other runtime components are PHP's PDO/PDO MySQL extension and MySQL/MariaDB, provided by XAMPP. No third-party PHP framework is required.
