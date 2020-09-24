<?php
/*______________________________________________________
 *======================================================
 * File: downtimes_calendar.php
 * Author: Tom Byrne
 * Description: Retrieves and draws the data for the downtime calendar
 *
 * License information
 *
 * Copyright � 2016 STFC
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

//Uses the GOCDBPI to return a list of downtimes as JSON, based on the params of the request
function getDowntimesAsJSON()
{

    $filterParams = array();

    //if no date window is specified, just get the next 30 days
    $windowStart = date("Y-m-d");
    $windowEnd = date_add(date_create(date("Y-m-d")), date_interval_create_from_date_string(30 . ' days'))->format('Y-m-d');

    //If the filter params are specified in the request, add them to the filter param array
    if (isset($_GET["start"]))
        $windowStart = date("Y-m-d", strtotime($_GET["start"]));

    if (isset($_GET["end"]))
        $windowEnd = date("Y-m-d", strtotime($_GET["end"]));

    if (!empty($_GET["classification"]))
        $filterParams['classification'] = $_GET["classification"];

    if (!empty($_GET["severity"]))
        $filterParams['severity'] = $_GET["severity"];

    if (!empty($_GET["production"]))
        $filterParams['production'] = $_GET["production"];

    if (!empty($_GET["monitored"]))
        $filterParams['monitored'] = $_GET["monitored"];

    if (!empty($_GET["certStatus"]))
        $filterParams['certification_status'] = $_GET["certStatus"];

//    if (!empty($_GET["scopeMatch"]))
//        $filterParams['scope_match'] = $_GET["scopeMatch"];

    $selectedScopes = "";
    if (!empty($_GET['scope'])) {
        $selectedScopes = $_GET['scope'];
    }

    $selectedServiceTypes = "";
    if (!empty($_GET['service_type'])) {
        $selectedServiceTypes = $_GET['service_type'];
    }

    $selectedSites = "";
    if (!empty($_GET['site'])) {
        $selectedSites = $_GET['site'];
    }

    $selectedNGIs = "";
    if (!empty($_GET['ngi'])) {
        $selectedNGIs = $_GET['ngi'];
    }

    $filterParams['windowstart'] = $windowStart;
    $filterParams['windowend'] = $windowEnd;

    if ($selectedScopes != "null" && $selectedScopes != "") {
        $filterParams['scope'] = $selectedScopes;

        if (!empty($_GET["scopeMatch"])){
            $filterParams['scope_match'] = $_GET["scopeMatch"];
        } else {
            $filterParams['scope_match'] = 'any';
        }
    }

    if ($selectedServiceTypes != "null" && $selectedServiceTypes != "") {
        $filterParams['service_type_list'] = $selectedServiceTypes;
    }

    if ($selectedSites != "null" && $selectedSites != "") {
        $filterParams['sitelist'] = $selectedSites;
    }

    if ($selectedNGIs != "null" && $selectedNGIs != "") {
        $filterParams['ngilist'] = $selectedNGIs;
    }

    //create the downtime service, and pass the filter array to getDowntimesFilterByParams
    $dtServ = \Factory::getDowntimeService();
    //this returns an array of doctrine downtime objects
    $downtimes = $dtServ->getDowntimesFilterByParams($filterParams);

    //initialise the array that will be our JSON structure
    $downtimesArray = array();

    //for each doctrine downtime obj, extract or get the data we want, and create the structure
    foreach ($downtimes as $dt) {
        $tempDowntime = array();
        $tempDowntime['id'] = $dt['id'];
        $tempDowntime['site'] = $dtServ->getDowntime($tempDowntime['id'])->getServices()->first()->getParentSite()->getName();
        $tempDowntime['title'] = "<b>" . $tempDowntime['site'] . "</b>" . ": " . utf8_encode($dt['description']);
        $tempDowntime['start'] = $dt['startDate']->format('c');
        $tempDowntime['end'] = $dt['endDate']->format('c');
        $tempDowntime['severity'] = $dt['severity'];
        $tempDowntime['class'] = $dt['classification'];
        $tempDowntime['url'] = "index.php?Page_Type=Downtime&id=" . $tempDowntime['id'];


        $downtimesArray[] = $tempDowntime;
    }


    //set the header
    header("Content-type:text/json");
    //return the json encoded array
    return json_encode($downtimesArray);
}

//this function grabs the data needed for the dontime calendar tooltips, and calls the tooltip view
function getTooltip()
{

    //downtimeID must be set
    if (isset($_GET['downtimeID'])) {
        $downtime = \Factory::getDowntimeService()->getDowntime($_GET['downtimeID']);
    } else {
        throw new \Exception("downtime" . $_GET['downtimeID'] . "does not exist.");
    }

    //initialise the parameter array to be passed through to the view
    $params = array();
    $start = $downtime->getStartDate();
    $end = $downtime->getEndDate();

    //populate the parameter array
    $params['id'] = $_GET['downtimeID'];
    //$params['duration'] = date_diff($start, $end)->days . " days " . date_diff($start, $end)->h . " hours";
    $params['start'] = $downtime->getStartDate()->format(DATE_ATOM);
    $params['end'] = $downtime->getEndDate()->format(DATE_ATOM);
    $params['description'] = $downtime->getDescription();
    $params['services'] = array();
    $params['scopes'] = array();
    $params['site'] = $downtime->getServices()->first()->getParentSite()->getName();

    //we need to get a list of all services, and then a list of all of those services scopes
    foreach ($downtime->getServices() as $service) {
        array_push($params['services'], $service->getHostName());
        foreach ($service->getScopes() as $scope) {
            array_push($params['scopes'], $scope->getName());
        };
    };

    //which we then remove duplicates from
    $params['scopes'] = implode(", ", array_unique($params['scopes']));

    //this gives us out "x of y services affected" line
    $params['affected'] = "<b>" . count($params['services']) . "</b> of <b>" . count($downtime->getServices()->first()->getParentSite()->getServices()) . "</b>";

    //shorten the services array to a sensible length so the tooltip fits on screen
    if (count($params['services']) > 10) {
        $params['services'] = array_slice($params['services'], 0, 10);
        array_push($params['services'], "...");
    }

    //call the view
    show_view("downtime/downtimes_calendar_tooltip.php", $params, null, true);
}


function view()
{
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';

//not necessary?
//    $dn = Get_User_Principle();
//    $user = \Factory::getUserService()->getUserByPrinciple($dn);
//    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);


    $timePeriod = 1;
    if (isset($_REQUEST['timePeriod'])) {
        $timePeriod = $_REQUEST['timePeriod'];
    }

    // Used by the calendar to get the downtimes
    if (isset($_GET['getDowntimesAsJSON'])) {
        die(getDowntimesAsJSON());
    }

    // Used by the tooltips to get the downtime info
    if (isset($_GET['getTooltip'])) {
        die(getTooltip());
    }

    //We need a list of scoped present to populate the select box
    $params['scopes'] = \Factory::getScopeService()->getScopes();

    //This section is reading the parameters passed into the page from the user.
    //these are used to select the starting values of the filter parameters on the calendar ui
    //which in turn determine the query sent to getDowntimesAsJSON.

    $selectedScopes = array();
    if (!empty($_GET['scope'])) {
        $selectedScopes = explode(',', $_GET['scope']);
    } else {
        if (\Factory::getConfigService()->getDefaultFilterByScope()) {
            $selectedScopes[] = \Factory::getConfigService()->getDefaultScopeName();
        }
    }

    $scopeMatch = "any";
    if (!empty($_GET['scopeMatch'])) {
        $scopeMatch = $_GET['scopeMatch'];
    }

    $selectedSites = array();
    if (!empty($_GET['site'])) {
        $selectedSites = explode(',', $_GET['site']);
    }

    $selectedServiceTypes = array();
    if (!empty($_GET['serviceType'])) {
        $selectedServiceTypes = explode(',', $_GET['service_type']);
    }

    $selectedNGIs = array();
    if (!empty($_GET['ngi'])) {
        $selectedNGIs = explode(',', $_GET['ngi']);
    }

    $severity = "ALL";
    if (!empty($_GET['severity'])) {
        $severity = $_GET['severity'];
    }

    $classification = "ALL";
    if (!empty($_GET['classification'])) {
        $classification = $_GET['classification'];
    }

    $certStatus = "ALL";
    if (!empty($_GET['certStatus'])) {
        $certStatus = $_GET['certStatus'];
    }

    $production = "ALL";
    if (!empty($_GET['production'])) {
        $production = $_GET['production'];
    }

    $monitored = "ALL";
    if (!empty($_GET['monitored'])) {
        $monitored = $_GET['monitored'];
    }

    $date = date("Y-m-d");

    if (!empty($_GET['date'])) {
        $date = date("Y-m-d", strtotime($_GET['date']));
    }

    $view = "basicWeek";

    if (!empty($_GET['view'])) {
        $view = $_GET['view'];
    }

    //construct the params to pass to the view
    $params['selectedScopes'] = $selectedScopes;
    $params['scopeMatch'] = $scopeMatch;
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


    show_view("downtime/downtimes_calendar.php", $params, "Downtimes Calendar");
}