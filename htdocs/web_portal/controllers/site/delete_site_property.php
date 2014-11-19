<?php
/*______________________________________________________
 *======================================================
 * File: delete_site_property.php
 * Author: James McCarthy
 * Description: Answers a site delete request
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
         
    //get the site
    if (isset($_REQUEST['propertyid'])){
    $property = \Factory::getSiteService()->getProperty($_REQUEST['propertyid']);
    $site = \Factory::getSiteService()->getSite($_REQUEST['id']);
    }
    else {
        throw new \Exception("A site must be specified");
    }

    if($_POST) {
        submit($property, $user, $site);
    }
    else {
        draw($property, $site, $user);
    }
    
}

function draw(\SiteProperty $property,\Site $site,\User $user) {
    if (is_null ( $user )) {
        throw new Exception ( "Unregistered users can't delete a service property." );
    }
    // Check user has permission to delete a site property    
    $serv = \Factory::getSiteService();    
    $serv->validatePropertyActions ( $user, $site );
    
    $params ['prop'] = $property;
    $params ['site'] = $site;
    
    show_view ( '/site/delete_site_property.php', $params );
}

function submit(\SiteProperty $property, \User $user = null, \Site $site) {

     $params['prop'] = $property;
     $params['site'] = $site;
     
     //remove site property
     try {
     	$serv = \Factory::getSiteService();
       	$serv->deleteSiteProperty($site, $user, $property);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }   
    
    
    show_view('/site/deleted_site_property.php', $params);

}