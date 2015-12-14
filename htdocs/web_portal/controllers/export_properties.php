<?php
/*______________________________________________________
 *======================================================
 * File: my_sites.php
 * Author: John Casson, David Meredith (modifications)
 * Description: Controller for showing the user's sites, NGIs and service
 *              groups
 *
 * License information
 *
 * Copyright 2011 STFC
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

function export() {

    require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../components/Get_User_Principle.php';
    $params = array();
    switch($_REQUEST['parent_type']){
        case "Service" :
            $service = \Factory::getServiceService()->getService($_REQUEST['id']);
            $params['properties'] = $service->getServiceProperties();
            break;
        case "EndpointLocation" :
            $endpoint = \Factory::getServiceService()->getEndpoint($_REQUEST['id']);
            $params['properties'] = $endpoint->getEndpointProperties();
            break;
        case "Site" :
            $site = \Factory::getSiteService()->getSite($_REQUEST['id']);
            $params['properties'] = $site->getSiteProperties();
            break;
        case "ServiceGroup" :
            $serviceGroup = \Factory::getServiceGroupService()->getServiceGroup($_REQUEST['id']);
            $params['properties'] = $serviceGroup->getServiceGroupProperties();
            break;
    }
    show_view('exportProperties.php', $params, null, true);

}





?>