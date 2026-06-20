<?php
/**
 * Database Configuration
 * Update kredensial ini sesuai dengan hosting Anda
 */

// Database credentials
define('DB_HOST', getenv('DB_HOST') ?: 'autoclipper-db-olwo2-mysql.autoclipper-db-olwo2.svc.cluster.local');
define('DB_NAME', getenv('DB_NAME') ?: 'edut8795_autoclipper');
define('DB_USER', getenv('DB_USER') ?: 'edut8795_autoclipper');
define('DB_PASS', getenv('DB_PASS') ?: 'Maxwin711@2026');

// Admin Key - CHANGE THIS!
define('ADMIN_KEY', getenv('ADMIN_KEY') ?: 'Tak-ada-yang-abadi');

// API Settings
define('API_VERSION', '5.1.2');
define('API_TIMEZONE', 'Asia/Jakarta');

// Set timezone
date_default_timezone_set(API_TIMEZONE);
?>
