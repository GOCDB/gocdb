<?php
/*______________________________________________________
 *======================================================
 * File: register.php
 * Author: John Casson, David Meredith
 * Description: Registers a new user
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
 * Controller for a register request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function register() {
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
    $serv = \Factory::getUserService();
    $dn = Get_User_Principle();
    if(empty($dn)){
        show_view('error.php', "Could not authenticate user - null user principle");
    die(); 
    }
    $user = $serv->getUserByPrinciple($dn);

    if(!is_null($user)) {
    show_view('error.php', "Only unregistered users can register");
    die();
    }

    /* @var $authToken \org\gocdb\security\authentication\IAuthentication */
    $authToken = Get_User_AuthToken();
    $params['authAttributes'] = $authToken->getDetails();
    
    $params['dn'] = $dn;
    show_view('user/register.php', $params);
}

function submit() {
    $values = getUserDataFromWeb();

    $dn = Get_User_Principle();
    if(empty($dn)){
        show_view('error.php', "Could not authenticate user - null user principle");
    die(); 
    }
    $values['CERTIFICATE_DN'] = $dn;

    // todo: on registering, we also want to persist the authAttributes, this 
    // will require new UserProperty records owned by the User.php entity. 
    /* @var $authToken \org\gocdb\security\authentication\IAuthentication */
    //$authToken = Get_User_AuthToken();
    //$params['authAttributes'] = $authToken->getDetails();

    $serv = \Factory::getUserService();
    try {
        $user = $serv->register($values);
        $params = array('user' => $user);
        show_view('user/registered.php', $params);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>