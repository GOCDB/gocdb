<?php 
$property = $params['prop'];
$site = $params['site'];
?>
<div class="rightPageContainer">
	<h1 class="Success">Deletion Successful</h1><br />	
    <p>
	Property name: <b><?php xecho($property->getKeyName());?><br/></b>
	Property value: <b><?php xecho($property->getKeyValue());?><br/></b>
    </p>
    <p>
    From site <?php xecho($site->getName());?> have been successfully removed from GOCDB<br/>
    <a href="index.php?Page_Type=Site&id=<?php echo $site->getId();?>">
    View site</a>	 
    </p>

</div>