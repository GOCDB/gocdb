<?php
/*______________________________________________________
 *======================================================
 * File: view_downtime.php
 * Author: James McCarthy
 * Description: Retrieves and draws the data for a downtime
 *
 * License information
 *
 * Copyright � 2009 STFC
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
function view() {
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';
    
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    
    //date_default_timezone_set("UTC");
    
    $timePeriod = 1;
    if(isset($_REQUEST['timePeriod'])) {
        $timePeriod = $_REQUEST['timePeriod'];
    }
    
    $days = 7 * $timePeriod;
    
    $windowStart = date("Y-m-d");    
    $windowEnd = date_add(date_create(date("Y-m-d")), date_interval_create_from_date_string($days.' days'));
    
    $downtimesA = \Factory::getDowntimeService()->getActiveDowntimes();
    $downtimesI = \Factory::getDowntimeService()->getImminentDowntimes($windowStart,$windowEnd);
    $params['timePeriod'] = $timePeriod;
    $params['downtimesActive'] = $downtimesA;
    $params['downtimesImmenent'] = $downtimesI;
    show_view("downtime/downtimes_overview.php", $params);
}