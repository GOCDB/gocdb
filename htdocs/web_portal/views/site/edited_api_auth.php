<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    The API authenication credential has now been updated. Type:<?php xecho($params['apiAuthenticationEntity']->getType()) ?>, identifier: <?php xecho($params['apiAuthenticationEntity']->getIdentifier()) ?>.
    <br />
    <a href="index.php?Page_Type=Site&id=<?php echo $params['site']->getID(); ?>">
        View site</a>
</div>
