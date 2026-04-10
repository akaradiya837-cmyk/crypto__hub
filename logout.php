<?php
require_once __DIR__ . '/auth.php';
// Log out server-side session
logout_user();
// Also clear client-side state by redirecting to a small page that clears localStorage
// Then redirect to index.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Logging out...</title>
    <script>
        try { localStorage.clear(); } catch(e) {}
        window.location.href = 'index.php';
    </script>
</head>
<body>
</body>
</html>
