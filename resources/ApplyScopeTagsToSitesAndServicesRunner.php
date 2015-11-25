<?php

/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * CLI script to join a batch of scopes to a list of Sites. 
 * <p>
 * You need to update the $requestedScopeNames and $requestedSiteNames arrays 
 * to specify which sites/scopes you want to link. Note, the sites and scopes 
 * must already be added to the DB. 
 * 
 * @author David Meredith 
 */
require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once __DIR__ . '/../lib/Gocdb_Services/Scope.php';
require_once __DIR__ . '/../lib/Gocdb_Services/Site.php';

// Specify a list of Scopes that you want to add to the sites/services. 
//$requestedScopeNames = array('DAVE', 'Local', 'EGI'); //'wlcg', 'cms');

// Specify a list of Site Names to add the scopes
//$requestedSiteNames = array('100IT', 'AEGIS01-IPB-SCL');

//$requestedScopeNames = array('wlcg', 'alice');
//$requestedSiteNames = array(
//'AM-04-YERPHI', 'BUDAPEST', 'CETA-GRID', 'CYFRONET-LCG2', 'FMPhI-UNIBA', 'FZK-LCG2', 'GRIF', 'GSI-LCG2', 'HG-03-AUTH', 'ICN-UNAM', 'IEPSAS-Kosice',
//'IN-DAE-VECC-02', 'IN2P3-CC', 'IN2P3-IPNL', 'IN2P3-IRES', 'IN2P3-LPC', 'IN2P3-LPSC', 'IN2P3-SUBATECH', 'INFN-BARI', 'INFN-CAGLIARI', 'INFN-CATANIA',
//'INFN-LNL-2', 'INFN-T1', 'INFN-TORINO', 'INFN-TRIESTE', 'ITEP', 'JINR-LCG2', 'JP-HIROSHIMA-WLCG', 'KR-KISTI-GSDC-01', 'NCP-LCG2', 'NDGF-T1', 'NIHAM',
//'NIKHEF-ELPROD', 'PK-CIIT', 'PSNC', 'RAL-LCG2', 'RO-07-NIPNE', 'RO-13-ISS', 'RRC-KI', 'RRC-KI-T1', 'RU-Protvino-IHEP', 'RU-SPbSU', 'Ru-Troitsk-INR-LCG2', 'SAMPA',
//'SARA-MATRIX', 'SE-SNIC-T2', 'SUPERCOMPUTO-UNAM', 'T2-TH-CUNSTDA', 'T2-TH-SUT', 'UA-BITP', 'UA-ISMA', 'UA-KNU', 'UKI-SOUTHGRID-BHAM-HEP', 'UKI-SOUTHGRID-OX-HEP', 'WUT',
//'ZA-CHPC', 'praguelcg2', 'ru-Moscow-MEPHI-LCG2', 'ru-PNPI'); 

//$requestedScopeNames = array('wlcg', 'lhcb');
//$requestedSiteNames = array( 'AUVERGRID', 'CBPF', 'CERN-PROD', 'CSCS-LCG2', 'CYFRONET-LCG2', 'DESY-HH', 'DESY-ZN', 'FZK-LCG2', 'GRIF', 'ICM', 'IL-TAU-HEP',
//'IN2P3-CC', 'IN2P3-CPPM', 'IN2P3-LAPP', 'IN2P3-LPC', 'INFN-BARI', 'INFN-CATANIA', 'INFN-FERRARA', 'INFN-FRASCATI', 'INFN-LNL-2', 'INFN-NAPOLI-ATLAS', 'INFN-PISA',
//'INFN-T1', 'INFN-TORINO', 'ITEP', 'JINR-LCG2', 'NIKHEF-ELPROD', 'PSNC', 'RAL-LCG2', 'RECAS-BARI', 'RO-07-NIPNE', 'RO-11-NIPNE', 'RO-15-NIPNE', 'RRC-KI', 'RRC-KI-T1',
//'RU-Protvino-IHEP', 'RU-SPbSU', 'Ru-Troitsk-INR-LCG2', 'SAMPA', 'SARA-MATRIX', 'TECHNION-HEP', 'UB-LCG2', 'UKI-LT2-Brunel', 'UKI-LT2-IC-HEP', 'UKI-LT2-QMUL', 'UKI-LT2-RHUL',
//'UKI-NORTHGRID-LANCS-HEP', 'UKI-NORTHGRID-LIV-HEP', 'UKI-NORTHGRID-MAN-HEP', 'UKI-NORTHGRID-SHEF-HEP', 'UKI-SCOTGRID-DURHAM', 'UKI-SCOTGRID-ECDF', 'UKI-SCOTGRID-GLASGOW',
//'UKI-SOUTHGRID-BHAM-HEP', 'UKI-SOUTHGRID-BRIS-HEP', 'UKI-SOUTHGRID-CAM-HEP', 'UKI-SOUTHGRID-OX-HEP', 'UKI-SOUTHGRID-RALPP', 'USC-LCG2', 'WEIZMANN-LCG2', 'pic', 'ru-PNPI',
//); 


//$requestedScopeNames = array('wlcg', 'cms'); 
//$requestedSiteNames = array(
//'CERN-PROD', 'FZK-LCG2', 'pic', 'IN2P3-CC', 'INFN-T1', 'JINR-T1', 'RAL-LCG2', 'Hephy-Vienna', 'BEgrid-ULB-VUB', 'BelGrid-UCL', 'CSCS-LCG2',
//'BEIJING-LCG2', 'DESY-HH', 'RWTH-Aachen', 'T2_Estonia', 'CIEMAT-LCG2', 'IFCA-LCG2', 'FI_HIP_T2', 'IN2P3-CC-T2', 'GRIF', 'IN2P3-IRES',
//'GR-07-UOI-HEPLAB', 'BUDAPEST', 'INDIACMS-TIFR', 'INFN-BARI', 'INFN-LNL-2', 'INFN-PISA', 'INFN-ROMA1-CMS', 'LCG_KNU', 'MY-UPM-BIRUNI-01', 'NCP-LCG2',
//'NCBJ-CIS', 'ICM', 'NCG-INGRID-PT', 'RU-Protvino-IHEP', 'Ru-Troitsk-INR-LCG2', 'ITEP', 'JINR-LCG2', 'ru-PNPI', 'RRC-KI', 'ru-Moscow-SINP-LCG2', 'T2-TH-CUNSTDA',
//'TR-03-METU', 'Kharkov-KIPT-LCG2', 'UKI-LT2-Brunel', 'UKI-LT2-IC-HEP', 'UKI-SOUTHGRID-BRIS-HEP', 'UKI-SOUTHGRID-RALPP',
////'IFRU',
//); 

// set the $requestesSiteNames by including the file
//require __DIR__ . '/ApplyScopeTagsToSites_SiteValues.php'; 
//$requestedSiteNames = $requestedSiteNames2; 

//$requestedScopeNames = array('wlcg', 'atlas'); 
//$requestedSiteNames = array(); 


$requestedScopeNames = array('tier1'); 
$requestedSiteNames = array( 'TRIUMF-LCG2', 'IN2P3-CC', 'FZK-LCG2', 'INFN-T1', 'NIKHEF-ELPROD',
'SARA-MATRIX', 'NDGF-T1', 'KR-KISTI-GSDC-01', 'RRC-KI-T1', 'JINR-T1', 'pic', 'Taiwan-LCG2',
'RAL-LCG2', 'USCMS-FNAL-WC1', 'BNL-ATLAS'); 


//$requestedScopeNames = array('tier2'); 
//$requestedSiteNames = array( 'Australia-ATLAS', 'HEPHY-UIBK', 'Hephy-Vienna', 'BEgrid-ULB-VUB', 'BelGrid-UCL',
//'CA-MCGILL-CLUMEQ-T2', 'CA-SCINET-T2', 'CA-VICTORIA-WESTGRID-T2', 'SFU-LCG2', 'BEIJING-LCG2', 'praguelcg2', 'T2_Estonia',
//'FI_HIP_T2', 'IN2P3-CC-T2', 'IN2P3-CPPM', 'GRIF', 'IN2P3-IRES', 'IN2P3-LAPP', 'IN2P3-LPC', 'IN2P3-LPSC', 'IN2P3-SUBATECH',
//'DESY-HH', 'DESY-ZN', 'UNI-FREIBURG', 'wuppertalprod', 'GoeGrid', 'LRZ-LMU', 'MPPMU', 'RWTH-Aachen', 'GSI-LCG2', 'GR-12-TEIKAV',
//'GR-07-UOI-HEPLAB', 'BUDAPEST', 'INDIACMS-TIFR', 'IN-DAE-VECC-02', 'IL-TAU-HEP', 'TECHNION-HEP', 'WEIZMANN-LCG2', 'INFN-BARI',
//'INFN-CATANIA', 'INFN-CNAF-LHCB', 'INFN-FRASCATI', 'INFN-LNL-2', 'INFN-MILANO-ATLASC', 'INFN-NAPOLI-ATLAS', 'INFN-PISA',
//'INFN-ROMA1', 'INFN-ROMA1-CMS', 'INFN-TORINO', 'TOKYO-LCG2', 'CBPF', 'EELA-UTFSM', 'ICN-UNAM', 'SAMPA', 'SUPERCOMPUTO-UNAM',
//'PK-CIIT', 'NCP-LCG2', 'CYFRONET-LCG2', 'ICM', 'PSNC', 'LIP-Coimbra', 'LIP-Lisbon', 'NCG-INGRID-PT', 'LCG_KNU', 'NIHAM',
//'RO-02-NIPNE', 'RO-07-NIPNE', 'RO-11-NIPNE', 'RO-13-ISS', 'RO-14-ITIM', 'RO-16-UAIC', 'ITEP', 'JINR-LCG2', 'RRC-KI', 'RU-Protvino-IHEP',
//'RU-SPbSU', 'Ru-Troitsk-INR-LCG2', 'ru-Moscow-FIAN-LCG2', 'ru-Moscow-SINP-LCG2', 'ru-PNPI', 'FMPhI-UNIBA', 'IEPSAS-Kosice', 'SiGNET',
//'ZA-CHPC', 'IFIC-LCG2', 'UAM-LCG2', 'ifae', 'CIEMAT-LCG2', 'IFCA-LCG2', 'UB-LCG2', 'USC-LCG2', 'SE-SNIC-T2', 'CSCS-LCG2',
//'UNIBE-LHEP', 'TW-FTT', 'T2-TH-CUNSTDA', 'T2-TH-SUT', 'TR-03-METU', 'TR-10-ULAKBIM', 'UKI-LT2-Brunel', 'UKI-LT2-IC-HEP', 'UKI-LT2-QMUL',
//'UKI-LT2-RHUL', 'UKI-LT2-UCL-HEP', 'UKI-NORTHGRID-LANCS-HEP', 'UKI-NORTHGRID-LIV-HEP', 'UKI-NORTHGRID-MAN-HEP', 'UKI-NORTHGRID-SHEF-HEP', 'UKI-SCOTGRID-DURHAM',
//'UKI-SCOTGRID-ECDF', 'UKI-SCOTGRID-GLASGOW', 'EFDA-JET', 'UKI-SOUTHGRID-BHAM-HEP', 'UKI-SOUTHGRID-BRIS-HEP', 'UKI-SOUTHGRID-CAM-HEP', 'UKI-SOUTHGRID-OX-HEP',
//'UKI-SOUTHGRID-RALPP', 'UKI-SOUTHGRID-SUSX', 'Kharkov-KIPT-LCG2', 'UA-BITP', 'UA-ISMA', 'UA-KNU',); 

// specify '--force' to actually execute the changes, otherwise script will 
// only show which sites will be updated with which scopes 
$forceOrShow = "--show";
$commandLineArgValid = false;
if (isset($argv[1])) {
    $forceOrShow = $argv[1];
    if ($forceOrShow == '--force' || $forceOrShow == '--show' || '--listSiteNames') {
	$commandLineArgValid = true;
    }
}
if (!$commandLineArgValid) {
    die("Usage: php <scriptName> --force or --show or --listSiteNames \n");
}




// Setp connection and services 
$em = $entityManager;
$scopeService = new org\gocdb\services\Scope();
$scopeService->setEntityManager($em);
$siteService = new org\gocdb\services\Site();
$siteService->setEntityManager($em);

echo "Checking for duplicates in requested sites/scopes \n"; 
if(count($requestedSiteNames) !== count(array_unique($requestedSiteNames))){
//    $uarr = array_unique($requestedSiteNames);
//    $dups = var_dump(array_diff($requestedSiteNames, array_diff($uarr, array_diff_assoc($requestedSiteNames, $uarr))));
//    foreach($dups as $dup){
//	echo "$dups\n"; 
//    }
    die("ERROR - Requested sites has a duplicate entry\n"); 
}
if(count($requestedScopeNames) !== count(array_unique($requestedScopeNames))){
    die("ERROR - Requested scope has a duplicate entry\n"); 
}


// Nasty shortcut just to extract all the GOCDB names from the site name list 
if ($forceOrShow == '--listSiteNames') {
    foreach ($requestedSiteNames as $requestedSiteName) {
	$filterParams = array('sitename' => $requestedSiteName);
	$siteArray = $siteService->getSitesFilterByParams($filterParams);
	if (count($siteArray) == 0) {
	    //die("ERROR - Requested Site does not exist in the DB [" . $requestedSiteName . "]\n");
	} else {
	    echo $siteArray[0]->getShortName()."\n"; 
	}
    }
    die('done'); 
}



// Check that the requested scopes actually exist in the DB and populate  
// the 'targetScopesToAdd' array. 
echo "Checking the requested Scopes exist in the DB\n";
$allScopes = $scopeService->getScopes();
$targetScopesToAdd = array();
foreach ($requestedScopeNames as $requestedScopeName) {
    $requestedScopeExist = false;
    // iterate all DB scopes and check the requested scope exists
    /* @var $scope \Scope */
    foreach ($allScopes as $scope) {
	if ($scope->getName() == $requestedScopeName) {
	    $targetScopesToAdd[] = $scope;
	    $requestedScopeExist = true;
	    break;
	}
    }
    if (!$requestedScopeExist) {
	die("Requested scope don't exist [" . $requestedScopeName . "]\n");
    }
}
echo "Scopes OK\n"; 




// Check that the requested sites exist in the DB. 
echo "Checking requested Sites exist in the DB\n";
foreach ($requestedSiteNames as $requestedSiteName) {
    $filterParams = array('sitename' => $requestedSiteName);
    $siteArray = $siteService->getSitesFilterByParams($filterParams);
    if (count($siteArray) == 0) {
	die("ERROR - Requested Site does not exist in the DB [" . $requestedSiteName . "]\n");
    }
}
echo "Sites OK\n";

// If true, the target scopes will be added to each Site's child services.
$applyScopeToChildSites = true;

// Run all updates in tx to rollback if issue occurs 
$em->getConnection()->beginTransaction();
try {
// Get Site instance, see if it is missing a targetScope  
    foreach ($requestedSiteNames as $requestedSiteName) {
	$filterParams = array('sitename' => $requestedSiteName);
	$siteArray = $siteService->getSitesFilterByParams($filterParams);
	/* @var $site \Site */
	$site = $siteArray[0];
	$siteScopes = $site->getScopes();
	echo $site->getName() . ':';

	foreach ($targetScopesToAdd as $addTargetScope) {
	    $addScope = true;

	    // Iterate Site's scopes and determine if it already has the targetScope 
	    /* @var $siteScope \Scope */
	    foreach ($siteScopes as $siteScope) {
		if ($siteScope->getName() == $addTargetScope->getName()) {
		    // the site already has the requested scope, so break
		    $addScope = false;
		    break;
		}
	    }
	    // Site don't have scope, so add it and echo 
	    if ($addScope) {
		echo ' +' . $addTargetScope->getName();
		//if ($forceOrShow == '--force') {
		    $site->addScope($addTargetScope);
		    $em->persist($addTargetScope);
		//}
	    }
	    // Iterate child services scopes and see if each is missing the targetScope
	    if ($applyScopeToChildSites) {
		//echo "\n\tSEs: (".$addTargetScope->getName(); 
		/* @var $se \Service */
		foreach ($site->getServices() as $se) {
		    $addSeScope = true;
		    $seScopes = $se->getScopes();
		    foreach ($seScopes as $seScope) {
			if ($seScope->getName() == $addTargetScope->getName()) {
			    // the service already has the scope, so break 
			    $addSeScope = false;
			    break;
			}
		    }
		    // Service don't have scope, so add it 
		    if ($addSeScope) {
			//echo ' +'.$se->getHostName().',';  	
			//if ($forceOrShow == '--force') {
			    $se->addScope($addTargetScope);
			    $em->persist($se); 
			//}
		    }
		}
		//echo ')'; 
	    }
	}
	echo "\n";
    }

    if ($forceOrShow == '--force') {
	$em->flush();
	$em->getConnection()->commit();
    }
    
} catch (\Exception $e) {
    $em->getConnection()->rollback();
    $em->close();
    echo $e->getMessage(); 
}


