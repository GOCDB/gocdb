<?php
$parent=$params['service'];
$propertyArray=$params['propArr']
?>

<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />

    <?php echo count($propertyArray) ?> new service property(s) added to <?php echo $parent->getHostName() ?>. <br/>

    <a href="index.php?Page_Type=Service&amp;id=<?php echo $parent->getID(); ?>">
    View service</a>
</div>
