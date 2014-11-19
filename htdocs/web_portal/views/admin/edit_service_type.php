<div class="rightPageContainer">
    <h1>Edit Service Type '<?php echo $params['Name'] ?>'</h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Edit_Service_Type" name="editSType">
        <span class="input_name">Name</span>
        <input type="text" value="<?php echo $params['Name'] ?>" name="Name" class="input_input_text">
        <span class="input_name">Description</span>
        <input type="text" value="<?php echo $params['Description'] ?>" name="Description" class="input_input_text">
        
		<br />
        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
        <br />
        <input type="submit" value="Submit Changes" class="input_button">
    </form>
</div>