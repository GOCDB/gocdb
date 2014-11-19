<?php
/*______________________________________________________
 *======================================================
 * File: edit_user.php
 * Author: John Casson, David Meredith
 * Description: Processes an edit user request from the web portal
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
 /*======================================================*/
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../components/Get_User_Principle.php';
require_once __DIR__.'/utils.php';
    
/**
 * Controller for an edit_user request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_user() {
    //Check the portal is not in read only mode, returns exception if it is
    checkPortalIsNotReadOnly();
    
    if($_POST) {     // If we receive a POST request it's to update a user
        submit();
    } else { // If there is no post data, draw the edit user form
        draw();
    }
}

/**
 * Draws the edit user form using the object ID passed in $_REQUEST
 * @return null
 */
function draw() {
	$serv = \Factory::getUserService();
	$dn = Get_User_Principle();
	// Current User
    $currentUser = $serv->getUserByPrinciple($dn);
    // User entity to edit
    $user = $serv->getUser($_REQUEST['id']);

    if(is_null($currentUser)) {
        show_view('error.php', "Unregistered users can't edit a user.");
        die();
    }

    try {
    	$serv->editUserAuthorization($user, $currentUser);
    } catch (Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    $params['user'] = $user;

    show_view('user/edit_user.php', $params);
}

function submit() {
    $newValues = getUserDataFromWeb();

    $dn = Get_User_Principle();
    $serv = \Factory::getUserService();
    // Current User
    $currentUser = $serv->getUserByPrinciple($dn);
    // User entity to edit
    $user = $serv->getUser($newValues['ID']);
    unset($newValues['ID']);

    try {
        $user = $serv->editUser($user, $newValues, $currentUser);
        $params = array('user' => $user);
        show_view('user/user_updated.php', $params);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>