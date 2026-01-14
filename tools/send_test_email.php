<?php
// send_test_email.php
// Simple page to test SMTP settings via auth_smtp_send().
// Place your SMTP settings in ../config.php (copy from config.sample.php).

require __DIR__ . '/../include/auth_include.php';

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? 'Test email from auth service';
    $body = $_POST['body'] ?? "This is a test email.\n\nIf you received this, SMTP settings work.";
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $result = ['ok' => false, 'msg' => 'Invalid recipient email'];
    } else {
        $sent = auth_smtp_send($to, $subject, $body, $config['mail_from']);
        if ($sent) $result = ['ok' => true, 'msg' => 'Send reported OK'];
        else $result = ['ok' => false, 'msg' => 'Send failed (check SMTP credentials/port)'];
    }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>SMTP Test</title></head>
<body>
<h1>SMTP Test</h1>
<?php if ($result): ?><div style="color:<?= $result['ok'] ? 'green' : 'red' ?>"><?=htmlspecialchars($result['msg'])?></div><?php endif; ?>
<form method="post">
  <label>To: <input type="email" name="to" required></label><br>
  <label>Subject: <input type="text" name="subject" value="Test email from auth service"></label><br>
  <label>Body:<br><textarea name="body" rows="6" cols="60">This is a test email.

If you received this, SMTP settings work.</textarea></label><br>
  <button type="submit">Send test email</button>
</form>
<p>Note: If sending fails on localhost, use the importer or provisioning links to set passwords.</p>
</body></html>
