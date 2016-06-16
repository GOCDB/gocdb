<?php
/*______________________________________________________
 *======================================================
 * File: retrieve_account.php.php
 * Author: John Casson, David Meredith
 * Description: Retrieves a user account
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
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../components/Get_User_Principle.php';
require_once __DIR__.'/utils.php';
    
/**
 * Controller for a retrieve account request.
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function retrieve() {
    //Check the portal is not in read only mode, returns exception if it is
    checkPortalIsNotReadOnly();
    
    if($_POST) { // If we receive a POST request it's to update a user
        submit();
    } else { // If there is no post data, draw the edit user form
        draw();
    }
}

/**
 * Draws the register user form
 * @return null
 */
function draw() {
    $dn = Get_User_Principle();
    if(empty($dn)){
        show_view('error.php', "Could not authenticate user - null user principle");
        die(); 
    }
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    if(!is_null($user)) {
        show_view('error.php', "Only unregistered users can retrieve an account.");
        die();
    }

    $params['DN'] = $dn;
    
    show_view('user/retrieve_account.php', $params, 'Retrieve Account');
}

function submit() {
    $oldDn = $_REQUEST['OLDDN'];
    $givenEmail =$_REQUEST['EMAIL'];
    $currentDn = Get_User_Principle();
    if(empty($currentDn)){
        show_view('error.php', "Could not authenticate user - null user principle");
        die(); 
    }
    
    try {
        $changeReq = \Factory::getRetrieveAccountService()->newRetrieveAccountRequest($currentDn, $givenEmail, $oldDn);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
    
    show_view('user/retrieve_account_accepted.php');
}