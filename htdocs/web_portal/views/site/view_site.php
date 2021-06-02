<?php
require_once __DIR__."/../../controllers/utils.php";

$site = $params['site'];
$downtimes = $params['Downtimes'];
$parentNgiName = $site->getNgi()->getName();
$portalIsReadOnly = $params['portalIsReadOnly'];
$extensionProperties = $site->getSiteProperties();
$showPD = $params['authenticated']; // display Personal Data
?>
<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/site.png" class="pageLogo" />
    </div>
    <div style="float: left; width: 50em;">
        <h1 style="float: left; margin-left: 0em;">Site: <?php xecho($site->getShortName()) ?></h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;"><?php xecho($site->getOfficialName()) ?><br />
        <?php xecho($site->getDescription()) ?>
        </span>
    </div>

    <!--  Edit Site link -->
    <!--  only show this link if we're in read / write mode -->
    <?php if (!$portalIsReadOnly): ?>
        <div style="float: right;">
        <?php if ($params['UserIsAdmin']): ?>
        <div style="float: right; margin-left: 2em; text-align:center;">
            <script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/confirm.js"></script>
            <a onclick="return confirmSubmit()"
               href="index.php?Page_Type=Delete_Site&id=<?php echo($site->getId()); ?>">
            <img src="<?php echo \GocContextPath::getPath() ?>img/trash.png" height="25px"/>
            <br/>
            <span>Admin<br>Delete</span>
            </a>
        </div>
        <?php endif; ?>
        <?php if ($params['ShowEdit']): ?>
        <div style="float: right; margin-left: 2em;">
            <a href="index.php?Page_Type=Edit_Site&amp;id=<?php echo($site->getId()) ?>">
            <img src="<?php echo \GocContextPath::getPath() ?>img/pencil.png" height="25px" style="float: right;" />
            <br />
            <br />
            <span>Edit</span>
            </a>
        </div>
        <?php endif; ?>

        </div>
    <?php endif; ?>

    <!-- Contacts and Project Data -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  Contacts -->
        <div class="tableContainer" style="width: 55%; float: left;" >
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Contact Info</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/contact_card.png" class="decoration" />
        <table style="clear: both; width: 100%; table-layout: fixed;">
        <tr class="site_table_row_1">
            <td class="site_table" style="width: 30%">E-Mail</td><td class="site_table">
            <?php if ($showPD) { ?>
                <a href="mailto:<?php xecho($site->getEmail()) ?>">
                <?php xecho($site->getEmail()) ?>
                </a>
            <?php } else echo(getInfoMessage()); ?>
            </td>
        </tr>
        <tr class="site_table_row_2">
            <td class="site_table">Telephone</td><td class="site_table"><?php
            if ($showPD) {
                xecho($site->getTelephone());
            } else
                echo(getInfoMessage());
            ?></td>
        </tr>
        <tr class="site_table_row_1">
            <td class="site_table">Emergency Tel</td><td class="site_table"><?php
            if ($showPD) {
                xecho($site->getEmergencyTel());
            } else
                echo(getInfoMessage());
            ?></td>
        </tr>
        <tr class="site_table_row_2">
            <td class="site_table">CSIRT Tel</td><td class="site_table"><?php
            if ($showPD) {
                xecho($site->getCsirtTel());
            } else
                echo(getInfoMessage())
                ?></td>
        </tr>
        <tr class="site_table_row_1">
            <td class="site_table">CSIRT E-Mail</td>
            <td class="site_table">
            <?php if ($showPD) { ?>
                <a href="mailto:<?php xecho($site->getCsirtEmail()) ?>">
                <?php xecho($site->getCsirtEmail()) ?>
                </a>
            <?php } else echo(getInfoMessage()); ?>
            </td>
        </tr>
        <tr class="site_table_row_2">
            <td class="site_table">Emergency E-Mail</td>
            <td class="site_table">
            <?php if ($showPD) { ?>
                <a href="mailto:<?php xecho($site->getEmergencyEmail()) ?>">
                <?php xecho($site->getEmergencyEmail()) ?>
                </a>
            <?php } else echo(getInfoMessage()); ?>
            </td>
        </tr>
        <tr class="site_table_row_1">
            <td class="site_table">Helpdesk E-Mail</td>
            <td class="site_table">
            <?php if ($showPD) { ?>
                <a href="mailto:<?php xecho($site->getHelpdeskEmail()); ?>">
                <?php xecho($site->getHelpdeskEmail()) ?>
                </a>
            <?php } else echo(getInfoMessage()); ?>
            </td>
        </tr>
        <tr class="site_table_row_2">
            <td class="site_table">Notifications</td>
            <td class="site_table">
                <img src="<?php echo(\GocContextPath::getPath());
                if($site->getNotify()) {
                    echo('img/tick.png');
                } else {
                    echo('img/cross.png');
                }?>" height="22px" style="vertical-align: middle;" />
            </td>
        </tr>
        </table>
        </div>

        <!--  Project Data -->
        <div class="tableContainer" style="width: 42%; float: right;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Project Data</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/project.png" class="decoration" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">NGI/ROC</td><td class="site_table">
            <a href="index.php?Page_Type=NGI&amp;id=<?php echo($site->getNgi()->getId()) ?>">
                <?php xecho($site->getNgi()->getName()) ?>
            </a>
            </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Infrastructure</td><td class="site_table"><?php xecho($site->getInfrastructure()->getName()) ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Certification Status</td>
                    <td class="site_table">
            <?php if ($showPD) { ?>
                <?php xecho($site->getCertificationStatus()->getName()) ?>
                &nbsp;
                <!--  only show this link if we're in read / write mode -->
                <?php if (!$portalIsReadOnly): ?>
                <a href="index.php?Page_Type=Edit_Certification_Status&amp;id=<?php echo($site->getId()) ?>">Change</a>
                <?php endif; ?>
            <?php } else echo(getInfoMessage()); ?>
                    </td>
                </tr>

                <tr class="site_table_row_2">
                    <?php
            $count = 0;
            $numScopes = sizeof($params['Scopes']);
            $scopeString = '';
            foreach ($params['Scopes'] as $scopeName => $sharedWithParent) {
                if ($sharedWithParent) {
                $scopeString .= $scopeName;
                } else {
                $scopeString .= $scopeName . '(x)';
                }
                if (++$count != $numScopes) {
                $scopeString .= ", ";
                }
            }
            ?>
                    <td class="site_table">
                        <a href="index.php?Page_Type=Scopes" style="word-wrap: normal"
                           title="Note, Scope(x) indicates the parent NGI does not share this scope">
                            Scope Tags
                        </a>
                    </td>
                    <td class="site_table">
            <textarea readonly="true" style="width: 100%; height: 60px;"><?php xecho($scopeString); ?></textarea>
                    </td>
                </tr>

            </table>
        </div>
    </div>

    <!-- Networking and Location -->
    <div style="float: left; width: 100%; margin-top: 3em;">
        <!--  Networking -->
        <div class="tableContainer" style="width: 55%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Networking</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/network.png" class="decoration" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Home URL</td>
            <td class="site_table">
            <?php if ($showPD) { ?>
                <a href="<?php xecho($site->getHomeUrl()) ?>">
                <?php xecho($site->getHomeUrl()) ?>
                </a>
            <?php } else echo(getInfoMessage()); ?>
            </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">GIIS URL</td>
                    <td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getGiisUrl());
            } else
                echo(getInfoMessage());
            ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">IP Range</td>
                    <td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getIpRange());
            } else
                echo(getInfoMessage());
            ?>
                    </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table" style="width:20%">IP v6 Range</td>
                    <td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getIpV6Range());
            } else
                echo(getInfoMessage());
            ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Domain</td>
                    <td class="site_table">
                        <?php
                            if ($showPD) {
                                xecho($site->getDomain());
                            } else
                                echo(getInfoMessage());
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Location Data -->
        <div class="tableContainer" style="width: 42%; float: right;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Location</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/pin.png" class="decoration" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Country</td><td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getCountry()->getName());
            } else {
                echo getInfoMessage();
            }
            ?>
                    </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Latitude</td><td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getLatitude());
            } else {
                echo getInfoMessage();
            }
            ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Longitude</td><td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getLongitude());
            } else {
                echo getInfoMessage();
            }
            ?>
                    </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Time Zone</td><td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getTimezoneId());
            } else {
                echo getInfoMessage();
            }
            ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Location</td><td class="site_table">
            <?php
            if ($showPD) {
                xecho($site->getLocation());
            } else {
                echo getInfoMessage();
            }
            ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!--  Site Properties -->
    <?php
    $parent = $site;
    $propertiesController = "Site_Properties_Controller";
    $addPropertiesPage = "Add_Site_Properties";
    $editPropertyPage = "Edit_Site_Property";


    require_once __DIR__ . '/../fragments/viewPropertiesTable.php';
    ?>

    <!--  Services -->
    <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
        Services (Note, Service scope values marked with (x) indicate the Site does not share that scope)
    </span>
        <img src="<?php echo \GocContextPath::getPath() ?>img/service.png" class="decoration" />

        <table id="servicesTable" class="table table-striped table-condensed tablesorter">
        <thead>
            <tr>
                <th>Hostname (service type)</th>
                <th>URL</th>
                <th>Production</th>
                <th>Monitored</th>
                <th>Scope Tags</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $num = 2;
            foreach ($params['ServicesAndScopes'] as $serviceAndScopes) {
            $se = $serviceAndScopes['Service'];
            $scopes = $serviceAndScopes['Scopes'];
            ?>
                <tr>
                    <td>
                        <a href="index.php?Page_Type=Service&amp;id=<?php echo($se->getId()) ?>">
                            <?php xecho($se->getHostname() . " (" . $se->getServiceType()->getName() . ")"); ?>
                        </a>
                    </td>
                    <td>
                        <textarea readonly="true" style="height: 25px;"><?php xecho((string) $se->getUrl()) ?></textarea>
                    </td>
                    <td>
                        <?php
                        switch ($se->getProduction()) {
                        case true:
                            ?>
                        <img src="<?php echo \GocContextPath::getPath() ?>img/tick.png" height="22px" style="vertical-align: middle;" />
                            <?php
                            break;
                        case false:
                            ?>
                        <img src="<?php echo \GocContextPath::getPath() ?>img/cross.png" height="22px" style="vertical-align: middle;" />
                            <?php
                            break;
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        switch ($se->getMonitored()) {
                        case true:
                            ?>
                        <img src="<?php echo \GocContextPath::getPath() ?>img/tick.png" height="22px" style="vertical-align: middle;" />
                            <?php
                            break;
                        case false:
                            ?>
                        <img src="<?php echo \GocContextPath::getPath() ?>img/cross.png" height="22px" style="vertical-align: middle;" />
                            <?php
                            break;
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        $count = 0;
                        $numScopes = sizeof($scopes);
                        $scopeString = '';
                        foreach ($scopes as $scopeName => $sharedWithParent) {
                        if ($sharedWithParent) {
                            $scopeString .= $scopeName;
                        } else {
                            $scopeString .= $scopeName . '(x)';
                        }
                        if (++$count != $numScopes) {
                            $scopeString .= ", ";
                        }
                        }
                        ?>
                        <textarea readonly="true" style="height: 25px;"><?php xecho($scopeString); ?></textarea>
                        </td>
                    </tr>
                <?php
                } // End of the foreach loop iterating over ServicesAndScopes
                ?>
            </tbody>
        </table>


    <!--  only show this link if we're in read / write mode -->
    <?php if (!$portalIsReadOnly && $params['ShowEdit']) : ?>
        <!-- Add new Service Link -->
        <a href="index.php?Page_Type=Add_Service&amp;siteId=<?php echo($site->getId()); ?>">
            <img src="<?php echo \GocContextPath::getPath() ?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
            <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
            Add Service
            </span>
        </a>
    <?php endif; ?>
    </div>


    <!--  Users -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">

        <?php
            if ($showPD) { ?>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Users (Click on name to manage roles)</span>
                <img src="<?php echo \GocContextPath::getPath() ?>img/people.png" class="decoration" />
                <table id="siteUsersTable" class="table table-striped table-condensed tablesorter">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($params['roles'] as $role) {
                        ?>
                            <tr>
                                <td>
                                    <div style="background-color: inherit;">
                                        <a style="vertical-align: middle;" href="index.php?Page_Type=User&id=<?php echo($role->getUser()->getId()) ?>">
                                            <img src="<?php echo \GocContextPath::getPath()?>img/person.png" class="person" />
                                            <?php xecho($role->getUser()->getFullName()) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        xecho($role->getRoleType()->getName());
                                    ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
        <?php
        } else {
            echo '<span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">';
            echo 'User personal data is hidden (' . getInfoMessage() . ') </span>';
            echo '<img src="' . \GocContextPath::getPath() . 'img/people.png" class="decoration" />';
        }
        ?>

    <!-- Request Role Link -->
    <!--  only show this link if we're in read / write mode -->
    <?php if (!$portalIsReadOnly && $showPD): ?>
        <div style="padding: 1em; padding-left: 1.4em; overflow: hidden;">
            <a href="index.php?Page_Type=Request_Role&amp;id=<?php echo($site->getId()); ?>">
            <img src="<?php echo \GocContextPath::getPath() ?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
            <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                Request Role
            </span>
            </a>
        </div>
    <?php endif; ?>
    </div>

    <!--  Downtimes -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
            Recent Downtimes Affecting <?php xecho($site->getShortName()) ?> Sevices and Endpoints
            <a href="index.php?Page_Type=Site_Downtimes&amp;id=<?php echo($site->getId()); ?>">
                (View all Downtimes)
            </a>
        </span>
        <img src="<?php echo \GocContextPath::getPath() ?>img/down_arrow.png" class="decoration" />

        <table id="siteDowntimesTable" class="table table-striped table-condensed tablesorter">
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
                            <a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo($dt->getId()) ?>">
                            <?php xecho($dt->getDescription()) ?>
                            </a>
                        </td>
                        <td><?php echo($dt->getStartDate()->format('Y-m-d H:i'/*$dt::DATE_FORMAT*/)) ?></td>
                        <td><?php echo($dt->getEndDate()->format('Y-m-d H:i'/*$dt::DATE_FORMAT*/)) ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

    <!--  only show this link if we're in read / write mode -->
    <?php if (!$portalIsReadOnly && $params['ShowEdit']): ?>
        <!-- Add new Downtime Link -->
        <a href="index.php?Page_Type=Add_Downtime&amp;site=<?php echo($site->getId()); ?>">
            <img src="<?php echo \GocContextPath::getPath() ?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
            <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
            Add Downtime
            </span>
        </a>
    <?php endif; ?>
    </div>

    <!-- Show RoleActionRecords if user has permissions over this object -->
    <?php
    if ($params['ShowEdit']) {
        require_once __DIR__ . '/../fragments/viewRoleActionsTable.php';
    }
    ?>

    <!-- Display API Authentication entities for this site -->
    <?php if($params['ShowEdit']):?>
        <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
                Credentials authorised to use the GOCDB read and write APIs (Only shown if you have the relevant permissions)
            </span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/key.png" class="decoration" />
            <table id="AuthenticatedEntities" class="table table-striped table-condensed tablesorter">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Identifier</th>
                        <th>User</th>
                        <th style="text-align:center">API Write</th>
                        <th style="text-align:center">Edit</th>
                        <th style="text-align:center">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    /** @var \APIAuthentication $APIAuthEnt */
                    foreach ($params['APIAuthEnts'] as $APIAuthEnt) {
                        // Finer grain control of edit or delete could be put here
                        // Currently work around pre-5.8 credentials having no owning user.
                        $disableEdit = true;
                        $disableDelete = true;
                        if ($APIAuthEnt->getIdentifier() == Get_User_Principle()) {
                            // If the owning user is making the request, we always allow them to
                            // delete the credential
                            $disableDelete = false;
                        }
                    ?>
                    <tr>
                        <td>
                            <?php xecho($APIAuthEnt->getType())?>
                        </td>
                        <td>
                            <?php xecho($APIAuthEnt->getIdentifier())?>
                        </td>
                        <td>
                            <?php
                            $disableEdit = false;
                            $disableDelete = false;
                            if ($APIAuthEnt->getUser() != null) {
                                // Credentials added prior to 5.8 have no owning user
                                echo "<a href=\"index.php?Page_Type=User&amp;id=", $APIAuthEnt->getUser()->getId(), "\" ";
                                echo "title=\"", $APIAuthEnt->getUser()->getFullname(), "\">";
                                echo substr($APIAuthEnt->getUser()->getSurname(),0,10);
                                echo "</a>";
                            }
                            ?>
                        </td>
                        <td style="width: 8%; text-align:center">
                            <img height="22px" src=
                                <?php if (($APIAuthEnt->getAllowAPIWrite())) {
                                    echo '"'.\GocContextPath::getPath().'img/tick.png"';
                                } else {
                                    echo '"'.\GocContextPath::getPath().'img/cross.png"';
                                } ?>
                            />
                        </td>
                        <td style="width: 8%;"align = "center">
                            <?php if(!$portalIsReadOnly):?>
                                <form action="index.php?Page_Type=Edit_API_Authentication_Entity&amp;authentityid=<?php echo $APIAuthEnt->getId();?>" method="post">
                                    <button type="submit" <?php if ($disableEdit) echo "disabled"; ?>
                                        >Edit</button>
                                </form>
                             <?php endif;?>
                        </td>
                        <td style="width: 8%;"align = "center">
                            <?php if(!$portalIsReadOnly):?>
                                <form action="index.php?Page_Type=Delete_API_Authentication_Entity&amp;authentityid=<?php echo $APIAuthEnt->getId();?>" method="post">
                                    <button type="submit" <?php if ($disableDelete) echo "disabled"; ?>
                                        >Delete</button>
                                </form>
                            <?php endif;?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if (!$portalIsReadOnly): ?>
                <!-- Add new API credential -->
                <a href="index.php?Page_Type=Add_API_Authentication_Entity&amp;parentid=<?php echo $site->getId()?>">
                    <img src="<?php echo \GocContextPath::getPath() ?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                    <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Add API credential
                    </span>
                </a>
            <?php endif; ?>

        </div>
    <?php endif;?>


</div>

<script>
   $(document).ready(function()
    {
    $("#siteDowntimesTable").tablesorter();
    $("#siteUsersTable").tablesorter();

       $("#servicesTable").tablesorter( {
        // pass the headers argument and assing a object
        headers: {
        // assign the third column (we start counting zero)
        2: {
            sorter: false
        },
        3: {
            sorter: false
        }
        }
    });

    // sort on first and second table cols only
        $("#siteExtensionPropsTable").tablesorter({
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

    //register handler for the select/deselect all properties checkbox
    $("#selectAllProps").change(function(){
        $(".propCheckBox").prop('checked', $(this).prop("checked"));
    });

    }
);


</script>
