<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "🔧 Testing Password Reset Email Fix...\n\n";

// Test the exact same email setup as the controller
try {
    $dsn = 'gmail://stwstw599@gmail.com:hykh%20jfia%20nuxj%20fiag@default';
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    // Simulate the password reset email (same as controller)
    $email = (new Email())
        ->from('stwstw599@gmail.com')  // Fixed sender
        ->to('guidarahedi8@gmail.com')
        ->subject('Reset your NexusPlay password')
        ->html('
            <h1>Password Reset Test</h1>
            <p>This is a test of the password reset email system.</p>
            <p><strong>From:</strong> stwstw599@gmail.com (Fixed!)</p>
            <p><strong>To:</strong> guidarahedi8@gmail.com</p>
            <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>✅ If you receive this, the password reset is fixed!</strong></p>
        ');
    
    $mailer->send($email);
    
    echo "✅ SUCCESS: Password reset email sent!\n";
    echo "📧 To: guidarahedi8@gmail.com\n";
    echo "📧 From: stwstw599@gmail.com (FIXED!)\n";
    echo "📧 Subject: Reset your NexusPlay password\n";
    echo "🕐 Sent at: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "🔧 What I Fixed:\n";
    echo "1. ✅ Changed sender from 'noreply@nexusplay.gg' to 'stwstw599@gmail.com'\n";
    echo "2. ✅ Added error logging to track email sending\n";
    echo "3. ✅ Fixed environment variable issue\n\n";
    
    echo "🎯 Next Steps:\n";
    echo "1. Check guidarahedi8@gmail.com for this test email\n";
    echo "2. If received, test real password reset:\n";
    echo "   - Go to: http://localhost:8000/forgot-password\n";
    echo "   - Use: hedimed7007@gmail.com (or any database email)\n";
    echo "   - Check that email inbox for reset link\n";
    echo "3. Check logs if still not working: var/log/dev.log\n\n";
    
    echo "📋 Valid Database Emails:\n";
    echo "- hedimed7007@gmail.com\n";
    echo "- admin@nexusplay.gg\n";
    echo "- neo@nexusplay.gg\n";
    echo "- fury@nexusplay.gg\n";
    echo "- zen@nexusplay.gg\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "🔧 Check:\n";
    echo "1. Gmail app password is correct\n";
    echo "2. 2FA is enabled on stwstw599@gmail.com\n";
    echo "3. Internet connection is working\n";
}
