<?php
/*______________________________________________________
 *======================================================
 * File: delete_site.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Answers a site delete request
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
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';

function delete() {
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }

    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //get the site
    $site = \Factory::getSiteService()->getSite($_REQUEST['id']);
    
    if($_POST or (sizeof($site->getServices()) == 0)) {
        submit($site, $user);
    }
    else {
        draw($site);
    }
    
}

function draw(\Site $site) {
     //Only administrators can delete sites, double check user is an administrator
     checkUserIsAdmin();
     
     $params['Site'] = $site;
     $params['Services'] = $site->getServices();
     
     show_view('/site/delete_site.php', $params);
     
}

function submit(\Site $site, \User $user = null) {
    //Only administrators can delete sites, double check user is an administrator
     checkUserIsAdmin();
     
     //save name to display later
     $params['Name'] = $site->getName();
     
     //remove Site
     try {
       \Factory::getSiteService()->deleteSite($site, $user);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }   
    
    show_view('/site/deleted_site.php', $params);

}