<?php
/**
 * PHP and Error Log Diagnostic
 * Shows PHP configuration and any error messages
 */

echo "<h2>PHP & Error Log Diagnostic</h2>";
echo "<hr>";

// PHP Information
echo "<h3>PHP Configuration</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";
echo "OS: " . php_uname() . "<br>";

// Check if running in CLI or web
if (PHP_SAPI === 'cli') {
    echo "<p style='color:red;'><strong>⚠ Running in CLI mode, not web mode</strong></p>";
}

echo "<h3>PHP Extensions</h3>";
echo "PDO Extension: " . (extension_loaded('pdo') ? "✓ Loaded" : "✗ NOT Loaded") . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? "✓ Loaded" : "✗ NOT Loaded") . "<br>";
echo "MySQLi Extension: " . (extension_loaded('mysqli') ? "✓ Loaded" : "✗ NOT Loaded") . "<br>";

echo "<h3>Error Logging</h3>";
echo "error_reporting: " . error_reporting() . "<br>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "log_errors: " . ini_get('log_errors') . "<br>";
echo "error_log: " . ini_get('error_log') . "<br>";

// Try to read PHP error log
$error_log = ini_get('error_log');
echo "<h3>Recent Error Log Entries</h3>";

if ($error_log && file_exists($error_log)) {
    echo "<p>Reading from: $error_log</p>";
    $lines = file($error_log);
    $recent = array_slice($lines, -50); // Last 50 lines
    
    echo "<pre style='background:#f0f0f0; padding:10px; border-radius:5px; max-height:400px; overflow-y:auto;'>";
    foreach ($recent as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>No error log file found at: " . htmlspecialchars($error_log) . "</p>";
    
    // Check common locations
    echo "<h3>Checking common error log locations:</h3>";
    $common_logs = [
        'c:/laragon/logs/php/error.log',
        'c:/laragon/logs/php.log',
        'c:/laragon/tmp/error.log',
        ini_get('error_log'),
    ];
    
    foreach ($common_logs as $log) {
        if ($log && file_exists($log)) {
            echo "Found error log at: $log<br>";
        }
    }
}

// Check file permissions
echo "<h3>File Permissions</h3>";
$check_files = [
    'users.json',
    'otp_data',
    'config.php',
    'db.php',
];

foreach ($check_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "$file: $perms ";
        echo is_writable($path) ? "✓ Writable" : "✗ Read-only";
        echo "<br>";
    }
}

echo "<h3>Database Configuration (from config.php)</h3>";
require_once __DIR__ . '/config.php';
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "use_db_enabled(): " . (use_db_enabled() ? "TRUE (using MySQL)" : "FALSE (will use JSON)") . "<br>";

// Detailed use_db_enabled() checks
echo "<h3>Debug use_db_enabled() Checks</h3>";
echo "FORCE_JSON_STORAGE defined: " . (defined('FORCE_JSON_STORAGE') ? "YES" : "NO") . "<br>";
echo "DB_HOST defined: " . (defined('DB_HOST') ? "YES" : "NO") . "<br>";
echo "DB_NAME defined: " . (defined('DB_NAME') ? "YES" : "NO") . "<br>";
echo "PDO extension loaded: " . (extension_loaded('pdo') ? "YES" : "NO") . "<br>";

$drivers = [];
try {
    $drivers = PDO::getAvailableDrivers();
    echo "Available PDO drivers: " . implode(", ", $drivers) . "<br>";
    echo "MySQL driver available: " . (in_array('mysql', $drivers) ? "YES" : "NO") . "<br>";
} catch (Exception $e) {
    echo "Error getting drivers: " . $e->getMessage() . "<br>";
}

?>
