<div class="rightPageContainer">
    <h1>Update Certificate DN</h1>
    <br />
    <br />
    The current certificate DN for 
    <b><?php echo xssafe($params['Title']) ." ". xssafe($params['Forename']) ." ". xssafe($params['Surname']) ?></b>
    is:
    <br />
    <?php xecho($params['CertDN']) ?>
    <br />
    <br />
    <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Edit_User_DN" name="editSType">
        <span class="input_name">New Certificate DN</span>
        <input type="text" value="<?php xecho($params['CertDN']) ?>" name="DN" class="input_input_text">
        <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
        <br />
        <input type="submit" value="Update DN" class="input_button">
    </form>
</div>