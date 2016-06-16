<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/* AddNGIs.php: Loads a list of scopes from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the xml input format for scope data
 */
$scopesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/Scopes.xml";
$scopes = simplexml_load_file($scopesFileName);

foreach($scopes as $scope) {
    $doctrineScope = new Scope();
    $name = "";
    foreach($scope as $key => $value) {
        if($key == "name") {
            $name = (string) $value;
        }
    }
    $doctrineScope->setName($name);
    $entityManager->persist($doctrineScope);
}

$entityManager->flush();