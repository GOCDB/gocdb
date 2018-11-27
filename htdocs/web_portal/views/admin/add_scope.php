<div class="rightPageContainer">
    <h1>Add Scope</h1>
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Add_Scope" name="addScope">
        <span class="input_name">Name</span>
        <input type="text" value="" name="Name" class="input_input_text">
        <span class="input_name">Description</span>
        <input type="text" value="" name="Description" class="input_input_text">
        <span class="input_name">Reserved - check to create a reserved scope</span>
        <input type="checkbox" value="1" <?php echo (($params['Reserved'] == true) ? 'checked' : ''); ?> name="Reserved" class="input_input_checkbox">
        <br />
        <input type="submit" value="Add Scope" class="input_button">
    </form>
</div>