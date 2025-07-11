<?php
$site = $params['site'];
?>
<div class="rightPageContainer">
    <form name="Add_Site_Property" action="index.php?Page_Type=Add_Site_Property" method="post" class="inputForm" id="Site_Property_Form" name="Site_Property_Form">

        <h1>Add Site Property</h1>
        <br />

        <span class="input_name">
            Property Name
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" />
        <span class="input_name">
            Property Value
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" />
        <input class="input_input_text" type="hidden" name ="SITE" value="<?php echo $site->getId();?>" />

        <input class="gocdb_btn gocdb_btn_props" type="submit" value="Add Site Property" />
    </form>
</div>
