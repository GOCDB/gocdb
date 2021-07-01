#!/bin/bash

doctrine="$PWD/vendor/bin/doctrine"

if [[ -n "$DB" ]]; then
    echo "Configuring unit tests for a $DB database"
    cp .github/actions/${DB}_bootstrap_doctrine.php tests/doctrine/bootstrap_doctrine.php
    cp .github/actions/${DB}_bootstrap_pdo.php tests/doctrine/bootstrap_pdo.php
    cd tests/doctrine
    $doctrine orm:schema-tool:create
    if [[ "$DB" = "mysql" ]]; then
        mysql --host '172.18.0.1' -u root -e 'set global max_connections = 200;'
    fi
else
    echo 'Cannot setup unit tests, $DB is not defined.'
    exit 1
fi
