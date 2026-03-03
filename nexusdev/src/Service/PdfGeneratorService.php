<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function generatePlayerStatsPdf($player, $statistics, $recentMatches): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render('pdf/player_stats.html.twig', [
            'player' => $player,
            'statistics' => $statistics,
            'recentMatches' => $recentMatches,
            'generatedAt' => new \DateTime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateTeamReportPdf($team, $players, $stats): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        $html = $this->twig->render('pdf/team_report.html.twig', [
            'team' => $team,
            'players' => $players,
            'stats' => $stats,
            'generatedAt' => new \DateTime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
