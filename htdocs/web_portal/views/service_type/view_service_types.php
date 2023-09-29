<div class="rightPageContainer">

    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                Service Types
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            All Service Types in GOCDB.
        </span>
    </div>

    <?php $numberOfServiceTypes = sizeof($params['ServiceTypes'])?>
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo $numberOfServiceTypes ?> Service Type<?php if ($numberOfServiceTypes) {
                echo "s";
            }?>
        </span>
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">Services</th>
                <th class="site_table">Description</th>
            </tr>
            <?php
            $num = 2;

            if ($numberOfServiceTypes > 0) {
                foreach ($params['ServiceTypes'] as $serviceType) {
                    ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 30%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <?php
                                echo "<a href=\"index.php"
                                    . "?Page_Type=Service_Type"
                                    . "&amp;id=" . $serviceType->getId()
                                    . "\">"
                                    . $serviceType->getName()
                                    . "</a>";
                                ?>
                            </span>
                        </div>
                    </td>

                    <td class="site_table">
                        <?php xecho(sizeof($serviceType->getServices())); ?>
                    </td>

                    <td class="site_table">
                        <?php xecho($serviceType->getDescription()); ?>
                    </td>
                </tr>
                    <?php
                    if ($num == 1) {
                        $num = 2;
                    } else {
                        $num = 1;
                    }
                } // End of the foreach loop iterating over service types
            }
            ?>
        </table>
    </div>
</div>
