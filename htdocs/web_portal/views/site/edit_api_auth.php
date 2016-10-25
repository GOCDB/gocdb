<div class="rightPageContainer">
    <h1>Edit API credential for <?php xecho($params['site']->getName());?></h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Edit_API_Authentication_Entity&parentid=<?php echo($params['site']->getId())?>&authentityid=<?php echo($params['authEnt']->getId())?>" name="addAPIAuthenticationEntity">
        <span class="input_name">Identifier (e.g. Certificate DN)*</span>
        <input type="text" value="<?php xecho($params['authEnt']->getIdentifier()) ?>" name="IDENTIFIER" class="input_input_text">
        <br />
        <span class="input_name">Credential type*</span>
        <select name="TYPE" class="input_input_text">
            <?php foreach($params['authTypes'] as $authType) {?>
                <option value="<?php xecho($authType) ?>"<?php if ($params['authEnt']->getType() == $authType) {echo " selected=\"selected\"";} ?>>
                    <?php xecho($authType) ?>
                </option>
            <?php } ?>
        </select>
        <br />
        <input type="submit" value="Edit credential" class="input_button">
    </form>
</div>
