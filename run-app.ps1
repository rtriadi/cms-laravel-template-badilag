param(
    [string]$BindHost = "127.0.0.1",
    [int]$Port = 8000,
    [switch]$WithVite,
    [switch]$NoBrowser
)

$ErrorActionPreference = "Stop"
Set-Location -Path $PSScriptRoot

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  SatkerCMS Badilag - Shortcut Running App (PHP 8.5)" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

# 1. Pilih PHP 8.5 / >= 8.4.1 dari Laragon
$phpCandidates = @(
    "C:\laragon\bin\php\php-8.5.12-1-Win32-vs17-x64\php.exe"
) + (Get-ChildItem -Path "C:\laragon\bin\php\php-8.*\php.exe" -ErrorAction SilentlyContinue | Sort-Object FullName -Descending | ForEach-Object { $_.FullName })

$phpExe = $phpCandidates | Where-Object {
    (Test-Path $_) -and ((& $_ -r "echo version_compare(PHP_VERSION, '8.4.1', '>=') ? 'ok' : '';" 2>$null) -eq 'ok')
} | Select-Object -First 1

if (-not $phpExe) {
    Write-Error "PHP >= 8.4.1 tidak ditemukan di C:\laragon\bin\php."
    exit 1
}

$phpDir = Split-Path -Parent $phpExe
$env:PATH = "$phpDir;$env:PATH"
$phpVersion = & $phpExe -r "echo PHP_VERSION;"
Write-Host "[INFO] Menggunakan PHP $phpVersion ($phpExe)" -ForegroundColor Green

# 2. Pastikan MySQL berjalan di 127.0.0.1:3306
$mysqlReady = $false
try {
    $tcp = New-Object System.Net.Sockets.TcpClient
    $tcp.Connect("127.0.0.1", 3306)
    $tcp.Close()
    $mysqlReady = $true
} catch {
    $mysqlReady = $false
}

if (-not $mysqlReady) {
    $mysqld = Get-ChildItem -Path "C:\laragon\bin\mysql\mysql-*\bin\mysqld.exe" -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($mysqld) {
        Write-Host "[INFO] Menyalakan layanan MySQL Laragon ($($mysqld.FullName))..." -ForegroundColor Yellow
        Start-Process -FilePath $mysqld.FullName -WindowStyle Hidden
        Start-Sleep -Seconds 3
    }
}

# 3. Persiapkan .env, storage:link, migrasi database, dan aset Vite
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    & $phpExe artisan key:generate --ansi
}

if (-not (Test-Path "public\storage")) {
    & $phpExe artisan storage:link --ansi | Out-Null
}

Write-Host "[INFO] Menjalankan migrasi database..." -ForegroundColor Cyan
& $phpExe artisan migrate --force --ansi

if (-not (Test-Path "public\build\manifest.json")) {
    Write-Host "[INFO] Membangun aset frontend Vite..." -ForegroundColor Cyan
    npm run build
}

$url = "http://${BindHost}:${Port}"
Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  Website Publik : $url" -ForegroundColor Green
Write-Host "  Panel Admin    : $url/panel-admin" -ForegroundColor Green
Write-Host "  Tekan Ctrl+C untuk menghentikan server." -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""

if (-not $NoBrowser) {
    Start-Process $url
}

if ($WithVite) {
    npx concurrently -c "#056e4f,#d97706" "php artisan serve --host=$BindHost --port=$Port" "npm run dev" --names=server,vite
} else {
    & $phpExe artisan serve --host=$BindHost --port=$Port
}

