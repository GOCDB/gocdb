<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    <p>
       <a href="index.php?Page_Type=Admin_Service_Type&amp;id=<?php echo $params['ID']?>"><?php xecho($params['Name'])?></a> has been successfully edited as follows:
    </p>
    <p>
        Name: <?php xecho($params['Name'])?>
        <br />
        Description: <?php xecho($params['Description'])?>
    </p>
    <p>
        <a href="index.php?Page_Type=Admin_Edit_Service_Type&amp;id=<?php echo $params['ID']?>">
        Click here</a> to edit the <?php xecho($params['Name'])?> service type again.

    </p>
</div>


