<?php
// CLI script to test SMTP sending using auth_smtp_send().
// Usage: php send_test_email_cli.php [recipient]

chdir(__DIR__);
require __DIR__ . '/../include/auth_include.php';

$to = $argv[1] ?? 'charlie@veerless.com';
$subject = 'SMTP test from auth service';
$body = "This is a test email sent by send_test_email_cli.php\n\nIf you receive it, SMTP is working.";

echo "Sending test email to: $to\n";
$sent = auth_smtp_send($to, $subject, $body, $config['mail_from'] ?? 'no-reply@veerl.es');
if ($sent) {
    echo "Send reported OK\n";
    exit(0);
} else {
    echo "Send failed\n";
    exit(2);
}
