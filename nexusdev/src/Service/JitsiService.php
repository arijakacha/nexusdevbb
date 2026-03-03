<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class JitsiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(HttpClientInterface $httpClient, string $jitsiApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $jitsiApiKey;
        $this->baseUrl = 'https://8x8.vc/vpaas-magic-cookie-d608256e812940009ae4a0a4573a8a70';
    }

    /**
     * Create a new Jitsi meeting room
     */
    public function createMeeting(string $roomName, array $options = []): array
    {
        $defaultOptions = [
            'startAudioOnly' => false,
            'startWithVideoMuted' => false,
            'startWithAudioMuted' => false,
            'prejoinPageEnabled' => true,
            'lobbyEnabled' => false,
            'subject' => 'Coaching Session',
            'password' => null,
            'exp' => (time() + 7200), // 2 hours expiry
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            // Generate JWT token for the meeting
            $token = $this->generateJwtToken($roomName, $options);
            
            // Build meeting URL
            $meetingUrl = $this->baseUrl . '/' . $roomName . '?jwt=' . $token;

            return [
                'success' => true,
                'meetingUrl' => $meetingUrl,
                'roomName' => $roomName,
                'token' => $token,
                'expiresAt' => $options['exp']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create meeting: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate JWT token for Jitsi meeting
     */
    private function generateJwtToken(string $roomName, array $options): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'iss' => 'vpaas-magic-cookie-d608256e812940009ae4a0a4573a8a70',
            'aud' => 'jitsi',
            'exp' => $options['exp'],
            'sub' => 'vpaas-magic-cookie-d608256e812940009ae4a0a4573a8a70',
            'room' => $roomName,
            'context' => [
                'user' => [
                    'name' => 'Coach',
                    'email' => 'coach@nexusplay.test',
                    'avatar' => ''
                ],
                'features' => [
                    'livestreaming' => false,
                    'recording' => false,
                    'transcription' => false,
                    'outbound-call' => false
                ]
            ]
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->apiKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Check if a meeting exists and is active
     */
    public function checkMeetingStatus(string $roomName): array
    {
        try {
            // For now, we'll assume the meeting exists if we can generate a valid token
            // In a real implementation, you might want to check Jitsi API for active participants
            return [
                'success' => true,
                'active' => true,
                'roomName' => $roomName
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to check meeting status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate a unique room name for a session
     */
    public function generateRoomName(int $sessionId, string $coachName, string $playerName): string
    {
        $timestamp = time();
        $coachSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $coachName));
        $playerSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $playerName));
        
        return sprintf('nexus-coach-%s-%s-%d-%s', 
            substr($coachSlug, 0, 10), 
            substr($playerSlug, 0, 10), 
            $sessionId, 
            substr($timestamp, -6)
        );
    }
}
