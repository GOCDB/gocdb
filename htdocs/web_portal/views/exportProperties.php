<?php
$extensionProperties = $params['properties'];
header("Content-Type: text/plain");
foreach($extensionProperties as $prop) {
    echo($prop->getKeyName() . " = " .  $prop->getKeyValue());
    echo(PHP_EOL);
}
?>

