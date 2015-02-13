<?php
function showServiceGroup() {
    require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    } 
    $sGroupId = $_REQUEST['id'];

    $sGroup = \Factory::getServiceGroupService()
    	->getServiceGroup($sGroupId);
    $params['sGroup'] = $sGroup;

    // get downtimes that affect services under this service group
    // 31 = the number of days worth of historical downtimes to show
    $downtimes = \Factory::getServiceGroupService()
    	->getDowntimes($sGroupId, 31);

    $params['downtimes'] = $downtimes;
    
    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);

    $allRoles = $sGroup->getRoles(); 
    $roles = array(); 
    foreach ($allRoles as $role){
        if($role->getStatus() == \RoleStatus::GRANTED){
            $roles[] = $role; 
        }
    }
    $params['Roles'] = $roles; 

    $title = $sGroup->getName();

    show_view("service_group/view_sgroup.php", $params, $title);
}