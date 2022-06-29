#!/bin/bash

if [[ $GITHUB_ACTIONS ]]; then
    # Check modified PHP files with PHP's internal syntax checker
    git diff --name-only --diff-filter=ACMRTUXB HEAD^ | grep '\.php$' | xargs -r -n 1 php -l || exit 1

    # Run test suite
    vendor/bin/phpunit -c tests/phpunit.xml --coverage-clover=coverage.xml tests/DoctrineTestSuite1.php
else
    echo 'ABORTED: NOT RUNNING ON GITHUB ACTIONS'
    exit 2
fi
