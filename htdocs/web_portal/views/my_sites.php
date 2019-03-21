<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/home.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                My Sites
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            Sites and groups from your roles
        </span>
    </div>

    <!-- NGIs and Sites from My Roles -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  Sites -->
        <div class="tableContainer" style="float: left; width: 55%;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Sites From Your Roles</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/site.png" class="titleIcon"/>
            <table style="clear: both; width: 100%; table-layout: fixed;">
                <?php if(!empty($params['sites_from_roles'])) {
                        $num = 1;
                        foreach($params['sites_from_roles'] as $site) { ?>
                        <tr class="site_table_row_<?php echo $num ?>">
                            <td class="site_table">
                                <a href="index.php?Page_Type=Site&amp;id=<?php echo $site->getId()?>"><?php xecho($site->getShortName()) ?></a>
                            </td>
                        </tr>
                        <?php if($num == 1) { $num = 2; } else { $num = 1; } } ?>

                <?php }?>
            </table>
        </div>

        <!--  NGIs -->
        <div class="tableContainer" style="width: 42%; float: right;" >
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">NGIs From Your Roles</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/ngi.png" class="titleIcon"/>
            <table style="clear: both; width: 100%; table-layout: fixed;">
                <?php if(!empty($params['ngis_from_roles'])) {
                        $num = 1;
                        foreach($params['ngis_from_roles'] as $ngi) { ?>
                        <tr class="site_table_row_<?php echo $num ?>">
                            <td class="site_table">
                                <a href="index.php?Page_Type=NGI&amp;id=<?php echo $ngi->getId()?>"><?php xecho($ngi->getName())?></a>
                            </td>
                        </tr>
                        <?php if($num == 1) { $num = 2; } else { $num = 1; } } ?>

                <?php } ?>
            </table>
        </div>
    </div>

    <!-- Service Groups and projects from My Roles -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  Service Groups -->
        <div class="tableContainer" style="width: 55%; float: left;" >
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Service Groups From Your Roles</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/virtualSite.png" class="titleIcon"/>
            <table style="clear: both; width: 100%; table-layout: fixed;">
                <?php if(!empty($params['sgroups_from_roles'])) {
                        $num = 1;
                        foreach($params['sgroups_from_roles'] as $sg) { ?>
                        <tr class="site_table_row_<?php echo $num ?>">
                            <td class="site_table">
                                <a href="index.php?Page_Type=Service_Group&amp;id=<?php echo $sg->getId()?>"><?php xecho($sg->getName())?></a>
                            </td>
                        </tr>
                        <?php if($num == 1) { $num = 2; } else { $num = 1; } } ?>

                <?php }?>
            </table>
        </div>

        <!--  Projects -->
        <div class="tableContainer" style="width: 42%; float: right;" >
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Projects From Your Roles</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/project.png" class="titleIcon"/>
            <table style="clear: both; width: 100%; table-layout: fixed;">
                <?php if(!empty($params['projects_from_roles'])) {
                        $num = 1;
                        foreach($params['projects_from_roles'] as $project) { ?>
                        <tr class="site_table_row_<?php echo $num ?>">
                            <td class="site_table">
                                <a href="index.php?Page_Type=Project&amp;id=<?php echo $project->getId()?>"><?php xecho($project->getName())?></a>
                            </td>
                        </tr>
                        <?php if($num == 1) { $num = 2; } else { $num = 1; } } ?>

                <?php } ?>
            </table>
        </div>
    </div>
</div>

