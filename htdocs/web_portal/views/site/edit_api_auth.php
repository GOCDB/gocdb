<div class="rightPageContainer">
    <?php

    $user = $params['user'];
    $entUser = $params['authEnt']->getUser();

    echo('<h1>');
    if ($params['isRenewalRequest']) {
        echo('Renew API credential for ');
    } else {
        echo('Edit API credential for ');
    }
    xecho($params['site']->getName());
    echo('</h1>');

    if (!is_null($entUser)) {
        echo('<h4>This credential is linked to GOCDB user ');
        echo('<a href="');
        xecho(\GocContextPath::getPath());
        echo('index.php?Page_Type=User&id=');
        xecho($entUser->getId());
        echo('">');
        xecho($entUser->getFullname());
        echo('</a></h4>');

        // entities created prior to GOCDB5.8 have a null owning user
        if ($entUser->getId() != $user->getId()) {
            echo('<div class="input_warning">');
            if ($params['isRenewalRequest']) {
                echo(
                    "WARNING: Renewing this will change the linked user from '"
                );
            } else {
                echo("WARNING: Editing will change the linked user from '");
            }
            xecho($entUser->getFullname());
            echo("' to '");
            xecho($user->getFullname());
            echo("'. Click the browser Back button to cancel the");
            if ($params['isRenewalRequest']) {
                echo(' renewal.</div>');
            } else {
                echo(' edit.</div>');
            }
        }
    } else {
            // This clause should be deleted or replaced with exception after all
            // authentication entities are assigned a user.
            echo('<div class="input_warning">');
            echo("WARNING: editing will link user '");
            xecho($user->getFullname());
            echo("' to this credential. Click the browser Back button to cancel the edit.</div>");
    }
    ?>
    <form class="inputForm" method="post" action="index.php?Page_Type=Edit_API_Authentication_Entity&parentid=<?php echo($params['site']->getId())?>&authentityid=<?php xecho($params['authEnt']->getId())?>" name="addAPIAuthenticationEntity">
        <div style="margin-bottom: 0.5em;">
            <span class="input_name">Identifier (e.g. Certificate DN or OIDC Subject)*</span>
            <input
                type="text"
                value="<?php xecho($params['authEnt']->getIdentifier()); ?>"
                name="IDENTIFIER"
                class="input_input_text"
                <?= $params['isRenewalRequest'] ? 'disabled' : ''; ?>
            >
        </div>

        <div style="margin-bottom: 0.5em;">
            <span class="input_name">Credential type*</span>
            <select
                name="TYPE"
                class="input_input_text"
                <?= $params['isRenewalRequest'] ? 'disabled' : ''; ?>
            >
                <?php foreach($params['authTypes'] as $authType) {?>
                    <option value="<?php xecho($authType) ?>"<?php if ($params['authEnt']->getType() == $authType) {echo " selected=\"selected\"";} ?>>
                        <?php xecho($authType) ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <?php if (!($params['isRenewalRequest'])) { ?>
        <div style="margin-bottom: 1em">
            <div class="input_warning">
                WARNING: it is possible to delete information using the write functionality of the API. Leave Allow API write unchecked if
                you do not need to write data.
            </div>
            <div class="input_checkbox">
                <input type="checkbox" name="ALLOW_WRITE" id="ALLOW_WRITE" value="checked"
                    <?php
                        if ($params['authEnt']->getAllowAPIWrite()) { echo('checked="checked"');}
                    ?>
                />
                <label class="input_label" for="ALLOW_WRITE">Allow API write</label>
            </div>
        </div>
        <?php } else { ?>
        <div>
            <p>
                Note: If you wish to edit the content for either Identifier
                or Credential type. Please, visit the
                <?php
                echo('<a href="');
                xecho(\GocContextPath::getPath());
                echo('index.php?Page_Type=Edit_API_Authentication_Entity');
                echo('&authentityid=');
                echo($params['authEnt']->getId());
                echo('">');
                echo('Edit API credential');
                echo('</a>');
                ?> page.
            </p>
        </div>
        <?php } ?>

        <br><p> Are you sure you want to continue? </P>

        <div>
            <?php if ($params['isRenewalRequest']) { ?>
                <input
                    class="input_input_hidden"
                    type="hidden"
                    name="isRenewalRequest"
                    value=true />
            <?php } ?>
            <input
                type="submit"
                class="input_button"
                value="<?php
                if ($params['isRenewalRequest']) {
                    echo 'Renew credential';
                } else {
                    echo 'Edit credential';
                } ?>"
            />
        </div>
    </form>
</div>
