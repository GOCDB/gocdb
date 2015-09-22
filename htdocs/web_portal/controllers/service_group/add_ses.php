<?php
/*______________________________________________________
 *======================================================
 * File: add_ses.php
 * Author: John Casson
 * Description: Adds SEs to a service group
 *
 * License information
 *
 * Copyright  2009 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 /*====================================================== */
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';

/**
 * Controller for a request to add SEs to a service group
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_ses() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's after form submission
        submit($user);
    } else { // If there is no post data, draw the add SEs form
        draw($user);
    }
}

/**
 * Draws a form for the user to add services to a service group
 * @global array $_REQUEST only set if the browser has sent parameters
 * @param \User $user current user
 * @return null
 */
function draw(\User $user = null) {
    $serv = \Factory::getServiceGroupService();

    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    // The service group to add SEs to
    $sg =  $serv->getServiceGroup($_REQUEST['id']);

    // Check the user is authorized to perform this operation
    //try { $serv->editAuthorization($sg, $user); } catch(Exception $e) {
    //    show_view('error.php', $e->getMessage()); die(); }
    //if(count($serv->authorize Action(\Action::EDIT_OBJECT, $sg, $user))==0){
    if (\Factory::getRoleActionAuthorisationService()->authoriseActionAbsolute(\Action::EDIT_OBJECT, $sg, $user) == FALSE)  {
        show_view('error.php', 'You do not have permission to edit this ServiceGroup');
        die();
    }
        
    // Check to see whether to show the link to "add a new SE to this virtual site"
    if(\Factory::getConfigService()
    		->IsOptionalFeatureSet("siteless_services")) {
        $siteLessServices = true;
    } else {
        $siteLessServices = false;
    }

    $params = array('sg' => $sg,
        			'siteLessServices' => $siteLessServices);

    show_view('service_group/add_ses.php', $params);
}

/**
 * Adds service to a service group
 * @global array $_REQUEST only set if the browser has sent parameters
 * @param \User $user current user
 * @return null
 */
function submit(\User $user = null) {
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    $sg = \Factory::getServiceGroupService()
    	->getServiceGroup($_REQUEST['id']);

    $ses = array();
    if(empty($_REQUEST['endpointIds'])) {
    	show_view('error.php', 'No service selected');
    	die();
    }
    foreach($_REQUEST['endpointIds'] as $seId) {
    	$ses[] = \Factory::getServiceService()
    		->getService($seId);
    }

    try {
        \Factory::getServiceGroupService()
        	->addServices($sg, $ses, $user);
        $params = array('sg' => $sg);
        show_view("service_group/submit_service_group_ses.php", $params);
    } catch (Exception $e) {
    	show_view('error.php', $e->getMessage());
        die();
    }
}
