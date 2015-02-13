<div class="rightPageContainer">
    <h1 class="Success">Success</h1>
    New Service group <?php xecho($params['sg']->getName()) ?> successfully created. <br />
    <a href="index.php?Page_Type=Service_Group&id=<?php echo $params['sg']->getId() ?>">
    View <?php xecho($params['sg']->getName()) ?></a>
</div>