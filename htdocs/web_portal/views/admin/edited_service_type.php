<div class="rightPageContainer">
	<h1 class="Success">Success</h1><br />
    <p>
       <a href="index.php?Page_Type=Admin_Service_Type&id=<?php echo $params['ID']?>"><?php echo $params['Name']?></a> has been successfully edited as follows:
    </p>    
    <p>
        Name: <?php echo $params['Name']?>
        <br />
        Description: <?php echo $params['Description']?>
    </p>
    <p>
        <a href="index.php?Page_Type=Admin_Edit_Service_Type&id=<?php echo $params['ID']?>">
        Click here</a> to edit the <?php echo $params['Name']?> service type again.
        
    </p>
</div>


