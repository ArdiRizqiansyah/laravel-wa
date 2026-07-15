@echo off
title WhatsApp Gateway Service
echo ===================================================
echo   Starting WhatsApp Web Sidecar and Event Listener
echo ===================================================
echo.

echo 1. Starting Node.js sidecar process...
:: Set environment variables for the Node.js sidecar
set PORT=3000
set HOST=127.0.0.1
set SIDECAR_TOKEN=secure_whatsapp_web_sidecar_token_2026
set SESSION_DIR=storage\app\whatsapp-sidecar\sessions
set SIDECAR_PID_FILE=storage\app\whatsapp-sidecar\sidecar.pid

:: Launch Node.js sidecar in a separate window
start "WhatsApp Web Sidecar" cmd /k "C:\laragon\bin\nodejs\node-v22\node.exe vendor\kstmostofa\laravel-whatsapp\sidecar\index.js"

echo.
echo 2. Starting event listener (SSE to Laravel Event Bridge)...
start "WhatsApp Web Listener" cmd /k "php artisan whatsapp:web:listen"

echo.
echo ===================================================
echo   Services are running!
echo   Open http://localhost:8000/whatsapp in your browser.
echo ===================================================
echo.
pause
