<?php

/**
 * Common definition of constants and functions for various tests.
 */

require_once __DIR__ . "/validate_local_info_xml.php";
require_once __DIR__ . "/../../../lib/Gocdb_Services/Factory.php";
require_once __DIR__ . "/../../../lib/Gocdb_Services/Config.php";

define("TEST_1", "GOCDB5 DB connection");
define("TEST_2", "GOCDBPI_v5 availability");
define("TEST_3", "GOCDB5 central portal availability");
define("TEST_4", "GOCDB5 server configuration validity");

define("OK", "ok");
define("NOK", "error");
define("UKN", "unknown");
define("OKMSG", "everything is well");
define("UKNMSG", "no information");

/**
 * Run GOCDB status checks reporting as an HTML page with formatted per-test status table
 */

require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';

// Initialise the configuration service.
// If non-default location for xml configuration file is needed
// use Factory::ConfigService->setLocalInfoFileLocation(...)

\Factory::getConfigService()->setLocalInfoOverride($_SERVER['SERVER_NAME']);

$test_statuses =  array(
    TEST_1  => UKN,
    TEST_2  => UKN,
    TEST_3  => UKN,
    TEST_4  => UKN
);

$test_desc =  array(
    TEST_1 =>
        "Connect to GOCDB5 (RAL/master instance) from this " .
        "machine using EntityManager->getConnection()->connect()",
    TEST_2 =>
        "Retrieve https://goc.egi.eu/gocdbpi/?" .
        "method=get_site_list&sitename=RAL-LCG2 using PHP CURL",
    TEST_3 =>
        "N/A",
    TEST_4 =>
        "Server XML configuration validation."
);

$test_doc =  array(
    TEST_1 =>
        "<a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT " .
        "Documents/Servers and Services/GOCDB/Cookbook and " .
        "recipes/database_is_down.txt' target='_blank'>" .
        "documentation/recipe</a>",
    TEST_2 =>
        "<a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT " .
        "Documents/Servers and Services/GOCDB/Cookbook and " .
        "recipes/failover_cookbook.txt' target='_blank'>" .
        "documentation/recipe</a>",
    TEST_3 =>
        "<a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT " .
        "Documents/Servers and Services/GOCDB/Cookbook and " .
        "recipes/failover_cookbook.txt' target='_blank'>" .
        "documentation/recipe</a>",
    TEST_4 =>
        "<p>Contact GOCDB service managers." .
        "<br>Other tests have dependencies on the server configuration " .
        "<br>so may show errors if the configuration is invalid.</p>"
);

$test_messages =  array(
    TEST_1 => UKNMSG,
    TEST_2 => UKNMSG,
    TEST_3 => UKNMSG,
    TEST_4 => UKNMSG
);

$disp = array(
        "unknown"   => "<td align='center' bgcolor='#A0A0A0'><font size='1'><i>unknown</i></font></td>",
        "warn"      => "<td align='center' bgcolor='#FFAA00'><font size='1'>WARNING</font></td>",
        "error"     => "<td align='center' bgcolor='#F00000'><font size='1'>ERROR</font></td>",
        "ok"        => "<td align='center' bgcolor='#00D000'><font size='1'>OK</font></td>",
);

// Run the tests but return nothing but a count of passes and failures
function get_test_counts($config)
{
    $res[1] = test_db_connection();
    $res[4] = test_config($config);

    if ($res[4]["status"] != "error") {
        // Only define test URLs if the config is valid
        define_test_urls($config);

        $res[2] = test_url(PI_URL);
        $res[3] = test_url(SERVER_BASE_URL);
    }

    $counts = array("ok" => 0,
                    "warn" => 0,
                    "error" => 0
            );

    foreach ($res as $r) {
        $counts[$r["status"]]++;
    }

    return $counts;
}

// Define url constants for testing.
// Note: Should only be called if test_config is successful
function define_test_urls(\org\gocdb\services\config $config)
{

    list($serverBaseURL, $webPortalURL, $piURL) = $config->getURLs();

    define("PI_URL", $piURL . get_testPiMethod());
    define("PORTAL_URL", $webPortalURL);
    define("SERVER_BASE_URL", $serverBaseURL);

    //define("SERVER_SSLCERT", "/etc/grid-security/hostcert.pem");
    //define("SERVER_SSLKEY", "/etc/pki/tls/private/hostkey.pem");
}

// Test the connection to the database using Doctrine
function test_db_connection()
{
    $retval = [];
    try {
        $entityManager = Factory::getNewEntityManager();
        $entityManager->getConnection()->connect();
        $retval["status"] = OK;
        $retval["message"] = OKMSG;
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $retval["status"] = NOK;
        $retval["message"] = "$message";
    }

    return $retval;
}

function test_url($url)
{
    $retval = [];
    try {
        get_https2($url);
        $retval["status"] = OK;
        $retval["message"] = OKMSG;
    } catch (Exception $exception) {
        $message = $exception->getMessage();
        $retval["status"] = NOK;
        $retval["message"] = "$message";
    }
    return $retval;
}

function get_https2($url)
{
    $curloptions = array (
        // In addition to transfer failures, check inside the HTTP response for an error
        // response code (HTTP > 400)
        CURLOPT_FAILONERROR    => true,
        CURLOPT_HEADER         => false,
        // No client authentication is being attempted. Any request to access a 'protected'
        // resource is currently redirected (HTTP 301/2) to the Shibboleth authentication
        // service which, if followed, results in a 'server error' (HTTP 500) response .
        // With this option set to false, a 'successful' request is actually just the
        // receipt of the 'redirect' HTTP 301/2.
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS      => 0,
        // VERIFYHOST checks that the CN in the certificate matches what we asked for
        CURLOPT_SSL_VERIFYHOST => 2,
        // VERIFYPEER checks that we trust the certificate's issuer (CA)
        // If you are testing with a self-signed host certificate, you will have to
        // copy the host's .pem certificate into CURLOPT_CAPATH or
        // set SSL_VERIFYPEER to false
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'GOCDB monitor',
        CURLOPT_VERBOSE        => false,
        CURLOPT_URL            => $url,
        // Return either the string response or a bool false for failure.
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CAPATH => '/etc/grid-security/certificates/'
    );
    if (defined('SERVER_SSLCERT') && defined('SERVER_SSLKEY')) {
        $curloptions[CURLOPT_SSLCERT] = constant("SERVER_SSLCERT");
        $curloptions[CURLOPT_SSLKEY] = constant("SERVER_SSLKEY");
    }

    $handle = curl_init();
    curl_setopt_array($handle, $curloptions);

    $return = curl_exec($handle);
    $httpResponse = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    if (!is_string($return)) {
        if (curl_errno($handle) == 22) {
            // CURLOPT_FAILONERROR detected HTTP response error
            // See man page for curl --fail option
            throw new Exception("http response code: $httpResponse");
        }
        throw new Exception("curl error:" . curl_error($handle));
    }
    curl_close($handle);

    if ($return == false) {
        throw new Exception("no result returned. curl says: " . curl_getinfo($handle));
    }

    return $return;
}

function get_testPiMethod()
{
    return  "/public/?method=get_site_list";
}
/**
 * Run the standard 3 GOCDB monitoring tests
 *
 * @param   string    &$message     Returned error messages or ''
 * @return  int                     Count of failed tests
 */
function run_tests(&$message)
{
    $errorCount = 0;
    $messages = [];

    $res = test_db_connection();

    if ($res["status"] != "ok") {
        $errorCount++;
        $messages[] = "Database connection test failed: " . $res["message"];
    }

    $res = test_url(Factory::getConfigService()->GetPiUrl() .
                    get_testPiMethod());

    if ($res["status"] != "ok") {
        $errorCount++;
        $messages[] = "PI interface test failed: " . $res["message"];
    }

    $res = test_url(Factory::getConfigService()->GetPortalURL());

    if ($res["status"] != "ok") {
        $errorCount++;
        $messages[] = "Server base URL test failed: " . $res["message"];
    }

    $message = join(" | ", $messages);

    return $errorCount;
}
function test_config($config)
{
    $retval = [];
    try {
        validate_local_info_xml($config->getLocalInfoFileLocation());
        $retval["status"] = OK;
        $retval["message"] = OKMSG;
    } catch (Exception $exception) {
        $retval["status"] = NOK;
        $retval["message"] = $exception->getMessage();
    }
    return $retval;
}
