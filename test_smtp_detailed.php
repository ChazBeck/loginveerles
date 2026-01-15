<?php
/**
 * Detailed SMTP Diagnostic Tool
 * Tests PHPMailer with full error output to identify connection issues
 */

require_once __DIR__ . '/include/common_functions.php';

$db = auth_db();
$testEmail = 'testuser@example.com';
$debugOutput = '';
$errors = [];

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMTP Detailed Diagnostic</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-left: 4px solid #2196F3; padding-left: 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 4px; margin: 10px 0; }
        pre { background: #1e1e1e; color: #dcdcdc; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px; line-height: 1.5; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-family: 'Monaco', 'Courier New', monospace; }
        .config { background: white; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .config dt { font-weight: bold; color: #555; margin-top: 10px; }
        .config dd { margin-left: 20px; color: #777; font-family: monospace; }
        button { background: #4CAF50; color: white; border: none; padding: 12px 24px; cursor: pointer; border-radius: 4px; font-size: 16px; margin: 10px 0; }
        button:hover { background: #45a049; }
        input[type="email"] { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 300px; margin-right: 10px; }
        .debug-output { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>🔍 SMTP Detailed Diagnostic</h1>
    
    <h2>Step 1: Configuration Check</h2>
    <?php
    global $config;
    $smtp = $config['smtp'] ?? [];
    
    if (empty($smtp['host'])) {
        echo '<div class="error">❌ <strong>SMTP not configured</strong> - No SMTP host found in config</div>';
    } else {
        echo '<div class="config">';
        echo '<dl>';
        echo '<dt>SMTP Host:</dt><dd>' . htmlspecialchars($smtp['host']) . '</dd>';
        echo '<dt>SMTP Port:</dt><dd>' . htmlspecialchars($smtp['port'] ?? 587) . '</dd>';
        echo '<dt>SMTP Username:</dt><dd>' . htmlspecialchars($smtp['username'] ?? 'not set') . '</dd>';
        echo '<dt>SMTP Password:</dt><dd>' . (empty($smtp['password']) ? 'not set' : '***SET*** (' . strlen($smtp['password']) . ' chars)') . '</dd>';
        echo '<dt>SMTP Secure:</dt><dd>' . htmlspecialchars($smtp['secure'] ?? 'tls') . '</dd>';
        echo '<dt>Mail From:</dt><dd>' . htmlspecialchars($smtp['from'] ?? 'not set') . '</dd>';
        echo '<dt>Timeout:</dt><dd>' . htmlspecialchars($smtp['timeout'] ?? 30) . ' seconds</dd>';
        echo '</dl>';
        echo '</div>';
    }
    ?>
    
    <h2>Step 2: PHPMailer Test with Debug Output</h2>
    <?php
    if (!class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        echo '<div class="error">❌ PHPMailer not loaded</div>';
    } else {
        echo '<div class="success">✓ PHPMailer library available</div>';
        
        // Test with full debug output
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
            $testEmailAddress = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
            
            if (!$testEmailAddress) {
                echo '<div class="error">❌ Invalid email address</div>';
            } else {
                echo '<div class="info"><strong>Testing email send to:</strong> ' . htmlspecialchars($testEmailAddress) . '</div>';
                
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    
                    // Enable verbose debug output
                    $mail->SMTPDebug = 2; // Show all SMTP communication
                    $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
                        $debugOutput .= htmlspecialchars($str) . "\n";
                    };
                    
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();
                    $mail->Host = $smtp['host'];
                    $mail->Port = $smtp['port'] ?? 587;
                    
                    $secure = strtolower($smtp['secure'] ?? 'tls');
                    if ($secure === 'ssl') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } elseif ($secure === 'tls') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    }
                    
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp['username'];
                    $mail->Password = $smtp['password'];
                    $mail->Timeout = $smtp['timeout'] ?? 30;
                    
                    $mail->setFrom($smtp['from'] ?? $smtp['username'], 'Veerl.es Authentication');
                    $mail->addAddress($testEmailAddress);
                    $mail->Subject = 'SMTP Test Email - ' . date('Y-m-d H:i:s');
                    $mail->Body = "This is a test email sent from the SMTP diagnostic tool.\n\nIf you received this, SMTP is working correctly!";
                    
                    if ($mail->send()) {
                        echo '<div class="success">✅ <strong>Email sent successfully!</strong><br>Check your inbox at ' . htmlspecialchars($testEmailAddress) . '</div>';
                    } else {
                        echo '<div class="error">❌ <strong>Email failed to send</strong><br>Error: ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
                    }
                    
                } catch (\Exception $e) {
                    echo '<div class="error">❌ <strong>Exception occurred:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
                    $errors[] = $e->getMessage();
                }
                
                if (!empty($debugOutput)) {
                    echo '<h3>SMTP Debug Output:</h3>';
                    echo '<pre class="debug-output">' . $debugOutput . '</pre>';
                }
            }
        }
    }
    ?>
    
    <h2>Step 3: Test Email Send</h2>
    <form method="POST">
        <p>Enter an email address to test SMTP sending:</p>
        <input type="email" name="test_email" placeholder="your@email.com" required value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>">
        <button type="submit">Send Test Email</button>
    </form>
    
    <h2>Step 4: Connection Test</h2>
    <?php
    if (!empty($smtp['host'])) {
        echo '<div class="info">';
        echo '<strong>Testing if server can reach SMTP host...</strong><br>';
        
        $host = $smtp['host'];
        $port = $smtp['port'] ?? 587;
        
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        if ($connection) {
            echo '✅ Successfully connected to ' . htmlspecialchars($host) . ':' . $port . '<br>';
            echo 'Server can reach the SMTP host.';
            fclose($connection);
        } else {
            echo '❌ Failed to connect to ' . htmlspecialchars($host) . ':' . $port . '<br>';
            echo 'Error: ' . htmlspecialchars($errstr) . ' (Code: ' . $errno . ')<br>';
            echo '<strong>Possible causes:</strong>';
            echo '<ul>';
            echo '<li>Firewall blocking outbound connections on port ' . $port . '</li>';
            echo '<li>SMTP server hostname is incorrect</li>';
            echo '<li>Server network issues</li>';
            echo '</ul>';
        }
        echo '</div>';
    }
    ?>
    
    <h2>Diagnosis Summary</h2>
    <div class="info">
        <h3>Common SMTP Issues:</h3>
        <ul>
            <li><strong>Authentication Failed:</strong> Wrong username or password - check SMTP credentials in .env</li>
            <li><strong>Connection Timeout:</strong> Firewall blocking port 587 - contact hosting provider</li>
            <li><strong>STARTTLS errors:</strong> TLS negotiation failing - try using SSL on port 465 instead</li>
            <li><strong>Relay denied:</strong> Server IP not whitelisted - check mail server settings</li>
            <li><strong>SSL certificate errors:</strong> May need to disable cert verification (not recommended for production)</li>
        </ul>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>Review the SMTP Debug Output above for specific error messages</li>
            <li>If authentication fails, verify SMTP password in .env file</li>
            <li>If connection fails, check with hosting provider about SMTP access</li>
            <li>Try alternative SMTP settings (different port, SSL vs TLS)</li>
            <li>As a workaround, use the manual password reset links from test_email.php</li>
        </ol>
    </div>
    
    <h2>Alternative Workaround</h2>
    <div class="warning">
        <strong>Until SMTP is fixed:</strong> You can use <a href="test_email.php">test_email.php</a> to generate manual password reset links.
        Copy the link and send it to users via Slack, personal email, or other communication method.
    </div>
</body>
</html>
