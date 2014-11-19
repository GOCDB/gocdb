<?php 
$property = $params['prop'];
$site = $params['site'];
?>
<div class="rightPageContainer">
	<h1 class="Success">Deletion Successful</h1><br />	
    <p>
	Property name: <b><?php echo $property->getKeyName();?><br/></b>
	Property value: <b><?php echo $property->getKeyValue();?><br/></b>
    </p>
    <p>
    From site <?php echo $site->getName();?> have been successfully removed from GOCDB<br/>
    <a href="index.php?Page_Type=Site&id=<?php echo $site->getId();?>">
    View site</a>	 
    </p>

</div>