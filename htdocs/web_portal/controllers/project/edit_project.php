<?php
/*______________________________________________________
 *======================================================
 * File: edit_project.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Processes an edit project request. If the user
 *              hasn't POSTed any data we draw the edit project
 *              form. If they post data we assume they've posted it from
 *              the form and validate then insert it into the DB.
 *
 * License information
 *
 * Copyright 2009 STFC
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
require_once __DIR__ . '/../utils.php';
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

/**
 * Controller for an edit project request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_project() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's for a projecte edit
        submit($user);
    } else { // If there is no post data, draw the edit project view
        draw();
    }
}

/**
 * Draws the edit project form
 * @return null
 */
function draw() {              
    // Get the project
    $project = \Factory::getProjectService()->getProject($_REQUEST['id']);

    //Check the user has permission to edit the project (and so view the page, 
    //will throw exception if correct permissions are lacking
    CheckCurrentUserCanEditProject($project);
  
    //show view
    $params = array('Name' => $project->getName(),
                    'ID' => $project->getId(),
                    'Description' => $project->getDescription());

    show_view("project/edit_project.php", $params, "Edit " . $project->getName());
}

/**
 * Retrieves the project edit from a portal request and submit it to the
 * services layer's vsite functions.
 * @param \User $user Current user
 * @return null
 */
function submit(\User $user = null) {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
    
    //get the post data
    $newValues = getProjectDataFromWeb();

    
    //get the project service and the project being edited
    $serv = \Factory::getProjectService();
    $unalteredProject = $serv->getProject($newValues['ID']);
           
    try {
        //function will throw error if user does not have the correct permissions
        $alteredProject= $serv->editProject($unalteredProject, $newValues, $user);
        
        $params = array('Name' => $alteredProject->getName(),
                        'Description'=> $alteredProject->getDescription(),
                        'ID'=> $alteredProject->getId());
        show_view("project/edited_project.php", $params);
    
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}