<?php
/**
 * Auto Clipper AI - Admin & License API
 * Refactored Version
 */

if (file_exists('config.php')) {
    include 'config.php';
} else {
    // Fallback if config missing
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'edut8795_autoclipper');
    define('DB_USER', 'edut8795_autoclipper');
    define('DB_PASS', '!*Maxwin711');
    define('ADMIN_KEY', 'Tak-ada-yang-abadi');
    date_default_timezone_set('Asia/Jakarta');
}

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Database Connection ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]));
}

// --- Auth Middleware ---
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

$public_actions = ['validate', 'login'];
$is_authenticated = isset($_SESSION['admin_auth']) || ($input['admin_key'] ?? '') === ADMIN_KEY;

if (!in_array($action, $public_actions) && !$is_authenticated) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']));
}

// --- Router ---
// --- Router ---
try {
    switch ($action) {
        case 'login': login($pdo, $input); break;
        case 'logout': logout(); break;
        case 'validate': validateLicense($pdo, $input); break;
        
        // License Actions
        case 'generate': generateLicense($pdo, $input); break;
        case 'list_licenses': listLicenses($pdo, $input); break;
        case 'update_license': updateLicense($pdo, $input); break;
        case 'delete_license': deleteLicense($pdo, $input); break;
        case 'broadcast': sendBroadcastToAll($pdo, $input); break;
        
        // Credential Actions
        case 'save_credential': saveCredential($pdo, $input); break;
        case 'get_credentials': getCredentials($pdo, $input); break;
        case 'get_gemini_keys': getGeminiKeys($pdo, $input); break;
        case 'add_gemini_keys': addGeminiKeys($pdo, $input); break;
        case 'test_gemini_key': testGeminiKey($pdo, $input); break;
        case 'delete_gemini_key': deleteGeminiKey($pdo, $input); break;
        
        case 'get_apify_keys': getApifyKeys($pdo, $input); break;
        case 'add_apify_keys': addApifyKeys($pdo, $input); break;
        case 'test_apify_key': testApifyKey($pdo, $input); break;
        case 'delete_apify_key': deleteApifyKey($pdo, $input); break;

        case 'get_deepgram_keys': getDeepgramKeys($pdo, $input); break;
        case 'add_deepgram_keys': addDeepgramKeys($pdo, $input); break;
        case 'test_deepgram_key': testDeepgramKey($pdo, $input); break;
        case 'delete_deepgram_key': deleteDeepgramKey($pdo, $input); break;
        
        // YOLO Actions
        case 'get_yolo_keys': getYoloKeys($pdo, $input); break;
        case 'add_yolo_keys': addYoloKeys($pdo, $input); break;
        case 'test_yolo_key': testYoloKey($pdo, $input); break;
        case 'delete_yolo_key': deleteYoloKey($pdo, $input); break;
        
        case 'report_key_failure': reportKeyFailure($pdo, $input); break;
        
        // YouTube Cookies Pool Actions
        case 'get_youtube_cookies': getYoutubeCookies($pdo, $input); break;
        case 'add_youtube_cookie': addYoutubeCookie($pdo, $input); break;
        case 'delete_youtube_cookie': deleteYoutubeCookie($pdo, $input); break;
        case 'toggle_youtube_cookie_status': toggleYoutubeCookieStatus($pdo, $input); break;

        // Reseller Actions
        case 'list_resellers': listResellers($pdo, $input); break;
        case 'add_reseller': addReseller($pdo, $input); break;
        case 'update_reseller': updateReseller($pdo, $input); break;
        case 'delete_reseller': deleteReseller($pdo, $input); break;
        case 'test_credential': testCredential($pdo, $input); break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}

// --- Action Handlers ---

function login($pdo, $input) {
    $u = $input['username'] ?? '';
    $p = $input['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
    $stmt->execute([$u]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($p, $user['password_hash'])) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        echo json_encode(['success' => true, 'message' => 'Welcome back, ' . $u]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
}

function logout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
}

function validateLicense($pdo, $input) {
    $key = $input['license_key'] ?? '';
    $hwid = $input['hwid'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ? AND status = 'active' AND expiration > NOW()");
    $stmt->execute([$key]);
    $lic = $stmt->fetch();
    
    if (!$lic) {
        die(json_encode(['success' => false, 'message' => 'License invalid or expired']));
    }
    
    // HWID Lock check
    if (!empty($lic['hwid']) && $lic['hwid'] !== $hwid) {
        die(json_encode(['success' => false, 'message' => 'License locked to another device']));
    }
    
    // Auto-activate on first use
    if (empty($lic['hwid'])) {
        $pdo->prepare("UPDATE licenses SET hwid = ?, activated_at = NOW() WHERE id = ?")->execute([$hwid, $lic['id']]);
    }
    
    $pdo->prepare("UPDATE licenses SET last_seen = NOW() WHERE id = ?")->execute([$lic['id']]);
    
    // Prepare data
    $data = [
        'expiration' => $lic['expiration'],
        'features' => [
            'autocaption' => (bool)$lic['feature_autocaption'],
            'youtube_upload' => (bool)$lic['feature_youtube_upload'],
            'tiktok_upload' => (bool)$lic['feature_tiktok_upload'],
            'movie_recap' => (bool)$lic['feature_movie_recap'],
            'lyric_matcher' => (bool)$lic['feature_lyric_matcher'], // New Feature
            'export_cloud' => (bool)$lic['feature_export_cloud']
        ],
        'credentials' => [],
        'message' => $lic['message'],
        'running_text' => (bool)$lic['running_text'],
        'auto_pilot' => (bool)($lic['auto_pilot'] ?? 0), // NEW: Auto Pilot Flag
        'user_msg' => $lic['user_msg'],
        'plan' => 'Licensed',
        'owner_name' => $lic['owner_name'] // NEW: Send owner name
    ];
    
    // Provision Credentials
    $types = [
        'gemini', 'groq', 'apify', 'deepgram', 
        'google_client_id', 'google_client_secret', 'google_redirect_uri',
        'youtube_client_id', 'youtube_client_secret', 'youtube_redirect_uri',
        'tiktok_client_key', 'tiktok_client_secret', 'tiktok_redirect_uri',
        'youtube_cookies'
    ];
    foreach ($types as $t) {
        $flag = 'provide_gemini_key'; // default
        if (strpos($t, 'gemini') !== false) $flag = 'provide_gemini_key';
        if (strpos($t, 'groq') !== false) $flag = 'provide_groq_key';
        if (strpos($t, 'apify') !== false) $flag = 'provide_apify_key';
        if (strpos($t, 'deepgram') !== false) $flag = 'provide_deepgram_key';
        if (strpos($t, 'google') !== false) $flag = 'provide_google_oauth';
        if (strpos($t, 'youtube_cookies') !== false) $flag = 'feature_youtube_upload';
        elseif (strpos($t, 'youtube') !== false) $flag = 'provide_google_oauth'; // YouTube uses Google OAuth flag
        if (strpos($t, 'tiktok') !== false) $flag = 'provide_tiktok_key';
        
        if (isset($lic[$flag]) && $lic[$flag]) {
            if ($t === 'gemini') {
                $stmt = $pdo->query("SELECT api_key FROM gemini_keys WHERE status = 'active' AND (cooldown_until IS NULL OR cooldown_until < NOW()) ORDER BY RAND() LIMIT 10");
                $pool = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $data['credentials']['gemini_pool'] = $pool;
                $data['credentials'][$t] = $pool[0] ?? fetchCredential($pdo, $t);
            } 
            elseif ($t === 'apify') {
                try {
                    $stmt = $pdo->query("SELECT api_key FROM apify_keys WHERE status = 'active' AND (cooldown_until IS NULL OR cooldown_until < NOW()) ORDER BY RAND() LIMIT 10");
                    $pool = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $data['credentials']['apify_pool'] = $pool;
                    $data['credentials'][$t] = $pool[0] ?? fetchCredential($pdo, $t);
                } catch (Exception $e) { $data['credentials'][$t] = fetchCredential($pdo, $t); }
            }
            elseif ($t === 'deepgram') {
                try {
                    $stmt = $pdo->query("SELECT api_key FROM deepgram_keys WHERE status = 'active' AND (cooldown_until IS NULL OR cooldown_until < NOW()) ORDER BY RAND() LIMIT 10");
                    $pool = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $data['credentials']['deepgram_pool'] = $pool;
                    $data['credentials'][$t] = $pool[0] ?? fetchCredential($pdo, $t);
                } catch (Exception $e) { $data['credentials'][$t] = fetchCredential($pdo, $t); }
            }
            elseif ($t === 'youtube_cookies') {
                try {
                    $stmt = $pdo->query("SELECT cookie_value FROM youtube_cookies WHERE status = 'active' ORDER BY RAND() LIMIT 1");
                    $cookie = $stmt->fetchColumn();
                    if ($cookie) {
                        $data['credentials'][$t] = $cookie;
                    } else {
                        $data['credentials'][$t] = fetchCredential($pdo, $t);
                    }
                } catch (Exception $e) {
                    $data['credentials'][$t] = fetchCredential($pdo, $t);
                }
            }
            else {
                $data['credentials'][$t] = fetchCredential($pdo, $t);
            }
        }
    }
    
    // Fetch active YOLO credentials from pool (endpoint and key)
    try {
        $stmt = $pdo->query("SELECT endpoint_url, api_key FROM yolo_keys WHERE status = 'active' ORDER BY RAND() LIMIT 1");
        $yolo = $stmt->fetch();
        if ($yolo) {
            $data['credentials']['yolo_endpoint'] = $yolo['endpoint_url'];
            $data['credentials']['yolo_key'] = $yolo['api_key'];
        } else {
            // Fallback to static credentials if database pool is empty
            $data['credentials']['yolo_endpoint'] = fetchCredential($pdo, 'yolo_endpoint');
            $data['credentials']['yolo_key'] = fetchCredential($pdo, 'yolo_key');
        }
    } catch (Exception $e) {
        $data['credentials']['yolo_endpoint'] = fetchCredential($pdo, 'yolo_endpoint');
        $data['credentials']['yolo_key'] = fetchCredential($pdo, 'yolo_key');
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function fetchCredential($pdo, $type) {
    // ORDER BY id DESC to get the LATEST added key (fixes issue where old placeholder 'YOUR_KEY' is picked)
    $stmt = $pdo->prepare("SELECT credential_value FROM server_credentials WHERE credential_type = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute([$type]);
    return $stmt->fetchColumn() ?: null;
}

function generateLicense($pdo, $input) {
    $key = strtoupper(substr(bin2hex(random_bytes(16)), 0, 16));
    $key = implode('-', str_split($key, 4));
    
    $days = $input['duration_days'] ?? 30;
    $exp = date('Y-m-d H:i:s', strtotime("+$days days"));
    
    $sql = "INSERT INTO licenses (license_key, owner_name, expiration, duration_days, 
            feature_autocaption, feature_youtube_upload, feature_tiktok_upload, 
            feature_movie_recap, feature_lyric_matcher, feature_export_cloud, 
            provide_gemini_key, provide_groq_key, provide_apify_key, 
            provide_deepgram_key, provide_google_oauth, provide_tiktok_key,
            message, running_text, user_msg, auto_pilot) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $key, $input['owner_name'] ?? 'Unknown',
        $exp, $days,
        $input['feature_autocaption'] ?? 1, $input['feature_youtube_upload'] ?? 1, $input['feature_tiktok_upload'] ?? 1,
        $input['feature_movie_recap'] ?? 1, $input['feature_lyric_matcher'] ?? 1, $input['feature_export_cloud'] ?? 1,
        $input['provide_gemini_key'] ?? 0, $input['provide_groq_key'] ?? 0, $input['provide_apify_key'] ?? 0,
        $input['provide_deepgram_key'] ?? 0, $input['provide_google_oauth'] ?? 0, $input['provide_tiktok_key'] ?? 0,
        $input['message'] ?? '', $input['running_text'] ?? 0, $input['user_msg'] ?? '', $input['auto_pilot'] ?? 0
    ]);
    
    echo json_encode(['success' => true, 'license_key' => $key, 'expiration' => $exp, 'owner_name' => $input['owner_name'] ?? 'Unknown']);
}

function listLicenses($pdo, $input) {
    try {
        $limit = $input['limit'] ?? 1000;
        $stmt = $pdo->prepare("SELECT * FROM licenses ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateLicense($pdo, $input) {
    $id = $input['license_id'];
    unset($input['action'], $input['admin_key'], $input['license_id']);
    
    $sets = [];
    foreach ($input as $key => $val) { $sets[] = "$key = ?"; }
    
    if (empty($sets)) die(json_encode(['success' => false, 'message' => 'No fields to update']));
    
    $sql = "UPDATE licenses SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge(array_values($input), [$id]));
    
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
}

function deleteLicense($pdo, $input) {
    $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
    $stmt->execute([$input['license_id']]);
    echo json_encode(['success' => true, 'message' => 'Deleted']);
}

function sendBroadcastToAll($pdo, $input) {
    $msg = $input['message'] ?? '';
    $marquee = $input['running_text'] ?? 0;
    
    $stmt = $pdo->prepare("UPDATE licenses SET message = ?, running_text = ?");
    $stmt->execute([$msg, $marquee]);
    
    echo json_encode(['success' => true, 'message' => 'Broadcast sent to all users']);
}

// Credential Handlers
function saveCredential($pdo, $input) {
    $stmt = $pdo->prepare("INSERT INTO server_credentials (credential_type, credential_value, description) 
                           VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE credential_value = ?, description = ?");
    $stmt->execute([
        $input['credential_type'], $input['credential_value'], $input['description'] ?? '',
        $input['credential_value'], $input['description'] ?? ''
    ]);
    echo json_encode(['success' => true]);
}

function getCredentials($pdo, $input) {
    $stmt = $pdo->query("SELECT credential_type, 
                                CASE 
                                    WHEN credential_type = 'youtube_cookies' THEN credential_value
                                    WHEN credential_value IS NOT NULL AND credential_value != '' AND credential_value NOT LIKE 'YOUR_%' 
                                    THEN '✅ Tersimpan' 
                                    ELSE '❌ Kosong' 
                                END as credential_value, 
                                description, 
                                is_active,
                                CASE 
                                    WHEN credential_value IS NOT NULL AND credential_value != '' AND credential_value NOT LIKE 'YOUR_%' 
                                    THEN 1 
                                    ELSE 0 
                                END as has_value
                         FROM server_credentials");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

function getGeminiKeys($pdo, $input) {
    $stmt = $pdo->query("SELECT id, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 4)) as api_key, status, last_checked FROM gemini_keys ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

function validateGeminiKey($apiKey, $model = 'gemini-flash-latest') {
    // Allowed models only
    $allowedModels = [
        'gemini-3.5-flash',
        'gemini-3.1-pro-preview',
        'gemini-3-pro-preview',
        'gemini-3-flash-preview',
        'gemini-2.5-pro',
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
        'gemini-flash-latest'
    ];
    
    if (!in_array($model, $allowedModels)) {
        $model = 'gemini-flash-latest'; // Default fallback
    }
    
    // Test with generateContent endpoint using specific model
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . trim($apiKey);
    
    $payload = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => 'test']
                ]
            ]
        ]
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

function addGeminiKeys($pdo, $input) {
    $keys = $input['api_keys'] ?? [];
    $model = $input['model'] ?? 'gemini-flash-latest';
    $added = 0;
    $validCount = 0;
    
    foreach ($keys as $k) {
        if (strlen($k) < 10) continue; // Skip obviously bad keys
        
        // Validate first as requested with selected model
        $isValid = validateGeminiKey($k, $model);
        $status = $isValid ? 'active' : 'invalid';
        if ($isValid) $validCount++;
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO gemini_keys (api_key, status, last_checked) VALUES (?, ?, NOW())");
        $stmt->execute([$k, $status]);
        $added += $stmt->rowCount();
    }
    echo json_encode([
        'success' => true, 
        'message' => "Proses check selesai. $added kunci baru ditambahkan. ($validCount Valid) [Model: $model]"
    ]);
}

function testGeminiKey($pdo, $input) {
    $stmt = $pdo->prepare("SELECT api_key FROM gemini_keys WHERE id = ?");
    $stmt->execute([$input['key_id']]);
    $key = $stmt->fetchColumn();
    
    if (!$key) {
        echo json_encode(['success' => false, 'message' => 'Key not found']);
        return;
    }
    
    $model = $input['model'] ?? 'gemini-flash-latest';
    $isValid = validateGeminiKey($key, $model);
    $status = $isValid ? 'active' : 'invalid';
    
    $pdo->prepare("UPDATE gemini_keys SET status = ?, last_checked = NOW() WHERE id = ?")
        ->execute([$status, $input['key_id']]);
        
    if ($isValid) {
        echo json_encode(['success' => true, 'message' => "Key is VALID and Active [Model: $model]"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Key is INVALID/Expired [Model: $model]"]);
    }
}

function deleteGeminiKey($pdo, $input) {
    $pdo->prepare("DELETE FROM gemini_keys WHERE id = ?")->execute([$input['key_id']]);
    echo json_encode(['success' => true]);
}

// Reseller Handlers
function listResellers($pdo, $input) {
    $stmt = $pdo->query("SELECT id, username, full_name, balance, status, created_at FROM resellers ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

function addReseller($pdo, $input) {
    $hash = password_hash($input['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO resellers (username, password_hash, full_name, balance) VALUES (?, ?, ?, ?)");
    $stmt->execute([$input['username'], $hash, $input['full_name'], $input['balance'] ?? 0]);
    echo json_encode(['success' => true]);
}

function updateReseller($pdo, $input) {
    $id = $input['id'];
    $stmt = $pdo->prepare("UPDATE resellers SET balance = ?, status = ? WHERE id = ?");
    $stmt->execute([$input['balance'], $input['status'], $id]);
    echo json_encode(['success' => true]);
}

function deleteReseller($pdo, $input) {
    $pdo->prepare("DELETE FROM resellers WHERE id = ?")->execute([$input['id']]);
    echo json_encode(['success' => true]);
}

function testCredential($pdo, $input) {
    $type = $input['credential_type'];
    $val = $input['credential_value'] ?? '';

    // If generic value is not provided, fetch from DB
    if (empty($val)) {
        $stmt = $pdo->prepare("SELECT credential_value FROM server_credentials WHERE credential_type = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$type]);
        $val = $stmt->fetchColumn();
    }

    if (empty($val)) {
        echo json_encode(['success' => false, 'message' => 'No credential value found to test.']);
        return;
    }

    $success = false;
    $message = '';

    if ($type === 'apify') {
        $url = "https://api.apify.com/v2/users/me";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . trim($val),
            "Content-Type: application/json"
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = json_decode($response, true);
        curl_close($ch);

        if ($httpCode === 200 && isset($data['data']['id'])) {
            $success = true;
            $username = $data['data']['username'] ?? 'Unknown';
            $message = "Connected as: $username (Plan: " . ($data['data']['plan']['name'] ?? 'Unknown') . ")";
        } else {
            $message = "Apify Error ($httpCode): " . ($data['error']['message'] ?? 'Invalid Token');
        }
    } 
    elseif ($type === 'gemini' || $type === 'groq') {
         // Re-use logic or basic check
         // We already have testGeminiKey, but that tests from gemini_keys table.
         // This is checking single credential.
         // For now, just say not implemented for direct single test unless requested.
         // Actually, let's just allow it for Apify as requested.
         $message = "Test not implemented for $type yet.";
    }
    else {
         $message = "Unknown credential type for testing.";
    }

    echo json_encode(['success' => $success, 'message' => $message]);
}


// --- Bulk Apify Handlers ---

function getApifyKeys($pdo, $input) {
    $stmt = $pdo->query("SELECT id, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 4)) as api_key, status, last_checked, error_msg, cooldown_until FROM apify_keys ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

function addApifyKeys($pdo, $input) {
    $keys = $input['api_keys'] ?? [];
    $added = 0;
    $validCount = 0;
    
    foreach ($keys as $k) {
        if (strlen($k) < 10) continue;
        
        $isValid = validateApifyKey($k);
        $status = $isValid ? 'active' : 'invalid';
        if ($isValid) $validCount++;
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO apify_keys (api_key, status, last_checked) VALUES (?, ?, NOW())");
        $stmt->execute([$k, $status]);
        $added += $stmt->rowCount();
    }
    echo json_encode(['success' => true, 'message' => "$added Apify keys added. ($validCount Valid)"]);
}

function testApifyKey($pdo, $input) {
    $stmt = $pdo->prepare("SELECT api_key FROM apify_keys WHERE id = ?");
    $stmt->execute([$input['key_id']]);
    $key = $stmt->fetchColumn();
    
    if (!$key) {
        echo json_encode(['success' => false, 'message' => 'Key not found']);
        return;
    }
    
    $isValid = validateApifyKey($key);
    $status = $isValid ? 'active' : 'invalid';
    
    // If valid, clear cooldown/error
    $errMsg = $isValid ? null : 'Validation Failed';
    $cooldownSQL = $isValid ? "NULL" : "NULL"; // Reset cooldown on manual test Check

    $pdo->prepare("UPDATE apify_keys SET status = ?, last_checked = NOW(), error_msg = ?, cooldown_until = $cooldownSQL WHERE id = ?")
        ->execute([$status, $errMsg, $input['key_id']]);
        
    echo json_encode(['success' => $isValid, 'message' => $isValid ? "Key is VALID" : "Key is INVALID"]);
}

function deleteApifyKey($pdo, $input) {
    $pdo->prepare("DELETE FROM apify_keys WHERE id = ?")->execute([$input['key_id']]);
    echo json_encode(['success' => true]);
}

function validateApifyKey($key) {
    $ch = curl_init("https://api.apify.com/v2/users/me");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . trim($key)]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

// --- Bulk Deepgram Handlers ---

function getDeepgramKeys($pdo, $input) {
    $stmt = $pdo->query("SELECT id, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 4)) as api_key, status, last_checked, error_msg, cooldown_until FROM deepgram_keys ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

function addDeepgramKeys($pdo, $input) {
    $keys = $input['api_keys'] ?? [];
    $added = 0;
    $validCount = 0;
    
    foreach ($keys as $k) {
        if (strlen($k) < 10) continue;
        
        $isValid = validateDeepgramKey($k);
        $status = $isValid ? 'active' : 'invalid';
        if ($isValid) $validCount++;
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO deepgram_keys (api_key, status, last_checked) VALUES (?, ?, NOW())");
        $stmt->execute([$k, $status]);
        $added += $stmt->rowCount();
    }
    echo json_encode(['success' => true, 'message' => "$added Deepgram keys added. ($validCount Valid)"]);
}

function testDeepgramKey($pdo, $input) {
    $stmt = $pdo->prepare("SELECT api_key FROM deepgram_keys WHERE id = ?");
    $stmt->execute([$input['key_id']]);
    $key = $stmt->fetchColumn();
    
    if (!$key) {
        echo json_encode(['success' => false, 'message' => 'Key not found']);
        return;
    }
    
    $isValid = validateDeepgramKey($key);
    $status = $isValid ? 'active' : 'invalid';
    
    $pdo->prepare("UPDATE deepgram_keys SET status = ?, last_checked = NOW(), error_msg = NULL, cooldown_until = NULL WHERE id = ?")
        ->execute([$status, $input['key_id']]);
        
    echo json_encode(['success' => $isValid, 'message' => $isValid ? "Key is VALID" : "Key is INVALID"]);
}

function deleteDeepgramKey($pdo, $input) {
    $pdo->prepare("DELETE FROM deepgram_keys WHERE id = ?")->execute([$input['key_id']]);
    echo json_encode(['success' => true]);
}

function validateDeepgramKey($key) {
    $ch = curl_init("https://api.deepgram.com/v1/projects");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Token " . trim($key)]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

// --- Failure Reporting & Auto-Cooldown ---

function reportKeyFailure($pdo, $input) {
    $key = $input['api_key'] ?? '';
    $type = $input['type'] ?? ''; // 'gemini', 'apify', 'deepgram'
    $error = $input['error'] ?? 'Unknown Error';
    $isLimit = $input['is_limit'] ?? false; // true if 429/Limit
    
    if (!$key || !$type) {
        echo json_encode(['success' => false, 'message' => 'Missing key or type']);
        return;
    }
    
    $table = '';
    if ($type === 'gemini') $table = 'gemini_keys';
    elseif ($type === 'apify') $table = 'apify_keys';
    elseif ($type === 'deepgram') $table = 'deepgram_keys';
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        return;
    }
    
    $status = $isLimit ? 'limit' : 'cooldown'; 
    $cooldownSQL = "NOW() + INTERVAL 30 MINUTE";
    
    if (stripos($error, 'invalid') !== false || stripos($error, 'unauthorized') !== false || stripos($error, '401') !== false) {
        $status = 'dead';
        $cooldownSQL = "NULL"; 
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE $table SET status = ?, error_msg = ?, cooldown_until = $cooldownSQL, last_checked = NOW() WHERE api_key = ?");
        $stmt->execute([$status, $error, $key]);
        
        echo json_encode(['success' => true, 'message' => "Key marked as $status"]);
    } catch (Exception $e) {
         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getYoutubeCookies($pdo, $input) {
    try {
        $stmt = $pdo->query("SELECT id, description, status, created_at FROM youtube_cookies ORDER BY id DESC");
        $cookies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $cookies]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addYoutubeCookie($pdo, $input) {
    $cookie_value = $input['cookie_value'] ?? '';
    $description = $input['description'] ?? 'Account';
    
    if (empty(trim($cookie_value))) {
        echo json_encode(['success' => false, 'message' => 'Cookie value cannot be empty']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO youtube_cookies (cookie_value, description, status) VALUES (?, ?, 'active')");
        $stmt->execute([$cookie_value, $description]);
        echo json_encode(['success' => true, 'message' => 'Cookie added successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteYoutubeCookie($pdo, $input) {
    $id = $input['id'] ?? 0;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        return;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM youtube_cookies WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Cookie deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function toggleYoutubeCookieStatus($pdo, $input) {
    $id = $input['id'] ?? 0;
    $status = $input['status'] ?? 'active'; // 'active' or 'dead'
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        return;
    }
    try {
        $stmt = $pdo->prepare("UPDATE youtube_cookies SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// --- Bulk YOLO Handlers ---

function getYoloKeys($pdo, $input) {
    try {
        $stmt = $pdo->query("SELECT id, endpoint_url, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 4)) as api_key_masked, status, last_checked FROM yolo_keys ORDER BY created_at DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addYoloKeys($pdo, $input) {
    $entries = $input['entries'] ?? [];
    $added = 0;
    $validCount = 0;
    
    foreach ($entries as $entry) {
        $url = trim($entry['endpoint_url'] ?? '');
        $key = trim($entry['api_key'] ?? '');
        
        if (empty($url) || empty($key)) continue;
        
        $isValid = validateYoloKey($url, $key);
        $status = $isValid ? 'active' : 'dead';
        if ($isValid) $validCount++;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO yolo_keys (endpoint_url, api_key, status, last_checked) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE api_key = ?, status = ?, last_checked = NOW()");
            $stmt->execute([$url, $key, $status, $key, $status]);
            $added++;
        } catch (Exception $e) {
            // ignore duplicate/db errors
        }
    }
    echo json_encode(['success' => true, 'message' => "$added YOLO deployments registered. ($validCount Valid)"]);
}

function testYoloKey($pdo, $input) {
    $id = $input['key_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT endpoint_url, api_key FROM yolo_keys WHERE id = ?");
    $stmt->execute([$id]);
    $key = $stmt->fetch();
    
    if (!$key) {
        echo json_encode(['success' => false, 'message' => 'YOLO Deployment not found']);
        return;
    }
    
    $isValid = validateYoloKey($key['endpoint_url'], $key['api_key']);
    $status = $isValid ? 'active' : 'dead';
    
    $pdo->prepare("UPDATE yolo_keys SET status = ?, last_checked = NOW() WHERE id = ?")
        ->execute([$status, $id]);
        
    echo json_encode(['success' => $isValid, 'message' => $isValid ? "Deployment is ACTIVE and responding" : "Deployment validation failed (Unauthorized/Timeout)"]);
}

function deleteYoloKey($pdo, $input) {
    $id = $input['key_id'] ?? 0;
    $pdo->prepare("DELETE FROM yolo_keys WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
}

function validateYoloKey($endpoint, $apiKey) {
    $ch = curl_init(trim($endpoint));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: " . trim($apiKey)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($code === 200 || $code === 400 || $code === 422);
}


