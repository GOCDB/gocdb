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
    $downtimesArray = array();


    $windowStart = date("Y-m-d");
    $windowEnd = date_add(date_create(date("Y-m-d")), date_interval_create_from_date_string(30 .' days'));

    if(isset($_GET["start"])){
        $windowStart = date("Y-m-d", strtotime($_GET["start"]));
    }

    if(isset($_GET["end"])){
        $windowEnd = date("Y-m-d", strtotime($_GET["end"]));
    }

    $downtimes = \Factory::getDowntimeService()->getActiveAndImminentDowntimes($windowStart,$windowEnd);


    foreach($downtimes as $dt){
        $tempDowntime = array();
        $tempDowntime['id'] = $dt->getId();
        $tempDowntime['site'] = $dt->getServices()->first()->getParentSite()->getName();
        $tempDowntime['title'] = $tempDowntime['site'] . ": " . $dt->getDescription();
        $tempDowntime['start'] = $dt->getStartDate()->format('c');
        $tempDowntime['end'] = $dt->getEndDate()->format('c');
        $tempDowntime['url'] = "index.php?Page_Type=Downtime&id=" . $dt->getId();
        $tempDowntime['ngi'] = $dt->getServices()->first()->getParentSite()->getNgi()->getName();
        $tempDowntime['scopes'] = array();
        $tempDowntime['services'] = array();

        //throw new \Exception(var_dump($dt->getServices()->first()->getScopes());
        foreach($dt->getServices() as $service){
            array_push($tempDowntime['services'], $service->getHostName());
            foreach($service->getScopes() as $scope){
                array_push($tempDowntime['scopes'], $scope->getName());
            };
        };
        $tempDowntime['scopes'] = array_unique($tempDowntime['scopes']);

        $tempDowntime['severity'] = $dt->getSeverity();
        $tempDowntime['class'] = $dt->getClassification();

        if ($dt->getSeverity() === "OUTAGE"){
            $tempDowntime['color'] = "#BB4444";
        }

        $downtimesArray[] = $tempDowntime;
    }

    return json_encode($downtimesArray);
}

function getTooltip(){
    if(isset($_GET['downtimeID'])){
        $downtime = \Factory::getDowntimeService()->getDowntime($_REQUEST['downtimeID']);
    } else {
        throw new \Exception("downtime" . $_GET['downtimeID'] . "does not exist.");
    }
    $start = $downtime->getStartDate();
    $end = $downtime->getEndDate();
    $params['duration'] = date_diff($start, $end)->days . " days " . date_diff($start, $end)->h . " hours";
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
    $params['scopes'] = array_unique($params['scopes']);

    $params['affected'] = count($params['services']) . " of " .


    //$params['sites'] = array_unique($params['sites']);

    show_view("downtime/downtimes_calendar_tooltip.php", $params, null, true);
}


function view() {
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';
    
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



    $severity = "ALL";
    if(!empty($_GET['severity'])) {
        $severity = $_GET['severity'];
    }

    $selectedNGI = "ALL";
    if(!empty($_GET['ngi'])) {
        $selectedNGI = $_GET['ngi'];
    }

    $classification = "ALL";
    if(!empty($_GET['class'])) {
        $classification = $_GET['class'];
    }

    $date = date("Y-m-d");

    if(!empty($_GET['date'])) {

        $date = date( "Y-m-d", strtotime($_GET['date']));
    }

    $view = "month";

    if(!empty($_GET['view'])) {

        $view = $_GET['view'];
    }

    $params['selectedScopes'] = $selectedScopes; //$scope;

    $params['severity'] = $severity;
    $params['classification'] = $classification;
    $params['date'] = $date;
    $params['view'] = $view;
    $params['ngis'] = \Factory::getNgiService()->getNGIs();
    $params['selectedNGI'] = $selectedNGI;
    $params['selectedSites'] = $selectedSites;

    ///////////////////////////////////////////////////////
    $filterParams = array();
    $filterParams['scope'] = '';
    $serv = \Factory::getSiteService();
    $params['sites'] = $serv->getSitesFilterByParams($filterParams);
    //////////////////////////////////////////////////////
    //throw new \Exception($date);
    show_view("downtime/downtimes_calendar.php", $params);
}