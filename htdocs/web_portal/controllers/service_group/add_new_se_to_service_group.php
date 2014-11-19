<?php
/*______________________________________________________
 *======================================================
 * File: add_new_se_to_service_group.php
 * Author: John Casson
 * Author: David Meredith
 * Description: Processes a new service request and adds the new SE.
 *              to the passed virtual site.
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
 /*======================================================*/

//TODO make this support multiple scopes.

//TODO: make this check to see if portal is read only

/**
 * Controller for a new_service request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_new_se_to_service_group() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

    // Check to see whether to show the link to "add a new SE to this virtual site"
    if(!Factory::getConfigService()->IsOptionalFeatureSet("siteless_services")) {
        throw new Exception("This feature isn't enabled on this GOCDB " .
            "instance. Configuration keyword: siteless_services");
    }

    if($_POST) { // If we receive a POST request it's to add a new SE
        submit_form();
    } else { // If there is no post data, draw the New SE form
        draw_form();
    }
}

/**
 * Retrieves the raw new SE data from a portal request and submit it to the
 * services layer functions.
 * @return null
 */
function submit_form() {
    require_once __DIR__.'/../service/utils.php';
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    $se_data = getSeDataFromWeb();

    if(!is_numeric($_REQUEST['vSiteId'])) {
        throw new Exception("Invalid service group ID");
    }

    if(!is_numeric($_REQUEST['gridId'])) {
        throw new Exception("Invalid service group ID");
    }

    $vSiteId = $_REQUEST['vSiteId'];
    $gridId = $_REQUEST['gridId'];

    try {
        \Factory::getVSiteService()->addNewSeToVSite($se_data, $vSiteId, $gridId);
        // get the vsite's data to use the name in the form
        $vSite = \Factory::getVSiteService()->getVSite($vSiteId, $gridId);
        $params = array('vSite' => $vSite[0]);
        show_view("vsite/submit_add_new_se_to_service_group.php", $params);
    } catch (Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

/**
 *  Draws a form to add a new service to a virtual site
 *  @return null
 */
function draw_form() {
    require_once '../xml_output/get_xml.php';
    require_once '../web_portal/components/Get_User_Principle.php';
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

    if(!is_numeric($_REQUEST['s_group_id'])) {
        throw new Exception("Invalid service group ID");
    }

    if(!is_numeric($_REQUEST['grid_id'])) {
        throw new Exception("Invalid grid ID");
    }

    $vSiteId = $_REQUEST['s_group_id'];
    $gridId = $_REQUEST['grid_id'];
    authorize($vSiteId);

    // Get the vsite's data to show the name in the form
    $vSite = \Factory::getVSiteService()->getVSite($vSiteId, $gridId);

    $type_xml_string = get_xml('Draw_SE_Service_Types', null);
    $type_xml = simplexml_load_string($type_xml_string['XML_String']);
    foreach($type_xml->group as $type) {
        $service_types[] = (string)$type->NAME;
    }

    $params = array('Service_Types' => $service_types, 'vSite' => $vSite[0],
        'gridId' => $gridId);

    show_view("vsite/add_new_se_to_service_group.php", $params);
}

/**
 *  Authorization: does the user hold a role that would allow them to add a
 *  new SE? (e.g. a role over the virtual site)
 *  @return null
 */
function authorize($vSiteId) {
    // check to see if the user has a role over the virtual site
    if(!Factory::getRoleService()->userHasRoleOverVsite($vSiteId)) {
        show_view("error.php", "You do not have permission to add a service to this service group.");
        die();
    }
}

?>