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
                    <?php echo $site?></a>'s
                SEs
            </h1>
        </div>

        <!--  Downtimes -->
        <div class="listContainer" style="width: 99.5%; float: left; margin-top: 1em; margin-right: 10px;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">All Downtimes</span>
            <img src="img/down_arrow.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                	<th class="site_table">Description</th>
                    <th class="site_table" style="width: 10em;">From</th>
                    <th class="site_table" style="width: 10em;">To</th>
                </tr>
                <?php
                $num = 2;
                foreach($downtimes as $dt) {
                ?>

                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                    	<a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $dt->getId() ?>">
							<?php echo $dt->getDescription() ?>
						</a>
					</td>
                    <td class="site_table"><?php echo $dt->getStartDate()->format($dt::DATE_FORMAT) ?></td>
                    <td class="site_table"><?php echo $dt->getEndDate()->format($dt::DATE_FORMAT) ?></td>
                </tr>
                <?php
                if($num == 1) { $num = 2; } else { $num = 1; }
                }
                ?>
            </table>
        </div>
    </div>
</div>