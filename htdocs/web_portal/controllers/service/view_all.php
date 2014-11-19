<?php
/*______________________________________________________
 *======================================================
 * File: view_all.php
 * Author: John Casson, David Meredith
 * Description: Controller for viewing all Services in GOCDB
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
function drawSEs(){
    define("RECORDS_PER_PAGE", 30);
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

    $seServ = \Factory::getServiceService();
    $exServ = \Factory::getExtensionsService();
    $startRecord = 1;
    if(isset($_REQUEST['record'])) {
       $startRecord = $_REQUEST['record'];
    }
    // Validation, ensure start record >= 1
    if($startRecord < 1) {
        $startRecord = 1;
    }

    $searchTerm = "";
    if(!empty($_REQUEST['searchTerm'])) {
       $searchTerm = $_REQUEST['searchTerm'];
    }

    $serviceType = "";
    if(isset($_REQUEST['serviceType'])) {
       $serviceType = $_REQUEST['serviceType'];
    }
    $production = "";
    if(isset($_REQUEST['production'])) {
    	$production = $_REQUEST['production'];
    }

    $monitored = "";
    if(isset($_REQUEST['monitored'])) {
    	$monitored = $_REQUEST['monitored'];
    }

    $scope = "";
    if(isset($_REQUEST['scope'])) {
    	$scope = $_REQUEST['scope'];
    }

    $ngi = "";
    if(isset($_REQUEST['ngi'])) {
    	$ngi = $_REQUEST['ngi'];
    }
    
    //must be done before the if certstatus in the block that sets $certStatus
    $showClosed = false;
    if(isset($_REQUEST['showClosed'])) {
        $showClosed = true;
    }
    
    $servKeyNames = "";
    if(isset($_REQUEST['servKeyNames'])) {
        $servKeyNames = $_REQUEST['servKeyNames'];
    }
    
    $servKeyValues ="";    
    if(isset($_REQUEST['selectedServKeyValue'])) {
        $servKeyValues = $_REQUEST['selectedServKeyValue'];
    }
    
    $certStatus = "";
    if(!empty($_REQUEST['certificationStatus'])) { 
       $certStatus = $_REQUEST['certificationStatus']; 
       //set show closed as true if production status selected is 'closed' - otherwise
       // there will be no results
       if($certStatus == 'Closed'){
           $showClosed = true;
       }
    }
    
    $thisPage = 'index.php?Page_Type=Services';
    if($serviceType != "") {
        $thisPage .= '&serviceType=' . $serviceType;
    }

    if($searchTerm != "") {
        $thisPage .= '&searchTerm=' . $searchTerm;
    }

    if($production != "") {
    	$thisPage .= '&production=' . $production;
    }

    if($monitored != "") {
    	$thisPage .= '&monitored=' . $monitored;
    }

    if($scope != "") {
    	$thisPage .= '&scope=' . $scope;
    }

    if($ngi != "") {
    	$thisPage .= '&ngi=' . $ngi;
    }
    
    if($certStatus != "") {
    	$thisPage .= '&certStatus=' . $certStatus;
    }
    
    if($showClosed != "") {
    	$thisPage .= '&showClosed=' . $showClosed;
    }
    
    if($servKeyNames != "") {
        $thisPage .= '&servKeyNames=' . $servKeyNames;
    }
    
    if($servKeyValues != "") {
        $thisPage .= '&servKeyValues=' . $servKeyValues;
    }


    if($searchTerm != null || $searchTerm != ""){
        if(substr($searchTerm, 0,1) != '%'){
            $searchTerm ='%'.$searchTerm;
        }
    
        if(substr($searchTerm,-1) != '%'){
            $searchTerm = $searchTerm.'%';
        }
    }
           
    $numResults = $seServ->getSesCount($searchTerm, $serviceType, $production, $monitored, $scope, $ngi, $certStatus, $showClosed, $servKeyNames, $servKeyValues, null, null, false);

    $firstLink = $thisPage . "&record=1";
    // Set the "previous" link
    if($startRecord > RECORDS_PER_PAGE) {
        // Not showing the first page of results so enable the previous link
        $previousLink = $thisPage . "&record=" . ($startRecord - RECORDS_PER_PAGE);
    } else {
        // First page of results, disable previous button
        $previousLink = $thisPage . "&record=" . 0;
    }

    // Set the "Next" link
    // not the last page of results, normal next link
    if($numResults - $startRecord > RECORDS_PER_PAGE) {
        $nextLink = $thisPage . "&record=" . ($startRecord + RECORDS_PER_PAGE);
    } else {
        // last page of results, disable next link
        $nextLink = $thisPage . '&record=' . ($numResults - RECORDS_PER_PAGE + 1);
    }

    $lastLink = $thisPage . "&record=" . ($numResults + 1 - RECORDS_PER_PAGE);

    // $startRecord + RECORDS_PER_PAGE "-1" because record 1 in the web portal == record 0 from DB
    $ses = $seServ->getSes($searchTerm, $serviceType, $production, $monitored, $scope, $ngi, $certStatus, $showClosed, $servKeyNames, $servKeyValues,
            $startRecord - 1, RECORDS_PER_PAGE, false);
    $endRecord = $startRecord + RECORDS_PER_PAGE - 1;

    /* Due to differences in counting, startRecord is still set to 1
     * even if there are zero results. If this is the case it's
     * zero here to display accurately in the portal.  */
    if(count($ses) == 0) {
    	$startRecord = 0;
    }
    
    
    /* Doctrine will provide keynames that are the same even when selecting distinct becase the object
     * is distinct even though the name is not unique. To avoid showing the same name repeatdly in the filter
    * we will load all the keynames into an array before making it unique
    */
	$keynames=array();		
    foreach($exServ->getServiceExtensionsKeyNames() as $extension){
        $keynames[] = $extension->getKeyName();
    }
    $keynames = array_unique($keynames);
    
    $serv = \Factory::getSiteService();
        
    $params['scopes'] = \Factory::getScopeService()->getScopes();
    $params['serviceTypes'] = $seServ->getServiceTypes();
    $params['servKeyNames'] = $keynames;    
    $params['selectedServiceType'] = $serviceType;        
    $params['searchTerm'] = $searchTerm;
    $params['services'] = $ses;
    $params['totalServices'] = $numResults;
    $params['startRecord'] = $startRecord;
    $params['endRecord'] = $endRecord;
    $params['firstLink'] = $firstLink;
    $params['previousLink'] = $previousLink;
    $params['nextLink'] = $nextLink;
    $params['lastLink'] = $lastLink;
    $params['ngis'] = \Factory::getNgiService()->getNGIs();
    $params['certStatuses'] = $serv->getCertStatuses();
    $params['showClosed'] = $showClosed;
    $params['selectedProduction'] = $production;
    $params['selectedMonitored'] = $monitored;
    $params['selectedScope'] = $scope;
    $params['selectedNgi'] = $ngi;
    $params['selectedClosed'] = $showClosed;
    $params['selectedCertStatus'] = $certStatus;
    $params['selectedServKeyNames'] = $servKeyNames;
    $params['selectedServKeyValue'] = $servKeyValues;
    show_view("service/view_all.php", $params, "Services");
}






