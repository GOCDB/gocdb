<?php
/*______________________________________________________ 
 *======================================================
 * File: users.php
 * Author: George Ryall, David Meredith
 * Description: Controller for viewing all users in GOCDB
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
 *  
/*====================================================== */ 

function show_users(){
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';
     
    //Check the user has permission to see the page, will throw exception 
    //if correct permissions are lacking
    checkUserIsAdmin();
    
    //If specified, set parameters
    $surname = null;
    if(!empty($_REQUEST['Surname'])) { 
       $surname = $_REQUEST['Surname'];
    }
    $params["Surname"] = $surname;
    
    $forename = null;
    if(!empty($_REQUEST['Forename'])) { 
        $forename = $_REQUEST['Forename'];
    }
    $params["Forename"] = $forename;
    
    $dn = null;
    if(!empty($_REQUEST['DN'])) { 
        $dn = $_REQUEST['DN'];
    }
    $params["DN"] = $dn;
    
    //Note that the true/false specified must be converted into boolean true/false.
    $isAdmin = null;
    if(!empty($_REQUEST['IsAdmin'])) { 
       if($_REQUEST['IsAdmin']=="true"){
            $isAdmin = true;
       }
       elseif ($_REQUEST['IsAdmin']=="false"){
           $isAdmin = false;
       }
    }
    $params["IsAdmin"] = $isAdmin;
       
    $currentUserDN = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($currentUserDN);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    
    //get users
    $params["Users"] = \Factory::getUserService()->getUsers($surname, $forename, $dn, $isAdmin);
    
    
    show_view("admin/users.php", $params, "Users");
}