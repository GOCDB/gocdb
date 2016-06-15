<?php

/**
 * Script to update Mon=T for all Prod=T services. 
 * This script is not needed to perform a version update. 
 */
use Doctrine\ORM\EntityManager;

require_once dirname(__FILE__) . "/../bootstrap.php";

if (!isset($argv[1]) || strcmp($argv[1], '--force')) { //strcmp returns 0 (i.e. false) if strings are equal
    die("Error. Usage:  php " . basename(__FILE__) . " --force \n");
}
//if(true)die("forced die \n"); 

echo "Updating database relations and entities - For each service set Mon=T where Prod=T \n";

$em = $entityManager;

//get all services	
$dql = "SELECT se FROM Service se";
$services = $entityManager->createQuery($dql)->getResult();

//For each service set mon to true where prod is true 
echo "\n";
$s_count = 0;
foreach ($services as $service) {
    if ($service->getProduction() && ($service->getMonitored() == FALSE)) {
    // exclude closed sites 
    if ($service->getParentSite()->getCertificationStatus()->getName() != 'Closed') {
        if ($service->getServiceType()->getName() != 'VOMS' && $service->getServiceType()->getName() != 'emi.ARGUS') {
        echo 'Service: ' . $service->getId() . ' ' . $service->getHostName() . "\n";
        $service->setMonitored(TRUE);
        $em->persist($service);
        ++$s_count;
        }
    }
    }
}
//Write changes to db
$em->flush();
die('Done. Updated [' . $s_count . "] services \n");

