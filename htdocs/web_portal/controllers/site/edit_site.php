<?php
/*______________________________________________________
 *======================================================
 * File: edit_site.php
 * Author: John Casson, David Meredith, George Ryall (modifications)
 * Description: Processes an edit site request from the web portal
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
 /*======================================================*/
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';

/**
 * Controller for an edit site request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_site() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {
        submit($user);
    } else {
        draw($user);
    }
}

/**
 * Draws the edit site form using the object ID passed in $_REQUEST
 * @param \User $user current user
 * @throws Exception
 * @return null
 */
function draw(\User $user = null) {
    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $serv = \Factory::getSiteService(); 
    $site = $serv->getSite($_GET['id']);

    /*Old way: try { \Factory::getSiteService()->edit Authorization($site, $user);
    } catch(Exception $e) { show_view('error.php', $e->getMessage()); die(); }*/
    
    //if(count($serv->authorize Action(Action::EDIT_OBJECT, $site, $user)) == 0){ 
    if(\Factory::getRoleActionAuthorisationService()->authoriseActionAbsolute(\Action::EDIT_OBJECT, $site, $user) == FALSE){
        throw new Exception('You do not have permission to edit this Site');
    } 

    
    $countries = $serv->getCountries();
    //$timezones = $serv->getTimezones(); // Deprecated - don't use the lookup values in the GocDB
    $timezones =  DateTimeZone::listIdentifiers(); // get the standard values 
    $prodStatuses = $serv->getProdStatuses();
    
    //Remove SC and PPS infrastructures from drop down list (unless site has one of them). TODO: Delete this block once they no longer exist
    $SCInfrastructure = $serv->getProdStatusByName('SC');
    $PPSInfrastructure = $serv->getProdStatusByName('PPS'); 
    $hackprodStatuses=array();
    foreach($prodStatuses as $ps){
        if(($ps != $SCInfrastructure and $ps != $PPSInfrastructure) or $ps == $site->getInfrastructure()){
            $hackprodStatuses[]=$ps;
        }
    }
    $prodStatuses = $hackprodStatuses;
    //delete up to here once pps and sc infrastructures have been removed from database
    
    $scopes = \Factory::getScopeService()->getScopesSelectedArray($site->getScopes());
    //get parent scope ids to generate warning message in view
    $parentScopeIds = array();
    foreach ($site->getNgi()->getScopes() as $scope){
        $parentScopeIds[]=$scope->getId();
    }
    $configServ = \Factory::getConfigService();
    $numberScopesRequired = $configServ->getMinimumScopesRequired('site');

    $params = array("site" => $site, "timezones" => $timezones
        , "countries" => $countries, "prodStatuses" => $prodStatuses
        , "scopes"=>$scopes
        , "numberOfScopesRequired" =>$numberScopesRequired
        , "parentScopeIds" => $parentScopeIds);

    show_view('site/edit_site.php', $params);
}

/**
 * Processes an edit site request from a web request
 * @param \User $user current user
 * return null
 */
function submit(\User $user = null) {
    try {
        $newValues = getSiteDataFromWeb();
        $siteId = \Factory::getSiteService()->getSite($newValues['ID']);
        $site = \Factory::getSiteService()->editSite($siteId, $newValues, $user);
        $params = array('site' => $site);
        show_view('site/site_updated.php', $params);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>