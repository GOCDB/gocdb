<script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
<!-- onclick="return confirmSubmit()" -->
<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath()?>img/user.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em; padding-bottom: 0.3em;">
            <?php xecho($params['user']->getFullName()) ?>
        </h1>
    </div>

    <!--  Edit User link -->
    <!--  only show this link if we're in read / write mode -->
    <?php
    if(!$params['portalIsReadOnly']) {
    ?>
    <div style="float: right;">
        <?php if ($params['ShowEdit']):?>
            <div style="float: right; margin-left: 2em;">
                <a href="index.php?Page_Type=Edit_User&amp;id=<?php echo $params['user']->getId()?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/pencil.png" height="25px" style="float: right;" />
                    <br />
                    <br />
                    <span>Edit</span>
                </a>
            </div>
            <div style="float: right;">
                <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>
                <?php
                    # If the user owns any API Credentials we grey and disable the delete-user icon
                    $opacity = "1.0";
                    if (!$params['APIAuthEnts']->isEmpty()) {
                        $opacity = "0.5";
                    }
                    echo '<div style="opacity: '. $opacity . '" >';
                        echo '<a ';
                            if ($params['APIAuthEnts']->isEmpty()) {
                                echo ' onclick="return confirmSubmit()"';
                                echo ' href="index.php?Page_Type=Delete_User&id='. $params['user']->getId() . '"';
                            } else {
                                echo ' title="Delete disabled: Remove API credentials before deleting this user."';
                            }
                        echo ' >'; # <a ...
                            echo '<img src="' . \GocContextPath::getPath() .'img/trash.png" height="25px" style="float: right; margin-right: 0.4em;" />';
                            echo '<br /><br /><span>Delete</span>';
                        echo '</a>';
                    echo '</div>';

                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    }
    ?>

    <div style="float: left; width: 100%; margin-top: 2em;">
         <div class="alert alert-warning" role="alert">
            <ul>
              <li>By registering a GOCDB account you have agreed to abide by the
                <?php require __DIR__ . '/../fragments/aupLink.php'; ?>
              </li>
              <li>Personal data, which you provide and is collected when you use GOCDB, is processed in accordance with the
                <?php require __DIR__ . '/../fragments/privacyNoticeLink.php'; ?>
              </li>
            </ul>
        </div>


        <!--  User -->
        <div class="tableContainer" style="width: 55%; float: left;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">User Details</span>
            <img src="<?php echo \GocContextPath::getPath()?>img/contact_card.png" class="decoration" />
            <table style="clear: both; width: 100%; table-layout: fixed;">
                <tr class="site_table_row_1">
                    <td class="site_table" style="width: 30%">E-Mail</td><td class="site_table">
            <a href="mailto:<?php xecho($params['user']->getEmail()); ?>">
                <?php xecho($params['user']->getEmail()) ?>
            </a>
            </td>
                </tr>
                <tr class="site_table_row_2">
                    <td class="site_table">Telephone</td>
                    <td class="site_table">
                        <?php xecho($params['user']->getTelephone()) ?>
                    </td>
                </tr>
                <tr class="site_table_row_1">
                    <td class="site_table">Default ID String</td>
                    <td class="site_table">
                        <div style="word-wrap: break-word;">
                            <?php xecho($params['idString']) ?>
                        </div>
                    </td>
                </tr>
                <!-- Comment out for now -->
                <!--<tr class="site_table_row_2">
                    <td class="site_table">EGI SSO Username</td>
                    <td class="site_table">
                        <div style="word-wrap: break-word;">
                            <?php
                            //if($params['user']->getusername1() != null){
                            //    echo  'Should this be shown? - TBC'; //$params['user']->getusername1();
                            //} else {
                            //    echo 'Not known';
                            //}
                            ?>
                        </div>
                    </td>
                </tr>-->
                <?php if(sizeof($params['user']->getHomeSite()) != 0) { ?>
                    <tr class="site_table_row_2">
                        <td class="site_table">Home Site</td>
                        <td class="site_table">
                            <a href="index.php?Page_Type=Site&amp;id=<?php echo $params['user']->getHomeSite()->getId()?>">
                                <?php xecho($params['user']->getHomeSite()->getShortName()) ?>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <!-- ID strings from user identifiers -->
    <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
            Identifiers
        </span>

        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">ID String</th>
                <th class="site_table">Authentication type</th>
                <?php if (!$params['portalIsReadOnly'] && $params['ShowEdit']):?>
                    <th class="site_table">Remove Identifier</th>
                <?php endif; ?>
            </tr>
            <?php
            $num = 2;
            // Loop through each user identifier
            foreach ($params['user']->getUserIdentifiers() as $identifier): ?>

                <tr class="site_table_row_<?php echo $num ?>">
                    <td class="site_table" style="width: 40%">
                        <div style="background-color: inherit;">
                            <?php xecho($identifier->getKeyValue())?>
                        </div>
                    </td>
                    <td class="site_table">
                        <div style="background-color: inherit;">
                            <?php xecho($identifier->getKeyName())?>
                        </div>
                    </td>
                    <?php if (!$params['portalIsReadOnly'] && $params['ShowEdit']):?>
                        <td class="site_table">
                            <form action="index.php?Page_Type=Remove_User_Identifier&amp;id=<?php echo $params['user']->getId(); ?>&amp;identifierId=<?php echo $identifier->getId(); ?>" method="post">
                                <div class="btn-like"
                                    <?php if ($params['lastIdentifier']) echo "title='Cannot remove all identifiers from a user'";?>
                                    <?php if ($params['currentIdString'] === $identifier->getKeyValue()) echo "title='Cannot remove the identifier you are using'";?>
                                >
                                    <input
                                        id="revokeButton" type="submit" value="Remove" class="btn btn-sm btn-danger" onclick="return confirmSubmit()"
                                        <?php if ($params['lastIdentifier'] || $params['currentIdString'] === $identifier->getKeyValue()) echo "disabled";?>
                                    >
                                </div>
                            </form>
                        </td>
                    <?php endif;?>
                </tr>
                <?php if ($num == 1) { $num = 2; } else { $num = 1; }
            endforeach;?>
        </table>
    </div>

    <div style="float: left; width: 100%; margin-top: 2em;" class="alert alert-info" role="alert">
    See the <a href="index.php?Page_Type=View_Role_Action_Mappings">role action mappings</a> page to see which permissions are granted by which roles.
    </div>

    <!-- Roles per Project -->
    <?php foreach($params['projectNamesIds'] as $projId => $projName){ ?>
    <div class="listContainer" style="width: 99.5%; float: left; margin-top: 1em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
        Roles in Project
        <a href="index.php?Page_Type=Project&amp;id=<?php echo $projId ?>">
        [<?php xecho($projName) ?>]
        </a>
    </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/people.png" class="decoration" />


        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">Role Type <!--[roleId] --></th>
                <th class="site_table">Held Over</th>
                <?php if(!$params['portalIsReadOnly']):?>
                    <th class="site_table" style="width: 8em; text-align:center">Revoke Role</th>
                    <th><!--Disabled revoke button message--></th>
                <?php endif; ?>
            </tr>
            <?php
            $num = 2;
            foreach($params['role_ProjIds'] as $role_ProjIds ) { // foreach role
        $role = $role_ProjIds[0];
        $projIds = $role_ProjIds[1];

        if(in_array($projId, $projIds)){ // if projId in array
            ?>
            <tr class="site_table_row_<?php echo $num ?>">
                <td class="site_table" style="width: 40%">
                    <div style="background-color: inherit;">
                        <img src="<?php echo \GocContextPath::getPath()?>img/person.png" class="person" />
                            <?php xecho($role->getRoleType()->getName())/*.' ['.$role->getId().']'*/ ?>
                    </div>
                </td>
                <td class="site_table">
                    <?php
                    if($role->getOwnedEntity() instanceof \Site) {?>
                        <a style="vertical-align: middle;" href="index.php?Page_Type=Site&id=<?php echo $role->getOwnedEntity()->getId()?>">
                        <?php xecho($role->getOwnedEntity()->getShortName().' [Site]')?>
                    </a>
                    <?php } ?>

                    <?php
                    if($role->getOwnedEntity() instanceof \NGI) {?>
                        <a style="vertical-align: middle;" href="index.php?Page_Type=NGI&id=<?php echo $role->getOwnedEntity()->getId()?>">
                            <?php xecho($role->getOwnedEntity()->getName().' [NGI]')?>
                        </a>
                    <?php } ?>

                    <?php
                    if($role->getOwnedEntity() instanceof \ServiceGroup) {?>
                        <a style="vertical-align: middle;" href="index.php?Page_Type=Service_Group&id=<?php echo $role->getOwnedEntity()->getId()?>">
                            <?php xecho($role->getOwnedEntity()->getName().' [ServiceGroup]')?>
                        </a>
                    <?php } ?>

                    <?php
                    if($role->getOwnedEntity() instanceof \Project) {?>
                       <a style="vertical-align: middle;" href="index.php?Page_Type=Project&id=<?php echo $role->getOwnedEntity()->getId()?>">
                           <?php xecho($role->getOwnedEntity()->getName().' [Project]');?>
                       </a>
                    <?php } ?>
                </td>
                <td class="site_table" style="text-align:center">
                    <?php
                        $decorator = $role->getDecoratorObject();
                        if(!$params['portalIsReadOnly'] && $decorator != null) {
                            echo '<form action="index.php?Page_Type=Revoke_Role" method="post">';
                                echo "<input type='hidden' name='id' value='{$role->getId()}'/>";
                                echo "<input id='revokeButton' type='submit' {$decorator["revokeButton"]} " .
                                        "value='Revoke' class='btn btn-sm btn-danger' onclick='return confirmSubmit()' " .
                                        "title='Your roles allowing revoke: {$decorator['revokeMessage']}'/>";
                            echo '</form>';
                        };
                    ?>
                </td>
                <td class="site_table" style="width: 26%; padding-left:0em">
                    <?php
                        if(!$params['portalIsReadOnly'] &&
                            is_array($decorator) && $decorator["revokeButton"] == 'disabled') {
                                echo 'Remove or reassign API credentials from site(s) before revoking this role. ';
                                echo 'Sites: ' . $decorator["revokeMessage"];
                        }
                    ?>
                </td>
            </tr>
            <?php
              if($num == 1) { $num = 2; } else { $num = 1; }

          } // if projId in array
        } // foreach role
            ?>
        </table>
    </div>
    <?php } // foreach project ?>



    <!-- Roles NOT in Project, e.g. ServiceGroup roles -->
    <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
        <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
        Project Agnostic Roles (Selected roles may be over objects with no ancestor Project, e.g. ServiceGroups)
    </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/people.png" class="decoration" />


        <table style="clear: both; width: 100%;">
            <tr class="site_table_row_1">
                <th class="site_table">Role Type <!--[roleId] --></th>
                <th class="site_table">Held Over</th>
                <?php if(!$params['portalIsReadOnly']):?>
                    <th class="site_table">Revoke Role</th>
                <?php endif; ?>
            </tr>
            <?php
            $num = 2;
            foreach($params['role_ProjIds'] as $role_ProjIds ) { // foreach role
        $role = $role_ProjIds[0];
        $projIds = $role_ProjIds[1];

        if(count($projIds) == 0){ // role with no owning proj
            ?>
            <tr class="site_table_row_<?php echo $num ?>">
                <td class="site_table" style="width: 40%">
                    <div style="background-color: inherit;">
                        <img src="<?php echo \GocContextPath::getPath()?>img/person.png" height="25px" style="vertical-align: middle; padding-right: 1em;" />
                            <?php xecho($role->getRoleType()->getName())/*.' ['.$role->getId().']'*/ ?>
                    </div>
                </td>
                <td class="site_table">
                    <?php
                    if($role->getOwnedEntity() instanceof \Site) {?>
                        <a style="vertical-align: middle;" href="index.php?Page_Type=Site&id=<?php echo $role->getOwnedEntity()->getId()?>">
                        <?php xecho($role->getOwnedEntity()->getShortName().' [Site]')?>
                    </a>
                    <?php } ?>

                    <?php
                    if($role->getOwnedEntity() instanceof \NGI) {?>
                        <a style="vertical-align: middle;" href="index.php?Page_Type=NGI&id=<?php echo $role->getOwnedEntity()->getId()?>">
                            <?php xecho($role->getOwnedEntity()->getName().' [NGI]')?>
                        </a>
                    <?php } ?>

                    <?php
                    if($role->getOwnedEntity() instanceof \ServiceGroup) {?>
                        <a style="vertical-align: middle;" href="index.php?Page_Type=Service_Group&id=<?php echo $role->getOwnedEntity()->getId()?>">
                            <?php xecho($role->getOwnedEntity()->getName().' [ServiceGroup]')?>
                        </a>
                    <?php } ?>

                    <?php
                    if($role->getOwnedEntity() instanceof \Project) {?>
                       <a style="vertical-align: middle;" href="index.php?Page_Type=Project&id=<?php echo $role->getOwnedEntity()->getId()?>">
                           <?php xecho($role->getOwnedEntity()->getName().' [Project]');?>
                       </a>
                    <?php } ?>
                </td>
                <td class="site_table">
                    <?php if(!$params['portalIsReadOnly'] && $role->getDecoratorObject() != null):?>
                        <form action="index.php?Page_Type=Revoke_Role" method="post">
                            <input type="hidden" name="id" value="<?php echo $role->getId()?>" />
                            <input id="revokeButton" type="submit" value="Revoke" class="btn btn-sm btn-danger" onclick="return confirmSubmit()"
                                   title="Your roles allowing revoke: <?php xecho($decorator); ?>" >
                        </form>
                    <?php endif;?>
                </td>

            </tr>
            <?php
              if($num == 1) { $num = 2; } else { $num = 1; }

          } // end role with no owning proj
        } // end foreach role
            ?>
        </table>
    </div>
    <?php if (!$params['APIAuthEnts']->isEmpty()) { ?>
        <!-- API credentials -->
        <div class="listContainer" style="width: 99.5%; float: left; margin-top: 3em; margin-right: 10px;">
            <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
                Owned API Credentials (Add and remove credentials by clicking the relevant Site.)
            </span>
            <img src="<?php echo \GocContextPath::getPath()?>img/key.png" class="decoration" />
            <table style="clear: both; width: 100%;">
                <tr class="site_table_row_1">
                    <th class="site_table">Type</th>
                    <th class="site_table">Identifier</th>
                    <th class="site_table">Site</th>
                    <th class="site_table" style="width:10%">API Write</th>
                </tr>
                <?php
                    foreach ($params['APIAuthEnts'] as $APIAuthEnt) { ?>
                        <tr>
                            <td class="site_table"><?php xecho($APIAuthEnt->getType());?></td>
                            <td class="site_table"><?php xecho($APIAuthEnt->getIdentifier());?></td>
                            <td class="site_table">
                                <a href="index.php?Page_Type=Site&amp;id=<?php xecho($APIAuthEnt->getParentSite()->getId())?>"
                                    title="<?php xecho($APIAuthEnt->getParentSite()->getShortName())?>">
                                    <?php xecho(substr($APIAuthEnt->getParentSite()->getShortName(),0,16));?>
                                </a>
                            </td>
                            <td class="site_table" style="width: 8%; text-align:center">
                                <img height="22px" src=
                                    <?php if (($APIAuthEnt->getAllowAPIWrite())) {
                                        echo '"'.\GocContextPath::getPath().'img/tick.png"';
                                    } else {
                                        echo '"'.\GocContextPath::getPath().'img/cross.png"';
                                    } ?>
                                />
                            </td>
                        </tr>
                <?php
                    } ?>
            </table>
        </div>
    <?php } ?>
</div>

 <script type="text/javascript">
    //$(document).ready(function() {
    //    $('#revokeButton').tooltip();
    //});
</script>

<style>
    div.btn-like {
        padding: 0px 0px;
        display: inline-block;
        margin-bottom: 0;
        font-size: 12px;
    }
</style>
