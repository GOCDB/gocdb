<?php
/*______________________________________________________
 *======================================================
 * File: edit_cert_status.php
 * Author: John Casson, David Meredith
 * Description: Processes an edit certification status request
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
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

/**
 * Controller for an edit certification status request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit() {
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
 * Draws the edit certification status form
 * @return null
 * @param \User $user current user
 */
function draw(\User $user = null) {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $site = \Factory::getSiteService()->getSite($_REQUEST['id']);

    //try { \Factory::getCertStatusService()->editAuthorization($site, $user);
    //} catch (\Exception $e) { show_view('error.php', $e->getMessage()); die(); }
    //if(count(\Factory::getSiteService()->authorize Action(Action::SITE_EDIT_CERT_STATUS, $site, $user))==0 ){
    if(\Factory::getRoleActionAuthorisationService()->authoriseActionAbsolute(\Action::SITE_EDIT_CERT_STATUS, $site, $user) == FALSE){
       show_view('error.php', 'You do not have permission to change site certification status.'
               ." Either an NGI level role on the parent NGI, or a Project level role on one of the parent NGIs owning projects is required."); 
       die(); 
    }

    $statuses = \Factory::getCertStatusService()->getCertificationStatuses();

    $params = array("site" => $site, "statuses" => $statuses);
    show_view('site/edit_cert_status.php', $params);
}

/**
 * Processes an edit site request from a web request
 * return null
 * @param \User $user current user
 */
function submit(\User $user = null) {
    // TODO use validate service
    $reason = $_REQUEST['COMMENT']; 
    if(empty($reason) ){
       throw new Exception('A reason is required');     
    }
    if(strlen($reason) > 300){
        throw new Exception('Invalid reason - 300 char max'); 
    }
    try {
        require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
        
        try {
            $site = \Factory::getSiteService()->getSite($_REQUEST['SITEID']);
           
            $certStatus = \Factory::getCertStatusService()->getCertificationStatus($_REQUEST['CERTSTATUSID']);
            \Factory::getCertStatusService()->editCertificationStatus($site, $certStatus, $user, $reason); 
        } catch (\Exception $e) {
            show_view('error.php', $e->getMessage());
            die();
        }

        $params = array('site' => $site);
        show_view('site/cert_status_edited.php', $params);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>