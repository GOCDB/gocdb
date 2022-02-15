<?php
/*______________________________________________________
 *======================================================
 * File: scopes.php
 * Author: George Ryall, David Meredith, Elliott Kasoar
 * Description: Controller for showing all scope tags available in GOCDB
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

function show_scopes() {

    $scopes = \Factory::getScopeService()->getScopes();
    $params['Scopes'] = $scopes;

    $idString = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($idString);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    $params['UserIsAdmin'] = false;
    if(!is_null($user)) {
        $params['UserIsAdmin'] = $user->isAdmin();
    }

    $optionalScopes = \Factory::getScopeService()->getScopesFilterByParams(
                    array('excludeReserved' => true), null);
    $reservedScopes = \Factory::getScopeService()->getScopesFilterByParams(
                    array('excludeNonReserved' => true), null);

    $params['optionalScopes'] = $optionalScopes;
    $params['reservedScopes'] = $reservedScopes;

    show_view('scopes.php', $params, 'Scopes');
    die();
}
