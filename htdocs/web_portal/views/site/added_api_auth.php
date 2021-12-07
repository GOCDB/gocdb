<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    The new <?php xecho($params['apiAuthenticationEntity']->getType()) ?> credential with identifier <?php xecho($params['apiAuthenticationEntity']->getIdentifier()) ?> was successfully added.
    <br />
    <a href="index.php?Page_Type=Site&amp;id=<?php echo $params['site']->getID(); ?>">
        View site</a>
</div>
