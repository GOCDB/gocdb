<?php
/*______________________________________________________
 *======================================================
 * File: view_service_types.php
 * Author: George Ryall, David Meredith
 * Description: Controller for showing all service types in GOCDB
 *
 * License information
 *
 * Copyright  2011 STFC
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
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';

function show_all(){
    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();

    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    $serviceTypes = \Factory::getServiceTypeService()->getServiceTypes();
    $params['ServiceTypes']= $serviceTypes;
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    show_view('admin/view_service_types.php', $params, 'Service Types');
}
