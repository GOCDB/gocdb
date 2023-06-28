<?php
    $showPD = $params['authenticated'];
    $entityId = $params['ngi']->getId();
?>
<div class="rightPageContainer">
    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/ngi/fullSize/<?php echo $params['ngi']->getName() ?>.jpg" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                NGI: <?php xecho($params['ngi']->getName()) ?>
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;">
            <?php xecho($params['ngi']->getDescription()) ?>
            <br /><br />
            <a style="padding-top: 0.2em;" href="http://www.egi.eu/about/glossary/glossary_N.html">What is an NGI?</a>
        </span>
    </div>

    <!--  Edit NGI link -->
    <!--  only show this link if we're in read / write mode -->
   <?php if(!$params['portalIsReadOnly']):?>
        <div style="float: right;">
            <?php if($params['UserIsAdmin']):?>
                <div style="float: right; margin-left: 2em; text-align:center;">
                    <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                    <a onclick="return confirmSubmit()"
                       href="index.php?Page_Type=Admin_Delete_NGI&amp;id=<?php echo $entityId ?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" class="trash" />
                        <br />
                        <span>Admin<br>Delete</span>
                    </a>
                </div>
            <?php endif; ?>
            <?php if($params['ShowEdit']):?>
                <div style="float: right; margin-left: 2em;">
                    <a href="index.php?Page_Type=Edit_NGI&amp;id=<?php echo $entityId ?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" class="pencil" />
                        <br />
                        <br />
                        <span>Edit</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- NGI Contacts & Projects -->
    <div style="float: left; width: 100%; margin-top: 2em;">
        <!--  Contacts (left) -->
        <div class="tableContainer" style="width: 55%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Contacts</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/contact_card.png" class="titleIcon"/>
                <table style="clear: both; width: 100%; table-layout: fixed;">
                    <tr class="site_table_row_even">
                        <td class="site_table" style="width: 30%">E-Mail</td><td class="site_table">
                            <?php if($showPD) { ?>
                            <a href="mailto:<?php xecho($params['ngi']->getEmail()) ?>">
                                <?php xecho($params['ngi']->getEmail()) ?>
                            </a>
                            <?php } else {echo(getInfoMessage());} ?>
                        </td>
                    </tr>
                    <tr class="site_table_row_odd">
                        <td class="site_table" style="width: 30%">ROD E-Mail</td><td class="site_table">
                            <?php if($showPD) { ?>
                            <a href="mailto:<?php xecho($params['ngi']->getRodEmail()) ?>">
                            <?php xecho($params['ngi']->getRodEmail()) ?>
                            </a>
                            <?php } else {echo(getInfoMessage());} ?>
                        </td>
                    </tr>
                    <tr class="site_table_row_even">
                        <td class="site_table" style="width: 30%">Helpdesk E-Mail</td><td class="site_table">
                            <?php if($showPD) { ?>
                            <a href="mailto:<?php xecho($params['ngi']->getHelpdeskEmail()) ;?>">
                            <?php xecho($params['ngi']->getHelpdeskEmail()) ?>
                            </a>
                            <?php } else {echo(getInfoMessage());} ?>
                        </td>
                    </tr>
                    <tr class="site_table_row_odd">
                        <td class="site_table" style="width: 30%">Security E-Mail</td><td class="site_table">
                            <?php if($showPD) { ?>
                            <a href="mailto:<?php echo $params['ngi']->getSecurityEmail() ?>">
                            <?php xecho($params['ngi']->getSecurityEmail()) ?>
                            </a>
                            <?php } else {echo(getInfoMessage());} ?>
                        </td>
                    </tr>
                    <tr class="site_table_row_even">
                        <td class="site_table" style="width: 30%">GGUS Support Unit</td><td class="site_table">
                            <?php if($showPD) { ?>
                            <?php xecho($params['ngi']->getGgus_Su()) ?>
                            <?php } else {echo(getInfoMessage());} ?>
                        </td>
                    </tr>
                </table>
        </div>

        <!-- Project memberships (Top right) -->
        <div class="tableContainer" style="width: 42%; float: right;" >
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">Project memberships</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/project.png" class="titleIcon"/>
            <table style="clear: both; width: 100%; table-layout: fixed;">
                <?php if(!empty($params['Projects'])) {
                    foreach ($params['Projects'] as $index => $project) { ?>
                        <tr class="site_table_row_<?php
                            echo ($index % 2 == 0) ? 'even' : 'odd' ?>">
                            <td class="site_table">
                                <a href="index.php?Page_Type=Project&amp;id=<?php echo $project->getId()?>"><?php xecho($project->getName())?></a>
                            </td>
                        </tr>
                    <?php } ?>

                <?php } ?>
            </table>
        </div>

        <!-- Scopes (bottom right) -->
        <div class="tableContainer" style="width: 42%; float: right; margin-top: 1.6em" >
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em; word-wrap: normal">
                <a href="index.php?Page_Type=Scopes">Scope Tags</a>
            </span>
            <table style="clear: both; width: 100%; table-layout: fixed;">

                        <tr class="site_table_row_even">
                            <td class="site_table" >
                                <textarea readonly="true" style="width: 100%; height: 60px;"><?php xecho($params['ngi']->getScopeNamesAsString()); ?></textarea>
                            </td>

                        </tr>
            </table>
        </div>

    </div>


    <!--  Sites -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['ngi']->getSites()) ?> Site<?php if(sizeof($params['ngi']->getSites()) != 1) echo "s"?>
        (Note, Scope values marked with (x) indicate the parent NGI does not share that scope)
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/site.png" class="decoration" />

        <table id="sitesTable" class="table table-striped table-condensed tablesorter" >
            <thead>
            <tr>
                <th>Name</th>
                <th>Certification Status</th>
                <th>Production Status</th>
                <th>Scope(s)</th>
            </tr>
            </thead>
            <tbody>
                <?php
                foreach($params['SitesAndScopes'] as $siteAndScopes) {
                    $site = $siteAndScopes['Site'];
                    $scopes = $siteAndScopes['Scopes'];
                ?>
                    <tr>
                        <td>
                            <a href="index.php?Page_Type=Site&amp;id=<?php echo $site->getId() ?>">
                                <?php xecho($site->getShortName()); ?>
                            </a>
                        </td>
                        <td>
                            <?php xecho($site->getCertificationStatus()->getName()) ?>
                        </td>
                        <td>
                            <?php xecho($site->getInfrastructure()->getName()) ?>
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
                } // End of the foreach loop iterating over SitesAndScopes
                ?>
            </tbody>
        </table>
    </div>

    <!--  Users and Roles -->
    <div class="listContainer">
        <?php if ($showPD) { ?>
        <span class="header listHeader">
           Users (Click on name to manage roles)
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/user.png" class="decoration" />
        <table id="usersTable" class="table table-striped table-condensed tablesorter" >
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($params['roles'] as $role) {
                ?>
                    <tr>
                        <td>
                                <a href="index.php?Page_Type=User&amp;id=<?php echo $role->getUser()->getId() ?>">
                                    <img src="<?php echo \GocContextPath::getPath()?>img/person.png" class="person" />
                                    <?php xecho($role->getUser()->getFullName()) ?>
                                </a>
                        </td>

                            <td> <?php xecho($role->getRoleType()->getName()); ?> </td>
                    </tr>
                <?php
                    } // End of the foreach loop iterating over roles
                ?>
            </tbody>
        </table>
        <?php
            } else {
                require_once __DIR__.'/../fragments/hidePersonalData.php';
            }
        ?>
        <!-- Request Role Link -->
        <?php if (!$params['portalIsReadOnly']) {
            require_once __DIR__.'/../fragments/requestRole.php';
        } ?>
    </div>

    <!-- Show RoleActionRecords if user has permissions over this NGI -->
    <?php if ($params['ShowEdit']){
        require_once __DIR__ . '/../fragments/viewRoleActionsTable.php';
    } ?>


</div>

<script>
    $(document).ready(function()
    {

    $("#sitesTable").tablesorter();
    $("#usersTable").tablesorter();
    // sort on first and second table cols only
//	$("#sitesTable").tablesorter({
//	    // pass the headers argument and assing a object
//	    headers: {
//		// assign the third column (we start counting zero)
//		2: {
//		    sorter: false
//		},
//		3: {
//		    sorter: false
//		}
//	    }
//	});

    });
</script>
