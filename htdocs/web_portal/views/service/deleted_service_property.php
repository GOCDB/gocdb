<?php 
$property = $params['prop'];
$service = $params['service'];
?>
<div class="rightPageContainer">
	<h1 class="Success">Deletion Successful</h1><br />	
    <p>
	Property name: <b><?php xecho($property->getKeyName());?><br/></b>
	Property value: <b><?php xecho($property->getKeyValue());?><br/></b>
    </p>
    <p>
    Have been successfully removed from service <?php xecho($service->getHostName());?><br/>
    <a href="index.php?Page_Type=Service&id=<?php echo $service->getId();?>">
    View service</a>	 
    </p>

</div>