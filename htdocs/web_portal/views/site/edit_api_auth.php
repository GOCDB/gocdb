<div class="rightPageContainer">
    <h1>Edit API credential for <?php xecho($params['site']->getName());?></h1>
    <h4>This credential is linked to GOCDB user
        <a href="<?php echo \GocContextPath::getPath()?>index.php?Page_Type=User&id=<?php echo $params['authEnt']->getUser()->getId()?>" >
                <?php xecho($params['authEnt']->getUser()->getFullname())?>
        </a>
    </h4>
    <?php
        // currentUserIdent is only initialised if the user is changing
        if ($params['currentUserIdent']) {
            echo('<div class="input_warning">');
            echo("WARNING: editing will change the linked identity from '");
            xecho($params['currentUserIdent']);
            echo("' to '");
            xecho($params['user']->getCertificateDn());
            echo("'. Click the browser Back button to cancel the edit.</div>");
        }
    ?>
    <form class="inputForm" method="post" action="index.php?Page_Type=Edit_API_Authentication_Entity&parentid=<?php echo($params['site']->getId())?>&authentityid=<?php xecho($params['authEnt']->getId())?>" name="addAPIAuthenticationEntity">
        <div style="margin-bottom: 0.5em;">
            <span class="input_name">Identifier (e.g. Certificate DN or OIDC Subject)*</span>
            <input type="text" value="<?php xecho($params['authEnt']->getIdentifier()) ?>" name="IDENTIFIER" class="input_input_text">
        </div>
        <div style="margin-bottom: 0.5em;">
            <span class="input_name">Credential type*</span>
            <select name="TYPE" class="input_input_text">
                <?php foreach($params['authTypes'] as $authType) {?>
                    <option value="<?php xecho($authType) ?>"<?php if ($params['authEnt']->getType() == $authType) {echo " selected=\"selected\"";} ?>>
                        <?php xecho($authType) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div style="margin-bottom: 1em">
            <div class="input_warning">
                WARNING: it is possible to delete information using the write functionality of the API. Leave Allow API write unchecked if
                you do not need to write data.
            </div>
            <div class="input_checkbox">
                <input type="checkbox" name="ALLOW_WRITE" id="ALLOW_WRITE" value="checked"
                    <?php
                        if ($params['allowWrite']) { echo('checked="checked"');}
                    ?>
                />
                <label class="input_label" for="ALLOW_WRITE">Allow API write</label>
            </div>
        </div>
        <input type="submit" value="Edit credential" class="input_button">
    </form>
</div>
