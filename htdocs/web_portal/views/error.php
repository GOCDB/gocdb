<div class="rightPageContainer">
    <h1>Error</h1>
    <br />
    <?php

    if(strpos($params, 'DOCSVN.SERV_KEYPAIRS') || strpos($params, 'DOCSVN.SITE_KEYPAIRS')){
        echo "A key value pair already exists with this keyname and keyvalue.";
    }else{
        xecho($params);
    }

    ?>
</div>