
<div class="rightPageContainer">
    <h1>Edit Project <?php xecho($params['Name']) ?></h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Edit_Project" name="editProject">
        <span class="input_name">Name</span>
        <input type="text" value="<?php xecho($params['Name']) ?>" name="Name" class="input_input_text">
        <span class="input_name">Description</span>
        <input type="text" value="<?php xecho($params['Description']) ?>" name="Description" class="input_input_text">
        
		<br />
        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
        <br />
        <input type="submit" value="Submit Changes" class="input_button">
    </form>
</div>