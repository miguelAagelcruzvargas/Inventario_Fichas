@echo off
setlocal

:: Ejecutar ngrok minimizado
start /MIN "" ngrok http 8080 > nul

echo Esperando a que ngrok genere la URL pública...
timeout /t 5 > nul

:: Usar PowerShell para extraer correctamente la URL pública HTTPS
for /f "delims=" %%i in ('powershell -Command "(Invoke-RestMethod http://127.0.0.1:4040/api/tunnels).tunnels | Where-Object {$_.public_url -like 'https*'} | Select-Object -ExpandProperty public_url"') do (
    set "NGROK_URL=%%i"
)

cls
echo ================== NGROK ENLACES ==================
echo.
echo 🥛 Sistema Gestión Leche: %NGROK_URL%/gestion_leche_web/
echo 📋 Login Admin:           %NGROK_URL%/gestion_leche_web/login/login.php
echo.

:: Abrir las URLs en navegador
start "" "%NGROK_URL%/gestion_leche_web/"
start "" "%NGROK_URL%/gestion_leche_web/login/login.php"

pause
