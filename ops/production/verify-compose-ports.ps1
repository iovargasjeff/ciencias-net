param(
    [string] $ComposeFile = "docker-compose.production.yml",
    [string] $EnvFile = ".env.production.example"
)

$env:CIENCIASNET_ENV_FILE = $EnvFile
$json = docker compose --env-file $EnvFile -f $ComposeFile config --format json | ConvertFrom-Json
if ($LASTEXITCODE -ne 0) {
    throw "docker compose config failed"
}

$forbidden = @("db", "facial-api", "backend", "api", "queue", "scheduler")
$violations = @()

foreach ($serviceName in $forbidden) {
    $service = $json.services.$serviceName
    if ($null -ne $service -and $null -ne $service.ports) {
        $violations += "$serviceName exposes host ports"
    }
}

$frontend = $json.services.frontend
if ($null -eq $frontend.ports) {
    $violations += "frontend must expose only the local reverse-proxy port"
}

if ($violations.Count -gt 0) {
    $violations | ForEach-Object { Write-Error $_ }
    exit 1
}

Write-Output "Production compose ports are private except frontend reverse-proxy binding."
