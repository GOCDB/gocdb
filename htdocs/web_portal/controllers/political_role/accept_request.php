<?php
/*______________________________________________________
 *======================================================
 * File: accept_request.php
 * Author: John Casson, David Meredith
 * Description: Accepts a political role request
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
 *
 /*====================================================== */


function view_accept_request() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../../components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';

    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if($user == null){
        throw new Exception("Unregistered users can't grant role requests");
    }

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    $requestId = $_POST['id'];

    if(!isset($requestId) || !is_numeric($requestId)) {
        throw new LogicException("Invalid role request id");
    }

    // Lookup role request with id
    $roleRequest = \Factory::getRoleService()->getRoleById($requestId);

    \Factory::getRoleService()->grantRole($roleRequest, $user);

    show_view('political_role/request_accepted.php');
    die();
}