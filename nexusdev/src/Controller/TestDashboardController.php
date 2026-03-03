<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TestDashboardController extends AbstractController
{
    #[Route('/test-dashboard', name: 'app_test_dashboard')]
    public function index(): Response
    {
        // Exécuter les tests et récupérer les résultats
        $testResults = $this->runTests();
        
        return $this->render('test_dashboard.html.twig', [
            'testResults' => $testResults
        ]);
    }
    
    private function runTests(): array
    {
        $results = [];
        
        try {
            // Exécuter PHPUnit
            $process = new Process(['vendor/bin/phpunit', '--no-coverage']);
            $process->run();
            
            $results['phpunit'] = [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput()
            ];
            
            // Exécuter PHPStan
            $process = new Process(['vendor/bin/phpstan', 'analyse', '--no-progress', '--level=1']);
            $process->run();
            
            $results['phpstan'] = [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput()
            ];
            
        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
}
