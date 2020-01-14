<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    <p><a href="index.php?Page_Type=Admin_Service_Type&amp;id=<?php echo $params['ID']?>">
       <?php xecho($params['Name'])?></a> Service Type properties have been successfully edited to  -
    </p>

    <?php require_once __DIR__.'/../fragments/serviceTypeInfo.php'; ?>

    <p>
        <a href="index.php?Page_Type=Admin_Edit_Service_Type&amp;id=<?php echo $params['ID']?>">
        Click here</a> to edit the <?php xecho($params['Name'])?> service type again.

    </p>
</div>


