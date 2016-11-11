<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/* AddNGIs.php: Loads a list of infrastructures from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the xml input production status format.
 */
$infrastructureFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/Infrastructures.xml";
$infs = simplexml_load_file($infrastructureFileName);

foreach($infs as $xmlInf) {
    $doctrineInf = new Infrastructure();
    $name = "";
    foreach($xmlInf as $key => $value) {
        if($key == "name") {
            $name = (string) $value;
        }
    }
    $doctrineInf->setName($name);
    $entityManager->persist($doctrineInf);
}

$entityManager->flush();