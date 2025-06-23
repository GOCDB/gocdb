<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
        The API authenication credential has now been updated.
        Type: <?php xecho($params['apiAuthenticationEntity']->getType()); ?>,
        identifier: <?php
            xecho($params['apiAuthenticationEntity']->getIdentifier());
        ?>.
    <br />
    <a
        href="<?php
            echo "index.php?Page_Type=Site&amp;id=", $params['site']->getID();
        ?>"
    >View site</a>
</div>
