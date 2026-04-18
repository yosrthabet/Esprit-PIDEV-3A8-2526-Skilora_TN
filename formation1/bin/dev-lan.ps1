$ErrorActionPreference = "Stop"

$port = if ($env:APP_PORT) { $env:APP_PORT } else { "8000" }
$hostAddr = if ($env:APP_HOST) { $env:APP_HOST } else { "0.0.0.0" }

Write-Host "Starting Symfony LAN server on http://$hostAddr`:$port"
Write-Host "Accessible from mobile devices on the same WiFi."
Write-Host ""

php -S "$hostAddr`:$port" -t public
