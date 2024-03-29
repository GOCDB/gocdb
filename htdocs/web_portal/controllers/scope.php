<?php
/*______________________________________________________
 *======================================================
 * File: scope.php
 * Author: George Ryall, David Meredith, Elliott Kasoar
 * Description: Controller for displaying a scope and associated entities
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
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';

function view_scope() {

    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ) {
        throw new \Exception("An id must be specified");
    }
    $idString = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($idString);

    $params['UserIsAdmin'] = false;
    if(!is_null($user)) {
        $params['UserIsAdmin'] = $user->isAdmin();
    }

    $serv = \Factory::getScopeService();
    $scope = $serv->getScope($_REQUEST['id']);

    $params['Name'] = $scope->getName();
    $params['Description'] = $scope->getDescription();
    $params['ID'] = $scope->getId();
    $params['Reserved'] = $scope->getReserved();
    $params['NGIs'] = $serv->getNgisFromScope($scope);
    $params['Sites'] = $serv->getSitesFromScope($scope);
    $params['ServiceGroups'] = $serv->getServiceGroupsFromScope($scope);
    $params['Services'] = $serv->getServicesFromScope($scope);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    show_view("scope.php", $params, $params['Name']);
    die();
}
