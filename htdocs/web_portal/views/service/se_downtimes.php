<div class="rightPageContainer">
    <div style="overflow: hidden">
        <div style="float: left;">
            <h1 style="float: left; margin-left: 0em; padding-bottom: 0.3em;">
                Downtimes for Service:
                <br/>
                <a  style="font-family: inherit; font-size: inherit; font-weight: inherit; text-decoration: underline; padding-bottom: inherit; " 
                    href="index.php?Page_Type=Service&id=<?php echo $params['se']->getId()?>">
    				<?php xecho($params['se']->getServiceType()->getName()) ?> - 
                    <?php xecho($params['se']->getHostName())?>
                </a>
            </h1>
        </div>
        
        <!--  Downtimes -->
        <div class="listContainer">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">All Downtimes</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Description</th>
                    <th class="site_table" style="width: 20%">From</th>
                    <th class="site_table" style="width: 20%">To</th>
                </tr>
                <?php
                $num = 2;
                foreach($params['downtimes'] as $downtime) {
                ?>
                
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                    	<a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $downtime->getId() ?>">
                    		<?php echo $downtime->getDescription() ?>
                    	</a>
                    </td>
                    
                    <td class="site_table"><?php echo $downtime->getStartDate()->format($downtime::DATE_FORMAT) ?></td>
                    <td class="site_table"><?php echo $downtime->getEndDate()->format($downtime::DATE_FORMAT) ?></td>
                </tr>
                <?php
                if($num == 1) { $num = 2; } else { $num = 1; }
                }
                ?>
            </table>
        </div>
    </div>
</div>