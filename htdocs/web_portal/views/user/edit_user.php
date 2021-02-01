<div class="rightPageContainer">
    <form name="Edit_User" action="index.php?Page_Type=Edit_User" method="post" class="inputForm">
        <h1>Update User</h1>
        <br />
        <span class="input_name">
            Title
        </span>
        <?php
          $titles = array('', 'Dr', 'Miss', 'Mr', 'Mrs',  'Ms', 'Mx', 'Prof');
        ?>
        <select name="TITLE" class="add_edit_form">
        <?php
            foreach($titles as $title) {
                if($title == $params['user']->getTitle()) {
                    echo '<option selected="selected" value="'.$title.'" >'.$title.'</option>';
                } else {
                  echo '<option value="'.$title.'">'.$title.'</option>';
                }
            }
        ?>
        </select>

        <span class="input_name">
            Forename *
            <span class="input_syntax" >(unaccentuated letters, spaces, dashes and quotes)</span>
        </span>
        <input class="input_input_text" type="text" name="FORENAME" value="<?php xecho($params['user']->getForename()); ?>" />

        <span class="input_name">
            Surname *
            <span class="input_syntax" >(unaccentuated letters, spaces, dashes and quotes)</span>
        </span>
        <input class="input_input_text" type="text" name="SURNAME" value="<?php xecho($params['user']->getSurname()); ?>"/>

        <span class="input_name">
            E-Mail *
            <span class="input_syntax" >(valid e-mail format)</span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" value="<?php xecho($params['user']->getEmail()); ?>"/>

        <span class="input_name">
            Telephone Number
            <span class="input_syntax" >(numbers, optional +, dots spaces or dashes)</span>
        </span>
        <input class="input_input_text" type="text" name="TELEPHONE" value="<?php echo $params['user']->getTelephone(); ?>"/>

        <?php
            if ($params['user']->getHomeSite() != null) { ?>
                <span class="input_name">
                        Home Site: </br>
                        <div class="input_input_check">
                            GOCDB no longer supports a user's association with a Home Site. </br>
                            Check the box below to remove the existing association. This action cannot be reversed. </br>
                            <label class="control-label">
                                Remove legacy association with Home Site : <?php echo $params['user']->getHomeSite() ?> -
                                <input style="vertical-align:top" type="checkbox" name="UNLINK_HOMESITE" value="UNLINK_HOMESITE"/>
                            </label>
                        </div>
                </span>
        <?php } ?>

        <input class="input_input_hidden" type="hidden" value="<?php echo $params['user']->getId() ?>" name="OBJECTID">
        <br />
        <input class="input_button" type="submit" value="Update User" />
    </form>
</div>
