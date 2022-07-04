<?php
/*______________________________________________________
 *======================================================
 * File: view_user.php
 * Author: John Casson, David Meredith
 * Description: Retrieves and draws the data for a user
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
function view_user() {
    require_once __DIR__.'/utils.php';
    require_once __DIR__.'/../../../../lib/Gocdb_Services/User.php';
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }

    $userService = \Factory::getUserService();
    $user = $userService->getUser($_GET['id']);

    if($user === null){
       throw new Exception("No user with that ID");
    }

    $params = array();

    // Check if user only has one identifier to disable unlinking
    $params['lastIdentifier'] = (count($user->getUserIdentifiers()) === 1);
    // Add the display locations of the policy files.
    getPolicyURLs($params);

    $apiAuthEnts = $user->getAPIAuthenticationEntities();
    $authEntSites = array();
    /** @var \APIAuthentication $apiAuth */
    foreach ($apiAuthEnts as $apiAuth) {
        $authEntSites[$apiAuth->getParentSite()->getId()]++;
    }

    /** @var \User $callingUser */
    $callingUser = $userService->getUserByPrinciple(Get_User_Principle());

    // Restrict users to see only their own data unless authorised.
    // User objects are not 'owned' so we check their authz at connected sites.
    if (is_null($callingUser) ||
        (!$callingUser->isAdmin() &&
         $user !== $callingUser &&
        !$userService->isAllowReadPD($callingUser)))
        {
            throw new Exception('You are not authorised to read other users\' personal data.');
        }

    $params['user'] = $user;

    // 2D array, each element stores role and a child array holding project Ids
    $role_ProjIds = array();

    // get the targetUser's roles
    $roleService = \Factory::getRoleService();
    $roles = $roleService->getUserRoles($user, \RoleStatus::GRANTED); //$user->getRoles();

    $currentIdString = Get_User_Principle();
    $params['currentIdString'] = $currentIdString;

    // can the calling user revoke the targetUser's roles?
    /** @var \Role $r */

    foreach ($roles as $r) {

        $decoratorString = '';
        $disableButton = '';

        /** @var \OwnedEntity $roleOwnedEntity */
        $roleOwnedEntity = $r->getOwnedEntity();

        try {
            // only meaningful for Site entities, but there is an
            // internal check in the function.
            $roleService->checkOrphanAPIAuth($r);
        } catch (Exception $e) {
            $disableButton = 'disabled';
        }

        $authorisingRoles = \Factory::getRoleActionAuthorisationService()
            ->authoriseAction(\Action::REVOKE_ROLE, $roleOwnedEntity, $callingUser)
            ->getGrantingRoles();

        if ($user != $callingUser) {
            // determine if callingUser can REVOKE this role instance
            if ($callingUser->isAdmin()) {
                $decoratorString .= 'GOCDB_ADMIN';
                if (count($authorisingRoles) >= 1) {
                    $decoratorString .= ': ' ;
                }
            }
            if (count($authorisingRoles) >= 1) {
                /** @var \Role $authRole */
                $roleNames = array();
                foreach ($authorisingRoles as $authRole) {
                    $roleNames[] = $authRole->getRoleType()->getName();
                }
                $decoratorString .= '[' . implode(', ', $roleNames) . '] ';
            }
        } else {
            // current user is viewing their own roles, so they can revoke their own roles
            $decoratorString = '[Self revoke own role]';
        }

        if (strlen($decoratorString) > 0 || $disableButton == 'disabled') {
            $r->setDecoratorObject(array("revokeButton" => $disableButton, "revokeMessage" => $decoratorString));
        }

        // Get the names of the parent project(s) for this role so we can
        // group by project in the view
        $parentProjectsForRole = \Factory::getRoleActionAuthorisationService()
            ->getReachableProjectsFromOwnedEntity($r->getOwnedEntity());
        $projIds = array();
        foreach ($parentProjectsForRole as $_proj) {
            $projIds[] = $_proj->getId();
        }

        // store role and parent projIds in a 2D array for viewing
        $role_ProjIds[] = array($r, $projIds);
    }// end iterating roles

    // Get a list of the projects and their Ids for grouping roles by proj in view
    $projectNamesIds = array();
    $projects = \Factory::getProjectService()->getProjects();
    foreach($projects as $proj){
        $projectNamesIds[$proj->getId()] = $proj->getName();
    }

    // Check to see if the current calling user has permission to edit the target user
    try {
        $userService->editUserAuthorization($user, $callingUser);
        $params['ShowEdit'] = true;
    } catch (Exception $e) {
        $params['ShowEdit'] = false;
    }

    $params['idString'] = $userService->getDefaultIdString($user);
    $params['projectNamesIds'] = $projectNamesIds;
    $params['role_ProjIds'] = $role_ProjIds;
    $params['portalIsReadOnly'] = \Factory::getConfigService()->IsPortalReadOnly();
    $params['APIAuthEnts'] = $user->getAPIAuthenticationEntities();
    $title = $user->getFullName();
    show_view("user/view_user.php", $params, $title);
}
