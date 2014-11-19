<?php
/*______________________________________________________
 *======================================================
 * File: view_user.php
 * Author: John Casson, David Meredith
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
function view_user() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';

    $user = \Factory::getUserService()->getUser($_REQUEST['id']);
    $params['user'] = $user;

    $roles = \Factory::getRoleService()->getUserRoles($user, \RoleStatus::GRANTED); //$user->getRoles();
    $params['roles'] = $roles;

    $params['portalIsReadOnly'] = \Factory::getConfigService()->IsPortalReadOnly();

    $title = $user->getFullName();
    show_view("user/view_user.php", $params, $title);
}