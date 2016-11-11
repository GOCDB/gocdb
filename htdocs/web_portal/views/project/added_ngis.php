<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    The following NGIs have been successfully added to  the
    <a href="index.php?Page_Type=Project&id=<?php echo $params['ID']?>">
    <?php echo $params['Name'];?>
    </a> project:
    <?php
        foreach($params['NGIs'] as $ngi){
            echo "<br />"
                . "<a href=\"index.php?Page_Type=NGI&id=" . $ngi->getId() ."\">"
                .  $ngi->getName()
                . "</a> ";
        }
    ?>
</div>