param(
    [string]$MysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe",
    [string]$DbName = "maatlaswerk_local",
    [string]$DbUser = "root",
    [string]$DbPassword = "",
    [string]$SqlFile = (Join-Path $PSScriptRoot "..\matlas01_bcig1.sql")
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $MysqlExe)) {
    throw "MySQL executable not found: $MysqlExe"
}

if (-not (Test-Path $SqlFile)) {
    throw "SQL file not found: $SqlFile"
}

$mysqlArgs = @("-u", $DbUser, "--default-character-set=utf8mb4")
if ($DbPassword) {
    $mysqlArgs += "-p$DbPassword"
}

Write-Host "Creating database '$DbName' (if missing)..."
& $MysqlExe @mysqlArgs -e "CREATE DATABASE IF NOT EXISTS \`$DbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Write-Host "Importing SQL from '$SqlFile'..."
Get-Content -Path $SqlFile -Raw | & $MysqlExe @mysqlArgs $DbName

Write-Host "Database import finished."
Write-Host "Now open your local domain and run Settings > Permalinks > Save."
