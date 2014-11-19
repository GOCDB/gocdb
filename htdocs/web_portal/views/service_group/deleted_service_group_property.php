<?php 
$property = $params['prop'];
$serviceGroup = $params['serviceGroup'];
?>
<div class="rightPageContainer">
	<h1 class="Success">Deletion Successful</h1><br />	
    <p>
	Property name: <b><?php echo $property->getKeyName();?><br/></b>
	Property value: <b><?php echo $property->getKeyValue();?><br/></b>
    </p>
    <p>
    From site <?php echo $serviceGroup->getName();?> have been successfully removed from GOCDB<br/>
    <a href="index.php?Page_Type=Service_Group&id=<?php echo $serviceGroup->getId();?>">
    View service group</a>	 
    </p>

</div>