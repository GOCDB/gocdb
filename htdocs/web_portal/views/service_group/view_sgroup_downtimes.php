<div class="rightPageContainer">
    <div style="overflow: hidden">
        <div style="float: left;">
            <h1 style="float: left; margin-left: 0em;">
                Downtimes for 
                <a  style="font-family: inherit; font-size: inherit; font-weight: inherit; text-decoration: underline; padding-bottom: inherit; " 
                    href="index.php?Page_Type=Service_Group&id=<?php echo $params['sGroup']->getId(); ?>">
                    <?php echo $params['sGroup']->getName()?>
                </a>
            </h1>
        </div>
        
        <!--  Downtimes -->
        <div class="listContainer">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">All Downtimes</span>
            <img src="img/down_arrow.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                	<th class="site_table">Description</th>
                    <th class="site_table" style="width: 9em;">From</th>
                    <th class="site_table" style="width: 9em;">To</th>
                </tr>
                <?php
	                $num = 2;
	                foreach($params['downtimes'] as $d) {
                ?>
                
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                    	<a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $d->getId() ?>">
                    		<?php echo $d->getDescription() ?>
                    	</a>
                    </td>
                    <td class="site_table"><?php echo $d->getStartDate()->format($d::DATE_FORMAT); ?></td>
                    <td class="site_table"><?php echo $d->getStartDate()->format($d::DATE_FORMAT); ?></td>
                </tr>
                <?php 
                	if($num == 1) { $num = 2; } else { $num = 1; }
                }
                ?>
            </table>
        </div>
    </div>
</div>