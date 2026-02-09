# MerchantTrack Secure System Startup Script
# This script starts both Laravel and WebSocket servers securely

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MerchantTrack Secure System Startup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .env exists
if (-not (Test-Path ".env")) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Please copy .env.example to .env and configure it first." -ForegroundColor Yellow
    pause
    exit 1
}

# Get the IP address from .env or detect it
$envContent = Get-Content ".env" -Raw
$appUrlMatch = [regex]::Match($envContent, "APP_URL=(.+)")
if ($appUrlMatch.Success) {
    $appUrl = $appUrlMatch.Groups[1].Value.Trim()
    Write-Host "Current APP_URL: $appUrl" -ForegroundColor Green
} else {
    Write-Host "WARNING: APP_URL not found in .env" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "Starting Laravel development server..." -ForegroundColor Yellow
# Fix: Use $null to explicitly discard the return value instead of assigning to unused variable
$null = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "serve", "--host=0.0.0.0", "--port=8000" `
    -WindowStyle Normal `
    -PassThru

Start-Sleep -Seconds 2

Write-Host "Starting WebSocket server..." -ForegroundColor Yellow
# Fix: Use $null to explicitly discard the return value instead of assigning to unused variable
$null = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "websockets:serve", "--host=0.0.0.0", "--port=6001" `
    -WindowStyle Normal `
    -PassThru

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Servers are starting..." -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Laravel Server: http://0.0.0.0:8000" -ForegroundColor White
Write-Host "WebSocket Server: ws://0.0.0.0:6001" -ForegroundColor White
Write-Host ""
Write-Host "To access from other computers, use your IP address:" -ForegroundColor Yellow
Write-Host "Example: http://YOUR_IP:8000" -ForegroundColor Gray
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

# Try to open browser with WebSocket dashboard
if ($appUrlMatch.Success) {
    $url = $appUrl -replace "http://", "" -replace "https://", "" -replace ":8000", ""
    $dashboardUrl = "http://${url}:8000/laravel-websockets"
    Write-Host "Opening WebSocket dashboard: $dashboardUrl" -ForegroundColor Cyan
    Start-Process $dashboardUrl
}

Write-Host ""
Write-Host "Servers are running in separate windows." -ForegroundColor Green
Write-Host "Close those windows to stop the servers." -ForegroundColor Yellow
Write-Host ""
pause
