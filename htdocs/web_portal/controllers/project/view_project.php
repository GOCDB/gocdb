<?php
/*______________________________________________________
 *======================================================
 * File: view_project.php
 * Author: George Ryall, David Meredith
 * Description: Controller for showing a single project
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

function show_project() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $projId = $_GET['id'];

    $serv=\Factory::getProjectService();
    $project = $serv->getProject($projId);
    $allRoles = $project->getRoles();
    $roles = array();
    foreach ($allRoles as $role){
        if($role->getStatus() == \RoleStatus::GRANTED &&
                $role->getRoleType()->getName() != \RoleTypeName::CIC_STAFF){
            $roles[] = $role;
        }
    }

    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $params['ShowEdit'] = false;
    if (\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $project, $user)->getGrantAction())  {
        $params['ShowEdit'] = true;
    }
//    if(count($serv->authorize Action(\Action::EDIT_OBJECT, $project, $user))>=1){
//       $params['ShowEdit'] = true;
//    }

    // Overload the 'authenticated' key for use to disable display of personal data
    list(, $params['authenticated']) = getReadPDParams($user);

    // Add RoleActionRecords to params
    $params['RoleActionRecords'] = \Factory::getRoleService()->getRoleActionRecordsById_Type($project->getId(), 'project');

    $params['Name'] = $project->getName();
    $params['Description'] = $project->getDescription();
    $params['ID']=$project->getId();
    $params['NGIs'] = $project->getNgis();
    $params['Sites']= $serv->getSites($project);
    $params['Roles'] =$roles;
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    show_view('project/view_project.php', $params, $params['Name']);
}

?>
