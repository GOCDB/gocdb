<?php

/*______________________________________________________
 *======================================================
 * File: view_service_type.php
 * Author: George Ryall, David Meredith
 * Description: Controller for displaying a service type and associated services
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
require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';

function view_service_type()
{
    $params = [];

    $identifier = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($identifier);

    if ($user != null) {
        $params['isUserAdmin'] = checkUserForAdminCredentials($user);
    }
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
        throw new Exception("An id must be specified");
    }

    $serv = \Factory::getServiceTypeService();
    /**
     * @var \ServiceType $serviceType
     */
    $serviceType = $serv ->getServiceType($_REQUEST['id']);

    $params['Name'] = $serviceType->getName();
    $params['Description'] = $serviceType->getDescription();
    $params['ID'] = $serviceType->getId();
    $params['AllowMonitoringException'] = $serviceType->getAllowMonitoringException();
    $params['Services'] = $serv->getServices($params['ID']);

    show_view("service_type/view_service_type.php", $params, $params['Name']);
}
