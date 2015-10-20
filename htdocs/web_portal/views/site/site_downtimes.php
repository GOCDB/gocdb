<?php
$site = $params['site'];
$downtimes = $params['downtimes'];
?>
<div class="rightPageContainer">
    <div style="overflow: hidden">
        <div style="float: left;">
            <h1 style="float: left; margin-left: 0em; padding-bottom: 0.3em;">
                Downtimes Affecting
                <a  style="font-family: inherit; font-size: inherit; font-weight: inherit; padding-bottom: inherit; "
                    href="index.php?Page_Type=Site&id=<?php echo $site->getId(); ?>">
		    <?php xecho($site) ?></a>'s
                Services
            </h1>
        </div>

        <!--  Downtimes -->
         <div class="listContainer">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">All Downtimes (Year-Month-Day Time in UTC)</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/down_arrow.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table id="allSiteDowntimesTable" class="table table-striped table-condensed tablesorter">
		<thead>
		    <tr>
			<th>Description</th>
			<th>From</th>
			<th>To</th>
		    </tr>
		</thead>
		<tbody>
		    <?php
		    $num = 2;
		    foreach ($downtimes as $dt) {
			?>

    		    <tr>
    			<td>
    			    <a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $dt->getId() ?>">
				    <?php xecho($dt->getDescription()) ?>
    			    </a>
    			</td>
    			<td style="width: 20%"><?php echo $dt->getStartDate()->format('Y-m-d H:i'/* $dt::DATE_FORMAT */); ?></td>
    			<td style="width: 20%"><?php echo $dt->getEndDate()->format('Y-m-d H:i'/* $dt::DATE_FORMAT */) ?></td>
    		    </tr>
			<?php
			if ($num == 1) {
			    $num = 2;
			} else {
			    $num = 1;
			}
		    }
		    ?>
		</tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function ()
    {
	$("#allSiteDowntimesTable").tablesorter();
    }
    );
</script> 