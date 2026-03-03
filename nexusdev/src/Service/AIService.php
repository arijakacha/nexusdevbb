<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $googleAiApiKey = null)
    {
        $this->client = $client;
        // Try multiple ways to get the API key
        $this->apiKey = $googleAiApiKey ?? $_ENV['GOOGLE_AI_API_KEY'] ?? getenv('GOOGLE_AI_API_KEY') ?? null;
    }

    /**
     * Generate organization description using AI
     */
    public function generateOrganizationDescription(string $orgName, string $type = 'esports'): string
    {
        if (!$this->apiKey) {
            return $this->generateFallbackDescription($orgName, $type);
        }

        try {
            $prompt = "Generate a professional, compelling description for an esports organization called '{$orgName}'. 
            The description should be 2-3 paragraphs, highlight competitive gaming, team spirit, and community engagement. 
            Make it inspiring and professional.";

            $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->generateFallbackDescription($orgName, $type);
        } catch (\Exception $e) {
            return $this->generateFallbackDescription($orgName, $type);
        }
    }

    /**
     * Generate team name suggestions
     */
    public function generateTeamNames(string $organizationName, string $game = 'general'): array
    {
        if (!$this->apiKey) {
            return $this->generateFallbackTeamNames($organizationName);
        }

        try {
            $prompt = "Generate 10 creative esports team names for an organization called '{$organizationName}'. 
            The names should be professional, memorable, and suitable for competitive gaming. 
            Return as a simple list, one name per line.";

            $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            return array_filter(array_map('trim', explode("\n", $text)));
        } catch (\Exception $e) {
            return $this->generateFallbackTeamNames($organizationName);
        }
    }

    /**
     * Generate team description using AI
     */
    public function generateTeamDescription(string $game, string $teamName): string
    {
        if (!$this->apiKey) {
            return $this->generateFallbackTeamDescription($game, $teamName);
        }

        try {
            $prompt = "Generate a professional, compelling description for a {$game} esports team called '{$teamName}'. 
            The description should be 2-3 paragraphs, highlight competitive gameplay, teamwork, strategic excellence, and dedication to victory. 
            Make it inspiring and professional, suitable for a serious gaming organization.";

            $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->generateFallbackTeamDescription($game, $teamName);
        } catch (\Exception $e) {
            return $this->generateFallbackTeamDescription($game, $teamName);
        }
    }

    /**
     * Generate social media content
     */
    public function generateSocialMediaPost(string $organizationName, string $eventType = 'announcement'): string
    {
        if (!$this->apiKey) {
            return $this->generateFallbackSocialPost($organizationName, $eventType);
        }

        try {
            $prompt = "Write an engaging social media post for {$organizationName} about a {$eventType}. 
            Make it exciting, include relevant hashtags, and keep it under 280 characters.";

            $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->generateFallbackSocialPost($organizationName, $eventType);
        } catch (\Exception $e) {
            return $this->generateFallbackSocialPost($organizationName, $eventType);
        }
    }

    /**
     * Fallback descriptions when AI is not available
     */
    private function generateFallbackDescription(string $orgName, string $type): string
    {
        $descriptions = [
            "Welcome to {$orgName}, a premier esports organization dedicated to excellence in competitive gaming. 
            Our teams compete at the highest levels, fostering talent and sportsmanship in the gaming community. 
            Join us on our journey to redefine competitive gaming and achieve greatness together.",

            "{$orgName} stands at the forefront of esports innovation, bringing together passionate gamers and 
            skilled professionals. We believe in the power of teamwork, dedication, and the relentless pursuit of victory. 
            Our organization provides the perfect environment for players to grow and excel.",

            "At {$orgName}, we're more than just an esports organization – we're a family of gamers united by 
            our passion for competition. Our teams embody the spirit of excellence, pushing boundaries and setting 
            new standards in the world of professional gaming."
        ];

        return $descriptions[array_rand($descriptions)];
    }

    /**
     * Fallback team names
     */
    private function generateFallbackTeamNames(string $organizationName): array
    {
        $prefixes = ['Elite', 'Prime', 'Apex', 'Vanguard', 'Unity', 'Fusion', 'Nova', 'Phoenix', 'Titan', 'Quantum'];
        $suffixes = ['Squad', 'Force', 'Legion', 'Division', 'Unit', 'Crew', 'Clan', 'Guild', 'Team', 'Syndicate'];
        
        $names = [];
        for ($i = 0; $i < 10; $i++) {
            $names[] = $prefixes[array_rand($prefixes)] . ' ' . $suffixes[array_rand($suffixes)];
        }
        
        return $names;
    }

    /**
     * Fallback team descriptions
     */
    private function generateFallbackTeamDescription(string $game, string $teamName): string
    {
        $descriptions = [
            "Welcome to {$teamName}, a premier {$game} team dedicated to excellence in competitive gaming. 
            Our roster combines strategic mastery with mechanical skill, creating a force to be reckoned with in the {$game} scene. 
            We train relentlessly, analyze every match, and push the boundaries of what's possible in professional {$game} competition.",

            "{$teamName} represents the pinnacle of {$game} competitive play. Our team is built on a foundation of trust, 
            communication, and shared ambition. Each player brings their unique strengths to create a cohesive unit that can adapt 
            to any situation and overcome any challenge in the {$game} arena.",

            "At {$teamName}, we embody the spirit of competitive {$game}. Our philosophy combines aggressive early-game pressure 
            with strategic late-game decision making. We believe in constant improvement, innovative strategies, and the relentless 
            pursuit of victory in every {$game} match we compete."
        ];

        return $descriptions[array_rand($descriptions)];
    }

    /**
     * Fallback social media posts
     */
    private function generateFallbackSocialPost(string $organizationName, string $eventType): string
    {
        $posts = [
            "🚀 Big news from {$organizationName}! Exciting things coming soon. Stay tuned! #esports #gaming #{$organizationName}",
            "🏆 {$organizationName} is leveling up! Join us on this incredible journey. #competitivegaming #esports",
            "🎮 {$organizationName} - Where champions are made. Follow us for epic gaming content! #gamingcommunity"
        ];

        return $posts[array_rand($posts)];
    }

    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
