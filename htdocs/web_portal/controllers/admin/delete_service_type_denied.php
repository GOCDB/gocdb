<?php
/*______________________________________________________
 *======================================================
 * File: delete_service_type_denied.php
 * Author: George Ryall
 * Description: Displays a page explaining a service type delition has failed
 *              this happens when there are still services of that
 *              service type in the database
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
require_once __DIR__ . '/../utils.php';

function deny_delete_type(){
     //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();

    //Get a service type service and then the service type to be deleted
    $serv= \Factory::getServiceTypeService();
    $serviceType =$serv ->getServiceType($_REQUEST['id']);

    //Get the services for that service and pass them to the denied view
    $params['ServiceType'] = $serviceType;
    $params['Services'] = $serv ->getServices($serviceType->getId());

    //display the deletion denied view
    show_view("admin/delete_service_type_denied.php", $params, 'Deletion Failed');

}