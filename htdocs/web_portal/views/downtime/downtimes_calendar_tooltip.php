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

    <h3 class="tooltip-title"><b><?php echo $site;?></b></h3>
    <h3 class="tooltip-title"><i><?php echo $description;?></i></h3>

    <hr class="tooltip-hr"/>

    <p class="tooltip-p">
        <b>Duration:</b>
        <?php echo $duration;?>
    </p>

    <hr class="tooltip-hr"/>

    <p class="tooltip-p">
        <b>Service Scopes: </b>
        <?php echo $scopes;?>


    </p>

    <hr class="tooltip-hr"/>

    <p class="tooltip-p">
        <b>Services</b>
        <br/>
        <i><?php echo $affected;?> affected</i>
        <br/>
        <?php foreach($services as $service){?>
            <?php echo $service;?><br/>
        <?php };?>
    </p>

</div>
</html>
