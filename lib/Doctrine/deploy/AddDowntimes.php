<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__."/AddUtils.php";
require_once __DIR__.'/../entities/AlreadyLinkedException.php';


// For each of the hostnames below, GOCDB has two SRM services 
// registered for each.
// Similarly, the Doctrine prototype contains two of each of these SEs.
// However when linking a downtime to one of these service els,
// the Doctrine prototype doesn't know which of the two is affected.
// This leads to a problem where Doctrine tries to link one downtime to
// the same Service twice (not allowed). In the production GOCDB
// the one downtime is linked to both instances of the service ep
// but Doctrine doesn't have enough info from the PI query to figure
// this out.
//
// The duplicates are all SRM services apart from cert-ce-01 (CE) and
// hyx.grid.icm.edu.pl (Unicore6.StorageManagement)  - something may be wrong with the
// SRM service type the PROM GOCDB causing duplicate service eps 
// to be registered. Either that or there's a business reason
// for registering two. 
$duplicateSes = array ("se.reef.man.poznan.pl",  "lcg05.sinp.msu.ru",
		"grid-se.ii.edu.mk",
		"storage01.lcg.cscs.ch", "grid002.ics.forth.gr",
		"eymir.grid.metu.edu.tr", "torik1.ulakbim.gov.tr",
		"hyx.grid.icm.edu.pl", "cert-ce-01.cnaf.infn.it", // cert-ce-01 is a CE, not SRM
		"se01.mosigrid.utcluj.ro");
// Hack: the above SEs appear twice with the same service type. (nightmare)
// However in most cases both SEs share the same downtimes...

// AddDowntimes.php: Loads a list of downtimes  from an
// XML file and inserts them into the doctrine prototype.
// XML format is the output from get_service_endpoints PI query.
// 

// Set the timezone
date_default_timezone_set("UTC");

$allDowntimes = array();
$downtimeFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/Downtimes.xml";
$downtimes = simplexml_load_file($downtimeFileName);
$largestV4DowntimePK = 0; 

foreach ($downtimes as $downtimeXml) {
    // Each "downtimeXML" element is one SE's downtime.
    // If a single downtime affects many SEs, each
    // SE will be named in a different $downtimeXml that will duplicate
    // all the downtime specific fields.
    
    foreach($downtimeXml->attributes() as $key => $value) {
        if((string) $key == "ID") {
            $promId = (int) $value;
        }
    }

    $downtime = null;
    // See if we've already entered a downtime with this prom ID
    if(isset($allDowntimes[$promId])) {
        // load $downtime from db by $promId rather than loading from global array  
        // This assumes xml ID attribute (without 'G0' appended) is the same as 
        // xml PRIMARY_KEY attribute. 
        //  
        // $downtime = $entityManager->createQuery("select d FROM Downtime d WHERE d.primaryKey = ?1")
		//   ->setParameter(1, (string) $promId.'G0')
		//   ->getResult();
        $downtime = $allDowntimes[$promId];
    }

	if(!isset($downtime)) {
	    // Create a new downtime, add the SE to it
	    $downtime = newDowntime($downtimeXml);
        // Finds one or more SEs by hostname and service type
      	$services = findSEs((string) $downtimeXml->HOSTNAME
    		, (string) $downtimeXml->SERVICE_TYPE);
        
        // There are some edge cases where findSEs returns
        // more than one SE (see the comment at the top of this file)
        // However if the downtime isn't yet created we always
        // link to the first SE found.
        if(!isset($services[0])) {
        	throw new Exception("No SE found with "
    				. "hostname " . $downtimeXml->HOSTNAME . " ");
        }

        // Bidirectional link the el and dt 
        $els = $services[0]->getEndpointLocations(); 
        $downtime->addEndpointLocation($els[0]); 
	    //$downtime->addService($services[0]);

        // save the id rather than the whole downtime to reduce memory 
	    $GLOBALS['allDowntimes'][$promId] = $downtime;
	} else {
    	// Find the SE and link it to the downtime
    	$services = findSEs((string) $downtimeXml->HOSTNAME
    			, (string) $downtimeXml->SERVICE_TYPE);

    	if(!isset($services[0])) {
    		throw new Exception("No SE found with "
    				. "hostname " . $downtimeXml->HOSTNAME . " ");
    	}
    	try {
            // TODO? - We should probably iterate each el and try to link each 
            // to this DT. Will still need to throw alreadylinked exception when 
            // trying to link a SE that is already linked to the downtime
            // in order to detect the SE duplicates. But iterating may only be necessary 
            // if it emerges that there are instances of more than 2 duplicate SEs 
            // (the duplicate SE count is tested for the expected 2 duplicates 
            // below in catch block). 
            $els = $services[0]->getEndpointLocations(); 
            if(count($els) > 1){
                throw new LogicException('Coding error - there should only be one EL per Service'); 
            }
            
            // Check this endpoint isn't already linked to downtime. 
            // The els have already been persisted/flushed against
            // the DB and so already have IDs. 
            foreach($downtime->getEndpointLocations() as $existingEL) {
                if($existingEL == $els[0]) { // their Ids will be the same 
                    throw new AlreadyLinkedException("Downtime {$downtime->getId()} is already "
                    . "linked to el {$existingEL->getId()}");
                }
            }

            // Bidirectional link the el and dt 
            $downtime->addEndpointLocation($els[0]); 
		   	//$downtime->addService($services[0]);
            
    	} catch (Exception $e) {
    		if($e instanceof AlreadyLinkedException) {
                // Downtime is already linked to this SE 
                
    			// Check whether this exception is caused by a known issue
    			// with duplicate SEs (see comment at the top of the file).
    			// Issue is known if two SEs are found and the hostname is
    			// a known duplicate 
    			$twoSes = false;
    			if(count($services) == 2) {
    				$twoSes = true;
    			} else {
                    // we will have to deal with this case and link the 
                    throw new Exception("More than duplicate 2 SEs found: ".$services[0]->getHostName()); 
                }

    			$knownDup = false;
    			foreach($duplicateSes as $dup) {
    				if($dup == $services[0]->getHostName()) {
    					$knownDup = true;
    				}
    			}

    			// If the above two tests are true then we've hit an edge case
    			// where a downtime currently links to one SE that's a known
    			// duplicate and it needs to link to the other (duplicated) SE.
    			// The other SE will always be the second result in $services ([1])
    			if($twoSes && $knownDup) {
    				//$downtime->addService($services[1]);
                    $els = $services[1]->getEndpointLocations(); 

        
		// Check this SE isn't already registered
		//foreach($downtime->getEndpointLocations() as $existingEL) {
		//	if($existingEL == $els[0]) {
		//		throw new AlreadyLinkedException("Downtime {$downtime->getId()} is already "
		//		. "linked to el {$existingEL->getId()}");
		//	}
		//}
                   
                    // Bidirectional link the el and dt 
                    $downtime->addEndpointLocation($els[0]);
    			}
    		}
    	}
	}
}

foreach($allDowntimes as $downtime) {
	$GLOBALS['entityManager']->persist($downtime);
}

try {
	$entityManager->flush();
} catch(Exception $e) {
	print_r($e);
}

// Creates a new Doctrine downtime entity from the output of a get_downtime
// SimpleXML element.
function newDowntime($downtimeXml) {
    $downtime = new Downtime();
    foreach($downtimeXml->attributes() as $key => $value) {
        switch($key) {
            case "ID":
                $promId = (int) $value;
                break;
            case "PRIMARY_KEY":
                $primaryKey = (string) $value;
                break;
            case "CLASSIFICATION":
                $classification = (string) $value;
                break;
        }
    }

    // Get the largest v4 downtime PK which is an integer appended by the string 'G0'
    // slice off the 'G0' and get the integer value. 
    $v4pk = (int)substr($primaryKey, 0, strlen($primaryKey)-2); 
    if($v4pk > $GLOBALS['largestV4DowntimePK']){
        $GLOBALS['largestV4DowntimePK'] = $v4pk; 
    }

    $downtime->setClassification($classification);
    $downtime->setDescription((string) $downtimeXml->DESCRIPTION);
    $downtime->setSeverity((string) $downtimeXml->SEVERITY);
    $startDate = new DateTime("@" . (string) $downtimeXml->START_DATE);
    $downtime->setStartDate($startDate);
    $endDate = new DateTime("@" . (string) $downtimeXml->END_DATE);
    $downtime->setEndDate($endDate);
    $insertDate = new DateTime("@" . (string) $downtimeXml->INSERT_DATE);
    $downtime->setInsertDate($insertDate);
    $downtime->setPrimaryKey($primaryKey);  

    return $downtime;
}

