<?php
$parent=$params['serviceGroup'];
$propertyArray=$params['propArr']
?>

<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />

    <?php echo count($propertyArray) ?> new service group property(s) added to <?php echo $parent->getName(); ?>. <br/>

    <a href="index.php?Page_Type=Service_Group&amp;id=<?php echo $parent->getID(); ?>">
        View service group</a>
</div>
