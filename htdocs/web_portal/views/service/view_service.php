<?php
$se = $params['se'];
//throw new \Exception(var_dump(get_class($se)));

//throw new \Exception(var_dump($se));
$parentSiteName = $se->getParentSite()->getName();
$extensionProperties = $se->getServiceProperties();
$seId = $se->getId();
$configService = \Factory::getConfigService();
?>
<div class="rightPageContainer rounded">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath()?>img/service.png" class="pageLogo" />
    </div>
    <div style="float: left; width: 50em;">
        <h1 style="float: left; margin-left: 0em;"><?php xecho('Service: '.$se->getHostname()) ?> - <?php xecho($se->getServiceType()-> getName()) ?></h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;"><?php xecho($se->getDescription()) ?></span>
    </div>

    <!--  Edit link -->
    <!--  only show this link if we're in read / write mode -->
    <?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
        <div style="float: right;">
            <div style="float: right; margin-left: 2em;">
                <a href="index.php?Page_Type=Edit_Service&amp;id=<?php echo $se->getId() ?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" height="25px" style="float: right;" />
                    <br />
                    <br />
                    <span>Edit</span>
                </a>
            </div>
            <div style="float: right;">
                <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                <a onclick="return confirmSubmit()"
                    href="index.php?Page_Type=Delete_Service&id=<?php echo $se->getId() ?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" height="25px" style="float: right; margin-right: 0.4em;" />
                    <br />
                    <br />
                    <span>Delete</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- System and Grid Information -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  System -->
        <div class="tableContainer rounded" style="width: 55%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">System</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/server.png" class="titleIcon"/>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Host name</td><td class="site_table">
                        <?php if ($params['authenticated']) {
                            xecho($se->getHostName());
                        } else echo('PROTECTED - Auth required'); ?>
                    </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">IP Address</td><td class="site_table">
                        <?php if ($params['authenticated']) {
                          xecho($se->getIpAddress());
                        }else echo('PROTECED - Auth required');  ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">IP v6 Address</td><td class="site_table">
                        <?php if ($params['authenticated']) {
                            xecho($se->getIpV6Address());
                        } else echo('PROTECED - Auth required'); ?>
                    </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Operating System</td><td class="site_table">
                        <?php if ($params['authenticated']) {
                            xecho($se->getOperatingSystem());
                        } else echo('PROTECTED - Auth required'); ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Architecture</td><td class="site_table">
                        <?php if ($params['authenticated']) {
                            xecho($se->getArchitecture());
                        } else echo('PROTECTED - Auth required'); ?>
                    </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Contact E-Mail</td><td class="site_table">
                    <?php if (!$params['authenticated']) : ?>
                            PROTECTED - Auth required
                    <?php endif; ?>
                    <?php if ($params['authenticated']) : ?>
                            <?php xecho($se->getEmail()) ?>
                    <?php endif; ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Notifications</td>
                    <td class="site_table">
                        <img src="<?php echo(\GocContextPath::getPath());
                        if($se->getNotify()) {
                            echo('img/tick.png');
                        } else {
                            echo('img/cross.png');
                        }?>" height="22px" style="vertical-align: middle;" />
                    </td>
                </tr>
            </table>
        </div>

        <!--  Grid Information -->
        <div class="tableContainer rounded" style="width: 42%; float: right;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Grid Information</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="titleIcon"/>
            <table style="width: 100%; word-wrap:break-word;
              table-layout: fixed;">
                <tr class="site_table_row_1">
                    <td class="site_table" style="width: 5em;">Host DN</td><td class="site_table">
                        <div style="word-wrap: break-word;">
                                <?php if ($params['authenticated']) {
                                    xecho($se->getDn()) ;
                                } else echo('PROTECTED - Auth required'); ?>
                        </div>
                    </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">URL</td><td class="site_table"><?php xecho($se->getUrl()) ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Parent Site</td>
                    <td class="site_table">
                        <a href="index.php?Page_Type=Site&amp;id=<?php echo $se->getParentSite()->getId() ?>">
                            <?php xecho($se->getParentSite()->getShortName()); ?>
                        </a>
                    </td>
                </tr>
                <!-- scope: remove this for a non-scoping version of view_service -->
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
                        <a href="index.php?Page_Type=Scope_Help" style="word-wrap: normal"
                            title="Note, Scope(x) indicates the parent Site does not share this scope">
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

    <!-- Project Data and hosting Service Groups -->
    <div style="float: left; width: 100%; margin-top: 3em;">
        <!--  Project Data -->
        <div class="tableContainer rounded" style="width: 55%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Project Data</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/project.png" class="titleIcon"/>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Production Level</td>
                    <td class="site_table">
                    <?php
                    switch($se->getProduction() ) {
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
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Beta</td><td class="site_table">
                    <?php
                    switch($se->getBeta()) {
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
                            default:
                                ?>
                                <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="22px" style="vertical-align: middle;" />
                                <?php
                                break;
                        }
                    ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Monitored</td><td class="site_table">
                    <?php
                    switch($se->getMonitored()) {
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
                </tr>
            </table>
        </div>

        <!-- Service Groups -->
        <div class="tableContainer rounded" style="width: 42%; float: right;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Service Groups this Service Belongs To</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/virtualSite.png" class="titleIcon"/>
            <table style="clear: both; width: 100%;">
                <?php
                    $num = 1;
                    foreach($params['sGroups'] as $sg) {
                ?>
                <tr class="site_table_row_<?php echo $num; ?>">
                    <td class="site_table">
                        <a href="index.php?Page_Type=Service_Group&amp;id=<?php echo $sg->getId()?>">
                            <?php xecho($sg->getName()) ?>
                        </a>
                    </td>
                </tr>
                <?php
                        if($num == 1) { $num = 2; } else { $num = 1; }
                    }
                ?>
            </table>
        </div>
    </div>

     <!-- Service Endpoints -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
            <?php
                // Translate "endpoint" based on local configuration
                $epTxt = $configService->getNameMapping('Service','endpoint');
                echo ('Service '.ucfirst($epTxt)."s");
            ?>
            <a href="#" id="serviceEndpointLink" data-toggle="tooltip" data-placement="right"
                title="A Service may define optional <?php echo ucfirst($epTxt) ?> objects which
                model network locations for different service-functionalities
                that can't be described by the main ServiceType and URL alone.">
                    <?php
                        echo ('('.$epTxt.'s?)');
                    ?>
            </a>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/serviceEndpoint.png" class="titleIcon"/>
        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">Name</th>
                <th class="site_table">URL</th>
                <th class="site_table">Interface Name</th>
                <?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
                    <th class="site_table" >Edit</th>
                    <th class="site_table" >Remove</th>
                <?php endif; ?>
            </tr>
            <?php
            $num = 2;
            foreach($se->getEndpointLocations() as $endpoint) {
                ?>

                <tr class="site_table_row_<?php echo $num ?>">
                    <td style="width: 30%;"class="site_table">
                        <a href="index.php?Page_Type=View_Service_Endpoint&amp;id=<?php echo $endpoint->getId() ?>">
                            <?php xecho($endpoint->getName()) ?>
                        </a>
                    </td>
                    <td style="width: 30%;"class="site_table"><?php echo($endpoint->getUrl()); ?></td>
                    <td style="width: 30%;"class="site_table"><?php xecho($endpoint->getInterfaceName()); ?></td>
                    <?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
                        <td style="width: 10%;"align = "center"class="site_table">
                                <a href="index.php?Page_Type=Edit_Service_Endpoint&amp;endpointid=<?php echo $endpoint->getId();?>&amp;serviceid=<?php echo $seId;?>">
                                    <img height="25px" src="<?php echo \GocContextPath::getPath()?>img/pencil.png"/>
                                </a>
                        </td>
                        <td style="width: 10%;"align = "center"class="site_table">
                            <a href="index.php?Page_Type=Delete_Service_Endpoint&amp;endpointid=<?php echo $endpoint->getId();?>&amp;serviceid=<?php echo $seId;?>">
                                <img height="25px" src="<?php echo \GocContextPath::getPath()?>img/trash.png"/>
                            </a>
                        </td>
                    <?php endif; ?>

                </tr>
                <?php
                if($num == 1) { $num = 2; } else { $num = 1; }
            }
            ?>
        </table>
        <!--  only show this link if we're in read / write mode -->
        <?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
            <!-- Add new Service Endpoint -->
            <a href="index.php?Page_Type=Add_Service_Endpoint&amp;se=<?php echo $se->getId();?>">
                <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Add <?php echo( ucfirst($epTxt)) ?>
                </span>
            </a>
        <?php endif; ?>
    </div>



    <!--  Service Properties -->
    <?php
    $parent = $params['se'];
    $propertiesController = "Service_Properties_Controller";
    $addPropertiesPage = "Add_Service_Properties";
    $editPropertyPage = "Edit_Service_Property";

    require_once __DIR__ . '/../fragments/viewPropertiesTable.php';
    ?>

    <!--  Downtimes -->
    <div class="listContainer rounded" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Recent Downtimes</span>
        <a href="index.php?Page_Type=SE_Downtimes&amp;id=<?php echo $se->getId(); ?>" style="vertical-align:middle; float: left; padding-top: 1.3em; padding-left: 1em; font-size: 0.8em;">(View all Downtimes)</a>
        <img src="<?php echo \GocContextPath::getPath()?>img/down_arrow.png" class="decoration" />

        <table id="downtimesTable"  class="table table-striped table-condensed tablesorter">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>From</th>
                    <th>To</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($params['Downtimes'] as $d) {
                ?>
                    <tr>
                        <td>
                            <a style="padding-right: 1em;" href="index.php?Page_Type=Downtime&id=<?php echo $d->getId() ?>">
                                <?php xecho($d->getDescription()) ?>
                            </a>
                        </td>
                        <td><?php echo $d->getStartDate()->format('Y-m-d H:i'/*$d::DATE_FORMAT*/) ?></td>
                        <td><?php echo $d->getEndDate()->format('Y-m-d H:i'/*$d::DATE_FORMAT*/) ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

        <!--  only show this link if we're in read / write mode -->
        <?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
            <!-- Add new Downtime Link -->
            <a href="index.php?Page_Type=Add_Downtime&amp;se=<?php echo $se->getId();?>&amp;site=<?php echo $se->getParentSite()->getId(); ?>">
                <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Add Downtime
                </span>
            </a>
        <?php endif; ?>
    </div>

</div>

 <script type="text/javascript">
    $(document).ready(function() {
        $('#serviceEndpointLink').tooltip();
        $('#extensionsLink').tooltip();

        $('#downtimesTable').tablesorter();
    // sort on first and second table cols only
        $("#serviceExtensionPropsTable").tablesorter({
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
