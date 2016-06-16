<?php 
$endpoint = $params['endpoint'];
$service = $params['service'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1><br />	
    <p>
    Endponit Name: <b><?php xecho($endpoint->getName());?><br/></b> 
    Endpoint URL: <b><?php xecho($endpoint->getUrl());?><br/></b>
    Interface Name: <b><?php xecho($endpoint->getInterfaceName());?><br/></b>
    </p>
    <p>
    Has been successfully removed from service <?php xecho($service->getHostName());?><br/>
    <a href="index.php?Page_Type=Service&id=<?php echo $service->getId();?>">
    View service</a>	 
    </p>

</div>