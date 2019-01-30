<?php
/*______________________________________________________
 *======================================================
 * File: start_page.php
 * Author: John Casson, David Meredith, George Ryall
 * Description: Controller for showing the front page
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

function startPage() {
    require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../components/Get_User_Principle.php';
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    $roles = \Factory::getRoleService()->getPendingRolesUserCanApprove($user);

    $configServ = \Factory::getConfigService();
    $showMap = $configServ->getShowMapOnStartPage();

    $params = array('roles' => $roles,
                    'showMap'=>$showMap);
    $title = "GOCDB";
    show_view('start_page.php', $params, $title, null);
}
