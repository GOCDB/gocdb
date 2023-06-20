<?php
$parent=$params['site'];
$propertyArray=$params['propArr']
?>

<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />

    <?php echo count($propertyArray) ?> new site property(s) added to <?php echo $parent->getName() ?>. <br/>

    <a href="index.php?Page_Type=Site&amp;id=<?php echo $parent->getID(); ?>">
        View site</a>
</div>
