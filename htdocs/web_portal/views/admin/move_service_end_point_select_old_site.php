<div class="rightPageContainer">
     <form name="Move_Service" action="index.php?Page_Type=Admin_Move_SEP" method="post" class="inputForm">
        <h1>Move Service</h1>
        <br />

        <span class="input_name">Please select the site from which you wish to move services</span>
        <select class="add_edit_form" name="OldSite">
            <?php
            foreach($params['Sites'] as $site) {
                echo "<option value=\"". $site->getId() . "\">" . $site->getShortName(). "</option>";
            }
            ?>
        </select>

        <input class="gocdb_btn gocdb_btn_props" type="submit" value="Move service from this site" />
    </form>
</div>
