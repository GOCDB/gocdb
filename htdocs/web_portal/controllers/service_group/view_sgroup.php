<?php
function showServiceGroup() {

    require_once __DIR__.'/../../../../lib/Doctrine/entities/ServiceGroup.php';
    require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $sGroupId = $_GET['id'];

    /* @var $sGroup \ServiceGroup */
    $sGroup = \Factory::getServiceGroupService()->getServiceGroup($sGroupId);
    $params['sGroup'] = $sGroup;

    // get downtimes that affect services under this service group
    // 31 = the number of days worth of historical downtimes to show
    $downtimes = \Factory::getServiceGroupService()->getDowntimes($sGroupId, 31);
    $params['downtimes'] = $downtimes;

    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    list(, $params['authenticated']) = getReadPDParams($user);

    $allRoles = $sGroup->getRoles();
    $roles = array();
    foreach ($allRoles as $role){
        if($role->getStatus() == \RoleStatus::GRANTED){
            $roles[] = $role;
        }
    }
    $params['Roles'] = $roles;

    // Does current viewer have edit permissions over object ?
    $params['ShowEdit'] = false;
    //if(count( \Factory::getServiceGroupService()->authorize Action(\Action::EDIT_OBJECT, $sGroup, $user))>=1){
    if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $sGroup, $user)->getGrantAction()){
       $params['ShowEdit'] = true;
    }

    // Add RoleActionRecords to params
    $params['RoleActionRecords'] = \Factory::getRoleService()->getRoleActionRecordsById_Type($sGroup->getId(), 'servicegroup');

    $title = $sGroup->getName();

    show_view("service_group/view_sgroup.php", $params, $title);
}
