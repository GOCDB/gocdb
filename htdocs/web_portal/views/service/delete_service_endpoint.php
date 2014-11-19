<?php 
$endpoint = $params['endpoint'];
$service = $params['service'];
?>

<div class="rightPageContainer">
	<h1 class="Success">Delete Endpoint</h1><br/>
    <p>
	Are you sure you want to delete:<br/><br/>
    Endponit Name: <b><?php echo $endpoint->getName();?><br/></b> 
	Endpoint URL: <b><?php echo $endpoint->getUrl();?><br/></b>
	Interface Name: <b><?php echo $endpoint->getInterfaceName();?><br/></b>
    </p>
    <p>
        Are you sure you wish to continue?
    </p>
    
    <form class="inputForm" method="post" action="index.php?Page_Type=Delete_Service_Endpoint&endpointid=<?php echo $endpoint->getId();?>&serviceid=<?php echo $service->getId();?>" name="RemoveServiceEndpoint">
        <input class="input_input_hidden" type="hidden" name="UserConfirmed" value="true" />
        <input type="submit" value="Remove this endpoint from GOCDB" class="input_button">
    </form>

</div>