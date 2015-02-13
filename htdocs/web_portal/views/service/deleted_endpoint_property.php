<?php 
$property = $params['prop'];
$service = $params['service'];
$endpoint = $params['endpoint']; 
?>
<div class="rightPageContainer">
	<h1 class="Success">Deletion Successful</h1><br />	
    <p>
	Property name: <b><?php xecho($property->getKeyName());?><br/></b>
	Property value: <b><?php xecho($property->getKeyValue());?><br/></b>
    </p>
    <p>
    Have been successfully removed from endpoint <?php xecho($endpoint->getName());?><br/>
    <a href="index.php?Page_Type=View_Service_Endpoint&id=<?php echo $endpoint->getId();?>">
    View endpoint</a>	 
    </p>

</div>