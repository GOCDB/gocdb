<?php

/**
 * This script is for updating a 5.2 (pre-meps) DB to work with the multiple 
 * endpoints (mep) GOCDBv5.3 data model. Follow steps 1) and 2) below BEFORE
 * running this script. 
 * 
 * This is a one time script that is used on first deployment of the MEPS model 
 * against existing data from the pre-MEPS versions of GocDB. It is not required
 * for a new install of GocDB with no data.  
 * 
 * After running this script, you need to run the MEP GOCDBv5.3 
 * (v5.2 will not work against the udpated DB, you have been warned!).   
 * 
 * Usage:
 * ======
 *  
 * 1) Update DB tables
 * ====================
 * Before running this script, you MUST update the DB schema to correspond to the 
 * 5.3 mep Doctrine entity model. This can be done using the following Doctrine
 * commands on the command line:
 * 
 * // 1.1) Test you can run the schema-tool on the command line: 
 *  $doctrine orm:schema-tool:update --help
 * 
 * // 1.2) View what DB schema changes would occur without actually updating the DB:
 * $doctrine orm:schema-tool:update --dump-sql
 *     ...SQL DDL statemetns will be printed here...
 *
 * // 1.3) Update the DB schema using force (or copy the DDL as printed above and run manually): 
 * $doctrine orm:schema-tool:update --force
 *     Updating database schema...
 *     Database schema updated successfully! "n" queries were executed
 *
 *  
 * 2) Run this script 
 * ====================
 * // 2.1) Cd into '<GOCDB_SRC_HOME>/lib/Doctrine/versionUpdateScripts'  
 * 
 * // 2.2) Run this script on the command line using: 
 * $php MEPS_update_scriptRunner.php --force  
 * 
 * 
 * The script does the following: 
 * a. Copies up the URL field from each service's single EndpointLocation entity
 *    up to the service entity's url field. 
 * b. For each Downtime create a new link to the affected service and remove the join
 *    between the downtime and the endpoint 
 *    Then unlink the EndpointLocation from the downtime so only the Service-Downtime link remains. 
 * c. Finally all endpoints are unlinked from their parent services and deleted 
 *    to allow the MEPS model to be used correctly in the future. 
 */
use Doctrine\ORM\EntityManager; 

require_once dirname(__FILE__) . "/../bootstrap.php";
  
    if(!isset($argv[1]) || strcmp($argv[1],  '--force') ){ //strcmp returns 0 (i.e. false) if strings are equal
        die("Error. Usage:  php ".basename(__FILE__)." --force \n"); 
    }
    //if(true)die("forced die \n"); 

	echo "Updating database relations and entities for MEPS \n";
    $em = $entityManager; 
	
	//get all services	
	$dql = "SELECT se FROM Service se";
	$services = $entityManager->createQuery($dql)->getResult();

	//For each service extract the endpoint(s) and get the URL. Write that URL to the service.
    echo "Copying [Service->EndpointLocation->url] field to [Service->url field]\n";  
	foreach($services as $service){
		$endpoints = $service->getEndpointLocations();
        foreach($endpoints as $endpoint){
            $url = $endpoint->getUrl();
            if($url != null){
                //echo "Adding URL to ".$service->getHostName()."\n";
                //echo $url."\n";
                echo "."; 
                $service->setUrl($url);
                $em->persist($service);
            }
        }
	}
    //Write changes to db
	$em->flush();

	//get all downtimes
	$dql = "SELECT d FROM Downtime d";
	$downtimes =  $entityManager->createQuery($dql)->getResult();
    
	/*For each downtime make a new link to the affected service and remove the join
	* between the downtime and the endpoint*/
    echo "\n"; 
    echo "Linking [Service-to-Downtime] and deleting the current (single) [Downtime-to-EndpointLocation] association \n"; 
	foreach($downtimes as $downtime){
		$endpoints = $downtime->getEndpointLocations();
		foreach($endpoints as $endpoint){
			//echo $downtime->getId().",";
            echo "."; 
			$service = $endpoint->getService();			
			$downtime->addService($service);
			$downtime->removeEndpointLocation($endpoint);
			$em->persist($downtime);
		}
	}
	
	//Write changes to db
	$em->flush();
	
	//get all services	
	$dql = "SELECT se FROM Service se";
	$services = $entityManager->createQuery($dql)->getResult();
	
	//For each service remove the endpoint
    echo "\n"; 
    echo "Delete Service's single [EndpointLocation] entity \n"; 
	foreach($services as $service){
		$endpoints = $service->getEndpointLocations();
			foreach($endpoints as $endpoint){
				//echo $endpoint->getId().", ";
                echo "."; 
				//Remove the endpoint from the services collection of endpoints
				//$service->remove_EndpointLocation($endpoint);
                $service->getEndpointLocations()->removeElement($endpoint); 
				//Delete the orphaned entity
		        $em->remove($endpoint);
				$em->persist($service);				
			}
	}
	
	//Write changes to db
	$em->flush();
    echo "\n Done\n"; 	
?>
