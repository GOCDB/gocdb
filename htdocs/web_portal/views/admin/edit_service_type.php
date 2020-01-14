<div class="rightPageContainer">
    <h1>Edit Service Type '<?php xecho($params['Name']) ?>'</h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Edit_Service_Type" name="editSType">
        <span class="input_name">Name</span>
        <input type="text" value="<?php xecho($params['Name']) ?>" name="Name" class="input_input_text">
        <span class="input_name">Description</span>
        <input type="text" value="<?php xecho($params['Description']) ?>" name="Description" class="input_input_text">

        <span class="input_name" style="">
            <input type="checkbox" name="AllowMonitoringException" value="checked"
                <?php if ($params['AllowMonitoringException'] == TRUE) echo " checked ";?>
            />
            <label for="AllowMonitoringException">
                Allow <?php xecho($params['Name']) ?> services to be in production without monitoring?
            </label>
        </span>

        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
        <br />
        <input type="submit" value="Submit Changes" class="input_button">
    </form>
</div>