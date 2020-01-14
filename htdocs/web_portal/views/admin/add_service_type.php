<div class="rightPageContainer">
    <h1>Add Service Type</h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Add_Service_Type" name="addSType">
        <span class="input_name">Name</span>
        <input type="text" value="" name="Name" class="input_input_text">
        <span class="input_name">Description</span>
        <input type="text" value="" name="Description" class="input_input_text">
        <span class="input_name" style="">
            <input type="checkbox" name="AllowMonitoringException" value="checked"/>
            <label for="AllowMonitoringException">
                Allow services of this type to be in production without monitoring?
            </label>
        </span>
        <br />
        <input type="submit" value="Add Service Type" class="input_button">
    </form>
</div>