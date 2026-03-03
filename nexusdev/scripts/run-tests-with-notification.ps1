# Script PowerShell pour exécuter les tests avec notification
Write-Host "🧪 Exécution des tests en cours..." -ForegroundColor Blue

# Exécuter les tests
$process = Start-Process -FilePath "vendor/bin/phpunit" -ArgumentList "--no-coverage" -Wait -PassThru -NoNewWindow

if ($process.ExitCode -eq 0) {
    Write-Host "✅ Tous les tests sont passés !" -ForegroundColor Green
    # Notification Windows
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show("Tous les tests sont passés !", "Succès", "OK", "Information")
} else {
    Write-Host "❌ Certains tests ont échoué !" -ForegroundColor Red
    # Notification Windows
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show("Certains tests ont échoué !", "Erreur", "OK", "Warning")
}

Write-Host "Appuyez sur une touche pour continuer..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
