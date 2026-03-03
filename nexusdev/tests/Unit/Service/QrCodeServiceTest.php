<?php

namespace App\Tests\Unit\Service;

use App\Service\QrCodeService;
use PHPUnit\Framework\TestCase;

class QrCodeServiceTest extends TestCase
{
    private QrCodeService $qrCodeService;

    protected function setUp(): void
    {
        $this->qrCodeService = new QrCodeService();
    }

    public function testGeneratePlayerQrCode(): void
    {
        $playerId = 123;
        $nickname = 'TestPlayer';

        $qrCode = $this->qrCodeService->generatePlayerQrCode($playerId, $nickname);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
        // Check that it's a valid base64 PNG
        $this->assertTrue(base64_decode(substr($qrCode, 22)) !== false);
    }

    public function testGeneratePlayerQrCodeWithEmptyNickname(): void
    {
        $playerId = 123;
        $nickname = '';

        $qrCode = $this->qrCodeService->generatePlayerQrCode($playerId, $nickname);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
        $this->assertTrue(base64_decode(substr($qrCode, 22)) !== false);
    }

    public function testGenerateTeamQrCode(): void
    {
        $teamId = 456;
        $teamName = 'Thunder Squad';

        $qrCode = $this->qrCodeService->generateTeamQrCode($teamId, $teamName);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
        $this->assertTrue(base64_decode(substr($qrCode, 22)) !== false);
    }

    public function testGenerateMatchQrCode(): void
    {
        $matchId = 789;
        $matchTitle = 'Grand Final';

        $qrCode = $this->qrCodeService->generateMatchQrCode($matchId, $matchTitle);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
        $this->assertTrue(base64_decode(substr($qrCode, 22)) !== false);
    }

    public function testGenerateQrCodesReturnDifferentResults(): void
    {
        $playerId = 123;
        $teamId = 456;

        $playerQrCode = $this->qrCodeService->generatePlayerQrCode($playerId, 'Player1');
        $teamQrCode = $this->qrCodeService->generateTeamQrCode($teamId, 'Team1');

        $this->assertNotEquals($playerQrCode, $teamQrCode);
    }

    public function testGenerateQrCodesWithSameInputReturnsSameResult(): void
    {
        $playerId = 123;
        $nickname = 'TestPlayer';

        $qrCode1 = $this->qrCodeService->generatePlayerQrCode($playerId, $nickname);
        $qrCode2 = $this->qrCodeService->generatePlayerQrCode($playerId, $nickname);

        $this->assertEquals($qrCode1, $qrCode2);
    }
}
