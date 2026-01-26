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

require __DIR__ . '/../include/render_header.php';
render_page_head('Upload Avatar - Admin', ['pathPrefix' => '../', 'includeAdmin' => true]);
render_app_header(['pathPrefix' => '../', 'homeUrl' => '../index.php']);
?>

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
