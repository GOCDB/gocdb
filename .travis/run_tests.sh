#!/bin/bash

if [[ $TRAVIS ]]; then
    cd "$TRAVIS_BUILD_DIR" || exit 2

    # Check modified PHP files with PHP's internal syntax checker
    git diff --name-only --diff-filter=ACMRTUXB HEAD^ | grep '\.php$' | xargs -r -n 1 php -l || exit 1

    # Run test suite
    vendor/bin/phpunit tests/DoctrineTestSuite1.php
else
    echo 'ABORTED: NOT RUNNING ON TRAVIS'
    exit 2
fi
