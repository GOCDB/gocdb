<?php
$name = $params['Name'];
$id = $params['ID'];
$description = $params['Description'];
$ngis = $params['NGIs'];
$ngiCount = sizeof($ngis);
$sites = $params['Sites'];
$siteCount = sizeof($sites);
$serviceGroups = $params['ServiceGroups'];
$serviceGroupsCount = sizeof($serviceGroups);
$services = $params['Services'];
$serviceCount = sizeof($services);
$totalCount = $siteCount + $ngiCount + $serviceCount +$serviceGroupsCount;
?>

<div class="rightPageContainer">

    <!--Headings-->
    <div style="float: left; width: 50em;">
        <h1 style="float: left; margin-left: 0em;">Scope: <?php echo $name?></h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;"><?php echo $description ?></span>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            <?php if($totalCount>0):?>
                In total, there are <?php if($totalCount==1){echo "is";}else{echo "are";}?>
                <?php if ($totalCount == 0){echo "no";} else{echo $totalCount;} ?>
                entit<?php if($totalCount != 1){echo "ies";}else{echo "y";}?>
                (<?php echo $ngiCount?> NGIs, <?php echo $siteCount?> sites, <?php echo $serviceGroupsCount?>
                service groups, and <?php echo $serviceCount?> services) with this scope.
            <?php else: ?>
                This scope is currently not used by any NGI, site, service group, or service.
            <?php endif; ?>
        </span>

    </div>

    <!--Edit/Delete buttons-->
    <!-- don't display in read only mode or if user is not admin -->
    <?php if(!$params['portalIsReadOnly'] && $params['UserIsAdmin']):?>
        <div style="float: right;">
            <div style="float: right; margin-left: 2em;">
                <a href="index.php?Page_Type=Admin_Edit_Scope&amp;id=<?php echo $id ?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" height="25px" style="float: right;" />
                    <br />
                    <br />
                    <span>Edit</span>
                </a>
            </div>
            <div style="float: right;">
                <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                <a onclick="return confirmSubmit()"
                   href="index.php?Page_Type=Admin_Remove_Scope&id=<?php echo $id?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" height="25px" style="float: right; margin-right: 0.4em;" />
                    <br />
                    <br />
                    <span>Delete</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!--  NGIs -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
            There <?php if($ngiCount==1){echo "is";}else{echo "are";}?> <?php if ($ngiCount == 0){echo "no";} else{echo $ngiCount;} ?> NGI<?php if($ngiCount != 1) echo "s"?> with this scope
        </span>
<!--
        <img src="<?php echo \GocContextPath::getPath()?>img/NGI.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        -->

        <?php if ($ngiCount != 0): ?>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Description</th>
                </tr>

                <?php
                $num = 2;

                foreach($params['NGIs'] as $ngi) {
                ?>

                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=NGI&amp;id=<?php echo $ngi->getId() ?>">
                                    <img class="flag" style="vertical-align: middle" src="<?php echo \GocContextPath::getPath()?>img/ngi/<?php echo $ngi->getName() ?>.jpg">
                                    <span>&nbsp;&nbsp;</span><?php xecho($ngi->getName()); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td class="site_table"><?php xecho($ngi->getDescription()) ?></td>

                </tr>
                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                } // End of the foreach loop iterating over SEs
                ?>
            </table>
        <?php else: echo "<br><br>&nbsp &nbsp"; endif; ?>
    </div>

    <!--  Sites -->
    <div class="listContainer">
        <span class="header listHeader">
            There <?php if($siteCount==1){echo "is";}else{echo "are";}?> <?php if ($siteCount == 0){echo "no";} else{echo $siteCount;} ?> site<?php if($siteCount != 1) echo "s"?> with this scope
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/site.png" class="decoration" />
        <?php if($siteCount > 0): ?>
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Certification Status</th>
                    <th class="site_table">Production Status</th>
                </tr>
                <?php
                $num = 2;

                foreach($params['Sites'] as $site) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 30%">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Site&amp;id=<?php echo $site->getId() ?>">
                                    <span>&nbsp;&nbsp;</span><?php xecho($site->getShortName()); ?>
                                </a>
                            </span>
                        </div>
                    </td>

                    <td class="site_table">
                        <?php xecho($site->getCertificationStatus()->getName()) ?>
                    </td>

                    <td class="site_table">
                        <?php xecho($site->getInfrastructure()->getName()) ?>
                    </td>
                </tr>
                <?php if($num == 1) { $num = 2; } else { $num = 1; }}?>
            </table>
        <?php endif;?>
    </div>

    <!--  Service Groups -->
    <div class="listContainer">
        <span class="header listHeader">
            There <?php if($serviceGroupsCount==1){echo "is";}else{echo "are";}?> <?php if ($serviceGroupsCount == 0){echo "no";} else{echo $serviceGroupsCount;} ?> service group<?php if($serviceGroupsCount != 1) echo "s"?> with this scope
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/virtualSite.png" class="decoration" />
        <?php if($serviceGroupsCount>0): ?>
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Description</th>
                </tr>
                <?php
                $num = 2;
                foreach($serviceGroups as $sGroup) {
                ?>
                <?php if($sGroup->getScopes()->first()->getName() == "Local") { $style = " style=\"background-color: #A3D7A3;\""; } else { $style = ""; } ?>
                <tr class="site_table_row_<?php echo $num ?>" <?php echo $style ?>>
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Service_Group&amp;id=<?php echo $sGroup->getId()?>">
                                   <?php xecho($sGroup->getName()); ?>
                                </a>
                            </span>
                        </div>
                    </td>

                    <td class="site_table">
                        <?php xecho($sGroup->getDescription()); ?>
                    </td>

                </tr>
                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over SEs
                ?>
            </table>
        <?php endif; ?>
    </div>

    <!--  Services - count and link -->
    <div class="listContainer">
        <span class="header listHeader">
            There <?php if($serviceCount==1){echo "is";}else{echo "are";}?> <?php if ($serviceCount == 0){echo "no";} else{echo $serviceCount;} ?> service<?php if($serviceCount != 1) echo "s"?> with this scope
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="decoration" />
        <?php if($serviceCount>0): ?>
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <td class="site_table">
                        <a href="index.php?Page_Type=Services&amp;scope=<?php xecho($name)?>">
                            View Services
                        </a>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    </div>
</div>
