<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    <?php $NGI = $params['NewNGI'];?>
    The following sites have been moved to
    <a href="index.php?Page_Type=NGI&amp;id=<?php echo $NGI->getId();?>">
    <?php xecho($NGI->getName());?>
    </a>:
    <?php
        foreach($params['sites'] as $site){
            echo "<br />"
                . "<a href=\"index.php?Page_Type=Site&amp;id=" . $site->getId() ."\">"
                .  xssafe($site->getShortName())
                . "</a> ";
        }
    ?>
</div>
