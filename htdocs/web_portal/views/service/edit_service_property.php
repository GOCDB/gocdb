<?php
$service = $params['service'];
$prop = $params['prop'];
?>
<div class="rightPageContainer">
    <form name="Edit_Service_Property" action="index.php?Page_Type=Edit_Service_Property" method="post" class="inputForm" id="Service_Property_Form" name="Edit_Site_Property_Form">

        <h1>Edit Service Property</h1>
        <br />

        <span class="input_name">
            Property Name
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" value="<?php xecho($prop->getKeyName());?>" />
        <span class="input_name">
            Property Value
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" value="<?php xecho($prop->getKeyValue());?>"/>
        <input class="input_input_text" type="hidden" name ="SERVICE" value="<?php echo $service->getId();?>" />
        <input class="input_input_text" type="hidden" name ="PROP" value="<?php echo $prop->getId();?>" />

        <input class="gocdb_btn gocdb_btn_props" type="submit" value="Edit Service Property" />
    </form>
</div>
