@echo off
setlocal EnableDelayedExpansion
title SatkerCMS Badilag - App Runner
cd /d "%~dp0"

echo ============================================================
echo   SatkerCMS Badilag - Shortcut Running App (PHP 8.5)
echo ============================================================

:: 1. Pastikan PHP 8.5 Laragon berada di urutan pertama PATH
set "PHP85_DIR=C:\laragon\bin\php\php-8.5.12-1-Win32-vs17-x64"
if exist "%PHP85_DIR%\php.exe" (
    set "PATH=%PHP85_DIR%;%PATH%"
) else (
    for /d %%D in ("C:\laragon\bin\php\php-8.5*" "C:\laragon\bin\php\php-8.4*") do (
        if exist "%%~fD\php.exe" (
            set "PHP85_DIR=%%~fD"
            set "PATH=%%~fD;%PATH%"
            goto :php_found
        )
    )
)
:php_found

for /f "tokens=*" %%V in ('php -r "echo PHP_VERSION;" 2^>nul') do set "ACTIVE_PHP=%%V"
echo [INFO] Menggunakan PHP %ACTIVE_PHP% (%PHP85_DIR%\php.exe)

:: 2. Pastikan layanan MySQL Laragon aktif di port 3306
powershell -NoProfile -Command "$tcp = New-Object System.Net.Sockets.TcpClient; try { $tcp.Connect('127.0.0.1', 3306); $tcp.Close(); exit 0 } catch { exit 1 }"
if errorlevel 1 (
    echo [INFO] MySQL belum berjalan di port 3306. Mencoba menyalakan MySQL Laragon...
    for /d %%M in ("C:\laragon\bin\mysql\mysql-*") do (
        if exist "%%~fM\bin\mysqld.exe" (
            start "" /B "%%~fM\bin\mysqld.exe" --datadir="C:\laragon\data\mysql-8" >nul 2>&1
            timeout /t 3 /nobreak >nul
            goto :mysql_started
        )
    )
)
:mysql_started

:: 3. Pastikan file .env, APP_KEY, storage symlink, migrasi database, dan aset Vite siap
if not exist ".env" (
    echo [INFO] Menyalin .env dari .env.example...
    copy ".env.example" ".env" >nul
    php artisan key:generate --ansi
)

if not exist "public\storage" (
    echo [INFO] Membuat symlink storage publik...
    php artisan storage:link --ansi >nul 2>&1
)

echo [INFO] Menjalankan migrasi database terbaru...
php artisan migrate --force --ansi

if not exist "public\build\manifest.json" (
    echo [INFO] Membangun aset frontend Vite...
    call npm run build
)

set "HOST=127.0.0.1"
set "PORT=8000"
if not "%~1"=="" set "PORT=%~1"

echo.
echo ============================================================
echo   Server berjalan di: http://%HOST%:%PORT%
echo   Panel Admin       : http://%HOST%:%PORT%/panel-admin
echo   Tekan Ctrl+C untuk menghentikan server.
echo ============================================================
echo.

start "" "http://%HOST%:%PORT%"
php artisan serve --host=%HOST% --port=%PORT%

