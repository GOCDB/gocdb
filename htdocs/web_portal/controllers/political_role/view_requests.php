<?php
/*______________________________________________________
 *======================================================
 * File: view_requests.php
 * Author: John Casson, David Meredith 
 * Description: Shows all available role requests
 *
 * License information
 *
 * Copyright  2013 STFC
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
function view_requests() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../../components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';
    
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if($user == null) {
        throw new Exception("Unregistered users can't view/request roles"); 
    }
    
    
    // Entites is a two-dimensional array that lists both the id and name of 
    // OwnedEntities that a user can reqeust a role over (Projects, NGIs, Sites, 
    // ServiceGroups). If an inner dimesional array does not contain an Object_ID
    // array key, then it is used as a section title in a pull-down list. 
    $entities = array(); 
    
    $entities[] = array('Name' => 'Projects'); 
    $allProjects = \Factory::getProjectService()->getProjects(); 
    foreach($allProjects as $proj){
       $entities[] = array('Object_ID' => $proj->getId(), 'Name' => $proj->getName()); 
    }
    
    $entities[] = array('Name' => 'NGIs'); 
    $allNGIs = \Factory::getNgiService()->getNGIs(); 
    foreach($allNGIs as $ngi){
       $entities[] = array('Object_ID' => $ngi->getId(), 'Name' => $ngi->getName()); 
    }

    $entities[] = array('Name' => 'Sites'); 
    $allSites = \Factory::getSiteService()->getSitesBy(); 
    foreach($allSites as $site){
        $entities[] = array('Object_ID' => $site->getId(), 'Name' => $site->getShortName()); 
    }
    
    $entities[] = array('Name' => 'ServiceGroups'); 
    $allSGs = \Factory::getServiceGroupService()->getServiceGroups(); 
    foreach($allSGs as $sg){
       $entities[] = array('Object_ID' => $sg->getId(), 'Name' => $sg->getName()); 
    }
   
    // Current user's own pending roles 
    $myPendingRoleRequests = \Factory::getRoleService()->getUserRoles($user, \RoleStatus::PENDING); 
    // foreach role, lookup corresponding RoleActionRecord (if any) and populate 
    // the role.decoratorObject with the roleActionRecord for subsequent display 
//    foreach($myPendingRoleRequests as $role){
//       $rar = \Factory::getRoleService()->getRoleActionRecordByRoleId($role->getId());  
//       $role->setDecoratorObject($rar); 
//    }
    
    // Other roles current user can approve 
    $otherRolesUserCanApprove = \Factory::getRoleService()->getPendingRolesUserCanApprove($user); 
    
     // can the calling user grant or reject each role?  
    foreach ($otherRolesUserCanApprove as $r) {
        $grantRejectRoleNamesArray = array(); 
        $grantRejectRoleNamesArray['grant'] = ''; 
        $grantRejectRoleNamesArray['deny'] = ''; 
        
        // get list of roles that allows user to to grant the role request
        //$grantRoleAuthorisingRoleNames = \Factory::getRoleService()->authorize Action(\Action::GRANT_ROLE, $r->getOwnedEntity(), $user); 
        $grantRoleAuthorisingRoles = \Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::GRANT_ROLE, $r->getOwnedEntity(), $user); 
        $grantRoleAuthorisingRoleNames = array(); 
        foreach($grantRoleAuthorisingRoles as $grantRole){
            $grantRoleAuthorisingRoleNames[] = $grantRole->getRoleType()->getName(); 
        }
        
        if(count($grantRoleAuthorisingRoleNames)>=1){
            $allAuthorisingRoleNames = ''; 
            foreach($grantRoleAuthorisingRoleNames as $arName){
                $allAuthorisingRoleNames .= $arName.', '; 
            }
            $allAuthorisingRoleNames = substr($allAuthorisingRoleNames, 0, strlen($allAuthorisingRoleNames)-2);  
            $grantRejectRoleNamesArray['grant'] = '['.$allAuthorisingRoleNames.']'; 
        } 
        if($user->isAdmin()){
           $grantRejectRoleNamesArray['grant'] = 'GOCDB ADMIN ' . $grantRejectRoleNamesArray['grant']; 
        }

        // get list of roles that allows user to reject the role request
        //$denyRoleAuthorisingRoleNames = \Factory::getRoleService()->authorize Action(\Action::REJECT_ROLE, $r->getOwnedEntity(), $user); 
        $denyRoleAuthorisingRoles = \Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::REJECT_ROLE, $r->getOwnedEntity(), $user);  
        $denyRoleAuthorisingRoleNames = array();  
        foreach($denyRoleAuthorisingRoles as $denyingRole){
          $denyRoleAuthorisingRoleNames[] = $denyingRole->getRoleType()->getName();   
        } 
        
        if(count($denyRoleAuthorisingRoleNames)>=1){
           $allAuthorisingRoleNames = ''; 
            foreach($denyRoleAuthorisingRoleNames as $arName){
                $allAuthorisingRoleNames .= $arName.', '; 
            }
            $allAuthorisingRoleNames = substr($allAuthorisingRoleNames, 0, strlen($allAuthorisingRoleNames)-2);  
            $grantRejectRoleNamesArray['deny'] = '['.$allAuthorisingRoleNames.']'; 
        }
        if($user->isAdmin()){
           $grantRejectRoleNamesArray['deny'] = 'GOCDB ADMIN ' . $grantRejectRoleNamesArray['deny']; 
        }
        // store array of role names in decorator object 
        $r->setDecoratorObject($grantRejectRoleNamesArray); 
    }
    
    $params = array();
	$params['entities'] = $entities;
	$params['myRequests'] = $myPendingRoleRequests;
	$params['allRequests'] = $otherRolesUserCanApprove;
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    show_view("political_role/view_requests.php", $params, "Role Requests");
    die(); 
}