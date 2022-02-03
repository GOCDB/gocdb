<?php
/*______________________________________________________
 *======================================================
 * File: add_api_auth.php
 * Author: George Ryall
 * Description: Processes a new API Authentication entity  request. If the user
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

/**
 * Controller for a new authentication entity request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_entity() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if (!isset($_REQUEST['parentid']) || !is_numeric($_REQUEST['parentid']) ){
        throw new Exception("A site id must be specified in the url");
    }

    $serv = \Factory::getSiteService();
    $site = $serv->getSite($_REQUEST['parentid']);

    if($_POST) {     // If we receive a POST request it's for a new authentication entity
        submit($user, $site, $serv);
    } else { // If there is no post data, draw the new authentication entity form
        draw($user, $site);
    }
}

function draw(\User $user = null, \Site $site = null) {
    if(is_null($user)){
        throw new Exception("Unregistered users can't add new authentication credentials");
    }

    $params['site'] = $site;
    $params['authTypes'] = array();
    $params['authTypes'][]='X509';
    $params['authTypes'][]='OIDC Subject';

    show_view("site/add_api_auth.php", $params);
    die();
}

function submit(\User $user = null, \Site $site, $serv) {
    $newValues = getAPIAuthenticationFromWeb();

    try {
        $authEnt = $serv->addAPIAuthEntity($site, $user, $newValues);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
    $params['apiAuthenticationEntity'] = $authEnt;
    $params['site'] = $site;
    show_view("site/added_api_auth.php", $params);
    die();


}
