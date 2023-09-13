<?php
/*______________________________________________________
 *======================================================
 * File: edit_api_auth.php
 * Author: George Ryall
 * Description: Processes a edit API Authentication entity  request. If the user
 *              hasn't POSTed any data we draw the new site
 *              form. If they post data we assume they've posted it from
 *              the form and validate then insert it into the DB.
 *
 * License information
 *
 * Copyright 2016 STFC
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
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

use Exception;

/**
 * Controller to either edit authentication entity request or renewal request.
 *
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_entity() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if (!isset($_REQUEST['authentityid']) || !is_numeric($_REQUEST['authentityid']) ){
        throw new Exception("A authentication entity id must be specified in the url");
    }

    $serv = \Factory::getSiteService();
    $authEnt = $serv->getAPIAuthenticationEntity($_REQUEST['authentityid']);
    $site = $authEnt->getParentSite();

    // Validate the user has permission to edit properties
    if (!$serv->userCanEditSite($user, $site)) {
        throw new \Exception("Permission denied: a site role is required to edit authentication entities at " . $site->getShortName());
    }

    if($_POST) {     // If we receive a POST request it's to edit an authentication entity
        submit($user, $authEnt, $site, $serv);
    } else { // If there is no post data, draw the edit authentication entity form
        draw($user, $authEnt, $site);
    }
}

function draw(\User $user = null, \APIAuthentication $authEnt = null, \Site $site = null) {
    if(is_null($user)){
        throw new Exception("Unregistered users can't edit authentication credentials");
    }

    $params = array();
    $params['site'] = $site;
    $params['authEnt'] = $authEnt;
    $params['authTypes'] = array();
    $params['authTypes'][]='X.509';
    $params['authTypes'][]='OIDC Subject';
    $params['user'] = $user;

    if ($_REQUEST['isRenewalRequest']) {
        $params['isRenewalRequest'] = true;
    }

    show_view("site/edit_api_auth.php", $params);
    die();
}

/**
 * If this receives a POST request,
 * it can be either to edit an API authentication entity or
 * to update the `$lastRenewTime` in `APIAuthentication`.
 */
function submit(\User $user, \APIAuthentication $authEnt, \Site $site, org\gocdb\services\Site $serv) {

    $params = array();

    if ($_REQUEST['isRenewalRequest']) {
        // Need this variable to call `editAPIAuthEntity` function.
        $newValues['isRenewalRequest'] = true;
        $params['isRenewalRequest'] = true;
    } else {
        $newValues = getAPIAuthenticationFromWeb();
    }

    try {
        $authEnt = $serv->editAPIAuthEntity($authEnt, $user, $newValues);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    $params['apiAuthenticationEntity'] = $authEnt;
    $params['site'] = $site;
    show_view("site/edited_api_auth.php", $params);
    die();


}
