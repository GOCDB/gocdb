<?php
$parent=$params['service'];
$propertyArray=$params['proparr']
?>
<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    New service property successfully created. <br />

    <?php require_once __DIR__ . '/../fragments/propertiesTable.php';?>

    <a href="index.php?Page_Type=Service&id=<?php echo $params ?>">
    View service</a>
</div>