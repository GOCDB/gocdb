<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/* Loads a list of service types from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the PROM GOCDB PI output for get_service_type
 */
$stFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/ServiceGroups.xml";
$sts = simplexml_load_file($stFileName);

foreach($sts as $st) {
    $instance = new ServiceGroup();
    $name = "";
    $desc = "";
    $monitored = false;
    $email = "default@an_email.net";
    $scope = "Local";

    foreach ($st as $key => $value) {
        switch ($key) {
            case "NAME":
                $name = (string) $value;
                break;
            case "DESCRIPTION":
                $desc = (string) $value;
                break;
            case "MONITORED":
                if ( (string) $value == "Y") {$monitored = true;}
                break;
            case "CONTACT_EMAIL":
                $email = (string) $value;
                break;
            case "SCOPE":
                $scope = (string) $value;
                break;
            default:
                throw new LogicException("Unknown ServiceGroup key in input XML: ". $key);
        }
    }
    $instance->setName($name);
    $instance->setDescription($desc);
    $instance->setMonitored($monitored);
    $instance->setEmail($email);
    $instance->addScope(getScope($entityManager, $scope));

    $entityManager->persist($instance);
}

$entityManager->flush();
