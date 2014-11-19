<div class="rightPageContainer">
    <form name="Edit_User" action="index.php?Page_Type=Edit_User" method="post" class="inputForm">
    	<h1>Update User</h1>
        <br />
        <span class="input_name">
            Title
        </span>
        <?php
            $titles = array('Mr', 'Mrs', 'Miss', 'Ms', 'Prof', 'Dr');
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
        <input class="input_input_text" type="text" name="FORENAME" value="<?php echo $params['user']->getForename(); ?>" />
        
        <span class="input_name">
            Surname * 
            <span class="input_syntax" >(unaccentuated letters, spaces, dashes and quotes)</span>
        </span>
        <input class="input_input_text" type="text" name="SURNAME" value="<?php echo $params['user']->getSurname(); ?>"/>
        
        <span class="input_name">
            E-Mail *
            <span class="input_syntax" >(valid e-mail format)</span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" value="<?php echo $params['user']->getEmail(); ?>"/>
        
        <span class="input_name">
            Telephone Number
            <span class="input_syntax" >(numbers, optional +, dots spaces or dashes)</span>
        </span>
        <input class="input_input_text" type="text" name="TELEPHONE" value="<?php echo $params['user']->getTelephone(); ?>"/>
        
        <input class="input_input_hidden" type="hidden" value="<?php echo $params['user']->getId() ?>" name="OBJECTID">
        <br />
    	<input class="input_button" type="submit" value="Update User" />
    </form>
</div>