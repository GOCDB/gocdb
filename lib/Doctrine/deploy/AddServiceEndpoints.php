<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";

/* Loads a list of SEs from an
 * XML file and inserts them into the doctrine prototype.
 * XML format is the output from get_service_endpoints PI query.
 */
$seFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/ServiceEndpoints.xml";
$ses = simplexml_load_file($seFileName);

// Get EGI scope entity to join to later
$dql = "SELECT s from Scope s WHERE s.name = 'EGI'";
$scopes = $entityManager->createQuery($dql)->getResult();
/* Error checking: ensure we have the EGI scope */
if (count($scopes) !== 1) {
    throw new Exception(count($scopes) . " scopes found with short name: EGI");
}

foreach ($scopes as $egiScope) {
    $egiScope = $egiScope;
}

// Get local scope entity to join to later
$dql = "SELECT s from Scope s WHERE s.name = 'Local'";
$scopes = $entityManager->createQuery($dql)->getResult();
/* Error checking: ensure we have the local scope */
if (count($scopes) !== 1) {
    throw new Exception(count($scopes) . " scopes found with short name: Local");
}

foreach ($scopes as $localScope) {
    $localScope = $localScope;
}

foreach ($ses as $xmlSe) {
    $doctrineSe = new Service();
    // get the hosting site entity
    $dql = "SELECT s from Site s WHERE s.shortName = ?1";
    $parentSites = $entityManager->createQuery($dql)
            ->setParameter(1, (string) $xmlSe->SITENAME)
            ->getResult();

    /* Error checking: ensure each SE's "parent site" refers to exactly
     * one ngi */
    if (count($parentSites) !== 1) {
        throw new Exception(count($parentSites) . " sites found with short name: " .
                $xmlSe->SITENAME . ". SE hostname is " . $xmlSe->HOSTNAME);
    }

    foreach ($parentSites as $result) {
        $parentSite = $result;
    }

    $doctrineSe->setParentSiteDoJoin($parentSite);

    // get the hosting service type
    $dql = "SELECT s from ServiceType s WHERE s.name = ?1";
    $sts = $entityManager->createQuery($dql)
            ->setParameter(1, (string) $xmlSe->SERVICE_TYPE)
            ->getResult();

    /* Error checking: ensure each SE's "SERVICE_TYPE" refers to exactly
     * one SERVICE_TYPE */
    if (count($sts) !== 1) {
        throw new Exception(count($sts) . " SERVICE_TYPEs found with name: " .
                $xmlSe->SERVICE_TYPE);
    }

    foreach ($sts as $st) {
        $st = $st;
    }

    $doctrineSe->setServiceType($st);

    // Set production
    if ((string) $xmlSe->IN_PRODUCTION == "Y") {
        $doctrineSe->setProduction(true);
    } else {
        $doctrineSe->setProduction(false);
    }

    // Set Beta
    if ((string) $xmlSe->BETA == "Y") {
        $doctrineSe->setBeta(true);
    } else {
        $doctrineSe->setBeta(false);
    }

    // Set monitored
    if ((string) $xmlSe->NODE_MONITORED == "Y") {
        $doctrineSe->setMonitored(true);
    } else {
        $doctrineSe->setMonitored(false);
    }

    // Set the scope
    if ((string) $xmlSe->SCOPE == "EGI") {
        $doctrineSe->addScope($egiScope);
    } else if ((String) $xmlSe->SCOPE == 'Local') {
        $doctrineSe->addScope($localScope);
    } else {
        throw new Exception("Unknown scope " . $xmlSe->SCOPE . " for SE " . $xmlSe->HOSTNAME);
    }

    //set creation date
    $creationDate = new \DateTime("now", new DateTimeZone('UTC'));


    $doctrineSe->setCreationDate($creationDate);
    $doctrineSe->setDn((string) $xmlSe->HOSTDN);
    $doctrineSe->setIpAddress((string) $xmlSe->HOST_IP);
    $doctrineSe->setOperatingSystem((string) $xmlSe->HOST_OS);
    $doctrineSe->setArchitecture((string) $xmlSe->HOST_ARCH);
    $doctrineSe->setHostName((string) $xmlSe->HOSTNAME);
    $doctrineSe->setDescription((string) $xmlSe->DESCRIPTION);

    // A service has ELs
    $doctrineEndpointLocation = new EndpointLocation();
    $doctrineEndpointLocation->setUrl((string) $xmlSe->URL);
    $doctrineEndpointLocation->setName('sampleEndpoint');
    $doctrineEndpointLocation->setInterfaceName((string)$doctrineSe->getServiceType()->getName());
    $doctrineSe->addEndpointLocationDoJoin($doctrineEndpointLocation);

    $entityManager->persist($doctrineSe);
    $entityManager->persist($doctrineEndpointLocation);
}

$entityManager->flush();
