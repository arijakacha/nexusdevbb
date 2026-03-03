<?php

namespace App\Service;

use App\Entity\Organization;
use App\Entity\Team;
use App\Entity\Player;
use App\Entity\TeamInvitation;
use App\Entity\Notification;
use App\Entity\Game;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TeamInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AITeamCreatorService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private EntityManagerInterface $entityManager;
    private GameRepository $gameRepository;
    private PlayerRepository $playerRepository;
    private TeamRepository $teamRepository;
    private TeamInvitationRepository $invitationRepository;

    public function __construct(
        HttpClientInterface $client,
        string $googleAiApiKey = null,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        TeamRepository $teamRepository,
        TeamInvitationRepository $invitationRepository
    ) {
        $this->client = $client;
        // Try multiple ways to get the API key
        $this->apiKey = $googleAiApiKey ?? $_ENV['GOOGLE_AI_API_KEY'] ?? getenv('GOOGLE_AI_API_KEY') ?? null;
        $this->entityManager = $entityManager;
        $this->gameRepository = $gameRepository;
        $this->playerRepository = $playerRepository;
        $this->teamRepository = $teamRepository;
        $this->invitationRepository = $invitationRepository;
    }

    /**
     * Create a complete AI-generated team
     */
    public function createAITeam(Organization $organization, int $gameId, array $preferences = []): array
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \Exception('Game not found');
        }

        // Generate team details with AI
        $teamDetails = $this->generateTeamDetails($organization->getName(), $game->getName(), $preferences);
        
        // Create the team
        $team = new Team();
        $team->setName($teamDetails['name']);
        $team->setDescription($teamDetails['description']);
        $team->setOrganization($organization);
        $team->setCreatedAt(new \DateTime());
        
        $this->entityManager->persist($team);
        $this->entityManager->flush();

        // Find and recruit players
        $recruitedPlayers = $this->recruitPlayersForTeam($team, $game, $teamDetails['strategy']);

        return [
            'team' => $team,
            'details' => $teamDetails,
            'recruitedPlayers' => $recruitedPlayers,
            'success' => true
        ];
    }

    /**
     * Generate team details using AI
     */
    private function generateTeamDetails(string $organizationName, string $gameName, array $preferences): array
    {
        if (!$this->apiKey) {
            return $this->generateFallbackTeamDetails($organizationName, $gameName, $preferences);
        }

        try {
            $prompt = "Create a professional esports team for organization '{$organizationName}' playing {$gameName}. 
            Generate:
            1. A creative, professional team name (not including organization name)
            2. A compelling team description (2-3 sentences)
            3. Team strategy/playstyle (1-2 sentences)
            4. Player roles needed (list 5 roles)
            
            Format as JSON with keys: name, description, strategy, roles";

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
            
            // Try to parse JSON, fallback if fails
            $decoded = json_decode($text, true);
            if ($decoded && isset($decoded['name'])) {
                return [
                    'name' => $decoded['name'],
                    'description' => $decoded['description'],
                    'strategy' => $decoded['strategy'],
                    'roles' => $decoded['roles'] ?? []
                ];
            }
        } catch (\Exception $e) {
            // Fall back to template if AI fails
        }

        return $this->generateFallbackTeamDetails($organizationName, $gameName, $preferences);
    }

    /**
     * Fallback team details when AI is not available
     */
    private function generateFallbackTeamDetails(string $organizationName, string $gameName, array $preferences): array
    {
        $teamNames = [
            'Elite Squad', 'Vanguard Force', 'Apex Legion', 'Nova Division', 
            'Phoenix Unit', 'Quantum Syndicate', 'Storm Brigade', 'Shadow Elite',
            'Thunder Strike', 'Frost Guard'
        ];

        $descriptions = [
            "A competitive {$gameName} team focused on strategic gameplay and teamwork.",
            "Professional {$gameName} team dedicated to excellence and continuous improvement.",
            "Elite {$gameName} players united by passion and commitment to victory.",
            "Strategic {$gameName} team combining skill, coordination, and determination."
        ];

        $strategies = [
            "Aggressive early-game dominance with coordinated team fights",
            "Strategic late-game scaling with objective control",
            "Balanced playstyle adapting to opponent strategies",
            "Fast-paced aggression with map control focus"
        ];

        $gameRoles = [
            'League of Legends' => ['Top Lane', 'Jungle', 'Mid Lane', 'ADC', 'Support'],
            'Valorant' => ['Duelist', 'Controller', 'Initiator', 'Sentinel', 'Smokes'],
            'CS:GO' => ['Entry Fragger', 'AWPer', 'Support', 'Lurker', 'IGL'],
            'Overwatch' => ['Tank', 'Damage', 'Support', 'Flex', 'Shotcaller'],
            'Fortnite' => ['IGL', 'Builder', 'Shotcaller', 'Support', 'Aggressive'],
            'Default' => ['Player 1', 'Player 2', 'Player 3', 'Player 4', 'Player 5']
        ];

        $roles = $gameRoles[$gameName] ?? $gameRoles['Default'];

        return [
            'name' => $teamNames[array_rand($teamNames)],
            'description' => $descriptions[array_rand($descriptions)],
            'strategy' => $strategies[array_rand($strategies)],
            'roles' => $roles
        ];
    }

    /**
     * Recruit players for the team
     */
    private function recruitPlayersForTeam(Team $team, $game, string $strategy): array
    {
        // Find available players for this game
        $players = $this->playerRepository->findBy(['game' => $game], ['score' => 'DESC'], 20);
        
        $recruitedPlayers = [];
        $maxPlayers = 5; // Standard team size
        $playersRecruited = 0;

        foreach ($players as $player) {
            if ($playersRecruited >= $maxPlayers) {
                break;
            }

            // Skip if player already has a team
            if ($player->getTeam()) {
                continue;
            }

            // Create team invitation
            $invitation = new TeamInvitation();
            $invitation->setPlayer($player);
            $invitation->setTeam($team);
            $invitation->setStatus(TeamInvitation::STATUS_PENDING);
            $invitation->setMessage($this->generateInvitationMessage($team, $player, $strategy));
            $invitation->setCreatedAt(new \DateTime());

            $this->entityManager->persist($invitation);

            // Create notification for player
            $notification = new Notification();
            $notification->setUser($player->getUser());
            $notification->setMessage(sprintf(
                '🎮 AI-Generated Team "%s" wants you to join! %s. Click here to respond.',
                $team->getName(),
                $strategy
            ));

            $this->entityManager->persist($notification);

            $recruitedPlayers[] = [
                'player' => $player,
                'invitation' => $invitation,
                'message' => $invitation->getMessage()
            ];

            $playersRecruited++;
        }

        $this->entityManager->flush();

        return $recruitedPlayers;
    }

    /**
     * Generate personalized invitation message
     */
    private function generateInvitationMessage(Team $team, Player $player, string $strategy): string
    {
        if (!$this->apiKey) {
            return $this->generateFallbackInvitationMessage($team, $player, $strategy);
        }

        try {
            $prompt = "Write a personalized esports team invitation message. 
            Team: {$team->getName()}
            Player: {$player->getNickname()} (Score: {$player->getScore()})
            Strategy: {$strategy}
            
            Make it exciting, professional, and persuasive. Keep it under 200 characters.";

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
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->generateFallbackInvitationMessage($team, $player, $strategy);
        } catch (\Exception $e) {
            return $this->generateFallbackInvitationMessage($team, $player, $strategy);
        }
    }

    /**
     * Fallback invitation message
     */
    private function generateFallbackInvitationMessage(Team $team, Player $player, string $strategy): string
    {
        $messages = [
            "Join {$team->getName()}! Your skills are perfect for our strategy.",
            "Hey {$player->getNickname()}! Want to join {$team->getName()}?",
            "{$team->getName()} needs players like you. Interested in joining?",
            "Your talent fits perfectly with {$team->getName()}. Join us!"
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * Get available games for team creation (only games with players)
     */
    public function getAvailableGames(): array
    {
        // Query through Player entity to find games with available players
        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT g')
            ->from(Game::class, 'g')
            ->innerJoin(Player::class, 'p', 'WITH', 'p.game = g')
            ->where('p.team IS NULL')  // Only show games with available players
            ->orderBy('g.name', 'ASC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
