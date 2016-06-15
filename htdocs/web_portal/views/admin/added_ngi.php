<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    <br />
    <a href="index.php?Page_Type=NGI&id=<?php echo $params['ID'] ?>">
    <?php xecho($params['Name'])?> 
    </a> has been successfully added as a new NGI.
    <br />
    <br />
    If you have not done so already, please now <b>add a relevant image file</b> (usually a national flag)
    to web_portal/img/ngi/fullsize with the name '<?php xecho($params['Name'])?>.jpg'.
    A smaller copy with the same name should be placed in web_portal/img/ngi,
    this smaller image should not exceed width:28px, height:25px.
        
</div>

