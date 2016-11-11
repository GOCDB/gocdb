<?php
/*______________________________________________________
 *======================================================
 * File: delete_ngi.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Answers a ngi delete request
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

function delete_ngi() {
    checkUserIsAdmin();

    if($_POST) {
        submit();
    }
    else {
        draw();
    }
}

function draw() {
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
     if(isset($_REQUEST['id'])){
        $ngi = \Factory::getNgiService()->getNgi($_REQUEST['id']);
     }
     else {
         throw new \Exception("A NGI must be specified in the url");
     }

     $params['NGI']= $ngi;
     $sites = $ngi->getSites();
     $params['Sites'] = $sites;
     $params['Services'] = array();
     foreach($sites as $site){
         foreach($site->getServices() as $service){
             $params['Services'][]=$service;
         }
     }


     show_view('/admin/delete_ngi.php', $params);

}

function submit() {
    //Only administrators can delete sites, double check user is an administrator
    checkUserIsAdmin();
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    if(isset($_REQUEST['id'])){
        $ngi = \Factory::getNgiService()->getNgi($_REQUEST['id']);
     }
     else {
         throw new \Exception("A NGI must be specified in the url");
     }

     //save name to display later
     $params['Name'] = $ngi->getName();

     $dn = Get_User_Principle();
     $user = \Factory::getUserService()->getUserByPrinciple($dn);

     die("Safguard disabled delete - remove this line to enable in [".__FILE__."]");
     
     //remove ngi
     try {
       \Factory::getNgiService()->deleteNgi($ngi, $user);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    show_view('/site/deleted_site.php', $params);

}