<?php
require_once __DIR__ . '/../../controllers/utils.php';

$extensionProperties = $params['sGroup']->getServiceGroupProperties();
$showPD = $params['authenticated'];
?>

<script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/virtualSite.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                Service Group: <?php xecho($params['sGroup']->getName())?>
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;"><?php xecho($params['sGroup']->getDescription())?></span>
    </div>

    <!--  Edit Virtual Site link -->
    <!--  only show this link if we're in read / write mode -->
    <?php if(!$params['portalIsReadOnly']): ?>
        <?php if($params['ShowEdit']):?>
            <div style="float: right;">
                <div style="float: right; margin-left: 2em;">
                    <a href="index.php?Page_Type=Edit_Service_Group&amp;id=<?php echo $params['sGroup']->getId()?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" class="pencil" />
                        <br />
                        <br />
                        <span>Edit</span>
                    </a>
                </div>
                <div style="float: right;">
                    <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                    <a onclick="return confirmSubmit()"
                        href="index.php?Page_Type=Delete_Service_Group&id=<?php echo $params['sGroup']->getId()?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" class="trash" />
                        <br />
                        <br />
                        <span>Delete</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Virtual Service Group Properties container div -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  Data -->
        <div class="tableContainer" style="width: 55%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Properties</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/contact_card.png" class="decoration" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_even">
                    <td class="site_table">Monitored</td><td class="site_table">
                    <?php
                        switch($params['sGroup']->getMonitored()) {
                            case true:
                                ?>
                                <img src="<?php echo \GocContextPath::getPath()?>img/tick.png" height="22px" style="vertical-align: middle;" />
                                <?php
                                break;
                            case false:
                                ?>
                                <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="22px" style="vertical-align: middle;" />
                                <?php
                                break;
                        }
                        ?></td>
                </tr>
                <?php
                if($params['sGroup']->getScopes() != null && $params['sGroup']->getScopes()->first() != null &&
                        $params['sGroup']->getScopes()->first()->getName() == "Local") {
                    $style = " style=\"background-color: #A3D7A3;\""; } else { $style = ""; }
                ?>
                <tr class="site_table_row_odd" <?php echo $style ?>>
                    <td class="site_table">
                        <a href="index.php?Page_Type=Scopes" style="word-wrap: normal">Scope Tags</a>
                    </td>
                    <td class="site_table">
            <textarea readonly="true" style="width: 100%; height: 60px;"><?php xecho($params['sGroup']->getScopeNamesAsString())?></textarea>
                    </td>
                </tr>
                <tr class="site_table_row_even">
                    <td class="site_table">Contact E-Mail</td>
                    <td class="site_table">
                        <?php if($showPD) { ?>
                        <a href="mailto:<?php xecho($params['sGroup']->getEmail()); ?>">
                            <?php xecho($params['sGroup']->getEmail()); ?>
                        </a>
                        <?php } else {echo(getInfoMessage());} ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!--  Services -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Services</span>
        <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="decoration" />
        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_even">
                <th class="site_table">Hostname (service type)</th>
                <th class="site_table">Description</th>
                <th class="site_table">Production</th>
                <th class="site_table"><a href="index.php?Page_Type=Scopes">Scope(s)</a></th>
            </tr>

            <?php
            foreach ($params['sGroup']->getServices() as $index => $se) {
//	            if($se->getScopes()->first()->getName() == "Local") {
//					$style = " style=\"background-color: #A3D7A3;\"";
//				} else {
//					$style = "";
//				}
            ?>

            <tr class="site_table_row_<?php echo ($index % 2 == 0) ? 'odd' : 'even' ?>">
                <td class="site_table">
                    <div style="background-color: inherit;">
                       <div style="background-color: inherit;">
                            <span style="vertical-align: middle;">
                                <a href="index.php?Page_Type=Service&amp;id=<?php echo $se->getId() ?>">
                                    <?php xecho($se->getHostname() . " (" . $se->getServiceType()->getName() . ")");?>
                                </a>
                            </span>
                        </div>
                    </div>
                </td>
                <td class="site_table"><?php xecho($se->getDescription()) ?></td>
                <td class="site_table">
                <?php
                switch($se->getProduction()) {
                    case true:
                        ?>
                        <img src="<?php echo \GocContextPath::getPath()?>img/tick.png" height="22px" style="vertical-align: middle;" />
                        <?php
                        break;
                    case false:
                        ?>
                        <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="22px" style="vertical-align: middle;" />
                        <?php
                        break;
                }
                ?>
                </td>
                <td class="site_table">
            <textarea readonly="true" style="height: 25px;"><?php xecho($se->getScopeNamesAsString())?></textarea>
                </td>
            </tr>
            <?php
            } // End of the foreach loop iterating over SEs
            ?>
        </table>

        <!--  only show this link if we're in read / write mode -->
        <?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
            <!-- Add new Service Link -->
            <a href="index.php?Page_Type=Add_Service_Group_SEs&amp;id=<?php echo $params['sGroup']->getId();?>">
                <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Add Services
                </span>
            </a>
            <!-- Remove Service Link -->
            <a href="index.php?Page_Type=Remove_Service_Group_SEs&amp;id=<?php echo $params['sGroup']->getId();?>">
                <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Remove Services
                </span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Roles -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <?php
        if ($showPD) { ?>
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Users (Click on name to manage roles)</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/people.png" class="decoration" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_even">
                    <th class="site_table">Name</th>
                    <th class="site_table">Role</th>
                </tr>
                <?php
                foreach ($params['Roles'] as $index => $role) {
                ?>
                <tr class="site_table_row_<?php echo ($index % 2 == 0) ? 'odd' : 'even' ?>">
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <img src="<?php echo \GocContextPath::getPath()?>img/person.png" class="person" />
                            <a style="vertical-align: middle;" href="index.php?Page_Type=User&id=<?php echo $role->getUser()->getId()?>">
                                <?php xecho($role->getUser()->getFullName()); ?>
                            </a>
                        </div>
                    </td>
                    <td class="site_table">
                        <?php xecho($role->getRoleType()->getName()); ?>
                    </td>
                </tr>
                <?php
                } // End of the foreach loop iterating over user roles
                ?>
            </table>
        <?php
        } else {
            require_once __DIR__.'/../fragments/hidePersonalData.php';
        }
        ?>
        <!--  only show this link if we're in read / write mode -->
        <?php if(!$params['portalIsReadOnly'] && $showPD): ?>
            <!-- Request role Link -->
            <a href="index.php?Page_Type=Request_Role&amp;id=<?php echo $params['sGroup']->getId();?>">
                <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Request Role
                </span>
            </a>
        <?php endif; ?>
    </div>

    <!--  Service Group Properties -->
    <?php
    $parent = $params['sGroup'];
    $propertiesController = "Service_Group_Properties_Controller";
    $addPropertiesPage = "Add_Service_Group_Properties";
    $editPropertyPage = "Edit_Service_Group_Property";

    require_once __DIR__ . '/../fragments/viewPropertiesTable.php';
    ?>

    <!--  Downtimes -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Recent Downtimes</span>
        <a href="index.php?Page_Type=SGroup_Downtimes&amp;id=<?php echo $params['sGroup']->getId(); ?>" style="vertical-align:middle; float: left; padding-top: 1.3em; padding-left: 1em; font-size: 0.8em;">(View all Downtimes)</a>
        <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" class="decoration" />
        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_even">
                <th class="site_table">Description</th>
                <th class="site_table">From</th>
                <th class="site_table">To</th>
            </tr>
            <?php
            foreach ($params['downtimes'] as $index => $d) {
            ?>

            <tr class="site_table_row_<?php echo ($index % 2 == 0) ? 'odd' : 'even' ?>">
                <td class="site_table">
                    <a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $d->getId() ?>">
                        <?php xecho($d->getDescription()) ?>
                    </a>
                </td>
                <td class="site_table"><?php echo $d->getStartDate()->format($d::DATE_FORMAT) ?></td>
                <td class="site_table"><?php echo $d->getEndDate()->format($d::DATE_FORMAT) ?></td>
            </tr>
            <?php
            }
            ?>
        </table>
    </div>

    <!-- Show RoleActionRecords if user has permissions over this object -->
    <?php if ($params['ShowEdit']){
        require_once __DIR__ . '/../fragments/viewRoleActionsTable.php';
    } ?>

</div>


    <script type="text/javascript">
    $(document).ready(function () {

        // sort on first and second table cols only
        $("#sgExtensionPropsTable").tablesorter({
        // pass the headers argument and assing a object
        headers: {
            // assign the third column (we start counting zero)
            2: {
            // disable it by setting the property sorter to false
            sorter: false
            },
            3: {
            // disable it by setting the property sorter to false
            sorter: false
            }
        }
        });

    }
    );
    </script>
