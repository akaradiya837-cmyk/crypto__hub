<?php
// Database configuration for local Laragon environment
// Update these values if your MySQL credentials differ.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cryptohub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
// Default DB port used by Laragon / MySQL
define('DB_PORT', '3306');

// Administrator credentials (fixed single admin). Change as needed.
define('ADMIN_EMAIL', 'cryptoadminpirouser@gmail.com');
define('ADMIN_PASSWORD', 'Admin123');

/**
 * Returns whether the application should use the MySQL database.
 * Falls back to JSON storage when PDO/MySQL is unavailable or a
 * explicit flag disables DB usage.
 */
function use_db_enabled()
{
	if (defined('FORCE_JSON_STORAGE') && FORCE_JSON_STORAGE) {
		return false;
	}

	if (!defined('DB_HOST') || !defined('DB_NAME')) {
		return false;
	}

	if (!extension_loaded('pdo')) {
		return false;
	}

	try {
		$drivers = PDO::getAvailableDrivers();
	} catch (Throwable $e) {
		return false;
	}

	if (!in_array('mysql', $drivers, true)) {
		return false;
	}

	return true;
}

// SMTP configuration for sending OTP email via Gmail
// SMTP configuration removed — using PHP `mail()` or external services.
// If you need SMTP later, re-add `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, and `SMTP_PASSWORD` here.

// OTP removed from application — email/verification handled without OTP.