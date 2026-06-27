<?php
session_start();
if (!isset($_SESSION['admin_auth'])) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Clipper AI | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">AUTO CLIPPER</div>
            </div>
            <nav class="sidebar-nav">
                <a class="nav-item active" onclick="switchTab('dashboard', this)">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item" onclick="switchTab('generate', this)">
                    <i data-lucide="zap"></i>
                    <span>Generate License</span>
                </a>
                <a class="nav-item" onclick="switchTab('licenses', this)">
                    <i data-lucide="key"></i>
                    <span>License List</span>
                </a>
                <a class="nav-item" onclick="switchTab('resellers', this)">
                    <i data-lucide="users"></i>
                    <span>Reseller Management</span>
                </a>
                <a class="nav-item" onclick="switchTab('credentials', this)">
                    <i data-lucide="shield-check"></i>
                    <span>Server Credentials</span>
                </a>
                <a class="nav-item" onclick="switchTab('broadcast', this)">
                    <i data-lucide="megaphone"></i>
                    <span>Broadcast</span>
                </a>
                <a class="nav-item" onclick="switchTab('settings', this)">
                    <i data-lucide="settings"></i>
                    <span>System Settings</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <button class="btn btn-secondary btn-sm" style="width: 100%; justify-content: flex-start;"
                    onclick="logout()">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h2 class="page-title" id="pageTitle">Dashboard</h2>
                <div class="user-profile">
                    <span id="adminName" style="color: var(--text-muted); font-size: 0.875rem;">Administrator</span>
                    <div
                        style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem;">
                        A</div>
                </div>
            </header>

            <div class="content-area">
                <div id="dashboardAlerts" style="margin-bottom: 24px; display: none;"></div>
                <!-- Dashboard Panel -->
                <div id="panel-dashboard" class="tab-panel active">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-label">Total Licenses</span>
                            <span class="stat-value" id="stat-total">0</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Active Users</span>
                            <span class="stat-value" id="stat-active" style="color: var(--success);">0</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Expiring Soon</span>
                            <span class="stat-value" id="stat-expiring" style="color: var(--warning);">0</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Expired</span>
                            <span class="stat-value" id="stat-expired" style="color: var(--danger);">0</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                            <button class="btn btn-secondary btn-sm" onclick="loadLicenses()">Refresh</button>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table id="recentLicenseTable">
                                    <thead>
                                        <tr>
                                            <th>License Key</th>
                                            <th>Status</th>
                                            <th>Expiration</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentLicenseBody">
                                        <!-- Will be populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate License Panel -->
                <div id="panel-generate" class="tab-panel">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Create New License</h3>
                        </div>
                        <div class="card-body">
                            <form id="generateForm" onsubmit="handleGenerate(event)">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                                    <div class="form-group">
                                        <label>Nama Pemilik Lisensi <span style="color: var(--danger);">*</span></label>
                                        <input type="text" name="owner_name" placeholder="Contoh: John Doe" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Duration (Days)</label>
                                        <select name="duration_days">
                                            <option value="7">7 Days (Trial)</option>
                                            <option value="30" selected>30 Days (1 Month)</option>
                                            <option value="90">90 Days (3 Months)</option>
                                            <option value="180">180 Days (6 Months)</option>
                                            <option value="365">365 Days (1 Year)</option>
                                            <option value="730">730 Days (2 Years)</option>
                                        </select>
                                    </div>
                                </div>

                                <div
                                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 16px;">
                                    <div class="form-group">
                                        <label>Global Announcement</label>
                                        <input type="text" name="message"
                                            placeholder="e.g. Subscribe to our channel! | https://link.com">
                                    </div>
                                    <div class="form-group">
                                        <label>Personal Message to User</label>
                                        <input type="text" name="user_msg" placeholder="Welcome to Premium!">
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr; gap: 24px; margin-top: 16px;">
                                    <div class="form-group">
                                        <label>Associated Reseller (Optional)</label>
                                        <select id="resellerSelect" name="reseller_id">
                                            <option value="">None (Master Admin)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-section-title"
                                    style="margin: 24px 0 12px; font-weight: 600; color: var(--primary);">Features
                                    Access</div>
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="feature_autocaption" checked>
                                        <span>Auto Caption</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="feature_youtube_upload" checked>
                                        <span>YouTube Upload</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="feature_tiktok_upload" checked>
                                        <span>TikTok Upload</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="feature_movie_recap" checked>
                                        <span>Movie Recap</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="feature_lyric_matcher" checked>
                                        <span>Lyric Matcher</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="feature_export_cloud" checked>
                                        <span>Export Cloud</span>
                                    </label>
                                    <label class="checkbox-item"
                                        style="border: 1px solid var(--warning); background: rgba(234, 179, 8, 0.05);">
                                        <input type="checkbox" name="auto_pilot">
                                        <span style="color: var(--warning); font-weight: bold;">⚡ Auto Pilot</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="running_text">
                                        <span>Running Text (Marquee)</span>
                                    </label>
                                </div>

                                <div class="form-section-title"
                                    style="margin: 24px 0 12px; font-weight: 600; color: var(--secondary);">Server
                                    Credentials Provider</div>
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="provide_gemini_key">
                                        <span>Gemini API</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="provide_groq_key">
                                        <span>Groq API</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="provide_apify_key">
                                        <span>Apify API</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="provide_deepgram_key">
                                        <span>Deepgram API</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="provide_google_oauth">
                                        <span>Google OAuth</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="provide_tiktok_key">
                                        <span>TikTok Client</span>
                                    </label>
                                </div>


                                <div style="margin-top: 32px;">
                                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                                        <i data-lucide="plus-circle"></i>
                                        Generate License Key
                                    </button>
                                </div>
                            </form>

                            <div id="resultCard" class="card"
                                style="display: none; margin-top: 24px; background: rgba(16, 185, 129, 0.05); border-color: var(--success);">
                                <div class="card-body">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div>
                                            <div style="color: var(--success); font-weight: 700; margin-bottom: 4px;">
                                                LICENSE GENERATED!</div>
                                            <div id="licenseResult"
                                                style="font-family: monospace; font-size: 1.25rem; letter-spacing: 2px;">
                                                XXXX-XXXX-XXXX-XXXX</div>
                                        </div>
                                        <button class="btn btn-success" onclick="copyToClipboard('licenseResult')">Copy
                                            Key</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- License List Panel -->
                <div id="panel-licenses" class="tab-panel">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Manage Licenses</h3>
                            <div style="display: flex; gap: 12px;">
                                <input type="text" id="licenseSearch" placeholder="Search keys or HWID..."
                                    onkeyup="filterLicenses()" style="width: 300px;">
                                <button class="btn btn-secondary" onclick="loadLicenses()">
                                    <i data-lucide="rotate-cw"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table id="mainLicenseTable">
                                    <thead>
                                        <tr>
                                            <th>License Key</th>
                                            <th>Owner Name</th>
                                            <th>Details</th>
                                            <th>Status</th>
                                            <th>HWID / Device</th>
                                            <th>Expiration</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mainLicenseBody">
                                        <!-- Will be populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reseller Management Panel -->
                <div id="panel-resellers" class="tab-panel">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Reseller Management</h3>
                            <button class="btn btn-primary btn-sm" onclick="showModal('resellerModal')">+ Add
                                Reseller</button>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Full Name</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="resellerTableBody">
                                        <!-- Will be populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Server Credentials Panel -->
                <div id="panel-credentials" class="tab-panel">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="card" style="grid-column: span 2;">
                            <div class="card-header">
                                <h3 class="card-title">Gemini API Key Pool</h3>
                                <button class="btn btn-primary btn-sm" onclick="showModal('geminiModal')">Manage Bulk
                                    Keys</button>
                            </div>
                            <div class="card-body">
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 20px;">
                                    Active keys: <span id="geminiCount"
                                        style="color: var(--primary); font-weight: 700;">0</span>
                                </p>
                                <div id="geminiStatusGrid"
                                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                                </div>
                            </div>
                        </div>

                        <div class="card" style="grid-column: span 2;">
                            <div class="card-header">
                                <h3 class="card-title">Apify API Key Pool</h3>
                                <button class="btn btn-primary btn-sm" onclick="showModal('apifyModal')">Manage Bulk
                                    Keys</button>
                            </div>
                            <div class="card-body">
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 20px;">
                                    Active keys: <span id="apifyCount"
                                        style="color: var(--primary); font-weight: 700;">0</span>
                                </p>
                                <div id="apifyStatusGrid"
                                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                                </div>
                            </div>
                        </div>

                        <div class="card" style="grid-column: span 2;">
                            <div class="card-header">
                                <h3 class="card-title">Deepgram API Key Pool</h3>
                                <button class="btn btn-primary btn-sm" onclick="showModal('deepgramModal')">Manage Bulk
                                    Keys</button>
                            </div>
                            <div class="card-body">
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 20px;">
                                    Active keys: <span id="deepgramCount"
                                        style="color: var(--primary); font-weight: 700;">0</span>
                                </p>
                                <div id="deepgramStatusGrid"
                                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                                </div>
                            </div>
                        </div>

                        <div class="card" style="grid-column: span 2;">
                            <div class="card-header">
                                <h3 class="card-title">Other Credentials</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>
                                        Groq API Key
                                        <span id="status_groq" style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="password" id="cred_groq" placeholder="gsk_...">
                                        <button class="btn btn-secondary" onclick="saveCred('groq')">Save</button>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top: 20px;">
                                    <label>
                                        YouTube Cookies (Netscape format)
                                        <span id="status_youtube_cookies" style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <div style="display: flex; gap: 8px; flex-direction: column;">
                                        <textarea id="cred_youtube_cookies" placeholder="# Netscape HTTP Cookie File..." rows="8" style="font-family: monospace; width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; background: rgba(0,0,0,0.1); color: var(--text-color); resize: vertical;"></textarea>
                                        <div style="display: flex; gap: 12px;">
                                            <input type="file" id="cookies_file_input" accept=".txt" style="display: none;" onchange="uploadCookiesFile(this)">
                                            <button class="btn btn-secondary" onclick="document.getElementById('cookies_file_input').click()">Upload cookies.txt</button>
                                            <button class="btn btn-primary" onclick="saveCred('youtube_cookies')">Save Cookies</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">OAuth Credentials</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>
                                        Google Cloud Client ID
                                        <span id="status_google_client_id"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="text" id="google_client_id" placeholder="...">
                                </div>
                                <div class="form-group">
                                    <label>
                                        Google Cloud Client Secret
                                        <span id="status_google_client_secret"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="password" id="google_client_secret" placeholder="...">
                                </div>
                                <div class="form-group">
                                    <label>
                                        Google Redirect URI
                                        <span id="status_google_redirect_uri"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="text" id="google_redirect_uri" placeholder="...">
                                </div>
                                <button class="btn btn-primary" style="width: 100%;"
                                    onclick="saveOAuth('google')">Update Google OAuth</button>

                                <div class="form-group">
                                    <label>
                                        YouTube OAuth Client ID
                                        <span id="status_youtube_client_id"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="text" id="youtube_client_id" placeholder="...">
                                </div>
                                <div class="form-group">
                                    <label>
                                        YouTube OAuth Client Secret
                                        <span id="status_youtube_client_secret"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="password" id="youtube_client_secret" placeholder="...">
                                </div>
                                <div class="form-group">
                                    <label>
                                        YouTube Redirect URI
                                        <span id="status_youtube_redirect_uri"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="text" id="youtube_redirect_uri" placeholder="...">
                                </div>
                                <button class="btn btn-primary" style="width: 100%;"
                                    onclick="saveOAuth('youtube')">Update YouTube OAuth</button>

                                <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

                                <div class="form-group">
                                    <label>
                                        TikTok Client Key
                                        <span id="status_tiktok_client_key"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="text" id="tiktok_client_key" placeholder="...">
                                </div>
                                <div class="form-group">
                                    <label>
                                        TikTok Secret
                                        <span id="status_tiktok_client_secret"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="password" id="tiktok_client_secret" placeholder="...">
                                </div>
                                <div class="form-group">
                                    <label>
                                        TikTok Redirect URI
                                        <span id="status_tiktok_redirect_uri"
                                            style="margin-left: 8px; font-size: 0.875rem;"></span>
                                    </label>
                                    <input type="text" id="tiktok_redirect_uri" placeholder="...">
                                </div>
                                <button class="btn btn-primary" style="width: 100%;"
                                    onclick="saveOAuth('tiktok')">Update TikTok OAuth</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Broadcast Panel -->
                <div id="panel-broadcast" class="tab-panel">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Global Broadcast</h3>
                        </div>
                        <div class="card-body">
                            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 20px;">
                                This message will be sent to ALL active license holders.
                            </p>
                            <div class="form-group">
                                <label>Broadcast Message</label>
                                <input type="text" id="broadcast_msg"
                                    placeholder="e.g. Version 6.0 is out! | https://link.com">
                            </div>
                            <div class="form-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" id="broadcast_marquee">
                                    <span>Enable Marquee (Running Text)</span>
                                </label>
                            </div>
                            <button class="btn btn-primary" style="width: 100%;" onclick="sendBroadcast()">
                                <i data-lucide="send"></i>
                                Send to All Users
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Settings Panel -->
                <div id="panel-settings" class="tab-panel">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Security & System</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Master Admin Key</label>
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <input type="password" value="YOUR_SESSION_KEY" readonly style="flex: 1;">
                                    <button class="btn btn-secondary" onclick="changeAdminKey()">Change Key</button>
                                </div>
                                <p style="font-size: 0.75rem; color: var(--danger); margin-top: 8px;">⚠️ Warning:
                                    Changing this will require manual update in server files.</p>
                            </div>

                            <div style="margin-top: 32px;">
                                <h4 style="margin-bottom: 16px;">System Information</h4>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                                    <div style="padding: 16px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">PHP Version</div>
                                        <div style="font-weight: 600;">8.x</div>
                                    </div>
                                    <div style="padding: 16px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Database</div>
                                        <div style="font-weight: 600;">MySQL (autocliper_license)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <!-- Reseller Modal -->
    <div id="resellerModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Reseller</h3>
                <button class="btn btn-secondary btn-sm" onclick="hideModal('resellerModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="resellerForm" onsubmit="handleAddReseller(event)">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Initial Balance (License Keys)</label>
                        <input type="number" name="balance" value="10">
                    </div>
                    <div class="modal-footer" style="padding: 0; margin-top: 24px; border: none;">
                        <button type="button" class="btn btn-secondary"
                            onclick="hideModal('resellerModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Reseller</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- License Edit Modal -->
    <div id="licenseEditModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Edit License Details</h3>
                <button class="btn btn-secondary btn-sm" onclick="hideModal('licenseEditModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editLicenseForm" onsubmit="handleUpdateLicense(event)">
                    <input type="hidden" id="edit_license_id">

                    <div class="form-group">
                        <label>Owner Name</label>
                        <input type="text" id="edit_owner_name" placeholder="e.g., John Doe">
                    </div>

                    <div class="form-group">
                        <label>Personal Message to User</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="edit_user_msg" placeholder="Welcome!">
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="quickUpdateMsg(event)">Send</button>
                        </div>
                    </div>

                    <div class="form-section-title"
                        style="margin: 20px 0 10px; font-weight: 600; font-size: 0.9rem; color: var(--primary);">Feature
                        Permissions</div>
                    <div class="checkbox-group" style="grid-template-columns: repeat(2, 1fr);">
                        <label class="checkbox-item"><input type="checkbox" id="edit_feature_autocaption"> <span>Auto
                                Caption</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_feature_youtube_upload">
                            <span>YouTube Upload</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_feature_tiktok_upload">
                            <span>TikTok Upload</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_feature_movie_recap"> <span>Movie
                                Recap</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_feature_lyric_matcher"> <span>Lyric
                                Matcher</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_feature_export_cloud"> <span>Export
                                Cloud</span></label>
                        <label class="checkbox-item"
                            style="border: 1px solid var(--warning); background: rgba(234, 179, 8, 0.05);">
                            <input type="checkbox" id="edit_auto_pilot">
                            <span style="color: var(--warning); font-weight: bold;">⚡ Auto Pilot</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" id="edit_running_text">
                            <span>Running Text (Marquee)</span>
                        </label>
                    </div>

                    <div class="form-section-title"
                        style="margin: 20px 0 10px; font-weight: 600; font-size: 0.9rem; color: var(--secondary);">
                        Credentials Access</div>
                    <div class="checkbox-group" style="grid-template-columns: repeat(2, 1fr);">
                        <label class="checkbox-item"><input type="checkbox" id="edit_provide_gemini_key">
                            <span>Gemini</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_provide_groq_key">
                            <span>Groq</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_provide_apify_key">
                            <span>Apify</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_provide_deepgram_key">
                            <span>Deepgram</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_provide_google_oauth"> <span>Google
                                OAuth</span></label>
                        <label class="checkbox-item"><input type="checkbox" id="edit_provide_tiktok_key">
                            <span>TikTok Client</span></label>
                    </div>

                    <div class="modal-footer" style="padding: 0; margin-top: 24px; border: none;">
                        <button type="button" class="btn btn-secondary"
                            onclick="hideModal('licenseEditModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Gemini Bulk Keys Modal -->
    <div id="geminiModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Bulk Manage Gemini Keys</h3>
                <button class="btn btn-secondary btn-sm" onclick="hideModal('geminiModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select Gemini Model for Validation</label>
                    <select id="geminiModelSelect" style="margin-bottom: 16px;">
                        <option value="gemini-3.5-flash">Gemini 3.5 Flash</option>
                        <option value="gemini-3.1-pro-preview">Gemini 3.1 Pro Preview</option>
                        <option value="gemini-3-pro-preview">Gemini 3 Pro Preview</option>
                        <option value="gemini-3-flash-preview">Gemini 3 Flash Preview</option>
                        <option value="gemini-2.5-pro">Gemini 2.5 Pro</option>
                        <option value="gemini-2.5-flash" selected>Gemini 2.5 Flash</option>
                        <option value="gemini-2.5-flash-lite">Gemini 2.5 Flash Lite</option>
                        <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                        <option value="gemini-1.5-flash">Gemini 1.5 Flash</option>
                        <option value="gemini-1.5-pro">Gemini 1.5 Pro</option>
                        <option value="gemini-flash-latest">Gemini Flash Latest</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Add New Keys (one per line)</label>
                    <textarea id="bulkGeminiKeys" rows="6" placeholder="AIzaSy...&#10;AIzaSy..."></textarea>
                </div>
                <button class="btn btn-primary" style="width: 100%;" onclick="handleBulkGemini()">Import Keys</button>

                <h4 style="margin: 24px 0 12px;">Active Key Pool</h4>
                <div id="geminiListTable" class="table-container" style="max-height: 300px;">
                    <table>
                        <thead>
                            <tr>
                                <th>API Key</th>
                                <th>Status</th>
                                <th>Last Checked</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="geminiKeysBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Apify Bulk Keys Modal -->
    <div id="apifyModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Bulk Manage Apify Keys</h3>
                <button class="btn btn-secondary btn-sm" onclick="hideModal('apifyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Add New Keys (one per line)</label>
                    <textarea id="bulkApifyKeys" rows="6" placeholder="apify_api_...&#10;apify_api_..."></textarea>
                </div>
                <button class="btn btn-primary" style="width: 100%;" onclick="handleBulkApify()">Import Keys</button>

                <h4 style="margin: 24px 0 12px;">Active Key Pool</h4>
                <div id="apifyListTable" class="table-container" style="max-height: 300px;">
                    <table>
                        <thead>
                            <tr>
                                <th>API Key (Token)</th>
                                <th>Status</th>
                                <th>Error / Cooldown</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="apifyKeysBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Deepgram Bulk Keys Modal -->
    <div id="deepgramModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Bulk Manage Deepgram Keys</h3>
                <button class="btn btn-secondary btn-sm" onclick="hideModal('deepgramModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Add New Keys (one per line)</label>
                    <textarea id="bulkDeepgramKeys" rows="6" placeholder="..."></textarea>
                </div>
                <button class="btn btn-primary" style="width: 100%;" onclick="handleBulkDeepgram()">Import Keys</button>

                <h4 style="margin: 24px 0 12px;">Active Key Pool</h4>
                <div id="deepgramListTable" class="table-container" style="max-height: 300px;">
                    <table>
                        <thead>
                            <tr>
                                <th>API Key</th>
                                <th>Status</th>
                                <th>Error / Cooldown</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="deepgramKeysBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 3000; align-items: center; justify-content: center;">
        <div class="spinner" style="width: 48px; height: 48px;"></div>
    </div>

    <script src="script.js"></script>
</body>

</html>