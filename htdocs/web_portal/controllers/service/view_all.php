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
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    $seServ = \Factory::getServiceService();
    $exServ = \Factory::getExtensionsService();

    $startRecord = 1;
    if(isset($_GET['record'])) {
       $startRecord = $_GET['record'];
    }
    $searchTerm = "";
    if(!empty($_GET['searchTerm'])) {
       $searchTerm = $_GET['searchTerm'];
    }
    $serviceType = "";
    if(isset($_GET['serviceType'])) {
       $serviceType = $_GET['serviceType'];
    }
    $production = "";
    if(isset($_GET['production'])) {
        $production = $_GET['production'];
    }
    $monitored = "";
    if(isset($_GET['monitored'])) {
        $monitored = $_GET['monitored'];
    }
    $scopeMatch = "";
    if(isset($_GET['scopeMatch'])) {
        $scopeMatch = $_GET['scopeMatch'];
    }
    // By default, use an empty value to return all scopes, i.e. in the PI '&scope='
    // which is same as the PI. We don't want to fall back on default scope if scope param is not set.
    $selectedScopes = array();
    $scope = '';
    if(!empty($_GET['mscope'])) {
        foreach($_GET['mscope'] as $key => $scopeVal){
            $scope .= $scopeVal.',';
            $selectedScopes[] = $scopeVal;
        }
    } elseif (\Factory::getConfigService()->getDefaultFilterByScope()) {
        $scopeVal = \Factory::getConfigService()->getDefaultScopeName();
        $scope = $scopeVal;
        $selectedScopes[] = $scopeVal;
    }
    $ngi = "";
    if(isset($_GET['ngi'])) {
        $ngi = $_GET['ngi'];
    }
    $servKeyNames = "";
    if(isset($_GET['servKeyNames'])) {
        $servKeyNames = $_GET['servKeyNames'];
    }
    $servKeyValue ="";
    if(isset($_GET['servKeyValue'])) {
        $servKeyValue = $_GET['servKeyValue'];
    }
    //must be done before the if certstatus in the block that sets $certStatus
    $showClosed = false;
    if(isset($_GET['showClosed'])){
    // showClosed is a bool, so presence of param indicates it was
    // checked, no need to parse the value.
        $showClosed = true;
    }
    $certStatus = "";
    if(!empty($_GET['certStatus'])) {
       $certStatus = $_GET['certStatus'];
       //set show closed as true if production status selected is 'closed' - otherwise
       // there will be no results
       if($certStatus == 'Closed'){
           $showClosed = true;
       }
    }


    // If parsed vars have a value, validate and set the filterParams
    $filterParams = array();
    if($startRecord < 1) {
        $startRecord = 1;
    }
    if($serviceType != "") {
    $filterParams['serviceType'] = $serviceType;
    }
    if($production != "") {
    $filterParams['production'] = $production;
    }
    if($monitored != "") {
    $filterParams['monitored'] = $monitored;
    }
    if($scope != "") {
    $filterParams['scope'] = $scope;
    }
    if($scopeMatch != "") {
        $filterParams['scopeMatch'] = $scopeMatch;
    }
    if($ngi != "") {
    $filterParams['ngi'] = $ngi;
    }
    if($certStatus != "") {
    $filterParams['certStatus'] = $certStatus;
    }
    if($servKeyNames != "") {
    $filterParams['servKeyNames'] = $servKeyNames;
    }
    if($servKeyValue != "") {
    $filterParams['servKeyValue'] = $servKeyValue;
    }
    if ($searchTerm != "") {
    $searchTerm = strip_tags(trim($searchTerm));
    if (1 === preg_match("/[';\"]/", $searchTerm)) {
        throw new Exception("Invalid char in search term");
    }
    if (substr($searchTerm, 0, 1) != '%') {
        $searchTerm = '%' . $searchTerm;
    }
    if (substr($searchTerm, -1) != '%') {
        $searchTerm = $searchTerm . '%';
    }
    $filterParams['searchTerm'] = $searchTerm;
    }
    if($showClosed) {
    $filterParams['showClosed'] = $showClosed;
    }


    // Update the URL params for idempotent page refresh
    $thisPage = 'index.php?Page_Type=Services';
    $thisPage .= '&serviceType=' . $serviceType;
    $thisPage .= '&production=' . $production;
    $thisPage .= '&monitored=' . $monitored;
    //$thisPage .= '&scope=' . $scope;
    foreach($selectedScopes as $sc){
        $thisPage .= '&mscope[]='.$sc;
    }
    $thisPage .= '&scopeMatch=' . $scopeMatch;
    $thisPage .= '&ngi=' . $ngi;
    $thisPage .= '&certStatus=' . $certStatus;
    $thisPage .= '&servKeyNames=' . $servKeyNames;
    $thisPage .= '&servKeyValue=' . $servKeyValue;
    $thisPage .= '&searchTerm=' . $searchTerm;
    if($showClosed){
    // showClosed is a bool, so presence of param indicates it was
    // checked, no need to add a value.
        $thisPage .= '&showClosed=';
    }
    //print_r($filterParams); // debug


    // Count the total number of services that match the query
    $filterParams['count'] = TRUE;
    $numResults = $seServ->getServicesFilterByParams($filterParams);

    // create links to scroll forward/back through results
    $recordsPerPage = 50;
    $nextInt = 1;
    $prevInt = 1;
    $lastInt = 1;

    // < Set the "Previous" link
    if ($startRecord > $recordsPerPage) {
    $prevInt = ($startRecord - $recordsPerPage);
    } else {
    $prevInt = 1;
    }
    // Set the "Next" link >
    if (($startRecord + $recordsPerPage) < $numResults) { //($numResults - $startRecord) > $recordsPerPage
    $nextInt = $startRecord + $recordsPerPage;
    } else {
    if (($numResults - $startRecord) <= 1) {
        $nextInt = 1;
    } else {
        $nextInt = ($numResults + 1) - $recordsPerPage;
    }
    }
    if($nextInt < 1){
    $nextInt = 1;
    }
    // Set the "Last" link >>
    if (($numResults - $startRecord) <= 1) {
    $lastInt = 1;
    } else {
    $lastInt = ($numResults + 1 - $recordsPerPage);
    }
    if($lastInt < 1){
    $lastInt = 1;
    }

    $firstLink = $thisPage . "&record=1";
    $previousLink = $thisPage . "&record=" . $prevInt;
    $nextLink = $thisPage . "&record=" . $nextInt;
    $lastLink = $thisPage . "&record=" . $lastInt;



    $filterParams['count'] = FALSE;
    $filterParams['startRecord'] = $startRecord - 1;
    $filterParams['maxResults'] = $recordsPerPage;
    $ses = $seServ->getServicesFilterByParams($filterParams);


    $endRecord = $startRecord + $recordsPerPage - 1;
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


    $params['scopes'] = \Factory::getScopeService()->getScopes();
    $params['scopeMatch'] = $scopeMatch;
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
    $params['certStatuses'] = \Factory::getSiteService()->getCertStatuses();
    $params['showClosed'] = $showClosed;
    $params['selectedProduction'] = $production;
    $params['selectedMonitored'] = $monitored;
    $params['selectedScopes'] = $selectedScopes; //$scope;
    $params['selectedNgi'] = $ngi;
    $params['selectedClosed'] = $showClosed;
    $params['selectedCertStatus'] = $certStatus;
    $params['selectedServKeyNames'] = $servKeyNames;
    $params['selectedServKeyValue'] = $servKeyValue;
    show_view("service/view_all.php", $params, "Services");
}