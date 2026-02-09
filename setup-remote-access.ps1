# MerchantTrack Remote Access Setup Script
# This script helps configure your system for remote access

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MerchantTrack Remote Access Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get IP addresses
Write-Host "Detecting network IP addresses..." -ForegroundColor Yellow
$ipAddresses = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike "127.*" -and $_.IPAddress -notlike "169.254.*" } | Select-Object -ExpandProperty IPAddress

if ($ipAddresses.Count -eq 0) {
    Write-Host "ERROR: No network IP addresses found!" -ForegroundColor Red
    Write-Host "Please ensure you are connected to a network." -ForegroundColor Red
    pause
    exit 1
}

Write-Host "Found IP addresses:" -ForegroundColor Green
$index = 1
foreach ($ip in $ipAddresses) {
    Write-Host "  $index. $ip" -ForegroundColor White
    $index++
}

Write-Host ""
$selectedIndex = Read-Host "Select IP address to use (enter number)"

if ([int]$selectedIndex -lt 1 -or [int]$selectedIndex -gt $ipAddresses.Count) {
    Write-Host "Invalid selection!" -ForegroundColor Red
    pause
    exit 1
}

$selectedIP = $ipAddresses[[int]$selectedIndex - 1]
Write-Host ""
Write-Host "Selected IP: $selectedIP" -ForegroundColor Green
Write-Host ""

# Check if .env exists
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Write-Host "Copying .env.example to .env..." -ForegroundColor Yellow
        Copy-Item ".env.example" ".env"
        Write-Host "Please run: php artisan key:generate" -ForegroundColor Yellow
    } else {
        Write-Host "ERROR: .env file not found and .env.example doesn't exist!" -ForegroundColor Red
        pause
        exit 1
    }
}

# Read .env file
$envContent = Get-Content ".env" -Raw

# Update APP_URL
$appUrlPattern = "APP_URL=.*"
$newAppUrl = "APP_URL=http://$selectedIP`:8000"
if ($envContent -match $appUrlPattern) {
    $envContent = $envContent -replace $appUrlPattern, $newAppUrl
    Write-Host "Updated APP_URL to: $newAppUrl" -ForegroundColor Green
} else {
    $envContent += "`n$newAppUrl`n"
    Write-Host "Added APP_URL: $newAppUrl" -ForegroundColor Green
}

# Update/Create broadcasting settings
$broadcastSettings = @"
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=merchantrack
PUSHER_APP_KEY=merchantrack-key
PUSHER_APP_SECRET=merchantrack-secret
PUSHER_APP_CLUSTER=mt1
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=6001
"@

$settingsToAdd = @()
$settingsToCheck = @("BROADCAST_DRIVER", "PUSHER_APP_ID", "PUSHER_APP_KEY", "PUSHER_APP_SECRET", "PUSHER_APP_CLUSTER", "WEBSOCKET_HOST", "WEBSOCKET_PORT")

foreach ($setting in $settingsToCheck) {
    $pattern = "$setting=.*"
    if ($envContent -notmatch $pattern) {
        $line = $broadcastSettings | Select-String -Pattern "$setting=.*" | Select-Object -First 1
        if ($line) {
            $settingsToAdd += $line.Line
        }
    } else {
        Write-Host "Setting $setting already exists" -ForegroundColor Gray
    }
}

if ($settingsToAdd.Count -gt 0) {
    $envContent += "`n# Real-time Broadcasting Configuration`n"
    $envContent += ($settingsToAdd -join "`n") + "`n"
    Write-Host "Added broadcasting configuration" -ForegroundColor Green
}

# Write updated .env
Set-Content ".env" -Value $envContent -NoNewline

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Configuration Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Your system will be accessible at:" -ForegroundColor Yellow
Write-Host "  http://$selectedIP`:8000" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Install dependencies:" -ForegroundColor White
Write-Host "   composer require beyondcode/laravel-websockets" -ForegroundColor Gray
Write-Host "   php artisan vendor:publish --provider=`"BeyondCode\LaravelWebSockets\WebSocketsServiceProvider`" --tag=`"migrations`"" -ForegroundColor Gray
Write-Host "   php artisan migrate" -ForegroundColor Gray
Write-Host "   php artisan vendor:publish --provider=`"BeyondCode\LaravelWebSockets\WebSocketsServiceProvider`" --tag=`"config`"" -ForegroundColor Gray
Write-Host "   npm install --save laravel-echo pusher-js" -ForegroundColor Gray
Write-Host "   npm run dev" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Configure Windows Firewall to allow ports 8000 and 6001" -ForegroundColor White
Write-Host ""
Write-Host "3. Run start-servers.bat to start both servers" -ForegroundColor White
Write-Host ""
pause
