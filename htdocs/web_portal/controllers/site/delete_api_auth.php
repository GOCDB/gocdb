<?php
/*______________________________________________________
 *======================================================
 * File: delete_api_auth.php
 * Author: George Ryall
 * Description: Processes a request to remove an api authentication entity.
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

/**
 * Controller for a delete authentication entity request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function delete_entity() {
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

    if($_POST) {     // If we receive a POST request it's to remove an authentication entity
        submit($user, $authEnt, $site, $serv);
    } else { // If there is no post data, draw the remove authentication entity form
        draw($user, $authEnt, $site);
    }
}

function draw(\User $user = null, \APIAuthentication $authEnt = null, \Site $site) {
    if(is_null($user)){
        throw new Exception("Unregistered users can't remove authentication credentials");
    }

    $params['authEnt'] = $authEnt;
    $params['site'] = $site;

    show_view("site/delete_api_auth.php", $params);
    die();
}

function submit(\User $user = null, \APIAuthentication $authEnt = null, \Site $site = null, $serv = null) {
    $params['authEnt']['identifier'] = $authEnt->getIdentifier();
    $params['authEnt']['type'] = $authEnt->getType();
    $params['site'] = $site;

    try {
        $authEnt = $serv->deleteAPIAuthEntity($authEnt, $user);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    show_view("site/deleted_api_auth.php", $params);
    die();


}
