<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="img/user.png" class="pageLogo" />
    </div>
    <div style="float: left;">
        <h1 style="float: left; margin-left: 0em; padding-bottom: 0.3em;">
            Select a Role for the <?php echo $params['entityType']?>
        </h1>
        <span class="input_name" style="clear: both; float: left;">
            Select Role to Request for <?php echo $params['entityType'].' ['.$params['entityName'].']'?>:</span><br/>
    </div>

    <form   style="clear: both; float: left; margin-top: 1em;" 
            action="index.php?Page_Type=Request_Role" method="post" class="inputForm">    
        <select name="Role_Name_Value">        
        <?php foreach($params['roles'] as $roleName) { ?>
            <option value="<?php echo $roleName ?>"><?php echo $roleName ?></option>
        <?php } ?>
        </select>
        <br/><br/>
        <input type="hidden" name="Object_ID" value="<?php echo $params['objectId'] ?>"/>
        <input type="submit" />
    </form>
</div>