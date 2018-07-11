<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    The <?php xecho($params['authEnt']['type'])?> credential with identifier <?php $params['authEnt']['identifier']?> was successfully removed.
    <br />
    <a href="index.php?Page_Type=Site&amp;id=<?php echo $params['site']->getID(); ?>">
        View site</a>
</div>
