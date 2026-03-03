<?php

namespace App\Command;

use App\Service\SmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-sms',
    description: 'Test SMS functionality by sending a test message'
)]
class TestSmsCommand extends Command
{
    public function __construct(
        private SmsService $smsService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('phone', InputArgument::REQUIRED, 'Phone number to send test SMS to')
            ->setHelp('This command allows you to test SMS functionality by sending a test message to a specified phone number.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $phoneNumber = $input->getArgument('phone');

        $io->title('Testing SMS Service');

        $io->section('SMS Service Information');
        $smsInfo = $this->smsService->getSmsInfo();
        $io->table(
            ['Property', 'Value'],
            [
                ['Provider', $smsInfo['provider']],
                ['Free Tier', $smsInfo['free_tier']],
                ['Cost per SMS', $smsInfo['cost_per_sms']],
                ['Supported Countries', $smsInfo['supported_countries']],
            ]
        );

        $io->section('Sending Test SMS');
        $io->text("Sending test SMS to: $phoneNumber");

        $result = $this->smsService->sendTestSms($phoneNumber);

        if ($result['success']) {
            $io->success('✅ Test SMS sent successfully!');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Message ID', $result['messageId'] ?? 'N/A'],
                    ['Status', $result['status'] ?? 'sent'],
                    ['To', $phoneNumber],
                ]
            );
        } else {
            $io->error('❌ Failed to send test SMS');
            $io->text('Error: ' . $result['error']);
            
            $io->section('Troubleshooting Tips');
            $io->text([
                '1. Check your Twilio credentials in .env.local',
                '2. Verify phone number format (include country code: +1XXXXXXXXXX)',
                '3. Ensure your Twilio account has sufficient credit',
                '4. Check if your Twilio phone number is verified (trial accounts)',
                '5. Verify the recipient phone number can receive SMS',
            ]);
            
            return Command::FAILURE;
        }

        $io->section('Next Steps');
        $io->text([
            '1. Check your phone for the test message',
            '2. Update player profiles with phone numbers',
            '3. Enable SMS consent for players',
            '4. Create a meeting to test automatic SMS notifications',
        ]);

        return Command::SUCCESS;
    }
}
