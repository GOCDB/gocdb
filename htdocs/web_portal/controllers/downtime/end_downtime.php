<?php
/*______________________________________________________
 *======================================================
 * File: end.php
 * Author: David Meredith, John Casson
 * Description: Ends a downtime now.
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


/**
 * Controller for an edit downtime request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function endDt() {
	require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
	require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';
    
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    
   	$dn = Get_User_Principle();
	$user = \Factory::getUserService()->getUserByPrinciple($dn);
 
    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
	$serv = \Factory::getDowntimeService();
	$dt = $serv->getDowntime($_REQUEST['id']);

    $serv->endDowntime($dt, $user);

    $params = array('downtime' => $dt);
    show_view("downtime/ended_downtime.php", $params);
}
?>