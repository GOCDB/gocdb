<?php
/*______________________________________________________
 *======================================================
 * File: view_downtime.php
 * Author: James McCarthy
 * Description: Retrieves and draws the data for a downtime
 *
 * License information
 *
 * Copyright � 2009 STFC
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


function getDowntimesAsJSON(){

    $filterParams = array();

    $windowStart = date("Y-m-d");
    $windowEnd = date_add(date_create(date("Y-m-d")), date_interval_create_from_date_string(30 .' days'))->format('Y-m-d');


    if(isset($_GET["start"])){
        $windowStart = date("Y-m-d", strtotime($_GET["start"]));
    }

    if(isset($_GET["end"])){
        $windowEnd = date("Y-m-d", strtotime($_GET["end"]));
    }


    if(!empty($_GET["classification"]))
        $filterParams['classification'] = $_GET["classification"];

    if(!empty($_GET["severity"]))
        $filterParams['severity'] = $_GET["severity"];

    if(!empty($_GET["production"]))
        $filterParams['production'] = $_GET["production"];

    if(!empty($_GET["monitored"]))
        $filterParams['monitored'] = $_GET["monitored"];

    if(!empty($_GET["certStatus"]))
        $filterParams['certification_status'] = $_GET["certStatus"];

    $selectedScopes = "";
    if(!empty($_GET['scope'])){
        $selectedScopes = $_GET['scope'];
    }

    $selectedServiceTypes = "";
    if(!empty($_GET['service_type'])){
        $selectedServiceTypes = $_GET['service_type'];
    }

    $selectedSites = "";
    if(!empty($_GET['site'])) {
        $selectedSites = $_GET['site'];
    }

    $selectedNGIs = "";
    if(!empty($_GET['ngi'])) {
        $selectedNGIs = $_GET['ngi'];
    }

    $filterParams['windowstart'] = $windowStart;
    $filterParams['windowend'] = $windowEnd;

    if($selectedScopes != "null" && $selectedScopes != "") {
        $filterParams['scope'] = $selectedScopes;
        $filterParams['scope_match'] = 'any';
    }

    if($selectedServiceTypes != "null" && $selectedServiceTypes != "" ) {
        $filterParams['service_type_list'] = $selectedServiceTypes;
    }

    if($selectedSites != "null" && $selectedSites != "" ) {
        $filterParams['sitelist'] = $selectedSites;
    }

    if($selectedNGIs != "null" && $selectedNGIs != "" ) {
        $filterParams['ngilist'] = $selectedNGIs;
    }

    $dtServ = \Factory::getDowntimeService();
    $downtimes = $dtServ->getDowntimesFilterByParams($filterParams);
    //$downtimes = $dt->getActiveAndImminentDowntimes($windowStart, $windowEnd);

//    print_r(var_dump($downtimes));
//    die("force die");

    $downtimesArray = array();

    foreach($downtimes as $dt){
        //print_r(var_dump($dt['id']));
        //continue;
        $tempDowntime = array();
        $tempDowntime['id'] = $dt['id'];
        $tempDowntime['site'] = $dtServ->getDowntime($tempDowntime['id'])->getServices()->first()->getParentSite()->getName();
        $tempDowntime['title'] = "<b>"  . $tempDowntime['site'] . "</b>" . ": " . utf8_encode($dt['description']);
        //$tempDowntime['title'] = utf8_encode($dt['description']);
        $tempDowntime['start'] = $dt['startDate']->format('c');
        $tempDowntime['end'] = $dt['endDate']->format('c');
        $tempDowntime['severity'] = $dt['severity'];
        $tempDowntime['class'] = $dt['classification'];
        //$tempDowntime['end'] = $dt->getEndDate()->format('c');
        $tempDowntime['url'] = "index.php?Page_Type=Downtime&id=" . $tempDowntime['id'];


        $downtimesArray[] = $tempDowntime;
    }
    header("Content-type:text/json");
    //print_r(var_dump(json_encode($downtimesArray)));
    //print_r(var_dump($downtimesArray));
    //die("force die");

    //throw here if false
    return json_encode($downtimesArray);
}

function getTooltip(){
    if(isset($_GET['downtimeID'])){
        $downtime = \Factory::getDowntimeService()->getDowntime($_GET['downtimeID']);
    } else {
        throw new \Exception("downtime" . $_GET['downtimeID'] . "does not exist.");
    }
    $params = array();
    $start = $downtime->getStartDate();
    $end = $downtime->getEndDate();

    $params['duration'] = date_diff($start, $end)->days . " days " . date_diff($start, $end)->h . " hours";
    //$params['endsIn'] = date_diff(date_create(), $end)->days . " days " . date_diff($start, $end)->h . " hours";



    $params['start'] = $downtime->getStartDate()->format('d-m-Y H:i');
    $params['end'] = $downtime->getEndDate()->format('d-m-Y H:i');
    $params['description'] = $downtime->getDescription();
    $params['services'] = array();
    $params['scopes'] = array();


    $params['site'] = $downtime->getServices()->first()->getParentSite()->getName();

    //throw new \Exception(var_dump($dt->getServices()->first()->getScopes());
    foreach($downtime->getServices() as $service){
        array_push($params['services'], $service->getHostName());
        //array_push($params['sites'], $service->getParentSite()->getName());
        foreach($service->getScopes() as $scope){
            array_push($params['scopes'], $scope->getName());
        };
    };

    $params['services'] = array_unique($params['services']);
    $params['scopes'] = implode( ", " ,array_unique($params['scopes']));

    $params['affected'] = "<b>" . count($params['services']) . "</b> of <b>" . count($downtime->getServices()->first()->getParentSite()->getServices()) . "</b>";

    //shorten the services array to a sensible length so the tooltip fits on screen
    if (count($params['services']) > 10){
        $params['services'] = array_slice($params['services'], 0, 10);
        array_push($params['services'], "...");
    }


    //$params['sites'] = array_unique($params['sites']);
    //throw new \Exception(var_dump($params));

    show_view("downtime/downtimes_calendar_tooltip.php", $params, null, true);
}


function view() {
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';

//    $dt = \Factory::getDowntimeService();
//
//    $downtimeArray = $dt->getDowntimesFilterByParams(null);
//    print_r($downtimeArray);
//    die("force die");
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    
    //date_default_timezone_set("UTC");
    
    $timePeriod = 1;
    if(isset($_REQUEST['timePeriod'])) {
    	$timePeriod = $_REQUEST['timePeriod'];
    }

    // URL mapping
    // Return all scopes for the parent Site with the specified Id as a JSON object
    // Used in ajax requests for display purposes
    if(isset($_GET['getDowntimesAsJSON'])){
        die(getDowntimesAsJSON());
    }

    // URL mapping
    // Return all scopes for the parent Site with the specified Id as a JSON object
    // Used in ajax requests for display purposes
    if(isset($_GET['getTooltip'])){
        die(getTooltip());
    }

    $params['scopes'] = \Factory::getScopeService()->getScopes();

    $selectedScopes = array();
    if(!empty($_GET['scope'])) {
        $selectedScopes = explode(',', $_GET['scope']);
    }

    $selectedSites = array();
    if(!empty($_GET['site'])) {
        $selectedSites = explode(',', $_GET['site']);
    }

    $selectedServiceTypes = array();
    if(!empty($_GET['serviceType'])) {
        $selectedServiceTypes = explode(',', $_GET['service_type']);
    }

    $selectedNGIs = array();
    if(!empty($_GET['ngi'])) {
        $selectedNGIs = explode(',', $_GET['ngi']);
    }



    $severity = "ALL";
    if(!empty($_GET['severity'])) {
        $severity = $_GET['severity'];
    }



    $classification = "ALL";
    if(!empty($_GET['classification'])) {
        $classification = $_GET['classification'];
    }

    $certStatus = "ALL";
    if(!empty($_GET['certStatus'])) {
        $certStatus = $_GET['certStatus'];
    }

    $production = "ALL";
    if(!empty($_GET['production'])) {
        $production = $_GET['production'];
    }

    $monitored = "ALL";
    if(!empty($_GET['monitored'])) {
        $monitored = $_GET['monitored'];
    }

    $date = date("Y-m-d");

    if(!empty($_GET['date'])) {

        $date = date( "Y-m-d", strtotime($_GET['date']));
    }

    $view = "basicWeek";

    if(!empty($_GET['view'])) {

        $view = $_GET['view'];
    }

    $params['selectedScopes'] = $selectedScopes;
    $params['severity'] = $severity;
    $params['classification'] = $classification;
    $params['production'] = $production;
    $params['monitored'] = $monitored;
    $params['certStatus'] = $certStatus;
    $params['date'] = $date;
    $params['view'] = $view;
    $params['ngis'] = \Factory::getNgiService()->getNGIs();
    $params['serviceTypes'] = \Factory::getServiceService()->getServiceTypes();
    $params['selectedNGIs'] = $selectedNGIs;
    $params['selectedSites'] = $selectedSites;
    $params['selectedServiceTypes'] = $selectedServiceTypes;

    $serv = \Factory::getSiteService();
    $params['certStatuses'] = $serv->getCertStatuses();

    //Need a list of the sites for the site multi select
    //so an array of filter params is needed to pass to getSitesFilterByParams()
    //so it doesn't complain
    $siteFilterParams = array();
    $siteFilterParams['scope'] = '';
    $params['sites'] = $serv->getSitesFilterByParams($siteFilterParams);
    //throw new \Exception(var_dump($params));
    show_view("downtime/downtimes_calendar.php", $params, "Downtimes Calendar");
}