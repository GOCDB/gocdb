<?php
require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';

// Initialise the configuration service.
// If non-default location for xml configuration file is needed
// use Factory::ConfigService->setLocalInfoFileLocation(...)
\Factory::getConfigService()->setLocalInfoOverride($_SERVER['SERVER_NAME']);

$test_statuses =  array(
        "GOCDB5 DB connection" 						=> "unknown",
        "GOCDBPI_v5 availability" 					=> "unknown",
        "GOCDB5 central portal availability" 		=> "unknown"
);

$test_desc =  array(
        "GOCDB5 DB connection" 						=> "Connect to GOCDB5 (RAL/master instance) from this machine using EntityManager->getConnection()->connect()",
        "GOCDBPI_v5 availability" 					=> "Retrieve https://goc.egi.eu/gocdbpi/?method=get_site_list&sitename=RAL-LCG2 using PHP CURL",
        "GOCDB5 central portal availability" 		=> "N/A",
);

$test_doc =  array(
        "GOCDB5 DB connection" 						=> "<a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT Documents/Servers and Services/GOCDB/Cookbook and recipes/database_is_down.txt' target='_blank'>documentation/recipe</a>",
        "GOCDBPI_v5 availability" 					=> "<a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT Documents/Servers and Services/GOCDB/Cookbook and recipes/failover_cookbook.txt' target='_blank'>documentation/recipe</a>",
        "GOCDB5 central portal availability" 		=> "<a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT Documents/Servers and Services/GOCDB/Cookbook and recipes/failover_cookbook.txt' target='_blank'>documentation/recipe</a>"
);


$test_messages =  array(
        "GOCDB5 DB connection" 						=> "no information",
        "GOCDBPI_v5 availability" 					=> "no information",
        "GOCDB5 central portal availability" 		=> "no information"
);

$disp = array(
        "unknown"   => "<td align='center' bgcolor='#A0A0A0'><font size='1'><i>unknown</i></font></td>",
        "warn"      => "<td align='center' bgcolor='#FFAA00'><font size='1'>WARNING</font></td>",
        "error"     => "<td align='center' bgcolor='#F00000'><font size='1'>ERROR</font></td>",
        "ok"        => "<td align='center' bgcolor='#00D000'><font size='1'>OK</font></td>",
);

// Test the connection to the database using Doctrine
function test_db_connection(){
    try {
        $entityManager = Factory::getNewEntityManager();
        $entityManager->getConnection()->connect();
        $retval["status"] = "ok";
        $retval["message"] = "everything is well";
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $retval["status"] = "error";
        $retval["message"] = "$message";
    }

    return $retval;
}

function test_url($url) {
    try{
        $res = get_https2($url);
        $retval["status"] = "ok";
        $retval["message"] = "everything is well";
    } catch (Exception $Exception){
        $message = $Exception->getMessage();
        $retval["status"] = "error";
        $retval["message"] = "$message";
    }
    return $retval;
}

function get_https2($url){

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
        CURLOPT_SSL_VERIFYHOST => true,
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
    if( defined('SERVER_SSLCERT') && defined('SERVER_SSLKEY') ){
      $curloptions[CURLOPT_SSLCERT] = SERVER_SSLCERT;
      $curloptions[CURLOPT_SSLKEY] = SERVER_SSLKEY;
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
        throw new Exception("curl error:".curl_error($handle));
    }
    curl_close($handle);

    if ($return == false) {
        throw new Exception("no result returned. curl says: ".curl_getinfo($handle));
    }

    return $return;
}

function get_testPiMethod () {
    return  "/public/?method=get_site_list";
}

?>
