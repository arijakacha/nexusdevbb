<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "🔧 DIRECT EMAIL TEST - Bypassing all complexity...\n\n";

try {
    // Use exact same configuration as the system
    $dsn = 'gmail://stwstw599@gmail.com:hykh%20jfia%20nuxj%20fiag@default';
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    // Create the EXACT same email as password reset
    $resetUrl = 'https://127.0.0.1:8000/reset-password/TEST-TOKEN-123';
    $expiresAt = new \DateTimeImmutable('+30 minutes');
    
    $email = (new Email())
        ->from('stwstw599@gmail.com')
        ->to('guidarahedi8@gmail.com')
        ->subject('Reset your NexusPlay password')
        ->html('
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Reset your NexusPlay password</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #eee; }
                    .content { padding: 20px 0; }
                    .button { display: inline-block; background-color: #007bff; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>NexusPlay</h1>
                </div>
                <div class="content">
                    <h2>Hello alii,</h2>
                    <p>You requested to reset your password for your NexusPlay account.</p>
                    <p>Click the button below to choose a new password. This link will expire on <strong>' . $expiresAt->format('Y-m-d H:i') . '</strong>.</p>
                    <p style="text-align: center;">
                        <a href="' . $resetUrl . '" class="button">Reset my password</a>
                    </p>
                    <p>If you didn\'t request this password reset, you can safely ignore this email. Your password won\'t change.</p>
                </div>
                <div class="footer">
                    <p>If the button above doesn\'t work, copy and paste this link into your browser:</p>
                    <p style="word-break: break-all;">' . $resetUrl . '</p>
                    <p>&copy; ' . date('Y') . ' NexusPlay. All rights reserved.</p>
                </div>
            </body>
            </html>
        ');
    
    echo "📧 Sending DIRECT test email...\n";
    $mailer->send($email);
    
    echo "✅ SUCCESS: Direct email sent!\n";
    echo "📧 To: guidarahedi8@gmail.com\n";
    echo "📧 From: stwstw599@gmail.com\n";
    echo "📧 Subject: Reset your NexusPlay password\n";
    echo "🕐 Sent at: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "🔍 This is the EXACT same email that the password reset system sends.\n";
    echo "📧 If you receive this email, then Gmail is working.\n";
    echo "❌ If you don\'t receive this email, then there\'s a Gmail delivery issue.\n\n";
    
    echo "🎯 CHECK NOW:\n";
    echo "1. Check guidarahedi8@gmail.com inbox\n";
    echo "2. Look for subject: \"Reset your NexusPlay password\"\n";
    echo "3. Check spam folder\n";
    echo "4. Look in \"All Mail\" folder\n\n";
    
    echo "📋 Tell me what happens:\n";
    echo "- Did you receive this direct test email?\n";
    echo "- Which folder did it appear in?\n";
    echo "- What time did it arrive?\n\n";
    
    echo "🚀 If this works, then the issue is in the password reset controller.\n";
    echo "❌ If this doesn\'t work, then Gmail is blocking these emails.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "🔧 This means there\'s a fundamental Gmail configuration issue.\n";
}
