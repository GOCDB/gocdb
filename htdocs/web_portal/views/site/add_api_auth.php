<div class="rightPageContainer">
    <h1>Add new API credential to <?php xecho($params['site']->getName());?></h1>
    <h4>The credential added will be linked to GOCDB account
        <a href="<?php echo \GocContextPath::getPath()?>index.php?Page_Type=User&id=<?php echo $params['user']->getId()?>" >
                <?php xecho($params['user']->getForename()." ".$params['user']->getSurname())?>
        </a>
    </h4>
    <form class="inputForm" method="post" action="index.php?Page_Type=Add_API_Authentication_Entity&parentid=<?php echo($params['site']->getId());?>" name="addAPIAuthenticationEntity">
        <div style="margin-bottom: 0.5em;">
            <span class="input_name">Identifier (e.g. Certificate DN or OIDC Subject)*</span>
            <input type="text" value="" name="IDENTIFIER" class="input_input_text">
        </div>
        <div style="margin-bottom: 0.5em;">
            <span class="input_name">Credential type*</span>
            <select name="TYPE" class="input_input_text">
                <?php foreach($params['authTypes'] as $authType) {?>
                    <option value="<?php xecho($authType) ?>"><?php xecho($authType) ?></option>
                <?php } ?>
            </select>
        </div>
        <div style="margin-bottom: 1em">
            <b>Caution: it is possible to delete information using the write functionality of the API.</b></br>
            <div class="input_checkbox">
                <input type="checkbox" name="ALLOW_WRITE" id="ALLOW_WRITE" value="checked"
                    <?php if ($params['allowWrite']) { xecho(' checked="checked"'); } ?>
                />
                <label class="input_label" for="ALLOW_WRITE">Allow API write</label>
            </div>
        </div>
        <input type="submit" value="Add credential" class="input_button">
    </form>
</div>
