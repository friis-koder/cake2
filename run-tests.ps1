
function WRITE-DatabaseConfig() {
    Write-Host '------------------------------------------------------------------------------'
    Write-Host 'Checking Config'

    if (Test-Path -path './app/Config/database.php') {
        Write-Host '  ./app/Config/database.php is present.' -ForegroundColor Green
    } else {
        Write-Host '  ./app/Config/database.php not present. Copying app/Config/database.php.test' -ForegroundColor Yellow
        Copy-Item "./app/Config/database.php.test" -Destination "./app/Config/database.php"
    }

    Write-Host '------------------------------------------------------------------------------'
}

function INVOKE-Tests() {
    $env:DB='sqlite'
    lib/Cake/Console/cake test core AllTests --stderr --verbose
}

WRITE-DatabaseConfig;
INVOKE-Tests;
