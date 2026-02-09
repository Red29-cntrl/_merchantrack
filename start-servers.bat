@echo off
echo ========================================
echo MerchantTrack Server Startup
echo ========================================
echo.

REM Check if .env exists
if not exist .env (
    echo ERROR: .env file not found!
    echo Please copy .env.example to .env and configure it first.
    pause
    exit /b 1
)

REM Get the IP address from .env or detect it
for /f "tokens=2 delims==" %%a in ('findstr "APP_URL" .env') do set APP_URL=%%a
echo Current APP_URL: %APP_URL%
echo.

echo Starting Laravel development server...
start "Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

timeout /t 2 /nobreak >nul

echo Starting WebSocket server...
start "WebSocket Server" cmd /k "php artisan websockets:serve --host=0.0.0.0 --port=6001"

echo.
echo ========================================
echo Servers are starting...
echo ========================================
echo.
echo Laravel Server: http://0.0.0.0:8000
echo WebSocket Server: ws://0.0.0.0:6001
echo.
echo To access from other computers, use your IP address:
echo Example: http://YOUR_IP:8000
echo.
echo Press any key to open the WebSocket dashboard...
pause >nul

REM Try to open browser with WebSocket dashboard
for /f "tokens=2 delims==" %%a in ('findstr "APP_URL" .env') do (
    set URL=%%a
    set URL=!URL:http://=!
    set URL=!URL:https://=!
    set URL=!URL::8000=!
    start http://!URL!:8000/laravel-websockets
)

echo.
echo Servers are running in separate windows.
echo Close those windows to stop the servers.
echo.
pause
