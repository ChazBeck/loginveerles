<!-- 
    Shared Header Include for PHP Applications
    Include this file at the top of your PHP pages
    
    Usage: 
    <?php require_once __DIR__ . '/path/to/sharedheader-include.php'; ?>
-->

<!-- Shared Header CSS -->
<!-- Local: <link rel="stylesheet" href="/sharedheader/header.css"> -->
<!-- Using GitHub CDN: -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">

<!-- Shared Header HTML -->
<header id="shared-header" class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/" id="logo-link">
                <img src="/apps/auth/assets/logo.png" alt="Logo" id="header-logo-img" class="logo-image">
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

<!-- Configure Shared Header (must be before header.js) -->
<script src="/apps/auth/sharedheader-config.js"></script>

<!-- Include Shared Header JavaScript -->
<!-- Local: <script src="/sharedheader/header.js"></script> -->
<!-- Using GitHub CDN: -->
<script src="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.js"></script>
