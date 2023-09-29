<?php
/*______________________________________________________
 *======================================================
 * File: add_ngi.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Processes an add ngi request. If the user
 *              hasn't POSTed any data we draw the add ngi
 *              form. If they post data we assume they've posted it from
 *              the form and validate then insert it into the DB.
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
require_once __DIR__ . '/../utils.php';

/**
 * Controller for an add NGI request. Is only used by gocdb admin.
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_ngi() {
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {     // If we receive a POST request it's to add a NGI
        submit();
    } else { // If there is no post data, draw the add NGI form
        draw();
    }
}

/**
 * Draws the add NGI form
 * @return null
 */
function draw() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();

    // pass 2 nulls because we haven't created the ngi yet and we don't yet
    // support cascading of project scopes (not sure this is needed)
    $scopejsonStr = getEntityScopesAsJSON2(null, null, false);
    $params['numberOfScopesRequired'] = \Factory::getConfigService()->getMinimumScopesRequired('ngi');
    $params['scopejson'] = $scopejsonStr;

    //show the add NGI view
    show_view("admin/add_ngi.php", $params, "Add NGI");
}

/**
 * Retrieves the new NGI's data from a portal request and submit it to the
 * services layer's NGI functions.
 * @return null
 */
function submit() {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    //Get the posted NGI data
    $newValues = getNGIDataFromWeb();

    //get the user data for the add NGI function (so it can check permissions)
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    try {
        //function will through error if user does not have the correct permissions
        $ngi = \Factory::getNgiService()->addNGI($newValues, $user);
        $params = array('Name' => $ngi->getName(),
                        'ID'=> $ngi->getId());
        show_view("admin/added_ngi.php", $params);
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}
