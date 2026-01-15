<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/include/common_functions.php';

echo "<h2>Email Sending Diagnostic</h2>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f5f5f5;padding:10px;}</style>";

global $config;

echo "<h3>Step 1: Check Configuration</h3>";
echo "<pre>";
echo "SMTP Host: " . env('SMTP_HOST', 'NOT SET') . "\n";
echo "SMTP Port: " . env('SMTP_PORT', 'NOT SET') . "\n";
echo "SMTP Username: " . env('SMTP_USERNAME', 'NOT SET') . "\n";
echo "SMTP Password: " . (env('SMTP_PASSWORD') ? '***SET***' : 'NOT SET') . "\n";
echo "SMTP Secure: " . env('SMTP_SECURE', 'NOT SET') . "\n";
echo "Mail From: " . env('MAIL_FROM', 'NOT SET') . "\n";
echo "</pre>";

echo "<h3>Step 2: Test PHPMailer</h3>";
try {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<span class='err'>❌ PHPMailer not found. Run: composer install</span><br>";
    } else {
        echo "<span class='ok'>✓ PHPMailer library loaded</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

echo "<h3>Step 3: Test Password Reset Function</h3>";
echo "<p>Testing with a test email address...</p>";

$testEmail = 'testuser@example.com';

// Check if test user exists
$pdo = auth_db();
$stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :e LIMIT 1');
$stmt->execute([':e' => $testEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<span class='ok'>✓ Test user found: $testEmail</span><br>";
} else {
    echo "<span class='err'>❌ Test user not found. Creating one...</span><br>";
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, role, is_active, created_at) 
                           VALUES (:e, NULL, :fn, :ln, :r, 1, NOW())');
    $stmt->execute([':e' => $testEmail, ':fn' => 'Test', ':ln' => 'User', ':r' => 'User']);
    echo "<span class='ok'>✓ Test user created</span><br>";
}

echo "<h3>Step 4: Attempt to Send Password Reset Email</h3>";

try {
    $result = auth_request_password_reset($testEmail);
    
    if ($result === true) {
        echo "<span class='ok'>✓ Email sent successfully!</span><br>";
        echo "<p>Check your inbox for $testEmail</p>";
    } elseif (is_string($result) && filter_var($result, FILTER_VALIDATE_URL)) {
        echo "<span class='err'>⚠️ Email sending failed, but reset link generated:</span><br>";
        echo "<a href='" . htmlspecialchars($result) . "' target='_blank'>Password Reset Link</a><br>";
        echo "<p><small>This happens when SMTP is not configured. On production with working SMTP, the email would be sent.</small></p>";
    } else {
        echo "<span class='err'>❌ Unknown result: " . var_export($result, true) . "</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h3>Step 5: Check Password Reset Tokens Table</h3>";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as c FROM password_resets WHERE email = "' . $testEmail . '"');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $row['c'] ?? 0;
    
    if ($count > 0) {
        echo "<span class='ok'>✓ Password reset token created ($count found)</span><br>";
        
        // Show the latest token
        $stmt = $pdo->prepare('SELECT token, expires_at, created_at FROM password_resets WHERE email = :e ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([':e' => $testEmail]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset) {
            echo "<pre>";
            echo "Token: " . substr($reset['token'], 0, 20) . "...\n";
            echo "Created: {$reset['created_at']}\n";
            echo "Expires: {$reset['expires_at']}\n";
            echo "</pre>";
            
            // Generate manual reset link
            $baseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
            $resetUrl = $baseUrl . '/password_reset_form.php?token=' . urlencode($reset['token']);
            
            echo "<p><strong>Manual Password Reset Link:</strong></p>";
            echo "<a href='" . htmlspecialchars($resetUrl) . "' target='_blank'>Click here to reset password</a><br>";
        }
    } else {
        echo "<span class='err'>❌ No password reset token found</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='err'>❌ Error checking tokens: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

echo "<h3>Diagnosis Summary</h3>";
echo "<ul>";
echo "<li>If email sent successfully: ✅ Everything is working!</li>";
echo "<li>If you see a reset link but no email: ⚠️ SMTP may not be configured correctly</li>";
echo "<li>If you see errors: ❌ Check SMTP credentials in .env file</li>";
echo "</ul>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Check your SMTP credentials in production .env</li>";
echo "<li>Verify SMTP server allows connections from your server</li>";
echo "<li>Check spam folder for password reset emails</li>";
echo "<li>Test with your actual email address</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Test with Your Email</h3>";
echo "<form method='post'>";
echo "<label>Email: <input type='email' name='test_email' required placeholder='your@email.com'></label><br>";
echo "<button type='submit'>Send Test Password Reset</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $userEmail = $_POST['test_email'];
    echo "<hr><h3>Testing with: $userEmail</h3>";
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $stmt->execute([':e' => $userEmail]);
    
    if (!$stmt->fetch()) {
        echo "<span class='err'>❌ User not found. Please add this user first in the admin panel.</span><br>";
    } else {
        try {
            $result = auth_request_password_reset($userEmail);
            
            if ($result === true) {
                echo "<span class='ok'>✓ Email sent to $userEmail!</span><br>";
            } elseif (is_string($result) && filter_var($result, FILTER_VALIDATE_URL)) {
                echo "<span class='err'>⚠️ Email not sent, but here's the reset link:</span><br>";
                echo "<a href='" . htmlspecialchars($result) . "'>Password Reset Link</a><br>";
            }
        } catch (Exception $e) {
            echo "<span class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
    }
}
