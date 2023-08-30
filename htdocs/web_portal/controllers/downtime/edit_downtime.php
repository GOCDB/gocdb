<?php
/*______________________________________________________
 *======================================================
 * File: edit_downtime.php
 * Author: John Casson, George Ryall (modifications), David Meredith
 * Description: Processes an edit downtime request from the web portal
 *
 * License information
 *
 * Copyright 2009 STFC
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
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/downtime_utils.php';

/**
 * Controller for an edit downtime request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {     // If we receive a POST request it's to update a downtime
        submit($user);
    } else { // If there is no post data, draw the edit downtime form
        draw($user);
    }
}

/**
 * Draws the edit downtime form using the object ID passed in $_REQUEST
 * @param \User $user current user
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't edit a downtime.");
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("A downtime id must be specified");
    }
    $serv = \Factory::getDowntimeService();
    $dt = $serv->getDowntime($_GET['id']);
    if($dt == null){
        throw new Exception("No downtime with that id");
    }

    // check that this downtime is eligible for editing, throws exception if not.
    $serv->editValidationDatePreConditions($dt);
    $serv->authorisation($dt->getServices(), $user);

    $nowUtcDateTime = new \DateTime(null, new \DateTimeZone("UTC"));
    $twoDaysAgoUtcDateTime = $nowUtcDateTime->sub(\DateInterval::createFromDateString('2 days'));
    $twoDaysAgoUtc = $twoDaysAgoUtcDateTime->format('d/m/Y H:i'); //e.g.  02/10/2013 13:20

    $params = array(
        'dt' => $dt,
        'format' => getDateFormat(),
        'nowUtc' => $nowUtcDateTime->format('H:i T'),
        'twoDaysAgoUtc' => $twoDaysAgoUtc);
    show_view('downtime/edit_downtime.php', $params);
}

/**
 * Retrieves the raw edited downtime data from a portal request
 * and submits it to the services layer's downtime functions.
 *
 * @param \User $user current user
 *
 * @throws Exception
 */
function submit(\User $user = null)
{
    $confirmed = $_POST['CONFIRMED'];

    if ($confirmed == true) {
        // Downtime is confirmed, submit it
        $downtimeInfo = json_decode($_POST['newValues'], true);
        $params = [];
        $serv = \Factory::getDowntimeService();
        $dt = $serv->getDowntime($downtimeInfo['DOWNTIME']['EXISTINGID']);
        $downtimeInfo = unsetVariables($downtimeInfo, 'edit');

        foreach ($downtimeInfo['SITE_LEVEL_DETAILS'] as $siteIDs) {
            $downtimeInfo['Impacted_Services'] = $siteIDs['services'];
            $downtimeInfo['Impacted_Endpoints'] = $siteIDs['endpoints'];
        }

        unset($downtimeInfo['SITE_LEVEL_DETAILS']);

        $params['dt'] = $serv->editDowntime($dt, $downtimeInfo, $user);

        show_view("downtime/edited_downtime.php", $params);
    } else {
        // Show user confirmation screen with their input
        $downtimeInfo = getDowntimeFormData();

        /**
         * Need to sort the `impacted_ids` into
         * impacted services and impacted endpoints.
         */
        list(
            $siteLevelDetails,
            $serviceWithEndpoints
        ) = endpointToServiceMapping($downtimeInfo['IMPACTED_IDS']);

        // Delete the unsorted IDs from the downtime info
        unset($downtimeInfo['IMPACTED_IDS']);

        if (!count($siteLevelDetails) > 1) {
            $downtimeInfo['SINGLE_TIMEZONE'] = true;
        }

        list(
            $siteLevelDetails,
            $serviceWithEndpoints
        ) = addParentServiceForEndpoints(
            $serviceWithEndpoints,
            $siteLevelDetails,
            false,
            $downtimeInfo['DOWNTIME']
        );

        $downtimeInfo['SITE_LEVEL_DETAILS'] = $siteLevelDetails;
        $downtimeInfo['SERVICE_WITH_ENDPOINTS'] = $serviceWithEndpoints;
        /**
         * Pass the edit variable so the `confirm_add_downtime` view
         * works as the confirm edit view.
         */
        $downtimeInfo['isEdit'] = true;

        show_view("downtime/confirm_add_downtime.php", $downtimeInfo);
    }
}
