<?php
$site = $params['site'];
$downtimes = $params['downtimes'];
?>
<div class="rightPageContainer">
    <div style="overflow: hidden">
        <div>
            <div style="float: left;">
                <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" class="pageLogo" />
            </div>
            <div>
                <h1 style="float: none;">
                    <a  style="font-family: inherit; font-size: inherit; font-weight: inherit; padding-bottom: inherit; "
                        href="index.php?Page_Type=Site&id=<?php echo $site->getId(); ?>">
                    <?php xecho($site) ?></a> Downtimes
                </h1>
            </div>
            <div style="float: none;">
                All downtimes affecting <?php xecho($site) ?> services and endpoints.
            </div>
        </div>

        <!--  Downtimes -->
         <div class="listContainer">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
                <?php echo sizeof($downtimes) ?> Downtime<?php if(sizeof($downtimes) != 1) echo "s"?>
            </span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/grid.png" class="decoration" />
            <span style="vertical-align:middle; float: right; padding-top: 0.9em; padding-right: 8%;">
                Year-Month-Day Time in UTC
            </span>
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
