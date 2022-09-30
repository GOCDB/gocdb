<div class="rightPageContainer">
    <h1>Edit Scope '<?php xecho($params['Name']) ?>'</h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Edit_Scope" name="editScope">
        <span class="input_name">Name</span>
        <input type="text" value="<?php xecho($params['Name']) ?>" name="Name" class="input_input_text">
        <span class="input_name">Description</span>
        <input type="text" value="<?php xecho($params['Description']) ?>" name="Description" class="input_input_text">
        <span class="input_name">Reserved - check to set scope as Reserved</span>
        <input type="checkbox" value="1" <?php echo (($params['Reserved'] == true) ? 'checked' : ''); ?> name="Reserved" class="input_input_checkbox"> 
        <br />
        <input class="input_input_hidden" type="hidden" name="Id" value="<?php echo $params['Id'] ?>" />
        <br />
        <input type="submit" value="Submit Changes" class="input_button">
    </form>
</div>