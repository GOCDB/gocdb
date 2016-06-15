<?php 
$propertyArray = $params['propArr'];
$serviceGroup = $params['serviceGroup'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1><br />

    <br />

    <p>
        The following properties have been successfully removed from the service group <?php xecho($serviceGroup->getName());?>:<br/>
    </p>

    <?php require_once __DIR__ . '/../fragments/propertiesTable.php';?>

    <p>
    <a href="index.php?Page_Type=Service_Group&id=<?php echo $serviceGroup->getId();?>">View service group</a>
    </p>

</div>