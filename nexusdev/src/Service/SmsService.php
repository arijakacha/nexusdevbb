<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmsService
{
    private HttpClientInterface $httpClient;
    private string $twilioAccountSid;
    private string $twilioAuthToken;
    private string $twilioPhoneNumber;

    public function __construct(
        HttpClientInterface $httpClient,
        string $twilioAccountSid,
        string $twilioAuthToken,
        string $twilioPhoneNumber
    ) {
        $this->httpClient = $httpClient;
        $this->twilioAccountSid = $twilioAccountSid;
        $this->twilioAuthToken = $twilioAuthToken;
        $this->twilioPhoneNumber = $twilioPhoneNumber;
    }

    /**
     * Send SMS with meeting link to player
     */
    public function sendMeetingLink(string $playerPhone, string $playerName, string $meetingUrl, string $coachName, \DateTimeImmutable $scheduledAt): array
    {
        // Format phone number (remove any non-digit characters except +)
        $formattedPhone = $this->formatPhoneNumber($playerPhone);
        
        if (!$this->isValidPhoneNumber($formattedPhone)) {
            return [
                'success' => false,
                'error' => 'Invalid phone number format'
            ];
        }

        $message = $this->buildMeetingMessage($playerName, $meetingUrl, $coachName, $scheduledAt);

        try {
            $response = $this->httpClient->request('POST', "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioAccountSid}/Messages.json", [
                'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                'body' => [
                    'From' => $this->twilioPhoneNumber,
                    'To' => $formattedPhone,
                    'Body' => $message
                ]
            ]);

            $data = $response->toArray();

            return [
                'success' => true,
                'messageId' => $data['sid'] ?? null,
                'status' => $data['status'] ?? 'sent',
                'to' => $formattedPhone
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'SMS sending failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send test SMS (for verification)
     */
    public function sendTestSms(string $phoneNumber): array
    {
        $message = "🎮 NexusPlay Test: Your SMS service is working! Meeting links will be sent here when coaches start video calls.";

        try {
            $response = $this->httpClient->request('POST', "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioAccountSid}/Messages.json", [
                'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                'body' => [
                    'From' => $this->twilioPhoneNumber,
                    'To' => $this->formatPhoneNumber($phoneNumber),
                    'Body' => $message
                ]
            ]);

            $data = $response->toArray();

            return [
                'success' => true,
                'messageId' => $data['sid'] ?? null,
                'status' => $data['status'] ?? 'sent'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Test SMS failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build the meeting message content
     */
    private function buildMeetingMessage(string $playerName, string $meetingUrl, string $coachName, \DateTimeImmutable $scheduledAt): string
    {
        $time = $scheduledAt->format('H:i');
        $date = $scheduledAt->format('M j, Y');
        
        return "🎮 NexusPlay Coaching Session 🎥\n\n" .
               "Hi {$playerName}! Your coaching session with {$coachName} is starting.\n\n" .
               "📅 Date: {$date}\n" .
               "⏰ Time: {$time}\n\n" .
               "🔗 Join Video Call:\n" .
               $meetingUrl . "\n\n" .
               "Click the link or copy/paste in your browser. No registration needed!\n\n" .
               "📞 Having trouble? Contact your coach.\n" .
               "Powered by NexusPlay 🚀";
    }

    /**
     * Format phone number for Twilio
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If number doesn't start with +, add country code (assuming US/Canada)
        if (!str_starts_with($phone, '+')) {
            // Remove leading 1 if exists (US country code)
            if (str_starts_with($phone, '1') && strlen($phone) === 11) {
                $phone = substr($phone, 1);
            }
            
            // Add +1 for US/Canada numbers
            if (strlen($phone) === 10) {
                $phone = '+1' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }

    /**
     * Validate phone number format
     */
    private function isValidPhoneNumber(string $phone): bool
    {
        // Basic validation: should start with + and have 10-15 digits
        return preg_match('/^\+[1-9][0-9]{9,14}$/', $phone);
    }

    /**
     * Get SMS pricing info (Twilio free tier)
     */
    public function getSmsInfo(): array
    {
        return [
            'provider' => 'Twilio',
            'free_tier' => '15.00 USD starting credit',
            'cost_per_sms' => '~0.08 USD (US numbers)',
            'supported_countries' => '180+ countries',
            'features' => [
                'Delivery receipts',
                'Webhooks',
                'Message templates',
                'Phone number validation'
            ]
        ];
    }
}
