@echo off
title WhatsApp Gateway Service
echo ===================================================
echo   Starting WhatsApp Web Sidecar and Event Listener
echo ===================================================
echo.

:: Kill any lingering node processes on port 3000
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :3000 ^| findstr LISTENING') do (
    echo Killing existing process on port 3000 (PID: %%a)...
    taskkill /PID %%a /F /T >nul 2>&1
)

:: Brief pause to allow port to release
timeout /t 2 /nobreak >nul

:: Set environment variables for the Node.js sidecar
set PORT=3000
set HOST=127.0.0.1
set SIDECAR_TOKEN=secure_whatsapp_web_sidecar_token_2026
set SESSION_DIR=storage\app\whatsapp-sidecar\sessions
set SIDECAR_PID_FILE=storage\app\whatsapp-sidecar\sidecar.pid
set AUTO_START_SESSIONS=true

echo 1. Starting Node.js sidecar process...
:: Launch Node.js sidecar in a SEPARATE WINDOW so it keeps running when this bat closes
start "WhatsApp Web Sidecar" /D "%CD%" cmd /k "C:\laragon\bin\nodejs\node-v22\node.exe vendor\kstmostofa\laravel-whatsapp\sidecar\index.js"

:: Wait for sidecar to bind to port (up to 15 seconds)
echo Waiting for sidecar to start...
set /a attempts=0
:wait_loop
timeout /t 1 /nobreak >nul
set /a attempts+=1
netstat -aon | findstr :3000 | findstr LISTENING >nul 2>&1
if %errorlevel%==0 goto sidecar_ready
if %attempts% LSS 15 goto wait_loop
echo [WARNING] Sidecar may not have started. Check the sidecar window for errors.
goto start_listener

:sidecar_ready
echo Sidecar is listening on port 3000!

:start_listener
echo.
echo 2. Starting event listener (SSE to Laravel Event Bridge)...
start "WhatsApp Web Listener" /D "%CD%" cmd /k "php artisan whatsapp:web:listen"

echo.
echo ===================================================
echo   Services are running!
echo   Keep the "WhatsApp Web Sidecar" window OPEN.
echo   Open http://localhost:8000/whatsapp in your browser.
echo ===================================================
echo.
pause
