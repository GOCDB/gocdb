<?php 
$endpoint = $params['endpoint'];
$service = $params['service'];
?>
<div class="rightPageContainer">
	<h1 class="Success">Deletion Successful</h1><br />	
    <p>
    Endponit Name: <b><?php echo $endpoint->getName();?><br/></b> 
	Endpoint URL: <b><?php echo $endpoint->getUrl();?><br/></b>
	Interface Name: <b><?php echo $endpoint->getInterfaceName();?><br/></b>
    </p>
    <p>
    Has been successfully removed from service <?php echo $service->getHostName();?><br/>
    <a href="index.php?Page_Type=Service&id=<?php echo $service->getId();?>">
    View service</a>	 
    </p>

</div>