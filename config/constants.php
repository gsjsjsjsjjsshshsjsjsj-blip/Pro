<?php
/**
 * Application Constants
 * Define system-wide constants and configuration values
 */

// Application Information
define('APP_NAME', 'Medical Appointment System');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Professional Healthcare Management System');

// Directory Paths
define('ROOT_PATH', dirname(__DIR__));
define('FRONTEND_PATH', ROOT_PATH . '/frontend');
define('BACKEND_PATH', ROOT_PATH . '/backend');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('DATABASE_PATH', ROOT_PATH . '/database');

// URL Paths
define('BASE_URL', '/');
define('FRONTEND_URL', BASE_URL . 'frontend/');
define('BACKEND_URL', BASE_URL . 'backend/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_appointment_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'medical_app_session');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// User Roles
define('ROLE_PATIENT', 'patient');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_ADMIN', 'admin');

// Appointment Status
define('APPOINTMENT_SCHEDULED', 'scheduled');
define('APPOINTMENT_CONFIRMED', 'confirmed');
define('APPOINTMENT_COMPLETED', 'completed');
define('APPOINTMENT_CANCELLED', 'cancelled');

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', ASSETS_PATH . '/uploads/');

// Email Configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@medical-system.local');
define('FROM_NAME', APP_NAME);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Date and Time Formats
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_TIME_FORMAT', 'g:i A');

// Working Hours
define('WORKING_HOURS_START', '08:00');
define('WORKING_HOURS_END', '18:00');
define('APPOINTMENT_DURATION', 30); // minutes

// System Settings
define('SYSTEM_TIMEZONE', 'UTC');
define('DEFAULT_LANGUAGE', 'en');
define('MAINTENANCE_MODE', false);

// Logging
define('LOG_PATH', ROOT_PATH . '/logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Cache Configuration
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600); // 1 hour

// API Configuration
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per hour

// Error Reporting
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// Set timezone
date_default_timezone_set(SYSTEM_TIMEZONE);