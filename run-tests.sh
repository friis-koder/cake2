#!/bin/bash

set -e

function ensure_test_db() {
    echo '---------------------------------------------------------------'
    echo -n 'Check config: '
    if [ ! -f app/Config/database.php ]; then
        echo 'app/Config/database.php not present. Copying app/Config/database.php.test'
        cp app/Config/database.php.test app/Config/database.php
    else
        echo 'app/Config/database.php is present.'
    fi
    echo '---------------------------------------------------------------'
}

cd "${BASH_SOURCE%/*}"

export DB='sqlite'

ensure_test_db

if [ $# -eq 0 ]; then
    echo "Running: AllTests"
    lib/Cake/Console/cake test core AllTests --stderr --verbose --stop-on-error --stop-on-failure
	else
		echo "Running: $1"
    lib/Cake/Console/cake test core $1 --stderr --verbose --stop-on-error --stop-on-failure
fi

