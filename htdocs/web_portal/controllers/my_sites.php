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

function my_sites() {
    require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../components/Get_User_Principle.php';
    $params = array();

    $userServ = \Factory::getUserService();

    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    if(is_null($user)) {
        show_view('error.php', "Unregistered users can't hold a role over sites, NGIs or service groups.");
        die();
    }

    $sites = $userServ->getSitesFromRoles($user);
    if (!empty($sites)) {
        $params['sites_from_roles'] = $sites;
    }

    $sGroups = $userServ->getSGroupsFromRoles($user);
    if (!empty($sGroups)) {
    	$params['sgroups_from_roles'] = $sGroups;
    }

    $ngis = $userServ->getNgisFromRoles($user);
    if (!empty($ngis)) {
        $params['ngis_from_roles'] = $ngis;
    }
    
    $projects = $userServ->getProjectsFromRoles($user);
    if (!empty($projects)) {
        $params['projects_from_roles'] = $projects;
    }

    $title = "My Sites and Groups";
    show_view('my_sites.php', $params, $title);

}





?>