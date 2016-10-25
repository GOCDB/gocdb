<?php
function view_site() {
    require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../../web_portal/components/Get_User_Principle.php';

    $serv = \Factory::getSiteService();
    $servServ  = \Factory::getServiceService();

    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $siteId = $_GET['id'];

    $site = $serv->getSite($siteId);
    $allRoles = $site->getRoles();
    $roles = array();
    foreach ($allRoles as $role){
        if($role->getStatus() == \RoleStatus::GRANTED){
            $roles[] = $role;
        }
    }

    //get user for case that portal is read only and user is admin, so they can still see edit links
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $params['UserIsAdmin']=false;
    if(!is_null($user)) {
        $params['UserIsAdmin']=$user->isAdmin();
    }

    $params['authenticated'] = false;
    if($user != null){
        $params['authenticated'] = true;
    }

    // Does current viewer have edit permissions over Site ?
    $params['ShowEdit'] = false;
    //if(count($serv->authorize Action(\Action::EDIT_OBJECT, $site, $user))>=1){
    if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $site, $user)->getGrantAction()){
       $params['ShowEdit'] = true;
    }

    $params['Scopes']= $serv->getScopesWithParentScopeInfo($site);
    $params['ServicesAndScopes']=array();
    foreach($site->getServices() as $service){
        $params['ServicesAndScopes'][]=array(
            'Service'=>$service,
            'Scopes'=>$servServ->getScopesWithParentScopeInfo($service)
        );
    }

    $params['Downtimes'] = $serv->getDowntimes($site->getId(), 31);
    $params['portalIsReadOnly'] = portalIsReadOnlyAndUserIsNotAdmin($user);
    $title = $site->getShortName();
    $params['site'] = $site;
    $params['roles'] = $roles;

    $params['APIAuthenticationEntities'] = $site->getAPIAuthenticationEntities();

    // Add RoleActionRecords to params
    $params['RoleActionRecords'] = \Factory::getRoleService()->getRoleActionRecordsById_Type($site->getId(), 'site');

    show_view("site/view_site.php", $params, $title);
}
