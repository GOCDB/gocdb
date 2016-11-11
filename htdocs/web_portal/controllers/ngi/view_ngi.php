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

    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $ngiId = $_GET['id'];

    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    $params['UserIsAdmin']=false;
    if(!is_null($user)) {
        $params['UserIsAdmin']=$user->isAdmin();
    }

    $params['authenticated'] = false;
    if($user != null){
        $params['authenticated'] = true;
    }

    $ngiServ = \Factory::getNgiService();
    $siteServ = \Factory::getSiteService();
    $ngi = $ngiServ->getNgi($ngiId);

    // Does current viewer have edit permissions over NGI ?
    $params['ShowEdit'] = false;
    //if(count($ngiServ->authorize Action(\Action::EDIT_OBJECT, $ngi, $user))>=1){
    if (\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $ngi, $user)->getGrantAction())  {
        $params['ShowEdit'] = true;
    }

    // Add ngi to params
    $params['ngi'] = $ngi;

    // Add all roles over ngi to params
    $allRoles = $ngi->getRoles();
    $roles = array();
    foreach ($allRoles as $role){
        if($role->getStatus() == \RoleStatus::GRANTED){
            $roles[] = $role;
        }
    }
    $params['roles'] = $roles;

    // Add ngi's project to params
    $projects = $ngi->getProjects();
    $params['Projects']= $projects;

    // Add sites and scopes to params
    $params['SitesAndScopes']=array();
    foreach($ngi->getSites() as $site){
        $params['SitesAndScopes'][]=array('Site'=>$site, 'Scopes'=>$siteServ->getScopesWithParentScopeInfo($site));
    }

    // Add RoleActionRecords to params
    $params['RoleActionRecords'] = \Factory::getRoleService()->getRoleActionRecordsById_Type($ngi->getId(), 'ngi');

    show_view('ngi/view_ngi.php', $params, $ngi->getName());
    die();
}

?>