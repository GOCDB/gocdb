<?php
set_time_limit(0);
/**
 * Calls the gocdb PI methods in the 'methods' array foreach of the
 * given baseurls. The XML PI output is saved to a sub dir named after the
 * key in the baseurls associatative array (sub-dir created in same dir as script).
 * The script can be used to compare the output from multiple instances of gocdb,
 * e.g. when testing the output between versions.
 *
 * You will need to modify the CURL connection settings as required, e.g.
 * set the proxy, usercert/key, private key password and so on.
 *
 * @author David Meredith
 */
$methods = array(   /*
                    'get_site_count_per_country',
                    'get_site_count_per_country&certification_status=Certified',

                    'get_site',
                    'get_site&roc=NGI_DE',
                    'get_site&certification_status=Certified',
                    'get_site&extensions=(P4U_Pilot_VAT=20)(P4U_Pilot_Grid_CPU=)',
                    'get_site&scope=Local',
                    'get_site&scope=Local,EGI&scope_match=all',

                    'get_site_list',
                    'get_site_list&country=UK',
                    'get_site_list&scope=EGI',

                    'get_site_contacts',
                    'get_site_contacts&roletype=Site Security Officer',
                    'get_site_contacts&sitename=RAL-LCG2',

                    'get_site_security_info',
                    'get_site_security_info&production_status=PPS',
                    'get_site_security_info&roc=NGI_DE',

                    'get_roc_list',
                    'get_roc_list&roc=NGI_UK',

                    'get_roc_contacts',

                    'get_subgrid_list',
                    'get_service_types',

                    'get_user',
                    'get_user&roletype=Chief Operations Officer',

                    'get_cert_status_changes',
                    'get_cert_status_changes&site=RAL-LCG2',

                    'get_cert_status_date',

                    'get_ngi',
                    'get_ngi&roc=NGI_UK',
                    'get_ngi&scope=Local,EGI&scope_match=any',
                    'get_ngi&scope=Local,EGI&scope_match=all',

                    'get_project_contacts',

                    // expected diff <ENDPOINTS/>
                    'get_service_endpoint',
                    'get_service_endpoint&sitename=100IT',
                    'get_service_endpoint&scope=',
                    'get_service_endpoint&extensions=(P4U_Pilot_VAT=)',

                    // expected diff: <AFFECTED_ENDPOINTS/>
                    'get_service_group',
                    'get_service_group&service_group_name=OPSTOOLS',

                    'get_service_group_role',
                    'get_service_group_role&scope=Local',
                    'get_service_group_role&service_group_name=OPSTOOLS'

                    'get_site_security_info&certification_status=Certified',

                    // expected diffs: <AFFECTED_ENDPOINTS/> and ordering of services affected by downtime
                    'get_downtime_to_broadcast',
                    'get_downtime_to_broadcast&interval=5',
                    'get_downtime_to_broadcast&interval=1',

                    // expected diff: <AFFECTED_ENDPOINTS/>
                    'get_downtime&topentity=RAL-LCG2',
                    'get_downtime&scope=Local',
                    'get_downtime&page=1',
                    'get_downtime&all_lastmonth',
                    'get_downtime&startdate=2013-01-01&enddate=2013-02-01',
                    'get_downtime&ongoing_only=yes',
                    'get_downtime&topentity=GRIDOPS_GOCDB&startdate=2014-11-04&enddate=2014-11-05',
                    'get_downtime&site_extensions=(P4U_Pilot_VAT=20)&scope=EGI',
                    'get_downtime&windowstart=2014-10-01&windowend=2014-11-01',
                    'get_downtime&startdate=2014-10-01&enddate=2014-11-01',

                    // expected diff: <AFFECTED_ENDPOINTS/>
                    'get_downtime_nested_services&page=1',
                    'get_downtime_nested_services&topentity=RAL-LCG2',
                    'get_downtime_nested_services&all_lastmonth',
                    'get_downtime_nested_services&startdate=2013-01-01&enddate=2013-02-01',
                    'get_downtime_nested_services&windowstart=2014-10-01&windowend=2014-11-01',
                    'get_downtime_nested_services&site_extensions=(P4U_Pilot_VAT=20)&scope=EGI',
                    'get_downtime_nested_services&ongoing_only=yes',
                    'get_downtime_nested_services&site_extensions=(P4U_Pilot_VAT=20)&scope=EGI',*/

                    );

if(!isset($argv[1])){
    die("Error missing arg. Usage:  php ".basename(__FILE__)." query|diff \n");
}
$v = $argv[1];
if(strcmp($v,  'query') && strcmp($v,  'diff')){
    die("Error invalid arg. Usage:  php ".basename(__FILE__)." query|diff \n");
}

// associative array, key is used as result directory, value is used as baseurl
$baseurls = array(
    '5_3' => 'https://localhost/gocdbpi5_3',
    '5_2' => 'https://localhost/gocdbpi5_2'
    );

if("query" == $v){
    echo "Querying for output files\n";
    // foreach method call using the different baseurl
    foreach($methods as $method){
          foreach($baseurls as $key => $baseurl){
                $resultDir = $key;
                if(!file_exists($resultDir)){
                    mkdir($resultDir);
                }
                $fp = fopen($resultDir."/".$method.".xml", 'w');
                $piquery = $baseurl."/public/?method=".$method;
                echo "Call: ".$piquery."\n";
                //open connection
                $ch = curl_init($piquery);

                curl_setopt_array($ch, array(
                    CURLOPT_SSL_VERIFYPEER => false,  //only false during testing on local machine
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_SSLCERT => 'usercert.pem', // client certificate, in same dir as script
                    CURLOPT_SSLKEY => 'userkey.pem',   // client private key, in same dir as script
                    CURLOPT_SSLCERTPASSWD => 'somepassword',
                    CURLOPT_SSLCERTTYPE => 'PEM',
                    CURLOPT_FILE => $fp,
                    //CURLOPT_SSLVERSION => 3,
                    CURLOPT_PROXY => null  // set to null if querying PI on localhost
                ));
                //curl_setopt($ch, CURLOPT_SSLVERSION,3);

                $data = curl_exec($ch);
                //close connection
                curl_close($ch);
                fclose($fp);
        }
    }
}

else if ("diff" == $v) {
    echo "Diffing output files\n";
    foreach ($methods as $method) {
        $diff_files = array();
        foreach ($baseurls as $key => $baseurl) {
            $resultDir = $key;
            $diff_files[] = $resultDir."/".$method.".xml";
        }
        $diffstr = "";
        foreach($diff_files as $diff_file){
           $diffstr .= escapeshellarg($diff_file). " ";
        }
        $diffstr = trim($diffstr);
        $cmd = "diff ". $diffstr;
        echo $cmd;
        echo "\n";
        system($cmd);
    }
}

else {
    echo "Error no valid arg given\n";
}
