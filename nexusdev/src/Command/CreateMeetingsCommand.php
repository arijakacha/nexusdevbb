<?php

namespace App\Command;

use App\Repository\CoachingSessionRepository;
use App\Service\JitsiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-meetings',
    description: 'Automatically create Jitsi meetings for upcoming coaching sessions'
)]
class CreateMeetingsCommand extends Command
{
    public function __construct(
        private CoachingSessionRepository $sessionRepository,
        private JitsiService $jitsiService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command creates Jitsi meetings for sessions that are scheduled to start within the next 15 minutes and don\'t have an active meeting yet.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Creating Jitsi Meetings for Upcoming Sessions');

        // Find sessions starting in the next 15 minutes without active meetings
        $now = new \DateTimeImmutable();
        $in15Minutes = $now->modify('+15 minutes');
        
        $upcomingSessions = $this->sessionRepository->findSessionsNeedingMeetings($now, $in15Minutes);

        if (empty($upcomingSessions)) {
            $io->success('No upcoming sessions need meetings created.');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Found %d sessions needing meetings', count($upcomingSessions)));

        $createdCount = 0;
        $failedCount = 0;

        foreach ($upcomingSessions as $session) {
            $io->text(sprintf('Processing session #%d with %s', $session->getId(), $session->getPlayer()->getNickname()));

            try {
                // Generate room name
                $roomName = $this->jitsiService->generateRoomName(
                    $session->getId(),
                    $session->getCoach()->getUser()->getUsername(),
                    $session->getPlayer()->getNickname()
                );

                // Create meeting
                $meeting = $this->jitsiService->createMeeting($roomName, [
                    'subject' => 'Coaching Session with ' . $session->getPlayer()->getNickname(),
                    'exp' => (time() + 7200), // 2 hours
                ]);

                if ($meeting['success']) {
                    $session->setMeetingUrl($meeting['meetingUrl']);
                    $session->setMeetingRoom($meeting['roomName']);
                    $session->setMeetingExpiresAt(new \DateTimeImmutable('@' . $meeting['expiresAt']));
                    $this->entityManager->flush();

                    $io->success(sprintf('✓ Meeting created: %s', $roomName));
                    $createdCount++;
                } else {
                    $io->error(sprintf('✗ Failed to create meeting: %s', $meeting['error']));
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $io->error(sprintf('✗ Error processing session #%d: %s', $session->getId(), $e->getMessage()));
                $failedCount++;
            }
        }

        $io->section('Summary');
        $io->success(sprintf('Created: %d meetings', $createdCount));
        
        if ($failedCount > 0) {
            $io->warning(sprintf('Failed: %d meetings', $failedCount));
        }

        return Command::SUCCESS;
    }
}
