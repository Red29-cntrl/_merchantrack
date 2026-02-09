# Windows Firewall Configuration Script for MerchantTrack
# This script configures Windows Firewall to allow Laravel and WebSocket servers

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MerchantTrack Firewall Configuration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "WARNING: This script requires administrator privileges!" -ForegroundColor Red
    Write-Host "Please run PowerShell as Administrator and try again." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit 1
}

Write-Host "Configuring Windows Firewall rules..." -ForegroundColor Yellow
Write-Host ""

# Function to create firewall rule
function New-FirewallRule {
    param(
        [string]$Name,
        [int]$Port,
        [string]$Protocol = "TCP"
    )
    
    $ruleName = "MerchantTrack - $Name"
    
    # Check if rule already exists
    $existingRule = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
    
    if ($existingRule) {
        Write-Host "Rule '$ruleName' already exists. Skipping..." -ForegroundColor Gray
        return
    }
    
    try {
        New-NetFirewallRule -DisplayName $ruleName `
            -Direction Inbound `
            -LocalPort $Port `
            -Protocol $Protocol `
            -Action Allow `
            -Profile Domain,Private,Public | Out-Null
        
        Write-Host "Created firewall rule: $ruleName (Port $Port/$Protocol)" -ForegroundColor Green
    } catch {
        $errorMsg = $_.Exception.Message
        Write-Host "Failed to create rule for port ${Port}: $errorMsg" -ForegroundColor Red
    }
}

# Create rules for Laravel and WebSocket servers
New-FirewallRule -Name "Laravel Server" -Port 8000
New-FirewallRule -Name "WebSocket Server" -Port 6001

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Firewall Configuration Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Firewall rules have been created for:" -ForegroundColor Yellow
Write-Host "  - Port 8000 (Laravel Server)" -ForegroundColor White
Write-Host "  - Port 6001 (WebSocket Server)" -ForegroundColor White
Write-Host ""
Write-Host "Your system is now ready for remote access!" -ForegroundColor Green
Write-Host ""
pause
