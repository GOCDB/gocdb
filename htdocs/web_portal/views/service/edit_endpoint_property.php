<?php
$service = $params['service'];
$endpoint = $params['endpoint'];
$prop = $params['prop'];
?>
<div class="rightPageContainer">
    <form name="Edit_Endpoint_Property" action="index.php?Page_Type=Edit_Endpoint_Property" method="post" class="inputForm" id="Endpoint_Property_Form" name="Edit_Endpoint_Property_Form">

        <h1>Edit Endpoint Property</h1>
        <br />

        <span class="input_name">
            Property Name
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" value="<?php xecho($prop->getKeyName());?>" />
        <span class="input_name">
            Property Value
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" value="<?php xecho($prop->getKeyValue());?>"/>
        <input class="input_input_text" type="hidden" name ="ENDPOINTID" value="<?php echo $endpoint->getId();?>" />
        <input class="input_input_text" type="hidden" name ="PROP" value="<?php echo $prop->getId();?>" />

        <input class="input_button" type="submit" value="Edit Endpoint Property" />
    </form>
</div>