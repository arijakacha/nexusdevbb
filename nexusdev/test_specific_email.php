<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "📧 Sending test email to guidarahedi8@gmail.com...\n\n";

try {
    // Use the current Gmail configuration
    $dsn = 'gmail://stwstw599@gmail.com:hykh%20jfia%20nuxj%20fiag@default';
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('stwstw599@gmail.com')
        ->to('guidarahedi8@gmail.com')
        ->subject('🧪 Test Email - NexusPlay Password Reset System')
        ->text("This is a test email from NexusPlay password reset system.\n\nSent at: " . date('Y-m-d H:i:s') . "\nFrom: stwstw599@gmail.com\nTo: guidarahedi8@gmail.com")
        ->html("
            <h1>🧪 Test Email - NexusPlay</h1>
            <p>This is a test email from the NexusPlay password reset system.</p>
            <p><strong>Details:</strong></p>
            <ul>
                <li><strong>From:</strong> stwstw599@gmail.com</li>
                <li><strong>To:</strong> guidarahedi8@gmail.com</li>
                <li><strong>Sent at:</strong> " . date('Y-m-d H:i:s') . "</li>
                <li><strong>System:</strong> Symfony Mailer with Gmail</li>
            </ul>
            <p><strong>✅ If you receive this email, the Gmail setup is working perfectly!</strong></p>
            <hr>
            <p><em>This is a test email for NexusPlay password reset functionality.</em></p>
        ");
    
    $mailer->send($email);
    
    echo "✅ SUCCESS: Email sent to guidarahedi8@gmail.com!\n";
    echo "📧 Check the Gmail inbox for guidarahedi8@gmail.com\n";
    echo "📋 Email Details:\n";
    echo "   - From: stwstw599@gmail.com\n";
    echo "   - To: guidarahedi8@gmail.com\n";
    echo "   - Subject: 🧪 Test Email - NexusPlay Password Reset System\n";
    echo "   - Sent at: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "🎯 Next Steps:\n";
    echo "1. Check guidarahedi8@gmail.com inbox\n";
    echo "2. Look for email from stwstw599@gmail.com\n";
    echo "3. If received, Gmail setup is working!\n";
    echo "4. Then test password reset with a database email\n\n";
    
    echo "📋 Valid Database Emails for Password Reset:\n";
    echo "- hedimed7007@gmail.com\n";
    echo "- admin@nexusplay.gg\n";
    echo "- neo@nexusplay.gg\n";
    echo "- fury@nexusplay.gg\n";
    echo "- zen@nexusplay.gg\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "🔧 Troubleshooting:\n";
    echo "1. Check if Gmail app password is correct\n";
    echo "2. Verify 2FA is enabled on stwstw599@gmail.com\n";
    echo "3. Ensure using app password, not regular password\n";
    echo "4. Check internet connection\n";
}
