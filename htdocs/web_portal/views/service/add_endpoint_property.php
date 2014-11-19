<?php
$endpointId = $params['endpointid'];
?>
<div class="rightPageContainer">
    <form name="Add_Endpoint_Property" action="index.php?Page_Type=Add_Endpoint_Property" method="post" class="inputForm" id="Endpoint_Property_Form">

    	<h1>Add Endpoint Property</h1>    		
        <br />

        <span class="input_name">
            Property Name            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRNAME" />
        <span class="input_name">
            Property Value            
        </span>
        <input class="input_input_text" type="text" name="KEYPAIRVALUE" />
        <input class="input_input_text" type="hidden" name="ENDPOINTID" value="<?php echo $endpointId;?>" />

    	<input class="input_button" type="submit" value="Add Endpoint Property" />
    </form>
</div>