<?php $serviceType = $params['ServiceType']?>

<div class="rightPageContainer">
    <h1 class="Success">Deletion Failed</h1><br />
    The service type '
    <?php
    $serviceTypeRelPath = "index.php?Page_Type=Service_Type";
    ?>
    <a href="<?= $serviceTypeRelPath; ?>&amp;id=<?= $serviceType->getId(); ?>">
    <?php xecho($serviceType->getName());?>
    </a>'
    can not be deleted as the following services are still of this type:
    <?php
        foreach($params['Services'] as $sep){
            echo "<br />"
                . "<a href=\"index.php?Page_Type=Service&amp;id=" . $sep->getId() ."\">"
                .  xssafe($sep->getHostName())
                . "</a> ";
        }
    ?>
    <br>
    <br>
    These services will need their service type changing before the
    service type can be deleted.
</div>
