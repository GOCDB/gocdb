<?php
/*______________________________________________________
 *======================================================
 * File: delete_downtime.php
 * Author: John Casson, David Meredith
 * Description: Answers a request to delete a downtime
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

/**
 * Controller for a delete downtime request
 * @return null
 */
function delete() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';
    
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if(is_null($user)) {
       throw new \Exception("Unregistered users can't delete a downtime.");
    }

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    $serv = \Factory::getDowntimeService();
    if(isset($_REQUEST['id'])){
      $dt = $serv->getDowntime($_REQUEST['id']);
      if($dt != null){
         $serv->deleteDowntime($dt, $user);
      }
    }

    show_view('downtime/deleted_downtime.php');
}
?>