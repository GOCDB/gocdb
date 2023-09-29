<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    <p>
       The <a href="index.php?Page_Type=Project&amp;id=<?php echo $params['ID']?>">
           <?php xecho($params['Name'])?></a> project has been successfully edited as follows:
    </p>
    <p>
        Name: <?php xecho($params['Name'])?>
        <br />
        Description: <?php xecho($params['Description'])?>
    </p>
    <p>
        <a href="index.php?Page_Type=Edit_Project&amp;id=<?php echo $params['ID']?>">
        Click here</a> to edit the <?php xecho($params['Name'])?> project again.

    </p>
</div>
