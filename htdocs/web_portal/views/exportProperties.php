<?php
$extensionProperties = $params['properties'];
header("Content-Type: text/plain");
?>


    <?php foreach($extensionProperties as $prop) { ?>

        <?php echo($prop->getKeyName() . " = " .  $prop->getKeyValue()); ?>


    <?php
    }
    ?>

