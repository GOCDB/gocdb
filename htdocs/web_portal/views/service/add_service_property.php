<?php
$serviceEndpointId = $params['serviceid'];
?>
<div class="rightPageContainer">
    <form name="Add_Service_Property" action="index.php?Page_Type=Add_Service_Property" method="post" class="inputForm" id="Service_Property_Form">

    	<h1>Add Service Property</h1>    		
        <br />

        <span class="input_name">
            Property Name            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" />
        <span class="input_name">
            Property Value            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" />
        <input class="input_input_text" type="hidden" name ="SERVICE" value="<?php echo $serviceEndpointId;?>" />

    	<input class="input_button" type="submit" value="Add Service Property" />
    </form>
</div>