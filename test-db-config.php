<?php
/**
 * Database Configuration Test
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "<h2>Database Configuration Check</h2>";
echo "<hr>";

echo "<h3>Configuration Constants</h3>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_PORT: " . DB_PORT . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_CHARSET: " . DB_CHARSET . "<br>";

echo "<h3>PHP Extensions</h3>";
echo "PDO Extension: " . (extension_loaded('pdo') ? "✓ Loaded" : "✗ Not Loaded") . "<br>";
echo "MySQLi Extension: " . (extension_loaded('mysqli') ? "✓ Loaded" : "✗ Not Loaded") . "<br>";

echo "<h3>PDO Drivers</h3>";
$drivers = [];
try {
    $drivers = PDO::getAvailableDrivers();
    echo "Available: " . implode(", ", $drivers) . "<br>";
    echo "MySQL available: " . (in_array('mysql', $drivers) ? "✓ Yes" : "✗ No") . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Database Connection</h3>";
$pdo = getDB();
if ($pdo) {
    echo "✓ Database connected successfully<br>";
    
    // Test tables
    echo "<h3>Tables Check</h3>";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'ch_%'");
        $tables = $stmt->fetchAll();
        echo "Found " . count($tables) . " tables:<br>";
        foreach ($tables as $table) {
            $table_name = $table[0];
            echo "  - $table_name<br>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
    
    // Check ch_otps specifically
    echo "<h3>ch_otps Table Structure</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE ch_otps");
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            echo $col['Field'] . " (" . $col['Type'] . ")" . ($col['Null'] == 'NO' ? " NOT NULL" : "") . "<br>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ Database not connected<br>";
    echo "<h3>Fallback Storage</h3>";
    echo "Using JSON file storage: " . (file_exists(__DIR__ . '/users.json') ? "✓ users.json exists" : "✗ users.json not found") . "<br>";
}

?>
