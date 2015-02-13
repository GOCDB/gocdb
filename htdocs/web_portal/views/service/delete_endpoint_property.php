<?php 
$property = $params['prop'];
$service = $params['service'];
?>

<div class="rightPageContainer">
	<h1 class="Success">Delete Endpoint Property</h1><br/>
    <p>
	Are you sure you want to delete:<br/><br/>
	Property name: <b><?php xecho($property->getKeyName());?><br/></b>
	Property value: <b><?php xecho($property->getKeyValue());?><br/></b>
    </p>
    <p>
        Are you sure you wish to continue?
    </p>
    
    <!--<form class="inputForm" method="post" action="index.php?Page_Type=Delete_Endpoint_Property&propertyid=<?php echo $property->getId();?>&serviceid=<?php echo $service->getId();?>" name="RemoveEndpointProperty">-->
    <form class="inputForm" method="post" action="index.php?Page_Type=Delete_Endpoint_Property&propertyid=<?php echo $property->getId();?>" name="RemoveEndpointProperty">
        <input class="input_input_hidden" type="hidden" name="UserConfirmed" value="true" />
        <input type="submit" value="Remove this endpoint property from GOCDB" class="input_button">
    </form>

</div>