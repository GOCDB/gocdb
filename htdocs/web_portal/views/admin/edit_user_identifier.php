<div class="rightPageContainer">
    <h1>Update User Identifier</h1>

    <br />

    <div>
        The <b><?php xecho($params['authType']); ?></b> ID string for
        <b><?php xecho($params['Title'] . " " . $params['Forename'] . " " . $params['Surname']); ?></b>
        is:
        <br />
        <b><?php xecho($params['idString']); ?></b>
    </div>

    <br />

    <div class=<?php echo $params['dnWarning'] ? "" : "hidden"; ?>>
        <span style="color: red">Warning: This user does not have any UserIdentifiers. Submitting will create an identifier, which must share the current ID string.</span>
        <br />
        <br />
    </div>

    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Edit_User_Identifier" name="editSType">
        <span class="input_name">New ID String</span>
        <input type="text" value="<?php xecho($params['idString']); ?>" name="idString" class="input_input_text">
        <br />

        <div>
            <span class="input_name">New Authentication Type:</span>
            <select name="authType" class="input_input_text">
                <?php
                    foreach ($params['authTypes'] as $authType) {
                        echo '<option value="' . $authType . '"';
                        if ($authType === $params['authType']) {echo 'selected';}
                        echo '>' . $authType . '</option>';
                    }
                ?>
            </select>
        </div>

        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID']; ?>" />
        <input class="input_input_hidden" type="hidden" name="identifierId" value="<?php echo $params['identifierId']; ?>" />
        <input type="submit" value="Update Identifier" class="input_button">
    </form>
</div>