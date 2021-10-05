<?php
/*______________________________________________________
 *======================================================
 * File: request_role.php
 * Author: John Casson, David Meredith
 * Description: Shows roles available to request over the passed object
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
require_once __DIR__ . '/../utils.php';
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once '../web_portal/components/Get_User_Principle.php';

/**
 * Controller for a Site role request.
 * Is called by 'Page_Type=Request_Role' page mapping in index.php front controller.
 * @global array $_POST only set if the browser has POSTed data
 */
function request_role() {
    $user = \Factory::getUserService()->getUserByPrinciple(Get_User_Principle());
    if($user == null) {
        throw new Exception("Unregistered users can't request roles");
    }

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    // If we receive a POST request it's for a new role
    if(isset($_REQUEST['Role_Name_Value']) && isset($_REQUEST['Object_ID']) ) {
        submitRoleRequest($_REQUEST['Role_Name_Value'], $_REQUEST['Object_ID'], $user);

    } else if(isset($_REQUEST['id'])){
       drawViewRequestRole($_REQUEST['id'], $user);
    }
    else {
        // If there is no post data, draw the request role form
    }
}


/**
 * Show the select role page for the given entityId.
 * @param \User $user Current user
 * @param int $entityId
 */
function drawViewRequestRole($entityId, \User $user = null){
    if(!is_numeric($entityId)){
        throw new Exception('Invalid entityId');
    }

    $ownedEntity = \Factory::getOwnedEntityService()->getOwnedEntityById($entityId);

    // build model to be passed to view (a parameter map/array)
    $params['entityName'] = $ownedEntity->getName();
    $params['entityType'] = \Factory::getOwnedEntityService()->getOwnedEntityDerivedClassName($ownedEntity);
    $params['objectId'] = $entityId;
    // array ([0] => array(RoleTypeName => ProjectName))
    $roleTypes = \Factory::getRoleService()->getRoleTypeNamesForOwnedEntity($ownedEntity);
    $params['roles'] = $roleTypes;
    //print_r($params['roles']);

    show_view('political_role/request_role.php', $params);
    die();
}

/**
 * Processes a role request submission
 * @param type $roleName
 * @param type $entityId
 * @param \User $user current user
 * @throws Exception
 */
function submitRoleRequest($roleName, $entityId, \User $user =null) {
   // validate the enityId is numeric
   if(!is_numeric($entityId)){
        throw new Exception('Invalid entityId');
   }

   // Get the owned entity instance
   $entity = \Factory::getOwnedEntityService()->getOwnedEntityById($entityId);

   // Create a new Role linking user, entity and roletype. The addRole
   // perfoms role validation and throws exceptios accordingly.
   $newRole = \Factory::getRoleService()->addRole($roleName, $user, $entity);

   \Factory::getNotificationService()->roleRequest($newRole, $user, $entity);

   show_view('political_role/new_request.php');
}
