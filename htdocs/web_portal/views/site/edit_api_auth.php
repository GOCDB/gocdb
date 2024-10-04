<div class="rightPageContainer">
    <?php
    $user = $params['user'];
    $entUser = $params['authEnt']->getUser();

    echo('<h1>Edit API credential for ');
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
            echo("WARNING: editing will change the linked user from '");
            xecho($entUser->getFullname());
            echo("' to '");
            xecho($user->getFullname());
            echo("'. Click the browser Back button to cancel the edit.</div>");
        }
    } else {
        /**
         * This clause should be deleted or replaced with exception after
         * all authentication entities are assigned a user.
         */
        echo('<div class="input_warning">');
        echo("WARNING: editing will link user '");
        xecho($user->getFullname());
        echo(
            "' to this credential. Click the browser Back button "
            . "to cancel the edit.</div>"
        );
    }
    ?>
    <form
        class="inputForm"
        method="post"
        action="<?php
            echo "index.php?Page_Type=Edit_API_Authentication_Entity",
                "&parentid=",
                $params['site']->getId(),
                "&authentityid=",
                xecho($params['authEnt']->getId());
        ?>"
        name="addAPIAuthenticationEntity"
    >
        <div style="margin-bottom: 0.5em;">
            <span class="input_name">
                Identifier (e.g. Certificate DN or OIDC Subject)*
            </span>

            <input
                type="text"
                value="<?php xecho($params['authEnt']->getIdentifier()); ?>"
                name="IDENTIFIER"
                class="input_input_text"
            >
        </div>

        <div style="margin-bottom: 0.5em;">
            <span class="input_name">Credential type*</span>

            <select name="TYPE" class="input_input_text">
                <?php foreach ($params['authTypes'] as $authType) { ?>
                    <option
                        value="<?php xecho($authType); ?>"
                        <?php
                        if ($params['authEnt']->getType() == $authType) {
                            echo " selected=\"selected\"";
                        }
                        ?>
                    >
                        <?php xecho($authType) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    
        <div style="margin-bottom: 1em">
            <div class="input_warning">
                WARNING: it is possible to delete information using the write
                functionality of the API. Leave Allow API write unchecked if
                you do not need to write data.
            </div>

            <div class="input_checkbox">
                <input
                    type="checkbox"
                    name="ALLOW_WRITE"
                    id="ALLOW_WRITE"
                    value="checked"
                    <?php
                    if ($params['authEnt']->getAllowAPIWrite()) {
                        echo('checked="checked"');
                    }
                    ?>
                />
                <label class="input_label" for="ALLOW_WRITE">
                    Allow API write
                </label>
            </div>
        </div>

        <input type="submit" value="Edit credential" class="input_button">
    </form>
</div>
