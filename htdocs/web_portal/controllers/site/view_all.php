<?php
/*______________________________________________________
 *======================================================
 * File: view_all.php
 * Author: John Casson, David Meredith
 * Description: Controller for viewing all Sites in GOCDB
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
function showAllSites(){
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

    $exServ = \Factory::getExtensionsService();

    // Do we really need to validate the URL parameter values, as the query
    // to the DB always uses bind variables to protect against injection?
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Validate.php';
    $validatorService = new \org\gocdb\services\Validate();

    // stores params to send to the doctrine query
    $filterParams = array();

    $ngi = '';
    if(!empty($_GET['NGI'])) {
       $ngi = $_GET['NGI'];
       if(!$validatorService->validate('ngi', 'NAME', $ngi)){
          throw new Exception("Invalid NGI parameter value");
       }
       $filterParams['roc'] = $ngi;
    }

    $prodStatus = '';
    if(!empty($_GET['prodStatus'])) {
       $prodStatus = $_GET['prodStatus'];
       $filterParams['production_status'] = $prodStatus;
    }

    $showClosed = false;
    if(isset($_GET['showClosed'])) {
    $showClosed = true;
    } else {
    $filterParams['exclude_certification_status'] = 'Closed';
    }

    $certStatus = '';
    if(!empty($_GET['certStatus'])) {
       $certStatus = $_GET['certStatus'];
       $filterParams['certification_status'] = $certStatus;
       $showClosed = false;
    }

    // Site extension property (currently only support filtering by one ext prop)
    // Can add filtering by many like scopes in future
    $siteExtensionPropName = "";
    $siteExtensionPropValue ="";
    if(!empty($_GET['siteKeyNames'])) {
        $siteExtensionPropName = $_GET['siteKeyNames'];
    // only set the ext prop value if the keyName has been set too, otherwise
    // we could end up with an illegal extensions parameter such as: '(=value)'
    if(!empty($siteExtensionPropName) && !empty($_GET['selectedSiteKeyValue']) ) {
        $siteExtensionPropValue = $_GET['selectedSiteKeyValue'];
    }
    $filterParams['extensions'] = '('.$siteExtensionPropName.'='.$siteExtensionPropValue.')';
    }

    // Scope parameters
    // By default, use an empty value to return all scopes, i.e. in the PI '&scope='
    // which is same as the PI. If the 'scope' param is not set, then it would fall
    // back to the default scope (if set), but this is not what we want in this interface.
    $filterParams['scope'] = '';
    $filterParams['scope_match'] = '';
    $selectedScopes = array();
    if(!empty($_GET['mscope'])) {
        $scopeStringParam = '';
        foreach($_GET['mscope'] as $key => $scopeVal){
            $scopeStringParam .= $scopeVal.',';
            $selectedScopes[] = $scopeVal;
        }
        $filterParams['scope'] = $scopeStringParam;
            $scopeMatch = 'all';
        if(isset($_GET['scopeMatch'])) {
            $scopeMatch = $_GET['scopeMatch'];
        }
        $filterParams['scope_match'] = $scopeMatch;

    } elseif (\Factory::getConfigService()->getDefaultFilterByScope()) {
        $scopeVal = \Factory::getConfigService()->getDefaultScopeName();
        $selectedScopes[] = $scopeVal;
        $filterParams['scope'] = $scopeVal;
        $filterParams['scope_match'] = 'all';
}

    $serv = \Factory::getSiteService();

    $params['scopes']=  \Factory::getScopeService()->getScopes();
    $params['scopeMatch']= $filterParams['scope_match'];
    //$params['sites'] = $serv->getSitesBy($ngi, $prodStatus, $certStatus, $scope, $showClosed, null, $siteKeyNames, $siteKeyValues);
    $params['sites'] = $serv->getSitesFilterByParams($filterParams);
    $params['NGIs'] = $serv->getNGIs();
    $params['prodStatuses'] = $serv->getProdStatuses();

    //Remove SC and PPS infrastructures from drop down list. TODO: Delete this block once they no longer exist
    $SCInfrastructure = $serv->getProdStatusByName('SC');
    $PPSInfrastructure = $serv->getProdStatusByName('PPS');
    $productionStatuses=array();
    foreach($params['prodStatuses'] as $ps){
        if($ps != $SCInfrastructure and $ps != $PPSInfrastructure){
            $productionStatuses[]=$ps;
        }
    }
    $params['prodStatuses'] = $productionStatuses;
    //delete up to here once pps and sc infrastructures have been removed from database

    /* Doctrine will provide keynames that are the same even when selecting distinct becase the object
     * is distinct even though the name is not unique. To avoid showing the same name repeatdly in the filter
     * we will load all the keynames into an array before making it unique
     */
    $keynames=array();
    foreach($exServ->getSiteExtensionsKeyNames() as $extension){
        $keynames[] = $extension->getKeyName();
    }
    $keynames = array_unique($keynames);

    $params['selectedNgi'] = $ngi;
    $params['certStatuses'] = $serv->getCertStatuses();
    $params['selectedProdStatus'] = $prodStatus;
    $params['selectedCertStatus'] = $certStatus;
    $params['selectedScopes'] = $selectedScopes;
    $params['showClosed'] = $showClosed;
    $params['siteKeyNames'] = $keynames;
    $params['selectedSiteKeyNames'] = $siteExtensionPropName;
    $params['selectedSiteKeyValue'] = $siteExtensionPropValue;

    show_view("site/view_all.php", $params, "Sites");
}