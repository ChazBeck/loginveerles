<?php
/**
 * Test Page for Shared Header Integration
 * This page demonstrates the shared header with SSO integration
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/include/jwt_include.php';

$config = require __DIR__ . '/config.php';
jwt_init();
$user = jwt_get_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Header Test - Veerl.es Tools</title>
    
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
        }
        .content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
        }
        .status-logged-in {
            background: #d4edda;
            color: #155724;
        }
        .status-logged-out {
            background: #f8d7da;
            color: #721c24;
        }
        .user-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .test-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        .test-button:hover {
            background: #5568d3;
        }
        pre {
            background: #2d3748;
            color: #fff;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .success { color: #155724; font-weight: 600; }
        .error { color: #721c24; font-weight: 600; }
    </style>
    
    <!-- Shared Header CSS - Update path based on where you cloned the repo -->
    <link rel="stylesheet" href="/sharedheader/header.css">
    <!-- Or use CDN: -->
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css"> -->
</head>
<body>
    <!-- Shared Header HTML -->
    <header id="shared-header" class="shared-header">
        <div class="header-container">
            <div class="header-logo">
                <a href="/apps/" id="logo-link">
                    <img src="/apps/auth/assets/logo.png" alt="Logo" id="header-logo-img" class="logo-image" style="height: 40px;">
                    <span class="logo-text" id="logo-text">Veerl.es Tools</span>
                </a>
            </div>
            <div class="header-right">
                <div class="user-avatar" id="user-avatar" style="display: none;">
                    <img src="" alt="User Avatar" id="avatar-img" class="avatar-image">
                    <span class="user-name" id="user-name"></span>
                </div>
                <button class="auth-button" id="auth-button">
                    <span id="auth-button-text">Login</span>
                </button>
            </div>
        </div>
    </header>

    <div class="content">
        <div class="card">
            <h1>🎨 Shared Header Integration Test</h1>
            <p>This page tests the shared header component with your SSO authentication system.</p>
            
            <!-- Current Status -->
            <h2>Current Authentication Status</h2>
            <?php if ($user): ?>
                <span class="status-badge status-logged-in">✓ Logged In</span>
                <div class="user-info">
                    <strong>User Details (from PHP):</strong><br>
                    Name: <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?><br>
                    Email: <?= htmlspecialchars($user['email'] ?? '') ?><br>
                    Role: <?= htmlspecialchars($user['role'] ?? '') ?><br>
                    User ID: <?= htmlspecialchars($user['id'] ?? '') ?>
                </div>
            <?php else: ?>
                <span class="status-badge status-logged-out">✗ Not Logged In</span>
                <p>Please <a href="/apps/auth/login.php?return_to=<?= urlencode($_SERVER['REQUEST_URI']) ?>">login</a> to test the shared header avatar.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>🔧 Integration Tests</h2>
            
            <div class="test-section">
                <h3>1. Test SSO Endpoint</h3>
                <p>Click the button below to test the <code>/api/auth/check.php</code> endpoint:</p>
                <button class="test-button" onclick="testSSOEndpoint()">Test SSO Endpoint</button>
                <div id="sso-result"></div>
            </div>

            <div class="test-section">
                <h3>2. Test Header JavaScript</h3>
                <p>Check if the shared header JavaScript loaded correctly:</p>
                <button class="test-button" onclick="testHeaderJS()">Test Header Instance</button>
                <div id="header-result"></div>
            </div>

            <div class="test-section">
                <h3>3. Refresh User Data</h3>
                <p>Manually trigger a refresh of user data in the header:</p>
                <button class="test-button" onclick="refreshUserData()">Refresh User Data</button>
                <div id="refresh-result"></div>
            </div>

            <?php if ($user): ?>
            <div class="test-section">
                <h3>4. Test Logout</h3>
                <p>Test the logout functionality:</p>
                <button class="test-button" onclick="testLogout()">Test Logout</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📝 Expected Behavior</h2>
            <ul>
                <li><strong>When logged out:</strong> Header shows "Login" button</li>
                <li><strong>When logged in:</strong> Header shows avatar with user's name and "Logout" button</li>
                <li><strong>Avatar:</strong> Generated using UI Avatars with user's initials</li>
                <li><strong>Click avatar:</strong> Logs user data to console (customizable)</li>
            </ul>
        </div>

        <div class="card">
            <h2>🛠️ Troubleshooting</h2>
            <ol>
                <li>Open browser DevTools (F12)</li>
                <li>Check Console tab for errors</li>
                <li>Check Network tab to see API calls</li>
                <li>Verify the shared header files are loaded (check Network tab)</li>
                <li>Test the SSO endpoint using the button above</li>
            </ol>
            
            <h3>Common Issues:</h3>
            <ul>
                <li><strong>404 errors:</strong> Check that shared header files are in <code>/sharedheader/</code> directory</li>
                <li><strong>Avatar not showing:</strong> Verify you're logged in and SSO endpoint returns data</li>
                <li><strong>CORS errors:</strong> Update CORS headers in <code>/api/auth/check.php</code></li>
            </ul>
        </div>

        <div class="card">
            <h2>📚 Next Steps</h2>
            <ol>
                <li>If tests pass, integrate the header into your applications</li>
                <li>Copy the header HTML/CSS/JS includes to your app templates</li>
                <li>Customize colors and styling in <code>sharedheader-config.js</code></li>
                <li>Add custom avatar upload functionality if desired</li>
                <li>See <code>SHAREDHEADER_INTEGRATION.md</code> for detailed instructions</li>
            </ol>
        </div>
    </div>

    <!-- Configure Shared Header (MUST come before header.js) -->
    <script src="/apps/auth/sharedheader-config.js"></script>
    
    <!-- Include Shared Header JavaScript -->
    <script src="/sharedheader/header.js"></script>
    <!-- Or use CDN: -->
    <!-- <script src="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.js"></script> -->

    <!-- Test Functions -->
    <script>
        async function testSSOEndpoint() {
            const resultDiv = document.getElementById('sso-result');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('/apps/auth/api/auth/check.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <p class="success">✓ Success! Endpoint returned:</p>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <p class="error">✗ Error: ${error.message}</p>
                    <p>Check browser console for details.</p>
                `;
            }
        }

        function testHeaderJS() {
            const resultDiv = document.getElementById('header-result');
            
            if (typeof window.sharedHeaderInstance !== 'undefined') {
                const user = window.sharedHeaderInstance.getUser();
                const isLoggedIn = window.sharedHeaderInstance.isLoggedIn;
                
                resultDiv.innerHTML = `
                    <p class="success">✓ Header instance found!</p>
                    <p>Logged in: ${isLoggedIn}</p>
                    ${user ? `<pre>${JSON.stringify(user, null, 2)}</pre>` : '<p>No user data (not logged in)</p>'}
                `;
            } else {
                resultDiv.innerHTML = `
                    <p class="error">✗ Header instance not found!</p>
                    <p>Make sure header.js is loaded correctly.</p>
                `;
            }
        }

        async function refreshUserData() {
            const resultDiv = document.getElementById('refresh-result');
            
            if (typeof window.sharedHeaderInstance !== 'undefined') {
                try {
                    // Force a check of auth status
                    await window.sharedHeaderInstance.checkAuthStatus();
                    resultDiv.innerHTML = '<p class="success">✓ User data refreshed! Check the header.</p>';
                } catch (error) {
                    resultDiv.innerHTML = `<p class="error">✗ Error: ${error.message}</p>`;
                }
            } else {
                resultDiv.innerHTML = '<p class="error">✗ Header instance not found!</p>';
            }
        }

        function testLogout() {
            if (window.sharedHeaderInstance) {
                window.sharedHeaderInstance.logout();
            } else {
                window.location.href = '/apps/auth/logout.php';
            }
        }

        // Auto-run tests when page loads
        window.addEventListener('load', () => {
            console.log('🧪 Running automatic tests...');
            setTimeout(() => {
                testSSOEndpoint();
                testHeaderJS();
            }, 1000); // Wait 1 second for header to initialize
        });
    </script>
</body>
</html>
