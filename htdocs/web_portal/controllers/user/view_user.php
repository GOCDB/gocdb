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
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $userId =  $_GET['id'];

    $serv = \Factory::getUserService();
    $user = $serv->getUser($userId);
    if ($user === null) {
       throw new Exception("No user with that ID");
    }

    $params = [];
    $params['user'] = $user;

    // Check if user only has one identifier to disable unlinking
    $params['lastIdentifier'] = (count($user->getUserIdentifiers()) === 1);
    // Add the display locations of the policy files.
    getPolicyURLs($params);

    // 2D array, each element stores role and a child array holding project Ids
    $role_ProjIds = array();

    // get the targetUser's roles
    $roles = \Factory::getRoleService()->getUserRoles($user, \RoleStatus::GRANTED); //$user->getRoles();

    $currentIdString = Get_User_Principle();
    $params['currentIdString'] = $currentIdString;
    $callingUser = $serv->getUserByPrinciple($currentIdString);

    // can the calling user revoke the targetUser's roles?
    /* @var $r \Role */
    foreach ($roles as $r) {
    //echo $r->getId().', '.$r->getRoleType()->getName().', '.$r->getOwnedEntity()->getName().'<br>';

    // determine if callingUser can REVOKE this role instance
    if($user != $callingUser){
        //echo '<br>'.$r->getOwnedEntity()->getName().' ';

            $authorisingRoles = \Factory::getRoleActionAuthorisationService()
            ->authoriseAction(\Action::REVOKE_ROLE, $r->getOwnedEntity(), $callingUser)
            ->getGrantingRoles();
            $authorisingRoleNames = array();
        //echo ' callingUser authorising Roles: ';
            /* @var $authRole \Role */
            foreach($authorisingRoles as $authRole){
               $authorisingRoleNames[] = $authRole->getRoleType()->getName();
           //echo $authRole->getRoleType()->getName().', ';
            }

            if(count($authorisingRoleNames)>=1){
                $allAuthorisingRoleNames = '';
                foreach($authorisingRoleNames as $arName){
                    $allAuthorisingRoleNames .= $arName.', ';
                }
                $allAuthorisingRoleNames = substr($allAuthorisingRoleNames, 0, strlen($allAuthorisingRoleNames)-2);
                $r->setDecoratorObject('['.$allAuthorisingRoleNames.'] ');
            }
            if($callingUser->isAdmin()){
                $existingVal = $r->getDecoratorObject();
                if($existingVal != null){
                   $r->setDecoratorObject('GOCDB ADMIN: '.$existingVal);
                } else {
                    $r->setDecoratorObject('GOCDB ADMIN');
                }
            }
    } else {
        // current user is viewing their own roles, so they can revoke their own roles
        $r->setDecoratorObject('[Self revoke own role]');
    }

    // Get the names of the parent project(s) for this role so we can
    // group by project in the view
    $parentProjectsForRole = \Factory::getRoleActionAuthorisationService()
        ->getReachableProjectsFromOwnedEntity($r->getOwnedEntity());
    $projIds = array();
    foreach($parentProjectsForRole as $_proj){
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
        $serv->editUserAuthorization($user, $callingUser);
        $params['ShowEdit'] = true;
    } catch (Exception $e) {
        $params['ShowEdit'] = false;
    }

    $params['idString'] = $serv->getDefaultIdString($user);

    $params['projectNamesIds'] = $projectNamesIds;
    $params['role_ProjIds'] = $role_ProjIds;
    $params['portalIsReadOnly'] = \Factory::getConfigService()->IsPortalReadOnly();
    $title = $user->getFullName();
    show_view("user/view_user.php", $params, $title);
}
