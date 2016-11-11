<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath()?>img/user.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em; padding-bottom: 0.3em;">
            Select a Role for the <?php xecho($params['entityType'])?>
        </h1>
        <span class="input_name" style="clear: both; float: left;">
            Select Role to Request for <?php xecho($params['entityType']); echo ' ['; xecho($params['entityName']); echo']'?>:
        </span><br/>
    </div>

    <form   style="clear: both; float: left; margin-top: 1em;"
            action="index.php?Page_Type=Request_Role" method="post" class="inputForm">
        <select name="Role_Name_Value">
        <?php foreach($params['roles'] as $roleName) { ?>
            <option value="<?php xecho($roleName) ?>"><?php xecho($roleName) ?></option>
        <?php } ?>
        </select> : [RoleTypeName]
        <br/><br/>
        <input type="hidden" name="Object_ID" value="<?php echo $params['objectId'] ?>"/>
        <input type="submit" />
    </form>
</div>