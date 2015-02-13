<?php
/*______________________________________________________
 *======================================================
 * File: delete_service_type.php
 * Author: George Ryall
 * Description: Deletes a service type
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
require_once __DIR__ . '/../utils.php';

function delete_project(){
    if(true){
        throw new Exception("Project deletion is disabled - see controller"); 
    }
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    
    //Get the project from the id
    $serv= \Factory::getProjectService();
    $project =$serv ->getProject($_REQUEST['id']);
    
    //keep the name to display later
    $params['Name'] = $project -> getName();
     
    //Delete the service type. This fuction will check the user is allowed to 
    //perform this action and throw an error if not.
    try {
        $serv->deleteProject($project, $user);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
    
    show_view("project/deleted_project.php", $params, $params['Name'].'deleted');
            
}