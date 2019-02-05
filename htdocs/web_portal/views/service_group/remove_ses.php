<?php
$sg = $params['sg'];
?>
<div class="rightPageContainer">
    <script language="JavaScript" src="<?php echo \GocContextPath::getPath()?>javascript/service_group/remove_se_from_vsite.js"></script>
    <script language="JavaScript" src="<?php echo \GocContextPath::getPath()?>javascript/ajax.js"></script>
    <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
    <div class="rightPageHolder">
        <div class="leftFloat">
            <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" class="pageLogo" />
        </div>
        <div class="leftFloat" style="width: 50em;">
            <h1 class="vSite">
                Remove Services from <?php xecho($sg->getName()) ?>
            </h1>
            <span class="vSiteDescription">
                Remove the services from this service group by clicking the remove button.
            </span>
            <span class="vSitesMoreInfo">
                For more information see
                <a href="https://wiki.egi.eu/wiki/GOCDB/Input_System_User_Documentation#Service_Groups">
                    service group help
                </a>
            </span>
        </div>

        <span class="vSiteNotice">Please ensure the service administrators are aware of your modifications.</span>

        <!--  Services -->
        <div class="listContainer">
            <span class="header listHeader">
                Services
            </span>
            <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Remove</th>
                    <th class="site_table">Service</th>
                    <th class="site_table">Description</th>
                    <th class="site_table">Hosting Site</th>
                </tr>
                <?php
                $num = 2;

                foreach($sg->getServices() as $se) {
                    if($se->getScopes()->first()->getName() == 'Local') {
                        $style = "style=\"background-color: #A3D7A3;\"";
                    }
                    else{
                        $style = "";
                    }

                ?>
                <tr class="site_table_row_<?php echo $num ?>"<?php echo $style; ?> id="<?php echo $se->getId(); ?>Row">
                    <td>
                        <a href="#" onclick="removeSe(<?php echo $se->getId() ?>, <?php echo $sg->getId() ?>, <?php if(is_null($se->getParentSite())) { echo "true"; } else { echo "false"; }?>)">
                           Remove
                        </a>
                    </td>

                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <img src="<?php echo \GocContextPath::getPath()?>img/server.png" height="25px" style="vertical-align: middle; padding-right: 1em;" />
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Service&amp;id=<?php echo $se->getId() ?>">
                                    <?php
                                        xecho($se->getServiceType()->getName());
                                        echo " - ";
                                        xecho($se->getHostName());
                                    ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td class="site_table">
                        <?php xecho($se->getDescription()); ?>
                    </td>
                    <td class="site_table">
                        <a href="index.php?Page_Type=Site&amp;id=<?php echo $se->getParentSite()->getId() ?>">
                            <?php xecho($se->getParentSite()->getShortName()) ?>
                        </a>
                    </td>
                </tr>
                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over SEs
                ?>
            </table>
        </div>
        <span class="leftFloat topMargin">
            Return to
            <a href="index.php?Page_Type=Service_Group&amp;id=<?php echo $sg->getId() ?>">
                 <?php xecho($sg->getName()) ?>
            </a>
        </span>
    </div>
</div>
