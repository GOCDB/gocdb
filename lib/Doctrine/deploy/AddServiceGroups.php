<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";

/* Loads a list of service types from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the PROM GOCDB PI output for get_service_type
 */

$stFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/ServiceGroups.xml";
$sts = simplexml_load_file($stFileName);

// Checking the XML file has parsed correctly
if ($sts === false) {
    echo "There were errors parsing the XML file.\n";
    // $error will be an object of the libXMLError class
    foreach (libxml_get_errors() as $error) {
        echo $error->message;
    }
    exit;
}

foreach($sts as $st) {
    $instance = new ServiceGroup();
    $name = "";
    $desc = "";
    $monitored = false;
    $email = "";
    $scope = "";

    foreach ($st as $key => $value) {
        if ((string) $key == "NAME") {
            $name = (string) $value;
        }

        if ((string) $key == "DESCRIPTION") {
            $desc = (string) $value;
        }

        if ((string) $key == "MONITORED") {
            if ((string) $value == "Y") {
                $monitored = true;
            }
        }

        if ((string) $key == "CONTACT_EMAIL") {
            $email = (string) $value;
        }
    }

    $instance->setName($name);
    $instance->setDescription($desc);
    $instance->setMonitored($monitored);
    $instance->setEmail($email);

    // Add the owned scope(s) to the service group
    $sc = $st->SCOPES;
    // Iterate through each owned scope
    foreach ($sc->SCOPE as $sco) {
        // Retrieve the scope
        $scope = (string) $sco;

        // Add the scope to the service group
        $instance->addScope(getScope($entityManager, $scope));
    }

    // Add the owned services to the service group
    // Iterate through each owned service
    foreach ($st->SERVICE_ENDPOINT as $se) {
        // Retrieve the service's hostname
        $seName = (string) $se->HOSTNAME;

        /* Query to find the service specified by hostname
         * getSingleResult() ensures only one service is returned
         */
        $dql = "SELECT s FROM Service s WHERE s.hostName = ?1";
        $serviceName = $entityManager->createQuery($dql)
                                     ->setParameter(1, $seName)
                                     ->getSingleResult();

        // Add the service to the service group
        $instance->addService($serviceName);
    }

    $entityManager->persist($instance);
}

$entityManager->flush();
