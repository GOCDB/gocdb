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
        <?php if($params['ShowEdit']):?>
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
                <a onclick="return confirmSubmit()"
                    href="index.php?Page_Type=Delete_User&id=<?php echo $params['user']->getId() ?>">
                    <img src="<?php echo \GocContextPath::getPath()?>img/trash.png" height="25px" style="float: right; margin-right: 0.4em;" />
                    <br />
                    <br />
                    <span>Delete</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    }
    ?>

    <div style="float: left; width: 100%; margin-top: 2em;">
         <div class="alert alert-warning" role="alert">
            <ul>
              <li>By registering a GOCDB account you have agreed to abide by the <a href="/aup.html" target="_blank" title="opens in new window">GOCDB Acceptable Use Policy and Conditions of Use <img src="/portal/img/new_window.png" alt="new window logo" class="new_window"></a>.</li>
              <li>Personal data, which you provide and is collected when you use GOCDB, is processed in accordance with the <a href="/privacy.html" target="_blank" title="opens in new window">GOCDB Privacy Notice <img src="/portal/img/new_window.png" alt="new window logo"  class="new_window"></a>.</li>
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
                    <td class="site_table">Identity String</td>
                    <td class="site_table">
                        <div style="word-wrap: break-word;">
                            <?php xecho($params['user']->getCertificateDn()) ?>
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



    <div class="listContainer">
      <span class="header" style="vertical-align:middle; float: left; padding-top: 0.9em; padding-left: 1em;">
        User Authentication Details
      </span>
      <table style="clear: both; width: 100%;">
        <tr class="site_table_row_1">
          <td class="site_table">
            <?php
              foreach ($params['authAttributes'] as $key => $val) {
                $attributeValStr = '';
                foreach ($val as $v) {
                  $attributeValStr .= ', '.$v;
                }
                if(strlen($attributeValStr) > 2){$attributeValStr = substr($attributeValStr, 2);}
                xecho($key . ': ' . $attributeValStr);
                echo '<br>';
              }
            ?>
          </td>
        </tr>
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
                    <th class="site_table">Revoke Role</th>
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
                <td class="site_table">
                    <?php if(!$params['portalIsReadOnly'] && $role->getDecoratorObject() != null):?>
                        <form action="index.php?Page_Type=Revoke_Role" method="post">
                            <input type="hidden" name="id" value="<?php echo $role->getId()?>" />
                            <input id="revokeButton" type="submit" value="Revoke" class="btn btn-sm btn-danger" onclick="return confirmSubmit()"
                                   title="Your roles allowing revoke: <?php xecho($role->getDecoratorObject()); ?>" >
                        </form>
                    <?php endif;?>
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
                                   title="Your roles allowing revoke: <?php xecho($role->getDecoratorObject()); ?>" >
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

</div>

 <script type="text/javascript">
    //$(document).ready(function() {
    //    $('#revokeButton').tooltip();
    //});
</script>
