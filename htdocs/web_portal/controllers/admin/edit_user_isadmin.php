<?php
/*______________________________________________________
 *======================================================
 * File: edit_user_isadmin.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Allows GOCDB Admins to promote and demote other admins.
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
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/../utils.php';

/**
 * Controller for promoting/demoting administrators using isAdmin property
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function make_admin() {
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {     // If we receive a POST request it's to change an admin status
        submit();
    } else { // If there is no post data, draw the edit isAdmin page
        draw();
    }
}

/**
 * Draws the edit user admin status form
 * @return null
 */
function draw() {
    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    //Get user details
    $serv = \Factory::getUserService();
    $user = $serv->getUser($_REQUEST['id']);

    //Throw exception if not a valid user
    if(is_null($user)) {
        throw new \Exception("A user with ID '".$_REQUEST['id']."' Can not be found");
    }

    $params["ID"]=$user->getId();
    $params["Forename"]=$user->getForename();
    $params["Surname"]=$user->getSurname();
    $params["IsAdmin"]=$user->isAdmin();

    if($params["IsAdmin"]){
        $title = "Demote ".$params["IsAdmin"]." ".$params["Surname"];
    }
    else{
        $title = "Promote ".$params["IsAdmin"]." ".$params["Surname"];
    }

    //show the edit user Admin view
    show_view("admin/edit_user_isadmin.php", $params, $title);
}

/**
 * Retrieves the new isadmin value from a portal request and submit it to the
 * services layer's user functions.
 * @return null
*/
function submit() {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
    if(true){
        throw new Exception("Disabled in controller");
    }
    //Check the user has permission, will throw exception
    //if correct permissions are lacking
    /*checkUserIsAdmin();

    //Get a user service
    $serv = \Factory::getUserService();

    //Get the posted user data
   $userID =$_REQUEST['ID'];
   $user = $serv->getUser($userID);

   //Note that a string is recived from post and must be converted to boolean
   if($_REQUEST['IsAdmin']=="true"){
       $isAdmin=true;
   }
   else {
       $isAdmin=false;
   }

    //get the user data for the set user isAdmin function (so it can check permissions)
    $currentIdString = Get_User_Principle();
    $currentUser = $serv->getUserByPrinciple($currentIdString);

    try {
        //function will through error if user does not have the correct permissions
        $serv->setUserIsAdmin($user, $currentUser, $isAdmin);

        $params = array('Name' => $user->getForename()." ".$user->getSurname(),
                        'IsAdmin'=> $user->isAdmin(),
                        'ID'=> $user->getId());

        show_view("admin/edited_user_isadmin.php", $params, "Success");
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }*/
}

?>
