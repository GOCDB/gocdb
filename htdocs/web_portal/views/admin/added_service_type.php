<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    
    <?php xecho($params['Name'])?> has been successfully added as a new service
    type with the following description: "<?php xecho($params['Description'])?>".
    <br />
    <br />
    <a href="index.php?Page_Type=Admin_Service_Type&id=<?php echo $params['ID'] ?>">
     Click here</a> to view the <?php xecho($params['Name'])?> service type.
     
        
</div>


