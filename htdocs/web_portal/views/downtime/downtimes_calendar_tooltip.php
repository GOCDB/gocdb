<?php
define('DATE_FORMAT', 'd-m-Y H:i');
$duration = $params['duration'];
$start = $params['start'];
$end = $params['end'];
$services = $params['services'];
$scopes = $params['scopes'];
$site = $params['site'];
$description = $params['description'];
$affected = $params['affected'];
?>

<!--This page is the html for the downtime calendar tooltips-->
<!--the tooltip-* css classes are found in css/downtime-calendar.css-->
<html>
<div>

    <h2 class="tooltip-title"><?php echo $site;?></h2>
    <h3 class="tooltip-title"><?php echo $description;?></h3>

    <hr class="tooltip-hr"/>

    <p class="tooltip-p">
        <b>Duration:</b>
        <?php echo $duration;?>
    </p>

    <hr class="tooltip-hr"/>

    <p class="tooltip-p">
        <b>Services:</b>
        <br/>
        <?php foreach($services as $service){?>
            <?php echo $service;?><br/>
        <?php };?>
        <i><?php echo $affected;?> services affected</i>
    </p>
    <hr class="tooltip-hr"/>

    <p class="tooltip-p">
        <b>Service Scopes:</b>
        <br/>

        <?php foreach($scopes as $scope){?>
            <?php echo $scope;?><br/>
        <?php };?>
    </p>
</div>
</html>
