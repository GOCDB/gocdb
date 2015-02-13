<?php
/*______________________________________________________
 *======================================================
 * File: remove_scope.php
 * Author: George Ryall, David Meredith
 * Description: Deletes a scope tag or tells a user why they can't.
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
 /*======================================================*/
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
require_once __DIR__.'/../utils.php';

function remove_scope(){
    
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    //Check user has permission
    checkUserIsAdmin();
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    
    //Get the scope from the id
    $serv= \Factory::getScopeService();
    $scope =$serv ->getScope($_REQUEST['id']);
    
    //keep the name to display later
    $params['Name'] = $scope -> getName();
    
    //check to see if there are NGIs, Sites, Service Groups, & services,
    // with this scope tag. If there are, prevent deletion of it.
    $ngisWithScope = $serv->getNgisFromScope($scope);
    $sitesWithScope = $serv->getSitesFromScope($scope);
    $sGroupWithScope = $serv->getServiceGroupsFromScope($scope);
    $serviceWithScope = $serv->getServicesFromScope($scope);
    
    $deletionAllowed = true;
    if(sizeof($ngisWithScope)>0){
      $deletionAllowed = false;  
    }
    if(sizeof($sitesWithScope )>0){
      $deletionAllowed = false;  
    }
   if(sizeof($sGroupWithScope)>0){
      $deletionAllowed = false;  
   }
   if(sizeof($serviceWithScope)>0){
      $deletionAllowed = false;  
    }
    
    //Allow the deletion of scopes that are in use
    $scopeInUseOveride=false;
    if(isset($_REQUEST['ScopeInUseOveride'])){
        if($_REQUEST['ScopeInUseOveride'] == 'true'){
            $scopeInUseOveride =true;
            $deletionAllowed  = true;
        }
    }
    
    if($deletionAllowed){
        //Delete the scope. This fuction will check the user is allowed to 
        //perform this action and throw an error if not.
        $dn = Get_User_Principle();
        $user = \Factory::getUserService()->getUserByPrinciple($dn);
        try {
            $serv->deleteScope($scope, $user, $scopeInUseOveride);
        } catch(\Exception $e) {
            show_view('error.php', $e->getMessage());
            die();
        }

        show_view("admin/deleted_scope.php", $params, $params['Name'].'deleted');
    }
    else{
        $params['ID']= $scope->getId();
        $params['NGIs'] = $ngisWithScope;
        $params['Sites'] = $sitesWithScope;
        $params['ServiceGroups']=$sGroupWithScope;
        $params['Services']=$serviceWithScope;
        show_view('admin/delete_scope_denied.php', $params, "Scope still in use");        
    }
            
}

