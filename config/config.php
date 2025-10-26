<?php
// Application configuration
define('APP_NAME', 'SEPNAS Event Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://your-domain.infinityfreeapp.com/'); // Replace with your actual domain

// OneSignal configuration
define('ONESIGNAL_APP_ID', 'bbdac752-319a-4245-9f1b-7ef78cf88bbb'); // Replace with your OneSignal App ID
define('ONESIGNAL_REST_API_KEY', 'os_v2_org_y5na3keuujdm7mmrxupo3ga3xwtokjmsqfyemiukloqjk2eyfseyfwafbv5id5j5uctxp3fq53jvhs2j6kkadzfioluex34zp4tgsii'); // Replace with your OneSignal REST API Key

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
