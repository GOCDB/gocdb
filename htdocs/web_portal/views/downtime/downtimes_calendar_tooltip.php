<?php
define('DATE_FORMAT', 'd-m-Y H:i');
//$duration = $params['duration'];
$start = $params['start'];
$end = $params['end'];
$services = $params['services'];
$scopes = $params['scopes'];
$site = $params['site'];
$description = $params['description'];
$affected = $params['affected'];




//I've elected to handle the time section in js, as moment.js makes this really simple
//however, I need to generate a unique id for each qtip's timefield so I don't replace each qtip's
//timeField with the most recently generated one's text
//
//
//due to the way the tooltips are generated, the timefield-<id> identifier will not be unique, as a
//new qtip is generated for the same event if an event spans two or more views (and you change the view), so we need to add some more uniqueness using rand()
$id = $params['id'];
$randomNumber = rand();

?>

<!--This page is the html for the downtime calendar tooltips-->
<!--the tooltip-* css classes are found in css/downtime-calendar.css-->
<html>
<div>

    <h3 class="tooltip-title"><b><?php echo $site;?></b></h3>
    <h3 class="tooltip-title"><i><?php echo $description;?></i></h3>

    <hr class="tooltip-hr"/>

    <p id="timeField-<?php echo $id . "-" . $randomNumber;?>" class="tooltip-p">
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

<script type="text/javascript">

    $(document).ready(function () {
        var startTime = moment(<?php echo( "\"". $start . "\"");?>);
        var endTime = moment(<?php echo( "\"". $end . "\"");?>);
        var nowTime = moment();
        var duration = moment.duration(endTime.diff(startTime));
        var timeField = "#timeField-<?php echo $id . "-" . $randomNumber;?>";

        var timeString = "";
        //inbetween
        if (moment(nowTime).isBetween(startTime, endTime)){
            timeString =  "Ongoing, ending " + moment(endTime).fromNow();
        //future
        } else if (moment(startTime).isAfter(nowTime)){
            timeString =  "Starting " + moment(startTime).fromNow() + "<br/> Lasting " + duration.humanize();
        //past
        } else {
            timeString =  "Ended " + moment(endTime).fromNow() + "<br/> Lasted " + duration.humanize();
        }

        $(timeField).html(timeString);

    });


</script>
</html>
