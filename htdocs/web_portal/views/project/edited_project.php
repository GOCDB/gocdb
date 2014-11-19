<div class="rightPageContainer">
	<h1 class="Success">Success</h1><br />
    <p>
       The <a href="index.php?Page_Type=Project&id=<?php echo $params['ID']?>">
           <?php echo $params['Name']?></a> project has been successfully edited as follows:
    </p>    
    <p>
        Name: <?php echo $params['Name']?>
        <br />
        Description: <?php echo $params['Description']?>
    </p>
    <p>
        <a href="index.php?Page_Type=Edit_Project&id=<?php echo $params['ID']?>">
        Click here</a> to edit the <?php echo $params['Name']?> project again.
        
    </p>
</div>


