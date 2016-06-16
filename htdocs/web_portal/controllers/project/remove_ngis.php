<?php
/*______________________________________________________
 *======================================================
 * File: remove_ngis.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Allows GOCD Admins to remove NGIs from a project
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
require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';

/**
 * Controller for a request to remove NGIs
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function remove_ngis_project() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    ////Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();


    if($_POST) {     // If we receive a POST request it's to remove ngis
        submit();
    } else { // If there is no post data, draw the remove NGI page
        draw();
    }
}

/**
 * Draws the remove ngis from project page
 * @return null
 */
function draw() {
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    //Get project details
    $serv = \Factory::getProjectService();
    $project = $serv->getProject($_REQUEST['id']);

    //Throw exception if not a valid project id
    if(is_null($project)) {
        throw new \Exception("A project with ID '".$_REQUEST['id']."' Can not be found");
    }

    $params["Name"]=$project->getName();
    $params["ID"]=$project->getId();
    $params["NGIs"]=$project->getNgis();


    //show the remove ngis view
    show_view("project/remove_ngis.php", $params, "Remove NGIS from". $params['Name']);
}

/**
 * Retrieves the NGIS to be removed and then removes them.
 * @return null
*/
function submit() {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    //Get user details (for the remove ngi function so it can check permissions)
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Get a project and NGI services
    $projectServ=  \Factory::getProjectService();
    $ngiServ= \Factory::getNgiService();

    //Get the posted service type data
    $projectId =$_REQUEST['ID'];
    $ngiIds = $_REQUEST['NGIs'];

    //turn ngiIds into NGIs
    $ngis = new Doctrine\Common\Collections\ArrayCollection;
    foreach ($ngiIds as $ngiId){
        $ngis[]=$ngiServ->getNgi($ngiId);
    }

    //get the project
    $project = $projectServ->getProject($projectId);

    try {
        //function will throw error if user does not have the correct permissions
        $projectServ->removeNgisFromProject($project, $ngis, $user);

        $params = array('Name' => $project->getName(),
                        'ID'=> $project->getId());
        show_view("project/removed_ngis.php", $params, "Success");
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}

?>