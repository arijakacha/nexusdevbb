<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "🔧 Testing Gmail Configuration...\n\n";

// Test 1: Check if DSN is properly configured
echo "1. Checking MAILER_DSN...\n";
$dsn = 'gmail://stwstw599@gmail.com:hykh%20jfia%20nuxj%20fiag@default';
echo "   DSN: " . $dsn . "\n\n";

// Test 2: Try to create transport
echo "2. Testing transport connection...\n";
try {
    $transport = Transport::fromDsn($dsn);
    echo "   ✅ Transport created successfully\n";
    
    // Test 3: Try to send a test email
    echo "3. Sending test email...\n";
    
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('noreply@nexusplay.test')
        ->to('stwstw599@gmail.com')
        ->subject('🧪 Test Email from NexusPlay')
        ->text('This is a test email to verify Gmail configuration is working.')
        ->html('<h1>Test Email</h1><p>This is a test email to verify Gmail configuration is working.</p>');
    
    $mailer->send($email);
    echo "   ✅ Test email sent successfully!\n";
    echo "   📧 Check stwstw599@gmail.com inbox\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   📋 Troubleshooting:\n";
    echo "      - Check 2FA is enabled on Gmail\n";
    echo "      - Verify app password is correct\n";
    echo "      - Ensure using app password, not regular password\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. If test email arrives → Gmail setup is working\n";
echo "2. Check if user exists in database for password reset\n";
echo "3. Test password reset at: http://localhost:8000/forgot-password\n";
