<?php
/*______________________________________________________
 *======================================================
 * File: view_ngi.php
 * Author: John Casson, David Meredith (modifications), George Ryall
 * Description: Controller for showing an ngi
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

function view_ngi() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';
    
    $ngiServ = \Factory::getNgiService();
    $siteServ = \Factory::getSiteService();
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    $ngiId= $_REQUEST['id'];

    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    
    $params['UserIsAdmin']=false;
    if(!is_null($user)) {
        $params['UserIsAdmin']=$user->isAdmin();
    }
    
    
    $ngi = $ngiServ->getNgi($ngiId);
    $allRoles = $ngi->getRoles();
    $roles = array(); 
    foreach ($allRoles as $role){
        if($role->getStatus() == \RoleStatus::GRANTED){
            $roles[] = $role; 
        }
    }
    $projects = $ngi->getProjects();

    $params['SitesAndScopes']=array();
    foreach($ngi->getSites() as $site){
        $params['SitesAndScopes'][]=array('Site'=>$site,
                                             'Scopes'=>$siteServ->getScopesWithParentScopeInfo($site));
    }
    
    
    $params['Projects']= $projects;
    
	$params['ngi'] = $ngi;
    $params['roles'] = $roles;
    show_view('ngi/view_ngi.php', $params, $ngi->getName());
    die();
}

?>