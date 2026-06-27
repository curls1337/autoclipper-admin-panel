<?php
include 'config.php';

// Simple security check using ADMIN_KEY
if (($_GET['key'] ?? '') !== ADMIN_KEY) {
    die("Unauthorized. Please provide valid key (e.g. ?key=" . ADMIN_KEY . ").");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if we want to reset or create
    if (isset($_GET['username']) && isset($_GET['password'])) {
        $username = $_GET['username'];
        $password = $_GET['password'];
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ?, is_active = 1 WHERE username = ?");
            $stmt->execute([$hash, $username]);
            echo "<b>SUCCESS:</b> Password for user '$username' has been reset successfully.<br><br>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$username, $hash]);
            echo "<b>SUCCESS:</b> User '$username' has been created successfully.<br><br>";
        }
    }
    
    // List existing users
    $stmt = $pdo->query("SELECT id, username, is_active FROM admin_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Admin Users List:</h3>";
    if (empty($users)) {
        echo "No admin users found in database.<br>";
    } else {
        foreach ($users as $u) {
            echo "- ID: {$u['id']} | Username: <b>{$u['username']}</b> | Active: {$u['is_active']}<br>";
        }
    }
    
    echo "<br><b>How to reset/create user:</b><br>";
    echo "Add parameters to URL: <code>?key=" . ADMIN_KEY . "&username=YOUR_USERNAME&password=YOUR_PASSWORD</code><br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
