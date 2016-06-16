<?php
/*______________________________________________________
 *======================================================
 * File: edit_site_property.php
 * Author: James McCarthy
 * Description: Processes an edit site property request from the web portal
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
 * Controller for an edit site property request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_property() {
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
 * Draws the edit site property form using the object ID passed in $_REQUEST
 * @param \User $user current user
 * @throws Exception
 * @return null
 */
function draw(\User $user = null) {
    $serv = \Factory::getSiteService(); 
    $site = $serv->getSite($_REQUEST['id']);
    $property = $serv->getProperty($_REQUEST['propertyid']);
    $serv->validatePropertyActions($user, $site);
    
    $params['prop'] = $property;
    $params['site'] = $site;
    
    show_view("site/edit_site_property.php", $params);     
    

}

/**
 * Processes an edit site property request from a web request
 * @param \User $user current user
 * return null
 */
function submit(\User $user = null) {
    try {
        $newValues = getSpDataFromWeb();  
        $siteID = $newValues['SITEPROPERTIES']['SITE'];
        $propID = $newValues['SITEPROPERTIES']['PROP'];
        if($newValues['SITEPROPERTIES']['NAME'] == null || $newValues['SITEPROPERTIES']['VALUE'] == null){
            show_view('error.php', "A property name and value must be provided.");
            die();
        }
        $property = \Factory::getSiteService()->getProperty($propID);
        $site = \Factory::getSiteService()->getSite($siteID);    	
        $site = \Factory::getSiteService()->editSiteProperty($site, $user, $property, $newValues);
        $params['siteid'] = $siteID;
        show_view('site/site_property_updated.php', $params);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>