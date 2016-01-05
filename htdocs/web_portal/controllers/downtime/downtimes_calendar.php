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
        $tempDowntime['title'] = $dt->getServices()->first()->getParentSite()->getName() . ": " . $dt->getDescription();
        $tempDowntime['start'] = $dt->getStartDate()->format('c');
        $tempDowntime['end'] = $dt->getEndDate()->format('c');
        $tempDowntime['url'] = "index.php?Page_Type=Downtime&id=" . $dt->getId();
        $tempDowntime['scopes'] = array();
        //throw new \Exception(var_dump($dt->getServices()->first()->getScopes());
        foreach($dt->getServices() as $service){
            foreach($service->getScopes() as $scope){
                array_push($tempDowntime['scopes'], $scope->getName());
            };
        };
        $tempDowntime['scopes'] = array_unique($tempDowntime['scopes']);

        $tempDowntime['severity'] = $dt->getSeverity();

        if ($dt->getSeverity() === "OUTAGE"){
            $tempDowntime['color'] = "#BB4444";
        }

        $downtimesArray[] = $tempDowntime;
    }

    return json_encode($downtimesArray);
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

    $days = 7 * $timePeriod;
    
    $windowStart = date("Y-m-d");    
    $windowEnd = date_add(date_create(date("Y-m-d")), date_interval_create_from_date_string($days.' days'));
    
    $downtimesA = \Factory::getDowntimeService()->getActiveDowntimes();
    $downtimesI = \Factory::getDowntimeService()->getImminentDowntimes($windowStart,$windowEnd);
    $params['scopes'] = \Factory::getScopeService()->getScopes();

    $selectedScopes = array();
    if(!empty($_GET['scope'])) {
        $selectedScopes = explode(',', $_GET['scope']);
    }
    //throw new \Exception(var_dump($scopeArray));


    $severity = "ALL";
    if(!empty($_GET['severity'])) {
        $severity = $_GET['severity'];
    }

    $date = date("Y-m-d");
    if(!empty($_GET['date'])) {
        $severity = $_GET['date'];
    }

    $params['selectedScopes'] = $selectedScopes; //$scope;
    $params['severity'] = $severity;
    $params['date'] = $date;
    $params['timePeriod'] = $timePeriod;
    $params['downtimesActive'] = $downtimesA;
    $params['downtimesImmenent'] = $downtimesI;
    show_view("downtime/downtimes_calendar.php", $params);
}