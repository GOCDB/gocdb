<?php

function view_se() {
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';

    if (!isset( $_GET['id']) || !is_numeric( $_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $id = $_GET['id'];

    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    $serv = \Factory::getServiceService();

    // Set values for showing personal data
    list( , $params['authenticated']) = getReadPDParams($user);

    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    $se = $serv->getService($id);

    // Does current viewer have edit permissions over object ?
    $params['ShowEdit'] = false;
//    if($user != null && count($serv->authorize Action(\Action::EDIT_OBJECT, $se, $user))>=1){
//       $params['ShowEdit'] = true;
//    }
    if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $se->getParentSite(), $user)->getGrantAction()){
       $params['ShowEdit'] = true;
    }


    $title = $se->getHostName() . " - " . $se->getServiceType()->getName();
    $params['se'] = $se;
    $params['sGroups'] = $se->getServiceGroups();

    $params['Scopes']= $serv->getScopesWithParentScopeInfo($se);

    // Show upcoming downtimes and downtimes that started within the last thirty days
    $downtimes = $serv->getDowntimes($id, 31);

    $params['Downtimes'] = $downtimes;
    show_view("service/view_service.php", $params, $title);
}
