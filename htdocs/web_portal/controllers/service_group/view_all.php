<?php
/*______________________________________________________ 
 *======================================================
 * File: view_all.php
 * Author: John Casson, David Meredith
 * Description: Controller for viewing all VSites in GOCDB
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
 * 
/*====================================================== */ 
function showAllServiceGroups(){
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
	
    $scope = '%%';
    if(!empty($_REQUEST['scope'])) { 
       $scope = $_REQUEST['scope'];
    }
    
    $scopes = \Factory::getScopeService()->getScopes();
    
    $sgKeyNames = "";
    if(isset($_REQUEST['sgKeyNames'])) {
        $sgKeyNames = $_REQUEST['sgKeyNames'];
    }
    
    $sgKeyValues ="";
    if(isset($_REQUEST['selectedSGKeyValue'])) {
        $sgKeyValues = $_REQUEST['selectedSGKeyValue'];
    }
    
    $sGroups = \Factory::getServiceGroupService()->getServiceGroups($scope, $sgKeyNames, $sgKeyValues);
    $exServ = \Factory::getExtensionsService();
    
    /* Doctrine will provide keynames that are the same even when selecting distinct becase the object
     * is distinct even though the name is not unique. To avoid showing the same name repeatdly in the filter
    * we will load all the keynames into an array before making it unique
    */
	$keynames=array();		
    foreach($exServ->getServiceGroupExtensionsKeyNames() as $extension){
        $keynames[] = $extension->getKeyName();
    }
    $keynames = array_unique($keynames);
        
    $params['sGroups'] = $sGroups;
    $params['scopes']=$scopes;
    $params['selectedScope']=$scope;
    $params['selectedSGKeyName']=$sgKeyNames;
    $params['selectedSGKeyValue']=$sgKeyValues;
    $params['sgKeyName']=$keynames;
    show_view("service_group/view_all.php", $params);
}