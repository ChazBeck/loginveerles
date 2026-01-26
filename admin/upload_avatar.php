<?php
/**
 * Upload Avatar for User
 * Admin-only page to upload user avatars
 */

require __DIR__ . '/../include/jwt_include.php';
jwt_init();
jwt_require_admin();

$error = null;
$success = null;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Get user info
$pdo = auth_db();
$stmt = $pdo->prepare('SELECT id, email, first_name, last_name, avatar_url FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: index.php?error=' . urlencode('User not found'));
    exit;
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission (CSRF)';
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        
        // Validate file type
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed)) {
            $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB max
            $error = 'File too large. Maximum size is 2MB.';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . '/../assets/uploads/avatars';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $filepath = $upload_dir . '/' . $filename;
            
            // Delete old avatar if exists
            if ($user['avatar_url'] && file_exists(__DIR__ . '/../' . $user['avatar_url'])) {
                unlink(__DIR__ . '/../' . $user['avatar_url']);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                $avatar_url = 'assets/uploads/avatars/' . $filename;
                $update = $pdo->prepare('UPDATE users SET avatar_url = :url WHERE id = :id');
                $update->execute([':url' => $avatar_url, ':id' => $user_id]);
                
                header('Location: index.php?success=' . urlencode('Avatar uploaded successfully'));
                exit;
            } else {
                $error = 'Failed to upload file. Check directory permissions.';
            }
        }
    } elseif (isset($_POST['remove_avatar'])) {
        // Remove avatar
        if ($user['avatar_url'] && file_exists(__DIR__ . '/../' . $user['avatar_url'])) {
            unlink(__DIR__ . '/../' . $user['avatar_url']);
        }
        
        $update = $pdo->prepare('UPDATE users SET avatar_url = NULL WHERE id = :id');
        $update->execute([':id' => $user_id]);
        
        header('Location: index.php?success=' . urlencode('Avatar removed successfully'));
        exit;
    } else {
        $error = 'No file uploaded or upload error occurred.';
    }
}

// Get user data for header
$current_user = jwt_get_user();
$userName = trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? ''));
$userAvatar = 'https://ui-avatars.com/api/?' . http_build_query([
    'name' => $userName,
    'background' => '667eea',
    'color' => 'fff',
    'size' => '128',
    'bold' => 'true'
]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Avatar - Admin</title>
    <link rel="icon" type="image/png" href="../assets/images/veerless-logo-mark-sunrise-rgb-1920px-w-144ppi.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: url('../assets/images/v2osk-214954-unsplash.jpg') center/cover fixed;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 { color: #043546; margin-top: 0; }
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .current-avatar {
            text-align: center;
            margin: 20px 0;
        }
        .current-avatar img {
            max-width: 200px;
            border-radius: 50%;
            border: 3px solid #667eea;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #043546;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            background: #f8f9fa;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-primary {
            background: #E58325;
            color: white;
            flex: 1;
        }
        .btn-primary:hover {
            background: #d67520;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        .info {
            background: #e7f3ff;
            color: #004085;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
            font-size: 0.9rem;
        }
        /* Navigation styling */
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
</head>
<body>
<!-- Shared Header -->
<header class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/">
                <img src="../assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png" alt="Veerless Logo" class="logo-image" style="height: 50px;">
            </a>
        </div>
        <nav class="header-nav">
            <a href="../index.php">Home</a>
        </nav>
        <div class="header-right">
            <div class="user-avatar" style="display: flex;">
                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="User Avatar" class="avatar-image">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            </div>
            <button class="auth-button" onclick="window.location.href='../logout.php'">
                <span>Logout</span>
            </button>
        </div>
    </div>
</header>

<div class="container">
    <h1>Upload Avatar</h1>
    
    <div class="user-info">
        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong><br>
        <?= htmlspecialchars($user['email']) ?>
    </div>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($user['avatar_url']): ?>
        <div class="current-avatar">
            <p><strong>Current Avatar:</strong></p>
            <img src="../<?= htmlspecialchars($user['avatar_url']) ?>" alt="Current Avatar">
        </div>
    <?php else: ?>
        <div class="current-avatar">
            <p><strong>Current Avatar (Auto-generated):</strong></p>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'] . ' ' . $user['last_name']) ?>&background=667eea&color=fff&size=200&bold=true" alt="Auto Avatar">
        </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
        
        <div class="form-group">
            <label>Upload New Avatar</label>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
            <div class="info">
                • Allowed formats: JPG, PNG, GIF, WebP<br>
                • Maximum size: 2MB<br>
                • Recommended: Square image, at least 200x200 pixels
            </div>
        </div>
        
        <div class="button-group">
            <button type="submit" class="btn-primary">Upload Avatar</button>
            <button type="button" class="btn-secondary" onclick="window.location.href='index.php'">Cancel</button>
        </div>
    </form>
    
    <?php if ($user['avatar_url']): ?>
        <form method="post" style="margin-top: 20px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
            <input type="hidden" name="remove_avatar" value="1">
            <button type="submit" class="btn-danger" onclick="return confirm('Remove this avatar and use auto-generated one?')">
                Remove Custom Avatar
            </button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
