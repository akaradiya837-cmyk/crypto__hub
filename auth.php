<?php
// Simple auth helper
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user_email'])) {
        header('Location: index.php');
        exit;
    }
}

function login_user($email, $name) {
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
}

function logout_user() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
}

function is_admin() {
    return !empty($_SESSION['is_admin']);
}

function require_admin_login() {
    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

function login_admin($email, $name) {
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_email'] = $email;
    $_SESSION['admin_name'] = $name;
}

function require_user_or_admin_view() {
    // Allow when user is logged in, or when admin is logged in and requests user-mode via ?as_admin=1
    if (isset($_SESSION['user_email'])) {
        return;
    }
    if (is_admin() && (!empty($_GET['as_admin']) && $_GET['as_admin'] == '1')) {
        return;
    }
    header('Location: index.php');
    exit;
}

/**
 * Find user by email. Tries DB first (if enabled), falls back to users.json.
 * Returns associative array or null.
 */
function find_user_by_email($email)
{
    $email = strtolower($email);
    
    $pdo = getDB();
    if ($pdo) {
        $stmt = $pdo->prepare('SELECT * FROM ch_users WHERE LOWER(email) = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    }

    // Fallback to JSON
    $usersFile = __DIR__ . '/users.json';
    if (file_exists($usersFile)) {
        $raw = file_get_contents($usersFile);
        $users = json_decode($raw, true);
        if (is_array($users)) {
            // Case-insensitive search
            foreach ($users as $userEmail => $userData) {
                if (strtolower($userEmail) === $email) {
                    return [
                        'email' => $userEmail,
                        'full_name' => $userData['fullName'] ?? ($userData['name'] ?? $userEmail),
                        'phone' => $userData['phone'] ?? null,
                        'password' => $userData['password'] ?? null,
                        'created_at' => $userData['created_at'] ?? null,
                        'email_verified' => !empty($userData['email_verified']) ? 1 : 0,
                    ];
                }
            }
        }
    }

    return null;
}

/**
 * Verify credentials for login. Returns user array on success, null on failure.
 */
function verify_user_credentials($email, $password)
{
    $user = find_user_by_email($email);
    if (!$user) return null;

    $stored = $user['password'] ?? null;
    if (!$stored) return null;

    if (password_verify($password, $stored)) {
        // Normalize name key
        if (isset($user['full_name'])) {
            $user['fullName'] = $user['full_name'];
        }
        return $user;
    }

    return null;
}

/**
 * Find admin by email. Uses admin-users.json only (no database table).
 */
function find_admin_by_email($email)
{
    $email = strtolower($email);
    $adminsFile = __DIR__ . '/admin-users.json';
    if (file_exists($adminsFile)) {
        $raw = file_get_contents($adminsFile);
        $admins = json_decode($raw, true);
        if (is_array($admins)) {
            // Case-insensitive search
            foreach ($admins as $adminEmail => $adminData) {
                if (strtolower($adminEmail) === $email) {
                    $adminData['email'] = $adminEmail;
                    return $adminData;
                }
            }
        }
    }

    return null;
}

/**
 * Verify admin credentials. Returns admin array on success, null on failure.
 */
function verify_admin_credentials($email, $password)
{
    $admin = find_admin_by_email($email);
    if (!$admin) return null;
    $stored = $admin['password'] ?? null;
    if (!$stored) return null;
    if (password_verify($password, $stored)) {
        if (isset($admin['full_name'])) $admin['fullName'] = $admin['full_name'];
        return $admin;
    }
    return null;
}
