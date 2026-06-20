<?php
/**
 * Quick Database Migration Tool
 * Created to add bulk key tables without phpMyAdmin
 */

if (file_exists('config.php')) {
    include 'config.php';
} else {
    // Fallback defaults matching api.php
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', 'edut8795_autoclipper');
    if (!defined('DB_USER')) define('DB_USER', 'edut8795_autoclipper');
    if (!defined('DB_PASS')) define('DB_PASS', '!*Maxwin711');
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<html><body style='font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0;'>";
    echo "<h1>🛠️ Database Migration Tool</h1>";
    echo "<div style='border: 1px solid #333; padding: 15px; border-radius: 5px; background: #222;'>";

    // 1. Create apify_keys
    echo "Checking 'apify_keys' table... ";
    $sql1 = "CREATE TABLE IF NOT EXISTS `apify_keys` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `api_key` VARCHAR(255) UNIQUE NOT NULL,
        `status` ENUM('active', 'limit', 'dead', 'cooldown') DEFAULT 'active',
        `error_msg` TEXT DEFAULT NULL,
        `cooldown_until` DATETIME DEFAULT NULL,
        `last_used` TIMESTAMP NULL DEFAULT NULL,
        `last_checked` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_api_key` (`api_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql1);
    echo "<span style='color: #4ade80;'>DONE</span><br>";

    // 2. Create deepgram_keys
    echo "Checking 'deepgram_keys' table... ";
    $sql2 = "CREATE TABLE IF NOT EXISTS `deepgram_keys` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `api_key` VARCHAR(255) UNIQUE NOT NULL,
        `status` ENUM('active', 'limit', 'dead', 'cooldown') DEFAULT 'active',
        `error_msg` TEXT DEFAULT NULL,
        `cooldown_until` DATETIME DEFAULT NULL,
        `last_used` TIMESTAMP NULL DEFAULT NULL,
        `last_checked` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_api_key` (`api_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql2);
    echo "<span style='color: #4ade80;'>DONE</span><br>";

    // 3. Update gemini_keys (Add cooldown_until)
    echo "Checking 'gemini_keys' schema... ";
    try {
        // Simple check mostly compatible
        $stmt = $pdo->query("SHOW COLUMNS FROM `gemini_keys` LIKE 'cooldown_until'");
        $col = $stmt->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE `gemini_keys` ADD COLUMN `cooldown_until` DATETIME DEFAULT NULL;");
            echo "<span style='color: #4ade80;'>UPDATED (Added cooldown_until)</span>";
        } else {
            echo "<span style='color: #60a5fa;'>OK (Column exists)</span>";
        }
    } catch (Exception $e) {
        echo "<span style='color: #f87171;'>SKIPPED (Table likely missing or error: " . $e->getMessage() . ")</span>";
    }
    echo "<br>";

    echo "</div>";
    echo "<h3 style='color: #fff;'>✅ Migration Complete successfully!</h3>";
    echo "<p>You can now close this tab and return to the Admin Dashboard.</p>";
    echo "</body></html>";

} catch (PDOException $e) {
    echo "<html><body style='font-family: sans-serif; padding: 20px; background: #fff0f0; color: #d00;'>";
    echo "<h1>❌ Database Error</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</body></html>";
}
?>
