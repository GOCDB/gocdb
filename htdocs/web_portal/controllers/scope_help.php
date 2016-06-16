<?php
/*______________________________________________________
 *======================================================
 * File: view_user.php
 * Author: George Ryall, David Meredith
 * Description: Retrieves and draws the data for a user
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
 *
 /*====================================================== */
require_once __DIR__.'/utils.php';
function show_help() {
    //$params['Scopes'] = \Factory::getScopeService()->getScopes();

    $optionalScopes = \Factory::getScopeService()->getScopesFilterByParams(
                    array('excludeReserved' => true), null);
    $reservedScopes = \Factory::getScopeService()->getScopesFilterByParams(
                    array('excludeNonReserved' => true), null);

    $params['optionalScopes'] = $optionalScopes;
    $params['reservedScopes'] = $reservedScopes;

    show_view("scope_help.php", $params, "Scopes");
}