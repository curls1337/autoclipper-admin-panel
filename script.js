/**
 * Auto Clipper AI - Admin JS
 * Refactored & Modernized Version
 */

const API_URL = 'api.php';
// We'll try to get this from session if possible, but keeping for legacy compatibility
// Keep this synchronized with config.php
const ADMIN_KEY = 'Tak-ada-yang-abadi';
let currentLicenses = [];

// --- Initialize Lucide Icons ---
function initIcons() {
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

// --- Tab Management ---
function switchTab(panelId, element) {
    // Update Menu UI
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    element.classList.add('active');

    // Update Panels
    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
    const targetPanel = document.getElementById(`panel-${panelId}`);
    if (targetPanel) {
        targetPanel.classList.add('active');
    }

    // Update Page Title
    const titleMap = {
        'dashboard': 'Dashboard',
        'generate': 'Generate License',
        'licenses': 'License List',
        'resellers': 'Reseller Management',
        'credentials': 'Server Credentials',
        'broadcast': 'Broadcasting',
        'settings': 'System Settings'
    };
    document.getElementById('pageTitle').textContent = titleMap[panelId] || 'Admin';

    // Route Actions
    if (panelId === 'licenses' || panelId === 'dashboard') loadLicenses();
    if (panelId === 'resellers') loadResellers();
    if (panelId === 'credentials') loadCredentialsPool();

    initIcons();
}

// --- UI Helpers ---
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast`;

    const icon = type === 'success' ? 'check-circle' : (type === 'danger' ? 'alert-circle' : 'info');
    const color = type === 'success' ? 'var(--success)' : (type === 'danger' ? 'var(--danger)' : 'var(--info)');

    toast.innerHTML = `
        <i data-lucide="${icon}" style="color: ${color}"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);
    initIcons();

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function toggleLoading(show = true) {
    document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
}

function showModal(id) {
    document.getElementById(id).classList.add('active');
}

function hideModal(id) {
    document.getElementById(id).classList.remove('active');
}

async function apiRequest(data) {
    data.admin_key = ADMIN_KEY;
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (!result.success && result.message === 'Unauthorized. Please login.') {
            window.location.href = 'login.html';
        }
        return result;
    } catch (error) {
        showToast('API Error: ' + error.message, 'danger');
        return { success: false, message: error.message };
    }
}

// --- Feature Logic ---

async function loadLicenses() {
    toggleLoading(true);
    const result = await apiRequest({ action: 'list_licenses' });
    toggleLoading(false);

    if (result.success) {
        currentLicenses = result.data; // Cache locally
        renderLicenses(result.data);
        updateDashboardStats(result.data);
    }
}

function updateDashboardStats(licenses) {
    const total = licenses.length;
    const active = licenses.filter(l => l.status === 'active').length;
    const expired = licenses.filter(l => new Date(l.expiration) < new Date()).length;
    const expiring = licenses.filter(l => {
        const days = (new Date(l.expiration) - new Date()) / (1000 * 3600 * 24);
        return days > 0 && days < 7;
    }).length;

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-expired').textContent = expired;
    document.getElementById('stat-expiring').textContent = expiring;
}

function renderLicenses(licenses) {
    const mainBody = document.getElementById('mainLicenseBody');
    const recentBody = document.getElementById('recentLicenseBody');

    const html = licenses.map(lic => {
        const isExpired = new Date(lic.expiration) < new Date();
        const statusBadge = lic.status === 'active'
            ? `<span class="badge badge-success">Active</span>`
            : `<span class="badge badge-danger">Suspended</span>`;

        const lastSeen = l => {
            if (!l.last_seen) return '<span class="status-dot offline"></span> Offline';
            const diff = (new Date() - new Date(l.last_seen)) / 1000 / 60; // minutes
            return diff < 5
                ? '<span class="status-dot online"></span> Online'
                : '<span class="status-dot offline"></span> Offline';
        };

        return `
            <tr>
                <td>
                    <code style="font-size: 0.9rem;">${lic.license_key}</code>
                </td>
                <td>
                    ${lic.owner_name ? `<strong>${lic.owner_name}</strong>` : '<span style="color: var(--text-muted); font-style: italic;">-</span>'}
                </td>
                <td>
                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                        ${parseInt(lic.feature_autocaption) ? '<span class="badge badge-info">Caption</span>' : ''}
                        ${parseInt(lic.feature_youtube_upload) ? '<span class="badge badge-info">YT</span>' : ''}
                        ${parseInt(lic.feature_tiktok_upload) ? '<span class="badge badge-info">TT</span>' : ''}
                        ${parseInt(lic.feature_movie_recap) ? '<span class="badge badge-info">Recap</span>' : ''}
                        ${parseInt(lic.feature_lyric_matcher) ? '<span class="badge badge-info">Lyric</span>' : ''}
                        ${parseInt(lic.feature_export_cloud) ? '<span class="badge badge-info">Cloud</span>' : ''}
                        ${parseInt(lic.provide_gemini_key) ? '<span class="badge badge-warning">Gemini</span>' : ''}
                        ${parseInt(lic.provide_groq_key) ? '<span class="badge badge-warning">Groq</span>' : ''}
                        ${parseInt(lic.provide_deepgram_key) ? '<span class="badge badge-warning">Deepgram</span>' : ''}
                        ${parseInt(lic.provide_google_oauth) ? '<span class="badge badge-warning">OAuth</span>' : ''}
                        ${parseInt(lic.provide_tiktok_key) ? '<span class="badge badge-warning">TikTok Client</span>' : ''}
                        ${parseInt(lic.auto_pilot) ? '<span class="badge" style="background:var(--warning); color:black;">⚡ Auto</span>' : ''}
                        ${parseInt(lic.running_text) ? '<span class="badge" style="background:var(--info); color:white;">⚡ Text</span>' : ''}
                        <span class="badge badge-secondary" style="opacity: 0.8;">[${lic.duration_days}d]</span>
                    </div>
                </td>
                <td>${isExpired ? '<span class="badge badge-danger">Expired</span>' : statusBadge}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div>${lastSeen(lic)}</div>
                        <div style="border-left: 1px solid var(--border); padding-left: 8px;">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">${lic.pc_name || 'N/A'}</span><br>
                            <span style="font-size: 0.7rem; opacity: 0.7;">${lic.hwid ? lic.hwid.substring(0, 16) + '...' : 'Not activated'}</span>
                        </div>
                    </div>
                </td>
                <td>${new Date(lic.expiration).toLocaleDateString()}</td>
                <td>
                    <div style="display: flex; gap: 4px;">
                        <button class="btn btn-secondary btn-sm" onclick="toggleStatus(${lic.id}, '${lic.status}')" title="Toggle Status">
                            <i data-lucide="${lic.status === 'active' ? 'user-x' : 'user-check'}"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="resetHWID(${lic.id})" title="Reset HWID">
                            <i data-lucide="refresh-cw"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="openEditModal(${lic.id})" title="Edit Details">
                            <i data-lucide="edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteLicense(${lic.id})" title="Delete">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    mainBody.innerHTML = html;
    recentBody.innerHTML = html.slice(0, 5); // Just show top 5 for dashboard
    initIcons();
}

async function handleGenerate(event) {
    event.preventDefault();
    toggleLoading(true);
    const formData = new FormData(event.target);
    const data = { action: 'generate' };

    // Map checkboxes to numeric 1/0
    const checkboxes = [
        'feature_autocaption', 'feature_youtube_upload', 'feature_tiktok_upload',
        'feature_movie_recap', 'feature_lyric_matcher', 'feature_export_cloud', 'provide_gemini_key',
        'provide_groq_key', 'provide_apify_key', 'provide_deepgram_key',
        'provide_google_oauth', 'provide_tiktok_key', 'running_text', 'auto_pilot'
    ];
    checkboxes.forEach(cb => data[cb] = formData.get(cb) ? 1 : 0);

    // Add other fields
    data.owner_name = formData.get('owner_name');
    data.duration_days = formData.get('duration_days');
    data.message = formData.get('message');
    data.user_msg = formData.get('user_msg');

    const result = await apiRequest(data);
    toggleLoading(false);

    if (result.success) {
        showToast(`License generated for ${result.owner_name}!`);
        document.getElementById('resultCard').style.display = 'block';
        document.getElementById('licenseResult').textContent = result.license_key;
        event.target.reset();
    } else {
        showToast(result.message, 'danger');
    }
}

async function toggleStatus(id, current) {
    const newStatus = current === 'active' ? 'suspended' : 'active';
    const result = await apiRequest({ action: 'update_license', license_id: id, status: newStatus });
    if (result.success) {
        showToast(`License ${newStatus} successfully!`);
        loadLicenses();
    }
}

async function resetHWID(id) {
    if (!confirm('Allow this license to be used on another computer?')) return;
    const result = await apiRequest({ action: 'update_license', license_id: id, hwid: '' });
    if (result.success) {
        showToast('Hardware ID has been reset.');
        loadLicenses();
    }
}

async function deleteLicense(id) {
    if (!confirm('PERMANENTLY DELETE this license?')) return;
    const result = await apiRequest({ action: 'delete_license', license_id: id });
    if (result.success) {
        showToast('License deleted permanently.');
        loadLicenses();
    }
}

async function openEditModal(id) {
    const lic = currentLicenses.find(l => l.id == id);
    if (!lic) return;

    document.getElementById('edit_license_id').value = lic.id;
    document.getElementById('edit_owner_name').value = lic.owner_name || '';
    document.getElementById('edit_user_msg').value = lic.user_msg || '';

    // Checkboxes
    const fields = [
        'feature_autocaption', 'feature_youtube_upload', 'feature_tiktok_upload',
        'feature_movie_recap', 'feature_lyric_matcher', 'feature_export_cloud', 'provide_gemini_key',
        'provide_groq_key', 'provide_apify_key', 'provide_deepgram_key',
        'provide_google_oauth', 'provide_tiktok_key', 'auto_pilot', 'running_text'
    ];

    fields.forEach(f => {
        const el = document.getElementById(`edit_${f}`);
        if (el) el.checked = !!parseInt(lic[f]);
    });

    showModal('licenseEditModal');
}

async function quickUpdateMsg(event) {
    const id = document.getElementById('edit_license_id').value;
    const msgInput = document.getElementById('edit_user_msg');
    const message = msgInput.value;

    if (!message) return showToast('Please enter a message first', 'danger');

    // Gather all data same as handleUpdateLicense to avoid resetting checkboxes
    const data = {
        action: 'update_license',
        license_id: id,
        user_msg: message
    };

    const fields = [
        'feature_autocaption', 'feature_youtube_upload', 'feature_tiktok_upload',
        'feature_movie_recap', 'feature_lyric_matcher', 'feature_export_cloud', 'provide_gemini_key',
        'provide_groq_key', 'provide_apify_key', 'provide_deepgram_key',
        'provide_google_oauth', 'provide_tiktok_key', 'auto_pilot', 'running_text'
    ];

    fields.forEach(f => {
        const el = document.getElementById(`edit_${f}`);
        if (el) data[f] = el.checked ? 1 : 0;
    });

    const btn = event ? event.target : null;
    if (btn && btn.tagName === 'BUTTON') {
        const originalText = btn.innerText;
        btn.innerText = 'Sending...';
        btn.disabled = true;

        const result = await apiRequest(data);

        if (result.success) {
            btn.innerText = 'Sent!';
            btn.classList.replace('btn-primary', 'btn-success'); // Visual feedback
            showToast('Message updated successfully!');

            // Short delay before resetting button state
            setTimeout(() => {
                btn.innerText = originalText;
                btn.classList.replace('btn-success', 'btn-primary');
                btn.disabled = false;
            }, 3000);

            loadLicenses(); // Refresh background table data
        } else {
            btn.innerText = 'Error';
            btn.disabled = false;
            showToast('Error: ' + result.message, 'danger');
        }
    }
}

async function handleUpdateLicense(event) {
    event.preventDefault();
    const id = document.getElementById('edit_license_id').value;
    const data = {
        action: 'update_license',
        license_id: id,
        owner_name: document.getElementById('edit_owner_name').value,
        user_msg: document.getElementById('edit_user_msg').value
    };

    const fields = [
        'feature_autocaption', 'feature_youtube_upload', 'feature_tiktok_upload',
        'feature_movie_recap', 'feature_lyric_matcher', 'feature_export_cloud', 'provide_gemini_key',
        'provide_groq_key', 'provide_apify_key', 'provide_deepgram_key',
        'provide_google_oauth', 'provide_tiktok_key', 'auto_pilot', 'running_text'
    ];

    fields.forEach(f => {
        const el = document.getElementById(`edit_${f}`);
        if (el) data[f] = el.checked ? 1 : 0;
    });

    toggleLoading(true);
    const result = await apiRequest(data);
    toggleLoading(false);

    if (result.success) {
        showToast('License details updated!');
        hideModal('licenseEditModal');
        loadLicenses();
    }
}

// --- Reseller Management ---
async function loadResellers() {
    toggleLoading(true);
    const result = await apiRequest({ action: 'list_resellers' });
    toggleLoading(false);

    if (result.success) {
        const tbody = document.getElementById('resellerTableBody');
        tbody.innerHTML = result.data.map(res => `
            <tr>
                <td><strong>${res.username}</strong></td>
                <td>${res.full_name}</td>
                <td><span class="badge badge-info">${res.balance} keys</span></td>
                <td><span class="badge badge-${res.status === 'active' ? 'success' : 'danger'}">${res.status}</span></td>
                <td>${new Date(res.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="editReseller(${res.id}, ${res.balance}, '${res.status}')">Edit</button>
                </td>
            </tr>
        `).join('');
    }
}

async function handleAddReseller(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    data.action = 'add_reseller';

    const result = await apiRequest(data);
    if (result.success) {
        showToast('Reseller account created!');
        hideModal('resellerModal');
        loadResellers();
        event.target.reset();
    } else {
        showToast(result.message, 'danger');
    }
}

async function editReseller(id, currentBalance, currentStatus) {
    const newBalance = prompt('New Balance (License Keys):', currentBalance);
    if (newBalance === null) return;

    const newStatus = confirm('Set status to ACTIVE? (Cancel for SUSPENDED)') ? 'active' : 'suspended';

    toggleLoading(true);
    const result = await apiRequest({
        action: 'update_reseller',
        id: id,
        balance: parseInt(newBalance),
        status: newStatus
    });
    toggleLoading(false);

    if (result.success) {
        showToast('Reseller updated!');
        loadResellers();
    }
}

// --- Credentials Management ---
async function loadCredentialsPool() {
    // 1. Gemini
    const resultGemini = await apiRequest({ action: 'get_gemini_keys' });
    if (resultGemini.success) {
        document.getElementById('geminiCount').textContent = resultGemini.data.length;
        document.getElementById('geminiKeysBody').innerHTML = renderKeyRows(resultGemini.data, 'Gemini');
    }

    // 2. Apify
    const resultApify = await apiRequest({ action: 'get_apify_keys' });
    if (resultApify.success) {
        document.getElementById('apifyCount').textContent = resultApify.data.length;
        document.getElementById('apifyKeysBody').innerHTML = renderKeyRows(resultApify.data, 'Apify');
    }

    // 3. Deepgram
    const resultDeepgram = await apiRequest({ action: 'get_deepgram_keys' });
    if (resultDeepgram.success) {
        document.getElementById('deepgramCount').textContent = resultDeepgram.data.length;
        document.getElementById('deepgramKeysBody').innerHTML = renderKeyRows(resultDeepgram.data, 'Deepgram');
    }

    // Check for alerts
    const allKeys = [...(resultGemini.data || []), ...(resultApify.data || []), ...(resultDeepgram.data || [])];
    const problems = allKeys.filter(k => k.status === 'dead' || k.status === 'limit' || k.status === 'cooldown');
    const alertBox = document.getElementById('dashboardAlerts');
    if (problems.length > 0) {
        const dead = problems.filter(k => k.status === 'dead').length;
        const limit = problems.filter(k => k.status !== 'dead').length;
        alertBox.innerHTML = `
            <div style="padding: 12px 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: 8px; color: var(--danger); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong>System Alert:</strong> Found ${problems.length} problematic API keys. 
                    (${dead} dead, ${limit} limited/cooldown). 
                    Please check Credentials section.
                </div>
                <button class="btn btn-sm btn-danger" onclick="showPanel('credentials')">Fix Now</button>
            </div>`;
        alertBox.style.display = 'block';
    } else {
        alertBox.style.display = 'none';
    }

    initIcons();

    // Load generic generic credential statuses (like Groq)
    const credResult = await apiRequest({ action: 'get_credentials' });
    if (credResult.success) {
        credResult.data.forEach(cred => {
            const statusElement = document.getElementById(`status_${cred.credential_type}`);
            if (statusElement) {
                statusElement.innerHTML = cred.has_value
                    ? '<span style="color: var(--success);">✅ Tersimpan</span>'
                    : '<span style="color: var(--danger);">❌ Kosong</span>';
            }
        });
    }
}

function renderKeyRows(keys, type) {
    return keys.map(key => {
        let statusBadge = 'secondary';
        if (key.status === 'active') statusBadge = 'success';
        if (key.status === 'invalid' || key.status === 'dead') statusBadge = 'danger';
        if (key.status === 'limit' || key.status === 'cooldown') statusBadge = 'warning';

        let errorDisplay = key.error_msg ? `<br><small style="color:var(--danger)">${key.error_msg}</small>` : '';
        if (key.cooldown_until) errorDisplay += `<br><small style="color:var(--warning)">Cooldown: ${key.cooldown_until}</small>`;

        return `
            <tr>
                <td><code>${key.api_key}</code></td>
                <td><span class="badge badge-${statusBadge}">${key.status}</span></td>
                <td>${errorDisplay || (key.last_checked || 'Never')}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="delete${type}(${key.id})"><i data-lucide="trash-2"></i></button>
                    <button class="btn btn-secondary btn-sm" onclick="test${type}(${key.id})"><i data-lucide="play"></i> Check</button>
                </td>
            </tr>
        `;
    }).join('');
}

async function handleBulkGemini() {
    const keysRaw = document.getElementById('bulkGeminiKeys').value;
    const keys = keysRaw.split('\n').map(k => k.trim()).filter(k => k.length > 5);
    const model = document.getElementById('geminiModelSelect').value;

    if (keys.length === 0) return showToast('Please enter some keys.', 'danger');

    toggleLoading(true);
    const result = await apiRequest({ action: 'add_gemini_keys', api_keys: keys, model: model });
    toggleLoading(false);

    if (result.success) {
        showToast(`Added ${result.message}`);
        document.getElementById('bulkGeminiKeys').value = '';
        loadCredentialsPool();
    }
}

async function testGemini(id) {
    const model = document.getElementById('geminiModelSelect')?.value || 'gemini-flash-latest';
    showToast(`Testing key with ${model}...`);
    const result = await apiRequest({ action: 'test_gemini_key', key_id: id, model: model });
    if (result.success) {
        showToast('Key is working! ' + result.message);
    } else {
        showToast('Key failed: ' + result.message, 'danger');
    }
    loadCredentialsPool();
}

async function deleteGemini(id) {
    if (!confirm('Remove this Gemini key?')) return;
    const result = await apiRequest({ action: 'delete_gemini_key', key_id: id });
    if (result.success) {
        showToast('Key removed.');
        loadCredentialsPool();
    }
}

// --- Apify Handlers ---
async function handleBulkApify() {
    const keysRaw = document.getElementById('bulkApifyKeys').value;
    const keys = keysRaw.split('\n').map(k => k.trim()).filter(k => k.length > 5);

    if (keys.length === 0) return showToast('Please enter keys.', 'danger');

    toggleLoading(true);
    const result = await apiRequest({ action: 'add_apify_keys', api_keys: keys });
    toggleLoading(false);

    if (result.success) {
        showToast(result.message);
        document.getElementById('bulkApifyKeys').value = '';
        loadCredentialsPool();
    }
}

async function testApify(id) {
    showToast(`Testing Apify key...`);
    const result = await apiRequest({ action: 'test_apify_key', key_id: id });
    if (result.success) showToast(result.message);
    else showToast('Failed: ' + result.message, 'danger');
    loadCredentialsPool();
}

async function deleteApify(id) {
    if (!confirm('Remove this Apify key?')) return;
    const result = await apiRequest({ action: 'delete_apify_key', key_id: id });
    if (result.success) { showToast('Key removed.'); loadCredentialsPool(); }
}

// --- Deepgram Handlers ---
async function handleBulkDeepgram() {
    const keysRaw = document.getElementById('bulkDeepgramKeys').value;
    const keys = keysRaw.split('\n').map(k => k.trim()).filter(k => k.length > 5);

    if (keys.length === 0) return showToast('Please enter keys.', 'danger');

    toggleLoading(true);
    const result = await apiRequest({ action: 'add_deepgram_keys', api_keys: keys });
    toggleLoading(false);

    if (result.success) {
        showToast(result.message);
        document.getElementById('bulkDeepgramKeys').value = '';
        loadCredentialsPool();
    }
}

async function testDeepgram(id) {
    showToast(`Testing Deepgram key...`);
    const result = await apiRequest({ action: 'test_deepgram_key', key_id: id });
    if (result.success) showToast(result.message);
    else showToast('Failed: ' + result.message, 'danger');
    loadCredentialsPool();
}

async function deleteDeepgram(id) {
    if (!confirm('Remove this Deepgram key?')) return;
    const result = await apiRequest({ action: 'delete_deepgram_key', key_id: id });
    if (result.success) { showToast('Key removed.'); loadCredentialsPool(); }
}

async function saveCred(type) {
    const val = document.getElementById(`cred_${type}`).value;
    if (!val) return showToast('Value cannot be empty.', 'danger');

    const result = await apiRequest({
        action: 'save_credential',
        credential_type: type,
        credential_value: val,
        description: `${type.toUpperCase()} Global Key`
    });

    if (result.success) {
        showToast(`${type.toUpperCase()} key updated!`);
        document.getElementById(`cred_${type}`).value = '';
    }
}

async function testCred(type) {
    showToast(`Testing ${type}...`);
    // Pass the value directly from input if user wants to test before saving, 
    // OR if empty, let server verify the stored one.
    const val = document.getElementById(`cred_${type}`).value;

    // If input is empty, testing the STORED credential
    // If input is not empty, testing the INPUT credential (optional feature)

    const result = await apiRequest({
        action: 'test_credential',
        credential_type: type,
        credential_value: val // Optional
    });

    if (result.success) {
        showToast(`✅ ${type.toUpperCase()} is Valid!`);
        alert(`Success: ${result.message}`);
    } else {
        showToast(`❌ ${type.toUpperCase()} Failed!`, 'danger');
        alert(`Error: ${result.message}`);
    }
}

async function saveOAuth(type) {
    const fields = (type === 'google' || type === 'youtube')
        ? ['client_id', 'client_secret', 'redirect_uri']
        : ['client_key', 'client_secret', 'redirect_uri'];

    toggleLoading(true);
    try {
        for (const field of fields) {
            const elId = `${type}_${field}`;
            const val = document.getElementById(elId).value;
            if (!val) continue;

            await apiRequest({
                action: 'save_credential',
                credential_type: `${type}_${field}`,
                credential_value: val,
                description: `${type.toUpperCase()} OAuth ${field}`
            });
        }
        showToast(`${type.toUpperCase()} credentials updated!`);
        loadCredentialsPool(); // Refresh status indicators
    } catch (e) {
        showToast('Error saving credentials', 'danger');
    }
    toggleLoading(false);
}

// Auth & Generic
function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!');
    });
}

function logout() {
    if (confirm('Logout from admin panel?')) {
        apiRequest({ action: 'logout' }).then(() => {
            window.location.href = 'login.html';
        });
    }
}

async function sendBroadcast() {
    const msg = document.getElementById('broadcast_msg').value;
    const running_text = document.getElementById('broadcast_marquee').checked ? 1 : 0;

    if (!msg) return showToast('Please enter a message', 'danger');

    if (!confirm('This will update the message for ALL licenses. Continue?')) return;

    toggleLoading(true);
    const result = await apiRequest({
        action: 'broadcast',
        message: msg,
        running_text: running_text
    });
    toggleLoading(false);

    if (result.success) {
        showToast('Broadcast sent successfully!');
        document.getElementById('broadcast_msg').value = '';
    }
}

function changeAdminKey() {
    const newKey = prompt('Please enter NEW Admin Key:\n(Note: You MUST also update it in config.php manually)');
    if (newKey) {
        alert('New key to copy: ' + newKey + '\n\nPlease update ADMIN_KEY in config.php to this value.');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initIcons();
    loadLicenses();
    loadCredentialsPool(); // Check for alerts immediately

    // Auto-refresh stats every minute
    setInterval(() => {
        if (document.getElementById('panel-dashboard').classList.contains('active')) {
            loadLicenses();
        }
    }, 60000);
});

// Polyfill for filter
function filterLicenses() {
    const q = document.getElementById('licenseSearch').value.toLowerCase();
    const lines = document.querySelectorAll('#mainLicenseBody tr');
    lines.forEach(line => {
        line.style.display = line.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
