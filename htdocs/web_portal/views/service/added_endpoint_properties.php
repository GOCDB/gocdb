<?php
$parent=$params['endpoint'];
$propertyArray=$params['propArr']
?>

<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />

    <?php echo count($propertyArray) ?> new endpoint property(s) added to <?php echo $parent->getName() ?>. <br/>

    <a href="index.php?Page_Type=View_Service_Endpoint&amp;id=<?php echo $parent->getID(); ?>">
        View endpoint</a>
</div>