param(
    [string]$ProjectName = 'salo-reservations',
    [int]$SitePort = 18087,
    [int]$MailpitPort = 18088,
    [string]$SiteUrl = '',
    [string]$AdminUser = 'site_admin',
    [string]$AdminEmail = 'admin@example.test'
)

$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

if (-not (Test-Path -LiteralPath '.env')) {
    $password = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24)).ToLowerInvariant()
    $rootPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24)).ToLowerInvariant()
    "DB_PASSWORD=$password`nDB_ROOT_PASSWORD=$rootPassword`nWP_PORT=$SitePort`nMAILPIT_PORT=$MailpitPort`n" | Set-Content -LiteralPath '.env' -NoNewline
    Write-Output 'Created .env with random database passwords.'
}

if (-not $SiteUrl) { $SiteUrl = "http://127.0.0.1:$SitePort" }
$env:WP_PORT = [string]$SitePort
$env:MAILPIT_PORT = [string]$MailpitPort
$compose = @('compose', '-p', $ProjectName)

docker @compose up -d db wordpress mailpit
if ($LASTEXITCODE -ne 0) { throw 'Docker services did not start.' }

$ready = $false
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    docker @compose run --rm -T wpcli wp db check --path=/var/www/html *> $null
    if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    Start-Sleep -Seconds 2
}
if (-not $ready) { throw 'Database did not become ready.' }

docker @compose run --rm -T wpcli wp core is-installed --path=/var/www/html *> $null
if ($LASTEXITCODE -ne 0) {
    if (-not $env:WP_ADMIN_PASSWORD) { throw 'Set WP_ADMIN_PASSWORD in your shell before the first setup run.' }
    $env:DEMO_SITE_URL = $SiteUrl
    $env:DEMO_ADMIN_USER = $AdminUser
    $env:DEMO_ADMIN_EMAIL = $AdminEmail
    docker @compose run --rm -T -e WP_ADMIN_PASSWORD -e DEMO_SITE_URL -e DEMO_ADMIN_USER -e DEMO_ADMIN_EMAIL wpcli sh -c 'exec wp core install --url="$DEMO_SITE_URL" --title="restaurant." --admin_user="$DEMO_ADMIN_USER" --admin_email="$DEMO_ADMIN_EMAIL" --admin_password="$WP_ADMIN_PASSWORD" --skip-email --path=/var/www/html'
    if ($LASTEXITCODE -ne 0) { throw 'WordPress installation failed.' }
}

foreach ($command in @(
    @('theme', 'activate', 'salo'),
    @('rewrite', 'structure', '/%postname%/'),
    @('option', 'update', 'timezone_string', 'Asia/Manila')
)) {
    docker @compose run --rm -T wpcli wp @command '--path=/var/www/html'
    if ($LASTEXITCODE -ne 0) { throw "WordPress setup failed: $($command -join ' ')" }
}

Write-Output "Site: $SiteUrl"
Write-Output "Admin: $SiteUrl/wp-admin/"
Write-Output "Local email inbox: http://127.0.0.1:$MailpitPort/"
