<?php
/*======================================================
 * File: sitesForGoogleMapXML.php
 * Author: George Ryall
 * Description: Returns xml containing all sites that are not closed and have
 * lcation information. Used by google map on start page.
 *
 *
 * License information
 *
 * Copyright � 2011 STFC
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
require_once __DIR__ . "/../../../lib/Doctrine/bootstrap.php";
require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';

function show_xml(){
    try{
        $xml = Factory::getSiteService()->getGoogleMapXMLString();
    }
    catch(Exception $e){
        show_view('error.php', $e->getMessage(), "Error");
    }

    $params['XML']=$xml;

    show_view('sitesForGoogleMapXML.php', $params, null, true);
}