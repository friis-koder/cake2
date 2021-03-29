#!/bin/bash

set -e

cd "${BASH_SOURCE%/*}"

vendors/bin/php-cs-fixer -vvv fix
