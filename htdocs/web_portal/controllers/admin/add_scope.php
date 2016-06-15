<?php
/*______________________________________________________
 *======================================================
 * File: add_scope.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Processes an add scope request. If the user
 *              hasn't POSTed any data we draw the add scope
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
require_once __DIR__ . '/../utils.php';
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

/**
 * Controller for an add scope request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_scope() {
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {     // If we receive a POST request it's to add a scope
        submit();
    } else { // If there is no post data, draw the add scope form
        draw();
    }
}

/**
 * Draws the add scope form
 * @return null
 */
function draw() {
    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();

    //show the add service type view
    show_view("admin/add_scope.php", null, "Add Scope");
}

/**
 * Retrieves the new scopes data from a portal request and submit it to the
 * services layer's scope functions.
 * @return null
 */
function submit() {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    //Get the posted service type data
    $values = getScopeDataFromWeb();

    //get the user data for the add scope function (so it can check permissions)
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    try {
        //function will through error if user does not have the correct permissions
        $scope = \Factory::getScopeService()->addScope($values, $user);
        $params = array('Name' => $scope->getName(),
                        'ID'=> $scope->getId());
        show_view("admin/added_scope.php", $params, "New scope succesffully created");
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}

?>