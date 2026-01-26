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
<style>
    .header-nav {
        display: flex;
        gap: 20px;
        align-items: center;
        margin-left: auto;
        margin-right: 20px;
    }
    .header-nav a {
        color: white !important;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        padding: 10px 20px;
        border-radius: 4px;
        transition: background 0.2s;
        background: rgba(255, 255, 255, 0.1);
    }
    .header-nav a:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>

<!-- Shared Header HTML -->
<header id="shared-header" class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/" id="logo-link">
                <img src="/apps/auth/assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png" alt="Veerless Logo" id="header-logo-img" class="logo-image" style="height: 50px;">
            </a>
        </div>
        <nav class="header-nav">
            <a href="/apps/auth/index.php">Home</a>
        </nav>
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
