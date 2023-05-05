<br>
<h1 align='center'>GOCDB MONITOR</h1>
<hr>

<?php
require_once __DIR__ . '/tests.php';

$config = Factory::getConfigService();

// GOCDB5 DB connection
$res = test_db_connection();
$test_statuses[TEST_1] = $res["status"];
$test_messages[TEST_1] = $res["message"];

// GOCDB5 configuration
$res = test_config($config);
$test_statuses[TEST_4] = $res["status"];
$test_messages[TEST_4] = $res["message"];

// Following tests depend on the config file being valid.
if (strcasecmp($res["status"], OK) == 0) {
    define_test_urls($config);
    // GOCDBPI v5
    $res = test_url(PI_URL);
    $test_statuses[TEST_2] = $res["status"];
    $test_messages[TEST_2] = $res["message"];
    // GOCDB5 web portal
    $res = test_url(SERVER_BASE_URL);
    $test_statuses[TEST_3] = $res["status"];
    $test_messages[TEST_3] = $res["message"];

    // DISPLAY RESULTS
    echo "<p>URLs as defined by local_info.xml</p>";
    echo "<p>PI URL is: " . PI_URL . "</p>";
    echo "<p>Portal URl is: " . PORTAL_URL . "</p>";
    echo "<p>Server Base URL is: " . SERVER_BASE_URL . "</p>";
} else {
    echo "<p>Unable to extract URL information due to configuration test failure.</p>";
}
?>

<h2>Service status overview</h2>
<p>Other tests may have dependencies on the server configuration so may
show ERROR or UNKNOWN if the configuration is invalid.</p>
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
    echo("<td><span title=\"{$test_desc[$test]}\">$test</span></font></td>");
    echo($disp[$status]);
    echo("<td>{$test_messages[$test]}</font></td>");
    echo("<td>{$test_doc[$test]}</font></td>");
    echo("</tr>");
}
?>
    </tbody>
</table>

<hr>

<h2>Other tests and check pages</h2>
<ul>
    <li><a href='http://sumatran.esc.rl.ac.uk/ganglia/?r=day&amp;
    c=Grid+services&amp;h=gocdb-base.esc.rl.ac.uk'>GOCDB server
    ganglia page</a> - Useful to see if there are memory or CPU
    problems</li>
    <li><a href='check.php'>Status check</a> - a less verbose check of
    GOCDB service status. Returns the single line 'All GOCDB tests
    are looking good' if all tests run without error and 'GOCDB
    Web Portal is unable to connect to the GOCDB back end database'
    otherwise. Used for automated tests.</li>
</ul>

<h2>Further documentation</h2>
<ul>
    <li><a href='https://svn.esc.rl.ac.uk/repos/sct-docs/SCT Documents/
    Servers and Services/GOCDB/Cookbook and recipes/
    GOCDB_daily_maintenance.txt'>GOCDB_daily_maintenance.txt in
    SCT docs on SVN</a> - This is where it all starts...</li>
    <li><a href='https://wiki.egi.eu/wiki/GOCDB_Documentation_Index'>
    GOCDB public documentation index</a> - The RTFM link to send to
    anyone who has questions</li>
</ul>
