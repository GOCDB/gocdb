<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/* Loads a list of service types from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the PROM GOCDB PI output for get_service_type
 */
$stFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/ServiceTypes.xml";
$sts = simplexml_load_file($stFileName);

foreach($sts as $st) {
    $doctrineSt = new ServiceType();
    $name = "";
    $desc = "";
    foreach($st as $key => $value) {
        if($key == "SERVICE_TYPE_NAME") {
            $name = (string) $value;
        }
        
        if($key == "SERVICE_TYPE_DESC") {
            $desc = (string) $value;
        }
    }
    $doctrineSt->setName($name);
    $doctrineSt->setDescription($desc);
    $entityManager->persist($doctrineSt);
}

$entityManager->flush();