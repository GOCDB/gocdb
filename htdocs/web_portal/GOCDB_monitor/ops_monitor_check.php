<?php

/**
 * Run GOCDB status checks reporting any failure with HTTP 500 return.
 * The URL parameter 'fake_failure' can be used to force failure for testing -
 * https://hostname.com/portal/GOCDB_monitor/ops_monitor_check.php?fake_failure
 */

require_once "tests.php";

/* If someone wants to test the failure, they can fake one using
 * the fake_failure parameter */
if (isset($_REQUEST['fake_failure'])) {
    $message = 'Fake failure';
    $errorCount = 1;
} else {
    $errorCount = run_tests($message);
}

if ($errorCount != 0) {
    header("HTTP/1.0 500");
    echo($message . "\n");
    die();
}

echo("All GOCDB tests are looking good\n");
die();
