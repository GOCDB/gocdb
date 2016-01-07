<?php
define('DATE_FORMAT', 'd-m-Y H:i');
$start = $params['start'];
$end = $params['end'];

$services = $params['services'];
$scopes = $params['scopes'];
$sites = $params['sites'];

$td1 = '<td>';
$td2 = '</td>';
?>

<!---
This page will show two tables, one of active downtimes and one of downtimes coming between 1-4 weeks. The user
can select the time period for planned downtimes to show. Extra information is shown by expanding a sub table 
from the main downtimes table. This table is shown and hidden by creating dynamically named tables and using 
javascript to show and hide these tables. 
--->
<html>
<div>

    <h1>Start:</h1>
    <p>
        <?php echo $start;?>
    </p>
    <h1>End:</h1>
    <p>
        <?php echo $end;?>
    </p>
    <h1>Sites:</h1>
    <?php foreach($sites as $site){?>
        <?php echo $site;?><br/>
    <?php };?>
    <h1>Services:</h1>
    <?php foreach($services as $service){?>
        <?php echo $service;?><br/>
    <?php };?>
    <h1>Scopes:</h1>
    <?php foreach($scopes as $scope){?>
        <?php echo $scope;?><br/>
    <?php };?>
</div>
</html>
