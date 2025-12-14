<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wmsu_bus_system');

// Site Configuration
define('SITE_NAME', 'WMSU Bus Reserve System');
define('SITE_URL', 'http://localhost/wmsu_bus_reserve_system/');

// ✅ Email Configuration - FINAL VERSION
define('ADMIN_EMAIL', 'wmsubussystem@gmail.com');  // ✅ Admin notifications inbox
define('FROM_EMAIL', 'wmsubussystem@gmail.com');   // ✅ Professional sender email
define('FROM_NAME', 'WMSU Bus System Support');    // ✅ Display name

// Password Requirements
define('MIN_PASSWORD_LENGTH', 8);

// Timezone
date_default_timezone_set('Asia/Manila');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Error Reporting (Turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>