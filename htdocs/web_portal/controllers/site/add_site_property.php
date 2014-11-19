<?php
/*______________________________________________________
 *======================================================
 * File: add_site_property.php
 * Author: James McCarthy
 * Description: Processes a new property request. If the user
 *              hasn't POSTed any data we draw the add property
 *              form. If they post data we assume they've posted it from
 *              the form and add it.
 *
 * License information
 *
 * Copyright 2013 STFC
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
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
    
/**
 * Controller for a new_property request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_site_property() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's for a new property
        submit($user);
    } else { // If there is no post data, draw the New property form
        draw($user);
    }
}

/**
 * Retrieves the raw new property data from a portal request and submit it
 * to the services layer's property functions.
 * @param \User $user current user
 * @return null */
function submit(\User $user = null) {
    $newValues = getSpDataFromWeb();
    $siteID = $newValues['SITEPROPERTIES']['SITE'];  
    if($newValues['SITEPROPERTIES']['NAME'] == null || $newValues['SITEPROPERTIES']['VALUE'] == null){
        show_view('error.php', "A property name and value must be provided.");
        die();
    }
    $serv = \Factory::getSiteService();
    $sp = $serv->addProperty($newValues, $user);
    show_view("site/added_site_property.php", $siteID);
}

/**
 *  Draws a form to add a new site property
 * @param \User $user current user 
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't add a site property.");
    }
    $serv = \Factory::getSiteService();
    $site = $serv->getSite($_REQUEST['site']);
    
    //Check user has permissions to add site property
    $serv->validatePropertyActions($user, $site);    
	
	$params = array('site' => $site);
	show_view("site/add_site_property.php", $params);

}

?>