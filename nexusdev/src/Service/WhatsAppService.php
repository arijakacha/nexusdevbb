<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WhatsAppService
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
        try {
            // Debug: Log configuration
            error_log("WhatsApp Service Debug - Account SID: " . substr($this->accountSid, 0, 8) . "...");
            error_log("WhatsApp Service Debug - Phone Number: " . $this->twilioPhoneNumber);
            error_log("WhatsApp Service Debug - Target: " . $phoneNumber);

            // Validate configuration
            if (empty($this->accountSid) || empty($this->authToken) || empty($this->twilioPhoneNumber)) {
                return [
                    'success' => false,
                    'error' => 'Twilio credentials are not properly configured'
                ];
            }

            // Clean and validate phone number
            $cleanPhoneNumber = $this->cleanPhoneNumber($phoneNumber);
            if (!$this->isValidPhoneNumber($cleanPhoneNumber)) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format: ' . $phoneNumber
                ];
            }

            // Format the WhatsApp message (simpler for testing)
            $message = $this->formatWhatsAppMessage($playerName, $coachName, $meetingUrl, $sessionDate);
            error_log("WhatsApp Service Debug - Message: " . substr($message, 0, 100) . "...");

            // Twilio WhatsApp API endpoint
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

            // Prepare the request data
            $data = [
                'To' => 'whatsapp:' . $cleanPhoneNumber,
                'From' => 'whatsapp:' . $this->twilioPhoneNumber,
                'Body' => $message,
            ];

            error_log("WhatsApp Service Debug - Request Data: " . json_encode($data));

            // Send the request
            $response = $this->httpClient->request('POST', $url, [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'body' => $data,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray();

            error_log("WhatsApp Service Debug - Status Code: " . $statusCode);
            error_log("WhatsApp Service Debug - Response: " . json_encode($responseData));

            if ($statusCode === 201) {
                return [
                    'success' => true,
                    'messageId' => $responseData['sid'] ?? null,
                    'status' => $responseData['status'] ?? 'queued',
                    'message' => 'WhatsApp message sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Twilio API error: ' . ($responseData['message'] ?? 'Unknown error'),
                    'code' => $statusCode
                ];
            }

        } catch (\Exception $e) {
            error_log("WhatsApp Service Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send WhatsApp message: ' . $e->getMessage()
            ];
        }
    }

    private function cleanPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters except +
        $clean = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Ensure it starts with +
        if (!str_starts_with($clean, '+')) {
            $clean = '+' . $clean;
        }
        
        return $clean;
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
        // Simple message for testing - avoid special formatting that might cause issues
        return "NexusPlay Coaching Session\n\n" .
               "Hi {$playerName}! Your coaching session with {$coachName} is starting.\n\n" .
               "Date: " . $sessionDate->format('M j, Y') . "\n" .
               "Time: " . $sessionDate->format('H:i') . "\n\n" .
               "Join Video Call:\n" . $meetingUrl . "\n\n" .
               "Click the link or copy/paste in your browser. No registration needed!\n\n" .
               "Having trouble? Contact your coach.\n" .
               "Powered by NexusPlay";
    }

    public function isConfigured(): bool
    {
        $configured = !empty($this->accountSid) && 
                     !empty($this->authToken) && 
                     !empty($this->twilioPhoneNumber);
        
        error_log("WhatsApp Service Configured: " . ($configured ? 'YES' : 'NO'));
        error_log("Account SID: " . (empty($this->accountSid) ? 'MISSING' : 'PRESENT'));
        error_log("Auth Token: " . (empty($this->authToken) ? 'MISSING' : 'PRESENT'));
        error_log("Phone Number: " . (empty($this->twilioPhoneNumber) ? 'MISSING' : 'PRESENT'));
        
        return $configured;
    }
}
