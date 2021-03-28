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

ensure_test_db

export DB='sqlite'

lib/Cake/Console/cake test core AllTests --stderr --verbose
