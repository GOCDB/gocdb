<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    
    <a href="index.php?Page_Type=User&id=<?php echo $params['ID']?>">
    <?php xecho($params['Name'])?>     
    </a>  
    is 
    <?php if($params['IsAdmin']){echo "now";}else{echo "no longer";} ?>
    a GOCDB administrator.
    <br>
    <br>
    <a href='index.php?Page_Type=Admin_Users'>
        Return to the users page.
    </a>
</div>


