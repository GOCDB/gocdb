<div class="rightPageContainer">
    <h1>Add new API credential to <?php xecho($params['site']->getName());?></h1>
    <br />
    <b>Caution: it is possible to delete information using the write functionality of the API.</b>
    <br/>
    <form class="inputForm" method="post" action="index.php?Page_Type=Add_API_Authentication_Entity&parentid=<?php echo($params['site']->getId());?>" name="addAPIAuthenticationEntity">
        <span class="input_name">Identifier (e.g. Certificate DN or OIDC Subject)*</span>
        <input type="text" value="" name="IDENTIFIER" class="input_input_text">
        <br />
        <span class="input_name">Credential type*</span>
        <select name="TYPE" class="input_input_text">
            <?php foreach($params['authTypes'] as $authType) {?>
                <option value="<?php xecho($authType) ?>"><?php xecho($authType) ?></option>
            <?php } ?>
        </select>
        <br />
        <input type="submit" value="Add credential" class="input_button">
    </form>
</div>
