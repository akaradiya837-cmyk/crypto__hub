<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a PDO instance or null if DB is disabled or connection fails.
 * Usage: $pdo = getDB(); if (!$pdo) { // fallback to JSON }
 * Note: avoid nested C-style comments in this docblock.
 */
function getDB()
{
    static $pdo = null;

    if ($pdo !== null) return $pdo;
    if (!use_db_enabled()) return null;

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('DB connection error: ' . $e->getMessage());
        return null;
    }

    return $pdo;
}
