<?php
require_once "tests.php";

// Initialise local_info.xml configuration overrides
\Factory::getConfigService()->setLocalInfoOverride($_SERVER['SERVER_NAME']);

$res[1] = test_db_connection();
$res[2] = test_url(
            \Factory::getConfigService()->GetPiUrl().
            get_testPiMethod()
            );
//$res[3] = test_url(PORTAL_URL);
$res[3] = test_url(
            \Factory::getConfigService()->getServerBaseUrl()
            );


$counts=array(	"ok" => 0,
                "warn" => 0,
                "error" => 0
            );

foreach ($res as $r){
    $counts[$r["status"]]++;
}

if ($counts["error"] != 0) {
    $url = \Factory::getConfigService()->GetPortalURL() . "/GOCDB_monitor/";

    echo("An error has been detected while checking GOCDB services. ".
        "Please check $url to find out more\n");
    exit(2); // return Nagios error code for CRITICAL
}
else if ($counts["warn"] != 0) {
    echo("At least one of GOCDB tests shows a warning. It is safe to ignore it anyway\n");
    exit(0); // we don't want notifications if there is just a warning
}
else {
    echo("All GOCDB tests are looking good\n");
    exit(0);
}

?>
