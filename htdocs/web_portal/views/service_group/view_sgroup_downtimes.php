<div class="rightPageContainer">
    <div style="overflow: hidden">
        <div style="float: left;">
            <h1 style="float: left; margin-left: 0em;">
                Downtimes for 
                <a  style="font-family: inherit; font-size: inherit; font-weight: inherit; text-decoration: underline; padding-bottom: inherit; " 
                    href="index.php?Page_Type=Service_Group&id=<?php echo $params['sGroup']->getId(); ?>">
                    <?php xecho($params['sGroup']->getName())?>
                </a>
            </h1>
        </div>
        
        <!--  Downtimes -->
        <div class="listContainer">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">All Downtimes (Year-Month-Day Time in UTC)</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table id="allSgDowntimesTable" class="table table-striped table-condensed tablesorter" >
		<thead>
                <tr>
		    <th>Description</th>
                    <th>From</th>
                    <th>To</th>
                </tr>
		</thead>
		<tbody>
                <?php
	                //$num = 2;
	                foreach($params['downtimes'] as $d) {
                ?>
                
                <tr>
                    <td>
                    	<a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $d->getId() ?>">
                    		<?php xecho($d->getDescription()) ?>
                    	</a>
                    </td>
                    <td style="width: 20%"><?php echo $d->getStartDate()->format('Y-m-d H:i'/*$d::DATE_FORMAT*/); ?></td>
                    <td style="width: 20%"><?php echo $d->getStartDate()->format('Y-m-d H:i'/*$d::DATE_FORMAT*/); ?></td>
                </tr>
                <?php 
                	//if($num == 1) { $num = 2; } else { $num = 1; }
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
	$("#allSgDowntimesTable").tablesorter();
    }
    );
</script> 