<?php
$endpoint = $params['endpoint'];
$se = $endpoint->getService();
$extensionProperties = $endpoint->getEndpointProperties();
$epId = $endpoint->getId();
$seId = $se->getId();
$epTxt = \Factory::getConfigService()->getNameMapping('Service','endpoint');
?>

<div class="rightPageContainer rounded">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/serviceEndpoint.png" class="pageLogo" />
    </div>
    <div style="float: left; width: 50em;">
        <h1 style="float: left; margin-left: 0em;"><?php xecho('Service '.ucfirst($epTxt).': '. $endpoint->getName()) ?> </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
        <?php xecho($endpoint->getDescription()) ?>
        </span>
    </div>


    <!--  Edit link -->
    <!--  only show this link if we're in read / write mode -->
    <?php if (!$params['portalIsReadOnly'] && $params['ShowEdit']): ?>
        <div style="float: right;">
        <div style="float: right; margin-left: 2em;">
            <a href="index.php?Page_Type=Edit_Service_Endpoint&amp;endpointid=<?php echo $endpoint->getId(); ?>&amp;serviceid=<?php echo $seId; ?>">
            <img src="<?php echo \GocContextPath::getPath() ?>img/pencil.png" height="25px" style="float: right;" />
            <br />
            <br />
            <span>Edit</span>
            </a>
        </div>
        <div style="float: right;">
            <script type="text/javascript" src="<?php echo \GocContextPath::getPath() ?>javascript/confirm.js"></script>
            <a onclick="return confirmSubmit()"
               href="index.php?Page_Type=Delete_Service_Endpoint&amp;endpointid=<?php echo $endpoint->getId(); ?>&serviceid=<?php echo $seId; ?>">
            <img src="<?php echo \GocContextPath::getPath() ?>img/trash.png" height="25px" style="float: right; margin-right: 0.4em;" />
            <br />
            <br />
            <span>Delete</span>
            </a>
        </div>
        </div>
    <?php endif; ?>

    <!-- Parent Service Information -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  System -->
        <div class="tableContainer rounded" style="width: 100%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Parent Service</span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/service.png" class="titleIcon"/>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Name</td><td class="site_table">
            <a href="index.php?Page_Type=Service&amp;id=<?php echo $se->getId() ?>">
                <?php xecho($se->getHostname() . " (" . $se->getServiceType()->getName() . ")"); ?>
            </a>
                    </td>
                </tr>
            </table>
        </div>
    </div>


    <!-- Endpoint Information -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  System -->
        <div class="tableContainer rounded" style="width: 100%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;"><?php echo(ucfirst($epTxt)) ?></span>
            <img src="<?php echo \GocContextPath::getPath() ?>img/serviceEndpoint.png" class="titleIcon"/>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <td class="site_table">Name</td><td class="site_table"><?php xecho($endpoint->getName()) ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Description</td><td class="site_table"><?php xecho($endpoint->getDescription()) ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Url</td><td class="site_table"><?php xecho($endpoint->getUrl()) ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Interface Name</td><td class="site_table"><?php xecho($endpoint->getInterfaceName()) ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Id</td><td class="site_table"><?php echo $endpoint->getId() ?></td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Contact E-mail</td><td class="site_table"><?php echo $endpoint->getEmail() ?></td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Monitored</td>
                    <td class="site_table">
                        <?php
                        if($endpoint->getMonitored()) {
                        ?>
                            <img src="<?php echo \GocContextPath::getPath()?>img/tick.png" height="22px" style="vertical-align: middle;" />
                        <?php
                        } else {
                        ?>
                            <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="22px" style="vertical-align: middle;" />
                        <?php
                        }
                        ?>
                    </td>
                </tr>

            </table>
        </div>
    </div>

    <div style="float: left; width: 100%; margin-top: 2em;">
    More (GLUE2) attributes can be added on request - please contact gocdb developers.
    </div>

    <!-- Extension Properties -->
    <?php
    $parent = $params['endpoint'];
    $propertiesController = "Endpoint_Properties_Controller";
    $addPropertiesPage = "Add_Endpoint_Properties";
    $editPropertyPage = "Edit_Endpoint_Property";


    require_once __DIR__ . '/../fragments/viewPropertiesTable.php';
    ?>

    <script type="text/javascript">
    $(document).ready(function () {

        // sort on first and second table cols only
        $("#endpointExtensionPropsTable").tablesorter({
        // pass the headers argument and passing a object
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
