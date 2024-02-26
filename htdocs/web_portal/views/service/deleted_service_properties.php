<?php
$propertyArray = $params['propArr'];
$service = $params['service'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1>

    <br />

    <p>
        The following properties have been successfully removed from service <?php xecho($service->getHostName());?>:<br/>
    </p>

    <?php require_once __DIR__ . '/../fragments/propertiesTable.php';?>

    <p>
        <a href="index.php?Page_Type=Service&amp;id=<?php echo $service->getId();?>">View service</a>
    </p>

</div>
