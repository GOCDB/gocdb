<?php 
$propertyArray = $params['propArr'];
$site = $params['site'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Deletion Successful</h1>

    <br />

    <p>
        The following properties have been successfully removed from site <?php xecho($site->getName());?>:<br/>
    </p>

    <?php require_once __DIR__ . '/../fragments/deletedPropertiesTable.php';?>

    <p>
        <a href="index.php?Page_Type=Site&id=<?php echo $site->getId();?>">View Site</a>
    </p>

</div>