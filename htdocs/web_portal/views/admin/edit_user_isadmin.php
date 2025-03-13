<?php $name = $params['Forename'] ." ". $params['Surname']; ?>
<div class="rightPageContainer">
    <h1>Change GOCD Administrator Status</h1>
    <br />
    <?php xecho($name) ?>
    is
    <?php if(!$params['IsAdmin']){echo "not currently";}?>
    a GOCDB administrator.
    <br />
    <br />
    <script type="text/javascript" src="<?php echo \GocContextPath::getPath()?>javascript/confirm.js"></script>

    <?php if($params['IsAdmin']): ?>
        <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Change_User_Admin_Status" name="editisAdmin">
            <input class="input_input_hidden" type="hidden" name="IsAdmin" value="false" />
            <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
            <input type="submit" value="Remove <?php xecho($name) ?>'s GOCDB administrator status" class="gocdb_btn_danger gocdb_btn_props" onclick="return confirmSubmit()">
        </form>

    <?php elseif(!$params['IsAdmin']): ?>
        <form class="inputForm" method="post" action="index.php?Page_Type=Admin_Change_User_Admin_Status" name="editisAdmin">
            <input class="input_input_hidden" type="hidden" name="IsAdmin" value="true" />
            <input class="input_input_hidden" type="hidden" name="ID" value="<?php echo $params['ID'] ?>" />
            <input type="submit" value="Make <?php xecho($name) ?> a GOCDB administrator" class="gocdb_btn gocdb_btn_props" onclick="return confirmSubmit()">
        </form>
    <?php endif; ?>
</div>
