<?php
require_once "tests.php";

$errorCount = run_tests($message);

if ($errorCount != 0) {
    echo("One or more errors have been detected while checking GOCDB services - " .
        $message .
        ". See https://goc.egi.eu/portal/GOCDB_monitor/ to find out more\n");
    exit(2); // return Nagios error code for CRITICAL
}

echo("All GOCDB tests are looking good\n");

exit(0);

?>
