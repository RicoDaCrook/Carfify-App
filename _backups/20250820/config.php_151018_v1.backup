<?php
// Carfify Configuration

// Datenbank
const DB_HOST = 'localhost';
const DB_NAME = 'carfify';
const DB_USER = 'root';
const DB_PASS = '';

// App Settings
const APP_NAME = 'Carfify';
const APP_VERSION = '4.0';
const DEBUG_MODE = true;

// PWA Settings
const PWA_NAME = 'Carfify Auto Manager';
const PWA_SHORT_NAME = 'Carfify';
const PWA_THEME_COLOR = '#1a73e8';
const PWA_BACKGROUND_COLOR = '#ffffff';

// Upload Limits
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

// Error Handling
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Session Konfiguration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
?>