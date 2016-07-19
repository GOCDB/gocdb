<?php
/*______________________________________________________
 *======================================================
 * File: search.php
 * Author: John Casson, David Meredith (modifications)
 * Description: Controller for searching
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

function search() {
    require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';
    
   
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    $params['authenticated'] = false; 
    if($user != null){
        $params['authenticated'] = true; 
    }
    
    $searchTerm = isset($_POST['SearchString']) ? $_POST['SearchString'] : '';
    
    //strip leading and trailing whitespace off search term
    $searchTerm = strip_tags(trim($searchTerm));
    if(1 === preg_match("/[';\"]/", $searchTerm)){ throw new Exception("Invalid char in search term"); } 
    
    /* Check to see if search string already has either a leading or trailing % 
     * supplied by the user, if not then add them. This is to aid searching*/
    
    
    $params['searchTerm'] = $searchTerm;
    $params['siteResults'] = null;
    $params['serviceResults'] = null;
    $params['userResults'] = null;
    $params['ngiResults'] = null;
    
    if($params['searchTerm'] == "") {
        show_view('search_results.php', $params, "Searching for {$params['searchTerm']}");
        die();
    }
    
    $searchServ = \Factory::getSearchService();
    $siteResults = $searchServ->getSites($params['searchTerm']);
    $serviceResults = $searchServ->getServices($params['searchTerm']);
    $userResults = $searchServ->getUsers($params['searchTerm']);
    $ngiResults = $searchServ->getNgis($params['searchTerm']);

    $params['siteResults'] = $siteResults;
    $params['serviceResults'] = $serviceResults;
    $params['userResults'] = $userResults;
    $params['ngiResults'] = $ngiResults;
    
    show_view('search_results.php', $params, "Searching for \"{$params['searchTerm']}\"");
}

?>
