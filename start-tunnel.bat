@echo off
cd /d "%~dp0"

set CLOUDFLARED=%USERPROFILE%\.cloudflared\cloudflared.exe
set PATH=%PATH%;%USERPROFILE%\.cloudflared

echo ============================================
echo   Cloudflare Tunnel
echo ============================================
echo.

echo [1/3] Menjalankan Laravel server...
start /B php artisan serve --host=0.0.0.0 --port=8000 > nul 2>&1
if %errorlevel% neq 0 (
    echo Gagal menjalankan Laravel server!
    pause
    exit /b 1
)
timeout /t 3 /nobreak > nul

echo [2/3] Build Vite assets...
call npm run build > nul 2>&1

echo [3/3] Menghubungkan ke Cloudflare Tunnel...
echo.
echo Akses dari luar jaringan:
%CLOUDFLARED% tunnel --url http://localhost:8000

echo.
echo Tunnel terputus. Membersihkan...
taskkill /f /im php.exe > nul 2>&1
taskkill /f /im node.exe > nul 2>&1
pause
