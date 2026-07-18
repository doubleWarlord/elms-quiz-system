# ELMS Quiz System - Docker one-shot setup script (Windows PowerShell)

$ErrorActionPreference = 'Stop'

function Assert-LastExit {
    param([string]$Step)
    if ($LASTEXITCODE -ne 0) {
        throw "Step failed: $Step (exit code $LASTEXITCODE)"
    }
}

function Set-EnvValue {
    param(
        [string]$Content,
        [string]$Key,
        [string]$Value
    )

    $pattern = "(?m)^$Key=.*$"
    if ($Content -match $pattern) {
        return [regex]::Replace($Content, $pattern, "$Key=$Value")
    }

    return "$Content`r`n$Key=$Value"
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ELMS Quiz System - Docker Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

Write-Host "[1/6] Checking Docker..." -ForegroundColor Yellow
docker ps | Out-Null
Assert-LastExit "docker ps"
docker compose version | Out-Null
Assert-LastExit "docker compose version"
Write-Host "OK: Docker and Docker Compose are available." -ForegroundColor Green

Write-Host "[2/6] Preparing .env..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
}

$envContent = Get-Content ".env" -Raw
$envContent = Set-EnvValue -Content $envContent -Key "APP_URL" -Value "http://localhost:8000"
$envContent = Set-EnvValue -Content $envContent -Key "DB_CONNECTION" -Value "mysql"
$envContent = Set-EnvValue -Content $envContent -Key "DB_HOST" -Value "db"
$envContent = Set-EnvValue -Content $envContent -Key "DB_PORT" -Value "3306"
$envContent = Set-EnvValue -Content $envContent -Key "DB_DATABASE" -Value "elms_quiz"
$envContent = Set-EnvValue -Content $envContent -Key "DB_USERNAME" -Value "elms_user"
$envContent = Set-EnvValue -Content $envContent -Key "DB_PASSWORD" -Value "elms_password"
$envContent = Set-EnvValue -Content $envContent -Key "VITE_API_URL" -Value "http://localhost:8000/api"
Set-Content ".env" $envContent
Write-Host "OK: .env configured for Docker." -ForegroundColor Green

Write-Host "[3/6] Starting containers..." -ForegroundColor Yellow
docker compose up -d --build
Assert-LastExit "docker compose up -d --build"
Write-Host "OK: Containers started." -ForegroundColor Green

Write-Host "[4/6] Installing dependencies in containers..." -ForegroundColor Yellow
docker compose exec -T app composer install --no-interaction
Assert-LastExit "docker compose exec app composer install"
docker compose exec -T node npm install
Assert-LastExit "docker compose exec node npm install"
Write-Host "OK: Dependencies installed." -ForegroundColor Green

Write-Host "[5/6] Initializing Laravel..." -ForegroundColor Yellow
docker compose exec -T app php artisan key:generate --force
Assert-LastExit "docker compose exec app php artisan key:generate"
try {
    docker compose exec -T app php artisan migrate --seed --force
    Assert-LastExit "docker compose exec app php artisan migrate --seed --force"
}
catch {
    Write-Host "WARN: Migration failed on first try. Retrying once..." -ForegroundColor Yellow
    docker compose exec -T app php artisan migrate --seed --force
    Assert-LastExit "docker compose exec app php artisan migrate --seed --force (retry)"
}
Write-Host "OK: Laravel initialized." -ForegroundColor Green

Write-Host "[6/6] Final status..." -ForegroundColor Yellow
docker compose ps
Assert-LastExit "docker compose ps"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "SETUP COMPLETE" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "Frontend login: http://localhost:5173/auth/login" -ForegroundColor Cyan
Write-Host "API base:       http://localhost:8000/api" -ForegroundColor Cyan
Write-Host ""
Write-Host "Useful commands:" -ForegroundColor Yellow
Write-Host "  docker compose logs -f app" -ForegroundColor Gray
Write-Host "  docker compose logs -f node" -ForegroundColor Gray
Write-Host "  docker compose logs -f db" -ForegroundColor Gray
Write-Host "  docker compose down -v" -ForegroundColor Gray
