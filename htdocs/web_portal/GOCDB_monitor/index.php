<?php

echo("<br/><H1 align='center'>GOCDB MONITOR</H1><hr/>");

require_once __DIR__ . '/tests.php';
echo "URL's as defined by local_info.xml<br>";
echo "PI URL is: ".PI_URL ."<br>";
echo "Portal URl is: ".PORTAL_URL."<br>";
echo "Server Base URL is: ".SERVER_BASE_URL."<br>";

// GOCDB5 DB connection
$res = test_db_connection();
$test_statuses["GOCDB5 DB connection"] = $res["status"];
$test_messages["GOCDB5 DB connection"] = $res["message"];

// GOCDBPI v5
$res = test_url(PI_URL);
$test_statuses["GOCDBPI_v5 availability"] = $res["status"];
$test_messages["GOCDBPI_v5 availability"] = $res["message"];

// GOCDB5 web portal
$res = test_url(SERVER_BASE_URL);
$test_statuses["GOCDB5 central portal availability"] = $res["status"];
$test_messages["GOCDB5 central portal availability"] = $res["message"];

            
// DISPLAY RESULTS			
echo("<H2>Service status overview</H2>");
echo("<table border=1 cellspacing=0 cellpadding=5>");
echo("<tr><td><b>Test</b></td><td><b>Status</b></td><td><b>Details</b></td><td><b>Doc/help</b></td></tr>");

foreach ($test_statuses as $test => $status){
    echo("<tr><td><font size='1'><span title=\"".$test_desc[$test]."\">
            $test</span></font></td>"
            .$disp[$status]."<td><font size='1'>".
            $test_messages[$test]."</font></td><td><font size='1'>".
            $test_doc[$test]."</font></td></tr>");
}			
echo("</table>");

echo("<hr/><br/><br/>");

echo("<H2>Other tests and check pages</H2>");
echo("<ul>");
echo("<li><a href='http://sumatran.esc.rl.ac.uk/ganglia/?r=day&c=Grid+services&h=gocdb-base.esc.rl.ac.uk'>GOCDB server ganglia page</a> - Useful to see if there are memory or CPU problems</li>");
echo("<li><a href='check.php'>Status check</a> - non vebose check of GOCDB service status. Just returns 'OK' if all the tests in \"service status overview\" are fine, 'WARNING' or 'ERROR' otherwise. Used for automatic tests</li>");
echo("</ul>");

echo("<H2>Further documentation</H2>");
echo("<ul>");
echo("<li><a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT Documents/Servers and Services/GOCDB/Cookbook and recipes/GOCDB_daily_maintenance.txt'>GOCDB_daily_maintenance.txt in SCT docs on SVN</a> - This is where it all starts...</li>");
echo("<li><a href='https://wiki.egi.eu/wiki/GOCDB_Documentation_Index'>GOCDB public documentation index</a> - The RTFM link to send to anyone who has questions</li>");
echo("</ul>");

?>
