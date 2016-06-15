<?php
$serviceGroup = $params['serviceGroup'];
$prop = $params['prop'];
?>
<div class="rightPageContainer">
    <form name="Edit_Service_Group_Property" action="index.php?Page_Type=Edit_Service_Group_Property" method="post" class="inputForm" id="Service_Group_Property_Form">

        <h1>Edit Service Group Property</h1>    		
        <br />

        <span class="input_name">
            Property Name            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" value="<?php xecho($prop->getKeyName());?>" />
        <span class="input_name">
            Property Value            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" value="<?php xecho($prop->getKeyValue());?>"/>
        <input class="input_input_text" type="hidden" name ="SERVICEGROUP" value="<?php echo $serviceGroup->getId();?>" />
        <input class="input_input_text" type="hidden" name ="PROP" value="<?php echo $prop->getId();?>" />

        <input class="input_button" type="submit" value="Edit Service Group Property" />
    </form>
</div>