<?php
$propertyArray = $params['propArr'];
$endpoint = $params['endpoint'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1>

    <br />

    <p>
        The following properties have been successfully removed from endpoint <?php xecho($endpoint->getName());?>:<br/>
    </p>

        <?php require_once __DIR__ . '/../fragments/propertiesTable.php';?>

    <p>
        <a href="index.php?Page_Type=View_Service_Endpoint&amp;id=<?php echo $endpoint->getId();?>">View endpoint</a>
    </p>

</div>
