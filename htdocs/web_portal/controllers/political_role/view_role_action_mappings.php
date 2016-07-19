<?php

/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Page controller for viewing the GOCDB's Role Action Mappings rules, i.e.
 * show a map which defines which roles held over which objects enable which
 * permissions over which target object types.
 *
 * @author David Meredith
 */
function view_role_action_mappings() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';

    // Get all the role type names from the role action mapping xml file
    // Returns an associative array where keys map to unique role type names and
    // values define the name of the object type that the role is over,
    // e.g. ['Service Group Administrator'] => 'ServiceGroup'
    //      ['Site Administrator'] => 'Site'
    $roleTypesOver = \Factory::getRoleActionMappingService()->getRoleTypeNamesForProject(null);

    // debug
//    $allActionsOverByRoleType = array();
//    foreach($roleTypesOver as $roleTypeName => $heldOverObjectType){
//	print_r($roleTypeName.' : '.$heldOverObjectType.'<br>');
//        $actionsOverArray = \Factory::getRoleActionMappingService()->getEnabledActionsForRoleType($roleTypeName);
//	foreach($actionsOverArray as $actionsOver){
//	    print_r($actionsOver[0]. ' '.$actionsOver[1].'<br>');
//	}
//	$allActionsOverByRoleType[$roleTypeName.':'.$heldOverObjectType] = $actionsOverArray;
//    }

    // Associative array where keys are the objectTypeName that the role is held over,
    // and Values are 3 element array instances holding strings for the roleName, actionName, targetObjectTypeName
    // e.g. ['ServiceGroup'] => array('Service Group Administrator', 'ACTION_EDIT_OBJECT', 'ServiceGroup')
    //      ['Project'] => array('COD Staff', 'ACTION_EDIT_OBJECT', 'Project')
    //      ...
    $roleTypeActionTarget_byObjectType = array();
    foreach($roleTypesOver as $roleTypeName => $heldOverObjectType){
    if(!array_key_exists($heldOverObjectType, $roleTypeActionTarget_byObjectType)){
       $roleTypeActionTarget_byObjectType[$heldOverObjectType] = array();
    }
        $actionsOverArray = \Factory::getRoleActionMappingService()->getEnabledActionsForRoleType($roleTypeName);
    foreach($actionsOverArray as $actionOverArray){
        $roleActionTarget = array();
        $roleActionTarget[0] = $roleTypeName;       //RoleType: 'Site Administrator'
        $roleActionTarget[1] = $actionOverArray[0]; //Action: 'EDIT_OBJECT'
        $roleActionTarget[2] = $actionOverArray[1]; //TargetObj: 'Site'

        // add element to result array (element is a new array)
        $roleTypeActionTarget_byObjectType[$heldOverObjectType][] = $roleActionTarget;
    }
    }

    $params = array();
    $params['roleTypeActionTarget_byObjectType'] = $roleTypeActionTarget_byObjectType;
    show_view("political_role/view_role_action_mappings.php", $params, "Role Action Mappings");
    die();

}

