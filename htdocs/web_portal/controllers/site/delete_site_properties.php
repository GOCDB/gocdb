<?php
/*______________________________________________________
 *======================================================
 * File: delete_site_properties.php
 * Author: Tom Byrne, George Ryall, John Casson, David Meredith, James McCarthy
 * Description: accepts an array of site property id's and then either
 * deletes them or prompts the user for confirmation
 *
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
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';

function delete() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if (empty($_REQUEST['selectedPropIDs'])) {
        throw new Exception("At least one property must be selected for deletion");
    }
    if (!isset($_REQUEST['parentID']) || !is_numeric($_REQUEST['parentID']) ){
        throw new Exception("A site id must be specified");
    }
    //get the service and properties, with the properties stored in an array
    $site = \Factory::getSiteService()->getSite($_REQUEST['parentID']);
    foreach ($_REQUEST['selectedPropIDs'] as $i => $propID){
        $propertyArray[$i] = \Factory::getSiteService()->getProperty($propID);

    }

    submit($propertyArray, $site, $user);

}

function submit(array $propertyArray, \Site $site, \User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't delete a site property.");
    }

    $params['propArr'] = $propertyArray;
    $params['site'] = $site;

    //remove site property
    try {
        $serv = \Factory::getSiteService();
        $serv->deleteSiteProperties($site, $user, $propertyArray);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }


    show_view('/site/deleted_site_properties.php', $params);

}