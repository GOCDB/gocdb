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
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';
    $userId =  $_GET['id']; 
    
    if (!isset($userId) || !is_numeric($userId) ){
        throw new Exception("An id must be specified");
    }
    $user = \Factory::getUserService()->getUser($userId);
    $params['user'] = $user;

    $roles = \Factory::getRoleService()->getUserRoles($user, \RoleStatus::GRANTED); //$user->getRoles();

    $params['portalIsReadOnly'] = \Factory::getConfigService()->IsPortalReadOnly();

    $callingUser = \Factory::getUserService()->getUserByPrinciple(Get_User_Principle());
   
    // can the calling user revoke each role?  
    foreach ($roles as $r) {
        $authorisingRoleNames = \Factory::getRoleService()->authorizeAction(\Action::REVOKE_ROLE, $r->getOwnedEntity(), $callingUser); 
        if(count($authorisingRoleNames)>=1){
            $allAuthorisingRoleNames = ''; 
            foreach($authorisingRoleNames as $arName){
                $allAuthorisingRoleNames .= $arName.', '; 
            }
            $allAuthorisingRoleNames = substr($allAuthorisingRoleNames, 0, strlen($allAuthorisingRoleNames)-2);  
            $r->setDecoratorObject('['.$allAuthorisingRoleNames.'] ');
        } 
    }

    $params['roles'] = $roles;
    $title = $user->getFullName();
    show_view("user/view_user.php", $params, $title);
}