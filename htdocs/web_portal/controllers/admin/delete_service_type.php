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

function delete_service_type(){

    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    //Get the service type from the id
    $serv= \Factory::getServiceTypeService();
    $serviceType =$serv ->getServiceType($_REQUEST['id']);

    //keep the name to display later
    $params['Name'] = $serviceType -> getName();

    //Delete the service type. This fuction will check the user is allowed to
    //perform this action and throw an error if not.
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    try {
        $serv->deleteServiceType($serviceType, $user);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    show_view("admin/deleted_service_type.php", $params, $params['Name'].'deleted');

}
