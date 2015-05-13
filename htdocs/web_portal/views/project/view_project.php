<?php $ngiCount = sizeof($params['NGIs']) ?>
<script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
<!-- onclick="return confirmSubmit()" -->
<div class="rightPageContainer">
<!--    <div style="float: left;">
        <img src="<?php echo \GocContextPath::getPath()?>img/project.png" class="pageLogo" />
    </div>-->
    
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em;">
                Project: <?php xecho($params['Name'])?>
        </h1>
        <span style="clear: both; float: left; padding-bottom: 0.4em;"><?php xecho($params['Description'])?></span>
    </div>

    <!-- Only show edit/delete in read/write mode -->
    <?php if(!$params['portalIsReadOnly']):?>
        <div style="float: right;">
            <div style="float: right; margin-left: 2em;">
                <?php if($params['ShowEdit']){?>
                    <a href="index.php?Page_Type=Edit_Project&id=<?php echo $params['ID']?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" height="25px" style="float: right;" />
                        <br />
                        <br />
                        <span>Edit</span>
                    </a>
                <?php } ?>
            </div>
            <div style="float: right;">
                <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                <?php if($params['ShowEdit']){?>
                    <a onclick="return confirmSubmit()"
                        href="index.php?Page_Type=Delete_Project&id=<?php echo $params['ID']?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="25px" style="float: right; margin-right: 0.4em;" />
                        <br />
                        <br />
                        <span>Delete</span>
                    </a>
                <?php } ?>
            </div>
        </div>
    <?php endif; ?>

    <!--  NGIs -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
            This project <?php if ($ngiCount == 0){echo "has no";} else{echo "consists of " . $ngiCount;} ?> NGI<?php if($ngiCount != 1) echo "s"?>
        </span>   
        <img src="<?php echo \GocContextPath::getPath()?>img/ngi.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        
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
                                <a href="index.php?Page_Type=NGI&id=<?php echo $ngi->getId() ?>">
                                    <img class="flag" style="vertical-align: middle" src="<?php echo \GocContextPath::getPath()?>img/ngi/<?php xecho($ngi->getName()) ?>.jpg">                            
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
        <!-- Don't show link in read only mode -->
        <?php if(!$params['portalIsReadOnly']):?>
            <!-- Add NGI link -->
            <?php if($params['ShowEdit']){?>
                <a href="index.php?Page_Type=Add_Project_NGIs&id=<?php echo $params['ID'];?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                    <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                            Add NGIs
                    </span>
                </a>
            <?php } ?>
        
            <?php if ($ngiCount > 0): ?> 
                <!-- Remove NGI Link -->
                <?php if($params['ShowEdit']){?>
                    <a href="index.php?Page_Type=Remove_Project_NGIs&id=<?php echo $params['ID'];?>">
                        <img src="<?php echo \GocContextPath::getPath()?>img/cross.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                        <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                                Remove NGIs
                        </span>
                    </a>
                <?php } ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    
    <!-- Roles -->
    <div class="tableContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
            Users (Click on name to manage roles)
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/people.png" height="25px" style="float: right; padding-right: 1em; padding-top: 0.5em; padding-bottom: 0.5em;" />
        <?php if (sizeof($params['Roles'])>0): ?>
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Role</th>
                    <!-- don't show revoke in read only mode -->
                    <!-- Note, COMMENTED OUT below -->
                    <!--<?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']):?>
                        <th class="site_table">Revoke</th>
                    <?php endif; ?>-->
                </tr>
                <?php
                    $num = 2;
                    foreach($params['Roles'] as $role) {
                ?>
                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <img src="<?php echo \GocContextPath::getPath()?>img/person.png" style="vertical-align: middle; padding-right: 1em;" />
                            <a style="vertical-align: middle;" href="index.php?Page_Type=User&id=<?php echo $role->getUser()->getId()?>">
                                <?php echo $role->getUser()->getFullName()/*.' ['.$role->getUser()->getId().']' */?>
                            </a>
                        </div>
                    </td>
                    <td class="site_table">
                        <?php xecho($role->getRoleType()->getName()) ?>
                    </td>
                    <!-- don't show revoke in read only mode -->
                    <!-- Note, COMMENTED OUT below -->
                    <!--<?php if(!$params['portalIsReadOnly'] && $params['ShowEdit']):?>    
                        <td class="site_table"><a href="index.php?Page_Type=Revoke_Role&id=<?php echo $role->getId()?>" onclick="return confirmSubmit()">Revoke</a></td>
                    <?php endif; ?>-->
                </tr>
                <?php
                    if($num == 1) { $num = 2; } else { $num = 1; }
                    } // End of the foreach loop iterating over user roles
                ?>
            </table>
        <?php else: echo "<br><br>&nbsp &nbsp There are currently no users with roles over this project<br>"; endif; ?>
        <!-- don't allow role requests in read only mode -->
        <?php if(!$params['portalIsReadOnly'] && $params['authenticated']):?>
            <a href="index.php?Page_Type=Request_Role&id=<?php echo $params['ID'];?>">
                <img src="<?php echo \GocContextPath::getPath()?>img/add.png" height="50px" style="float: left; padding-top: 0.9em; padding-left: 1.2em; padding-bottom: 0.9em;"/>
                <span class="header" style="vertical-align:middle; float: left; padding-top: 1.1em; padding-left: 1em; padding-bottom: 0.9em;">
                        Request Role
                </span>
            </a>
        <?php endif; ?>

    </div>

    <!--  Sites -->
    <div class="listContainer">
        <span class="header listHeader">
            <?php echo sizeof($params['Sites']) ?> Site<?php if(sizeof($params['Sites']) != 1) echo "s"?>
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/site.png" class="decoration" />
        <?php if(sizeof($params['Sites']) > 0): ?>
            <table class="vSiteResults" id="selectedSETable">
                <tr class="site_table_row_1">
                    <th class="site_table">Name</th>
                    <th class="site_table">Certification Status</th>
                    <th class="site_table">NGI</th>
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
                                <a href="index.php?Page_Type=Site&id=<?php echo $site->getId() ?>">
                                    <span>&nbsp;&nbsp;</span><?php xecho($site->getShortName()); ?>
                                </a>
                            </span>
                        </div>
                    </td>

                    <td class="site_table">
                        <?php xecho($site->getCertificationStatus()->getName()) ?>
                    </td>
                    
                    <td class="site_table">
                        <a href="index.php?Page_Type=NGI&id=<?php echo $site->getNGI()->getId() ?>">
                            <?php xecho($site->getNGI()->getName()) ?>
                        </a>
                    </td>

                    <td class="site_table">
                        <?php xecho($site->getInfrastructure()->getName()) ?>
                    </td>
                </tr>
                <?php if($num == 1) { $num = 2; } else { $num = 1; }}?>
            </table>
        <?php endif; // End of the foreach loop iterating over sites?>

    <!-- Show RoleActionRecords if user has permissions over this object -->
    <?php if ($params['ShowEdit']){
        require_once __DIR__ . '/../fragments/viewRoleActionsTable.php'; 
    } ?>
            
    </div>