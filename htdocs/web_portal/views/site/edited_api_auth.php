<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    The API authenication credential has now been
    <?php
    if ($params['isRenewalRequest']) {
        echo 'renewed,';
    } else {
        echo 'updated. Type: ';
        xecho($params['apiAuthenticationEntity']->getType());
        echo ',';
    }
    ?>
    identifier:
    <?php xecho($params['apiAuthenticationEntity']->getIdentifier()) ?>.
    <br />
    <a href="index.php?Page_Type=Site&amp;id=<?php echo $params['site']->getID(); ?>">
        View site</a>
</div>
