<?php
/*______________________________________________________ 
 *======================================================
 * File: view_all.php
 * Author: John Casson, David Meredith
 * Description: Controller for viewing all Sites in GOCDB
 * 
 * License information
 *   
 * Copyright 2009 STFC 
 * Licensed under the Apache License, Version 2.0 (the "License"); 
 * you may not use this file except in compliance with the License. 
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0 
 * Unless required by applicable law or agreed to in writing, software 
 * distributed under the License is distributed on an "AS IS" BASIS, 
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. 
 * See the License for the specific language governing permissions and 
 * limitations under the License.
 *  
/*====================================================== */ 
function showAllSites(){
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    
    $exServ = \Factory::getExtensionsService();    
   
    // Do we really need to validate the URL parameter values, as the query 
    // to the DB always uses bind variables to protect against injection? 
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Validate.php';
    $validatorService = new \org\gocdb\services\Validate();  
    
    
    $ngi = '%%';
    if(!empty($_GET['NGI'])) { 
       $ngi = $_GET['NGI'];
       if(!$validatorService->validate('ngi', 'NAME', $ngi)){
          throw new Exception("Invalid NGI parameter value");  
       }
    }
        
    $prodStatus = '%%';
    if(!empty($_GET['prodStatus'])) { 
       $prodStatus = $_GET['prodStatus'];
    }
    
    //must be done before the if certstatus in the block that sets $certStatus
    $showClosed = false;
    if(isset($_GET['showClosed'])) {
        $showClosed = true;
    }
    
    $certStatus = '%%';
    if(!empty($_GET['certStatus'])) { 
       $certStatus = $_GET['certStatus']; 
       //set show closed as true if production status selected is 'closed' - otherwise
       // there will be no results
       if($certStatus == 'Closed'){
           $showClosed = true;
       }
    }
   
    // Site extension property key name
    $siteKeyNames = "";
    if(isset($_GET['siteKeyNames'])) {
        $siteKeyNames = $_GET['siteKeyNames'];
    }
   
    // Site extension property key value
    $siteKeyValues ="";
    if(isset($_GET['selectedSiteKeyValue'])) {
        $siteKeyValues = $_GET['selectedSiteKeyValue'];
    }

//    if(!empty($_GET['scope'])) { 
//	print_r($_GET['scope']); 
//	foreach($_GET['scope'] as $key => $val){
//	    print_r($key. ' '.$val); 
//	}
//	die('forced diave');
//    }
   
    $scope = '%%';
    if(!empty($_GET['scope'])) { 
       $scope = $_GET['scope'];
       }
	
    $serv = \Factory::getSiteService();

    $params['scopes']=  \Factory::getScopeService()->getScopes(); 
    $params['sites'] = $serv->getSitesBy($ngi, $prodStatus, $certStatus, $scope, $showClosed, null, $siteKeyNames, $siteKeyValues); 
    //$params['sites'] = $serv->getSitesByTest2(); 
    $params['NGIs'] = $serv->getNGIs();
    $params['prodStatuses'] = $serv->getProdStatuses();
        
    //Remove SC and PPS infrastructures from drop down list. TODO: Delete this block once they no longer exist
    $SCInfrastructure = $serv->getProdStatusByName('SC');
    $PPSInfrastructure = $serv->getProdStatusByName('PPS'); 
    $productionStatuses=array();
    foreach($params['prodStatuses'] as $ps){
        if($ps != $SCInfrastructure and $ps != $PPSInfrastructure){
            $productionStatuses[]=$ps;
        }
    }
    $params['prodStatuses'] = $productionStatuses;
    //delete up to here once pps and sc infrastructures have been removed from database
        
    /* Doctrine will provide keynames that are the same even when selecting distinct becase the object
     * is distinct even though the name is not unique. To avoid showing the same name repeatdly in the filter
     * we will load all the keynames into an array before making it unique
     */
	$keynames=array();	
    foreach($exServ->getSiteExtensionsKeyNames() as $extension){
        $keynames[] = $extension->getKeyName();
    }
    $keynames = array_unique($keynames);
    
    $params['selectedNgi'] = $ngi;
    $params['certStatuses'] = $serv->getCertStatuses();
    $params['selectedProdStatus'] = $prodStatus;
    $params['selectedCertStatus'] = $certStatus;
    $params['selectedScope'] = $scope;
    $params['showClosed'] = $showClosed;
    $params['siteKeyNames'] = $keynames;
    $params['selectedSiteKeyNames'] = $siteKeyNames;
    $params['selectedSiteKeyValue'] = $siteKeyValues;   
    
    show_view("site/view_all.php", $params, "Sites");
}