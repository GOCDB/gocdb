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

    $filterParams = array();

    // Scope parameters
    // By default, use an empty value to return all scopes, i.e. in the PI '&scope='
    // which is same as the PI. If the 'scope' param is not set, then it would fall
    // back to the default scope (if set), but this is not what we want in this interface.
    $filterParams['scope'] = '';

    $selectedScopes = array();

    if (!empty($_GET['mscope'])) {
        $scopeStringParam = '';
        foreach (array_values($_GET['mscope']) as $scopeVal) {
            $scopeStringParam .= $scopeVal . ',';
            $selectedScopes[] = $scopeVal;
        }

    } elseif (\Factory::getConfigService()->getDefaultFilterByScope()) {
        $scopeVal = \Factory::getConfigService()->getDefaultScopeName();
        $scopeStringParam = $scopeVal;
        $selectedScopes[] = $scopeVal;
    }

    $filterParams['scope'] = $scopeStringParam;

    $scopeMatch = "";
    if(isset($_GET['scopeMatch'])) {
        $scopeMatch = $_GET['scopeMatch'];
        $filterParams['scope_match'] = $scopeMatch;
    }

    // extension property (currently only support filtering by one ext prop)
    // Can add filtering by many like scopes in future
    $extensionPropName = "";
    $extensionPropValue ="";
    if(!empty($_GET['extKeyNames'])) {
        $extensionPropName = $_GET['extKeyNames'];
    // only set the ext prop value if the keyName has been set too, otherwise
    // we could end up with an illegal extensions parameter such as: '(=value)'
    if(!empty($extensionPropName) && !empty($_GET['selectedExtKeyValue']) ) {
        $extensionPropValue = $_GET['selectedExtKeyValue'];
    }
    $filterParams['extensions'] = '('.$extensionPropName.'='.$extensionPropValue.')';
    }

    $scopes = \Factory::getScopeService()->getScopes();
    //$sGroups = \Factory::getServiceGroupService()->getServiceGroups($scope, $sgKeyNames, $sgKeyValues);
    $sGroups = \Factory::getServiceGroupService()->getServiceGroupsFilterByParams($filterParams);
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

    $params = array();
    $params['sGroups'] = $sGroups;
    $params['scopes'] = $scopes;
    $params['scopeMatch'] = $scopeMatch;
    $params['selectedScopes']= $selectedScopes; //$scope;
    $params['selectedExtKeyName'] = $extensionPropName; //$sgKeyNames;
    $params['selectedExtKeyValue'] = $extensionPropValue; //$sgKeyValues;
    $params['extKeyName'] = $keynames;
    show_view("service_group/view_all.php", $params);
}
