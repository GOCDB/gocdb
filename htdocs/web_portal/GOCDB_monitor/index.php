<br>
<h1 align='center'>GOCDB MONITOR</h1>
<hr>

<?php
require_once __DIR__ . '/tests.php';

echo "<p>URLs as defined by local_info.xml</p>";
echo "<p>PI URL is: ".PI_URL."</p>";
echo "<p>Portal URl is: ".PORTAL_URL."</p>";
echo "<p>Server Base URL is: ".SERVER_BASE_URL."</p>";

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
?>

<h2>Service status overview</h2>
<table border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <td><b>Test</b></td>
            <td><b>Status</b></td>
            <td><b>Details</b></td>
            <td><b>Doc/help</b></td>
        </tr>
    </thead>
    <tbody>
<?php
foreach ($test_statuses as $test => $status) {
    echo("<tr>");
    echo("<td><font size='1'><span title=\"{$test_desc[$test]}\">$test</span></font></td>");
    echo($disp[$status]);
    echo("<td><font size='1'>{$test_messages[$test]}</font></td>");
    echo("<td><font size='1'>{$test_doc[$test]}</font></td>");
    echo("</tr>");
}
?>
    </tbody>
</table>

<hr>

<h2>Other tests and check pages</h2>
<ul>
    <li><a href='http://sumatran.esc.rl.ac.uk/ganglia/?r=day&amp;c=Grid+services&amp;h=gocdb-base.esc.rl.ac.uk'>GOCDB server ganglia page</a> - Useful to see if there are memory or CPU problems</li>
    <li><a href='check.php'>Status check</a> - non vebose check of GOCDB service status. Just returns 'OK' if all the tests in \"service status overview\" are fine, 'WARNING' or 'ERROR' otherwise. Used for automatic tests</li>
</ul>

<h2>Further documentation</h2>
<ul>
    <li><a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT Documents/Servers and Services/GOCDB/Cookbook and recipes/GOCDB_daily_maintenance.txt'>GOCDB_daily_maintenance.txt in SCT docs on SVN</a> - This is where it all starts...</li>
    <li><a href='https://wiki.egi.eu/wiki/GOCDB_Documentation_Index'>GOCDB public documentation index</a> - The RTFM link to send to anyone who has questions</li>
</ul>
