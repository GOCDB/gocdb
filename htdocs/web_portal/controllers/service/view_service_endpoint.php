<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function view_endpoint() {
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';


    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $id = $_GET['id'];

    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    $serv = \Factory::getServiceService();

    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    /* @var $endpoint \EndpointLocation */
    $endpoint = $serv->getEndpoint($id);
    /* @var $service \Service */
    $service = $endpoint->getService();
    $site = $service->getParentSite();

    // Does current viewer have edit permissions over object ?
    $params['ShowEdit'] = false;
    //if(count($serv->authorize Action(\Action::EDIT_OBJECT, $endpoint->getService(), $user))>=1){
    if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $site, $user)->getGrantAction()){
       $params['ShowEdit'] = true;
    }

    list(, $params['authenticated']) = getReadPDParams($user);

    $title = $endpoint->getName();
    $params['endpoint'] = $endpoint;
    //$params['sGroups'] = $se->getServiceGroups();
    //$params['Scopes']= $serv->getScopesWithParentScopeInfo($se);
    // Show upcoming downtimes and downtimes that started within the last thirty days
    //$downtimes = $serv->getDowntimes($id, 31);
    //$params['Downtimes'] = $downtimes;
    show_view("service/view_service_endpoint.php", $params, $title);


}
