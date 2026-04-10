<?php
/**
 * Fix Missing Database Columns
 * Adds the 'balance' column to ch_users table if it doesn't exist
 */

require_once __DIR__ . '/config.php';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "<h2>Fixing Database Table Structure</h2>";
echo "<hr>";

// Check and fix ch_users table
echo "<h3>Checking ch_users table...</h3>";

try {
    // Get current columns
    $stmt = $pdo->query("DESCRIBE ch_users");
    $columns = $stmt->fetchAll();
    $column_names = array_map(fn($c) => $c['Field'], $columns);
    
    echo "Current columns: " . implode(", ", $column_names) . "<br>";
    
    // Check for missing columns
    $missing = [];
    
    if (!in_array('balance', $column_names)) {
        echo "✗ Missing column: balance<br>";
        $missing[] = 'balance';
    }
    
    if (!in_array('email_verified', $column_names)) {
        echo "✗ Missing column: email_verified<br>";
        $missing[] = 'email_verified';
    }
    
    if (!in_array('last_login', $column_names)) {
        echo "✗ Missing column: last_login<br>";
        $missing[] = 'last_login';
    }
    
    // Add missing columns
    if (!empty($missing)) {
        echo "<h3>Adding missing columns...</h3>";
        
        if (in_array('balance', $missing)) {
            try {
                $pdo->exec("ALTER TABLE ch_users ADD COLUMN balance DECIMAL(32,8) NOT NULL DEFAULT 0.00");
                echo "✓ Added column: balance<br>";
            } catch (Exception $e) {
                echo "⚠ Could not add balance: " . $e->getMessage() . "<br>";
            }
        }
        
        if (in_array('email_verified', $missing)) {
            try {
                $pdo->exec("ALTER TABLE ch_users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0");
                echo "✓ Added column: email_verified<br>";
            } catch (Exception $e) {
                echo "⚠ Could not add email_verified: " . $e->getMessage() . "<br>";
            }
        }
        
        if (in_array('last_login', $missing)) {
            try {
                $pdo->exec("ALTER TABLE ch_users ADD COLUMN last_login DATETIME DEFAULT NULL");
                echo "✓ Added column: last_login<br>";
            } catch (Exception $e) {
                echo "⚠ Could not add last_login: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "✓ All required columns present<br>";
    }
    
    // Verify structure now
    echo "<h3>Verifying table structure...</h3>";
    $stmt = $pdo->query("DESCRIBE ch_users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<hr>";
echo "<h3>✓ Database structure fixed!</h3>";
echo "<p><a href='quick-fix.php'>← Go back to Database Setup</a></p>";
?>
