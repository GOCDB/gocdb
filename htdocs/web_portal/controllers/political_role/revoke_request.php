<?php
/*______________________________________________________
 *======================================================
 * File: view_deny_request.php
 * Author: John Casson, David Meredith
 * Description: Revokes a role regardless of its status
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

function view_revoke_request(){
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';

    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if($user == null){
        throw new Exception("Unregistered users can't revoke roles");
    }
    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    $requestId = $_POST['id'];

    if(!isset($requestId) || !is_numeric($requestId)) {
        throw new LogicException("Invalid role id");
    }

    // Either a self revocation or revoke is requested by 2nd party
    // check to see that user has permission to revoke role
    $role = \Factory::getRoleService()->getRoleById($requestId);

    \Factory::getRoleService()->revokeRole($role, $user);
    if($role->getUser() != $user){
        // revoke by 2nd party
        show_view('political_role/role_revoked.php');
    } else {
        // Self revocation
        show_view('political_role/role_self_revoked.php');
    }

    die();
}
?>
