<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SimulatedWhatsAppService
{
    private HttpClientInterface $httpClient;
    private string $accountSid;
    private string $authToken;
    private string $twilioPhoneNumber;

    public function __construct(
        HttpClientInterface $httpClient,
        string $accountSid,
        string $authToken,
        string $twilioPhoneNumber
    ) {
        $this->httpClient = $httpClient;
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->twilioPhoneNumber = $twilioPhoneNumber;
    }

    public function sendMeetingLink(
        string $phoneNumber,
        string $playerName,
        string $meetingUrl,
        string $coachName,
        \DateTimeInterface $sessionDate
    ): array {
        // Simulate API delay
        usleep(1000000); // 1 second delay to feel real
        
        // Log the message that would be sent
        $message = $this->formatWhatsAppMessage($playerName, $coachName, $meetingUrl, $sessionDate);
        
        error_log("=== SIMULATED WhatsApp Message ===");
        error_log("To: $phoneNumber");
        error_log("From: $this->twilioPhoneNumber");
        error_log("Message: " . substr($message, 0, 200) . "...");
        error_log("================================");
        
        // Simulate realistic success/failure based on phone number
        if ($this->isValidPhoneNumber($phoneNumber)) {
            $messageId = 'WHATSAPP_' . strtoupper(uniqid()) . '_' . time();
            
            return [
                'success' => true,
                'messageId' => $messageId,
                'status' => 'sent',
                'message' => 'WhatsApp message sent successfully to ' . $phoneNumber,
                'simulated' => true,
                'to' => 'whatsapp:' . $phoneNumber,
                'from' => 'whatsapp:' . $this->twilioPhoneNumber
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Invalid phone number format: ' . $phoneNumber,
                'code' => 400,
                'simulated' => true
            ];
        }
    }
    
    private function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Basic validation: should start with + and have 8-15 digits
        return preg_match('/^\+[1-9][0-9]{7,14}$/', $phoneNumber);
    }

    private function formatWhatsAppMessage(
        string $playerName,
        string $coachName,
        string $meetingUrl,
        \DateTimeInterface $sessionDate
    ): string {
        return "🎮 *NexusPlay Coaching Session* 🎥\n\n" .
               "Hi {$playerName}! Your coaching session with {$coachName} is starting.\n\n" .
               "📅 *Date:* " . $sessionDate->format('M j, Y') . "\n" .
               "⏰ *Time:* " . $sessionDate->format('H:i') . "\n\n" .
               "🔗 *Join Video Call:*\n{$meetingUrl}\n\n" .
               "Click the link or copy/paste in your browser. No registration needed!\n\n" .
               "📞 Having trouble? Contact your coach.\n" .
               "Powered by NexusPlay 🚀";
    }
    
    public function isConfigured(): bool
    {
        // Always return true for simulation
        return true;
    }
    
    public function getServiceInfo(): array
    {
        return [
            'type' => 'Simulated WhatsApp Service',
            'status' => 'Active',
            'description' => 'Simulates WhatsApp sending without real API calls',
            'phone_number' => $this->twilioPhoneNumber,
            'note' => 'Messages are logged but not actually sent via WhatsApp'
        ];
    }
}
