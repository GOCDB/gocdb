<?php
/*______________________________________________________
 *======================================================
 * File: edit_ngi.php
 * Author: John Casson, David Meredith
 * Description: Processes an edit NGI request from the web portal
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
 * Controller for an edit downtime request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_ngi() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's to update a downtime
        submit($user);
    } else { // If there is no post data, draw the edit form
        draw($user);
    }
}

/**
 * Draws the edit ngi form using the object ID passed in $_REQUEST
 * @param \User $user current user
 * @return null
 */
function draw(\User $user = null) {
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    $ngi = \Factory::getNgiService()->getNgi($_REQUEST['id']);

    if($user == null){
       throw new Exception('You do not have permission to edit this NGI, null user'); 
    }
    if(\Factory::getRoleActionAuthorisationService()->authoriseAction(
            Action::EDIT_OBJECT, $ngi, $user)->getGrantAction() == FALSE){
        throw new Exception('You do not have permission to edit this NGI');
    }
    // can user assign reserved scopes ?
    $disableReservedScopes = true; 
    if($user->isAdmin()){
	$disableReservedScopes = false; 
    }
    $scopejsonStr = getEntityScopesAsJSON($ngi, $disableReservedScopes); 
     
    $params = array('ngi' => $ngi);
    $params['numberOfScopesRequired'] = \Factory::getConfigService()->getMinimumScopesRequired('ngi');
    $params['scopejson'] = $scopejsonStr; 
    
    show_view('ngi/edit_ngi.php', $params);
}

/**
 * Processes an edit service request from a web request
 * @param \User $user current user
 * @return null
 */
function submit(\User $user = null) {
	$serv = \Factory::getNgiService();
	$newValues = getNgiDataFromWeb();
//        print_r($newValues); 
//        die(); 
        
	$ngi = $serv->getNgi($newValues['ID']);
	$ngi = $serv->editNgi($ngi, $newValues, $user);
	$params = array('ngi' => $ngi);
	show_view('ngi/ngi_updated.php', $params);
}
?>