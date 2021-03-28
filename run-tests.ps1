
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

    if ($args[0]) {
        "Running: {0}" -f $args[0]
        lib/Cake/Console/cake test core $args[0] --stderr --verbose --stop-on-error --stop-on-failure
    } else {
        Write-Host "Running: AllTests"
        lib/Cake/Console/cake test core AllTests --stderr --verbose --stop-on-error --stop-on-failure
    }
}

WRITE-DatabaseConfig;
INVOKE-Tests $args[0];
