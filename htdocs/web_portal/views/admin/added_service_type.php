<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    <p>New service type created -</p>

    <?php require_once __DIR__ . '/../fragments/serviceTypeInfo.php'; ?>

    <a href="index.php?Page_Type=Service_Type&amp;id=<?= $params['ID']; ?>">
     Click here</a> to view the <?php xecho($params['Name'])?> service type.


</div>
