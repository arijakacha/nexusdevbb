<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "🔍 Debugging Password Reset Email...\n\n";

// Test with different Gmail addresses
$testEmails = [
    'stwstw599@gmail.com',
    'hedimed7007@gmail.com',
    'test@gmail.com'
];

foreach ($testEmails as $email) {
    echo "📧 Testing email to: $email\n";
    
    try {
        $dsn = 'gmail://stwstw599@gmail.com:hykh%20jfia%20nuxj%20fiag@default';
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);
        
        $emailMessage = (new Email())
            ->from('noreply@nexusplay.test')
            ->to($email)
            ->subject('🧪 Debug Test - Password Reset Email')
            ->text("This is a debug test email sent to: $email")
            ->html("<h1>Debug Test</h1><p>This is a debug test email sent to: <strong>$email</strong></p><p>Time: " . date('Y-m-d H:i:s') . "</p>");
        
        $mailer->send($emailMessage);
        echo "   ✅ Email sent successfully to $email\n";
        
    } catch (Exception $e) {
        echo "   ❌ Error sending to $email: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "🎯 Next Steps:\n";
echo "1. Check ALL Gmail inboxes for the test emails above\n";
echo "2. Check spam folders\n";
echo "3. Look for emails from: noreply@nexusplay.test\n";
echo "4. Subject: '🧪 Debug Test - Password Reset Email'\n";
echo "5. If you receive these emails, Gmail is working\n";
echo "6. Then try password reset with a VALID database email\n\n";

echo "📋 Valid Database Emails:\n";
echo "- hedimed7007@gmail.com (recommended)\n";
echo "- admin@nexusplay.gg\n";
echo "- neo@nexusplay.gg\n";
echo "- fury@nexusplay.gg\n";
echo "- zen@nexusplay.gg\n";
