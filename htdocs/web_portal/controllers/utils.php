<?php


/**
 * Checks with the config service if the portal is in read only mode and if
 * it is throws an exception (except when the user is a GOCDB admin)
 *
 * @throws \Exception
 */
function checkPortalIsNotReadOnlyOrUserIsAdmin(\User $user = null) {
    if (portalIsReadOnlyAndUserIsNotAdmin($user)){
        throw new \Exception("The portal is currently in read only mode, changes can not be made.");
    }
}

/**
 * Checks config service and returns true if the portal is in read only mode (and
 * the user is not a GOCDB admin.) Used to hide features of the portal used for
 * editing entities when in read only mode.
 *
 * @param \user $user
 *            current user
 * @return boolean
 */
function portalIsReadOnlyAndUserIsNotAdmin(\user $user = null) {
    require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';
    
    // this block is required to deal with unregistered users (where $user is null)
    $userIsAdmin = false;
    if (! is_null($user)){
        if ($user->isAdmin()){ // sub query required becauser ->isAdmin can't be called on null
            $userIsAdmin = true;
        }
    }
    
    if (\Factory::getConfigService()->IsPortalReadOnly() and ! $userIsAdmin){
        return true;
    }else{
        return false;
    }
}

/**
 * Checks the user has permission to perform/view admin functionality
 * 
 * @return null
 *
 */
function checkUserIsAdmin() {
    require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if ($user == null){
        throw new Exception("Unregistered users may not carry out this operation");
    }
    if (! $user->isAdmin()){
        throw new Exception("Only GOCDB administrators can perform this action.");
    }
}
function CheckCurrentUserCanEditProject(\Project $project) {
    require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    
    //$enablingRoles = \Factory::getProjectService()->authorize Action('ACTION_EDIT_OBJECT', $project, $user);
    //if (count($enablingRoles) == 0){
    if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $project, $user)->getGrantAction() == FALSE){
        throw new Exception("You do not have a role that enables you to edit this project");
    }
}

/**
 * Gets the relevant fields from a user's web request
 * ($_REQUEST) and returns an associative array compatible with
 * add_site().
 * 
 * @global array $_REQUEST site data submitted by the end user
 * @return array an array representation of a site
 */
function getSiteDataFromWeb() {
    // Fields that are used to link other objects to the site
    $fields = array (
            'Country',
            'ProductionStatus' 
    );
    
    foreach($fields as $field){
        $site_data [$field] = $_REQUEST [$field];
    }
   
    if(isset($_REQUEST['childServiceScopeAction'])){
	$site_data['childServiceScopeAction'] = $_REQUEST['childServiceScopeAction']; 
    } else {
	$site_data['childServiceScopeAction'] = 'noModify'; 
    }
    
    // get scopes if any are selected, if not set as null
    if (isset($_REQUEST ['Scope_ids'])){
        $site_data ['Scope_ids'] = $_REQUEST ['Scope_ids'];
    }else{
        $site_data ['Scope_ids'] = array ();
    }
    
    /*
     * Certification status is only set during the add_site procedure. Editing an existing site's cert status uses a separate form
     */
    if (isset($_REQUEST ['Certification_Status'])){
        $site_data ['Certification_Status'] = $_REQUEST ['Certification_Status'];
    }
    
    /*
     * ROC is only set during the add_site procedure. A site's ROC can't be edited in the web portal
     */
    if (isset($_REQUEST ['NGI'])){
        $site_data ['NGI'] = $_REQUEST ['NGI'];
    }
    
    // Fields specific to the site object and not linked to other entities
    $site_object_fields = array (
            'SHORT_NAME',
            'OFFICIAL_NAME',
            'HOME_URL',
            'GIIS_URL',
            'IP_RANGE',
            'IP_V6_RANGE',
            'LOCATION',
            'LATITUDE',
            'LONGITUDE',
            'DESCRIPTION',
            'EMAIL',
            'CONTACTTEL',
            'EMERGENCYTEL',
            'CSIRTEMAIL',
            'CSIRTTEL',
            'EMERGENCYEMAIL',
            'HELPDESKEMAIL',
            'DOMAIN', 
            'TIMEZONE'
    );
    
    foreach($site_object_fields as $field){
        $site_data ['Site'] [$field] = $_REQUEST [$field];
    }
    
    /*
     * If the user is updating a site the optional cobjectid parameter will be set. If it is set we return it as part of the array
     */
    if (! empty($_REQUEST ['ID'])){
        $site_data ['ID'] = $_REQUEST ['ID'];
    }
    
    //
    
    return $site_data;
}

/**
 * Gets the relevant fields from a user's web request
 * ($_REQUEST)
 * 
 * @global array $_REQUEST site data submitted by the end user
 * @return array An array of service group data
 */
function getSGroupDataFromWeb() {
    /*
     * $_REQUEST['monitored'] is set by the "Should this Virtual Site be monitored?" tick box
     */
    if (isset($_REQUEST ['monitored'])){
        $monitored = 'Y';
    }else{
        $monitored = 'N';
    }
    
    $sg ['MONITORED'] = $monitored;
    
    if (isset($_REQUEST ['objectId'])){
        $sg ['ID'] = $_REQUEST ['objectId'];
    }
    
    $sg ['SERVICEGROUP'] ['NAME'] = $_REQUEST ['name'];
    $sg ['SERVICEGROUP'] ['DESCRIPTION'] = $_REQUEST ['description'];
    $sg ['SERVICEGROUP'] ['EMAIL'] = $_REQUEST ['email'];
    
    // get scopes if any are selected, if not set as null
    if (isset($_REQUEST ['Scope_ids'])){
        $sg ['Scope_ids'] = $_REQUEST ['Scope_ids'];
    }else{
        $sg ['Scope_ids'] = array ();
    }
    
    return $sg;
}

/**
 * Gets the relevant fields from a user's web request
 * ($_REQUEST) and returns an associative array compatible with
 * add_new_service() or editService.
 * 
 * @global array $_REQUEST SE data submitted by the end user
 * @return array an array representation of a service
 */
function getSeDataFromWeb() {
    // Fields that are used to link other objects to the site
    $fields = array (
            'serviceType',
            'endpointUrl' 
    );
    
    foreach($fields as $field){
        $se_data [$field] = $_REQUEST [$field];
    }
    
    /*
     * If the user is adding a new service the optional HOSTING_SITE parameter will be set. If it is set we return it as part of the array
     */
    if (! empty($_REQUEST ['hostingSite'])){
        $se_data ['hostingSite'] = $_REQUEST ['hostingSite'];
    }
    
    // $se_data['SE']['ENDPOINT'] = $_REQUEST['HOSTNAME'] . $_REQUEST['serviceType'];
    $se_data ['SE'] ['HOSTNAME'] = $_REQUEST ['HOSTNAME'];
    $se_data ['SE'] ['HOST_IP'] = $_REQUEST ['HOST_IP'];
    $se_data ['SE'] ['HOST_IP_V6'] = $_REQUEST['HOST_IP_V6'];
    $se_data ['SE'] ['HOST_DN'] = $_REQUEST ['HOST_DN'];
    $se_data ['SE'] ['DESCRIPTION'] = $_REQUEST ['DESCRIPTION'];
    $se_data ['SE'] ['HOST_OS'] = $_REQUEST ['HOST_OS'];
    $se_data ['SE'] ['HOST_ARCH'] = $_REQUEST ['HOST_ARCH'];
    $se_data ['SE'] ['EMAIL'] = $_REQUEST ['EMAIL'];
    $se_data ['BETA'] = $_REQUEST ['HOST_BETA'];
    $se_data ['PRODUCTION_LEVEL'] = $_REQUEST ['PRODUCTION_LEVEL'];
    $se_data ['IS_MONITORED'] = $_REQUEST ['IS_MONITORED']; /*
                                                           * If the user is updating a service the optional cobjectid parameter will be set. If it is set we return it as part of the array
                                                           */
    if (! empty($_REQUEST ['ID'])){
        $se_data ['ID'] = $_REQUEST ['ID'];
    }
    
    // get scopes if any are selected, if not set as null
    if (isset($_REQUEST ['Scope_ids'])){
        $se_data ['Scope_ids'] = $_REQUEST ['Scope_ids'];
    }else{
        $se_data ['Scope_ids'] = array ();
    }
    
    return $se_data;
}

/**
 *
 * @return array
 */
function getProjectDataFromWeb() {
    // new projects won't have an id
    if (isset($_REQUEST ['ID'])){
        $projectValues ['ID'] = $_REQUEST ['ID'];
    }
    
    // Get the rest of the project post data into an array
    $fields = array (
            'Name',
            'Description' 
    );
    
    foreach($fields as $field){
        $projectValues [$field] = $_REQUEST [$field];
    }
    return $projectValues;
}
function getNGIDataFromWeb() {
    // Get the NGI post data into an array
    $fields = array (
            'EMAIL',
            'HELPDESK_EMAIL',
            'ROD_EMAIL',
            'SECURITY_EMAIL', 
            'GGUS_SU' 
    );
    
    foreach($fields as $field){
        $NGIValues [$field] = $_REQUEST [$field];
    }
    
    if (isset($_REQUEST ['NAME'])){
        $NGIValues ['NAME'] = $_REQUEST ['NAME'];
    }
    
    $scopes = array ();
    if (isset($_REQUEST ['SCOPE_IDS'])){
        $scopes = $_REQUEST ['SCOPE_IDS'];
    }
    
    $id = null;
    if (isset($_REQUEST ['ID'])){
        $id = $_REQUEST ['ID'];
    }
    
    $values = array (
            'NGI' => $NGIValues,
            'SCOPES' => $scopes,
            'ID' => $id 
    );
    
    return $values;
}

/**
 * Gets the relevant fields from a user's web request
 * ($_REQUEST) and returns an associative array.
 * 
 * @global array $_REQUEST Downtime data submitted by the end user
 * @return array an array representation of a downtime
 */
function getDtDataFromWeb() {
    $dt ['DOWNTIME'] ['SEVERITY'] = $_REQUEST ['SEVERITY'];
    $dt ['DOWNTIME'] ['DESCRIPTION'] = $_REQUEST ['DESCRIPTION'];
    $dt ['DOWNTIME'] ['START_TIMESTAMP'] = $_REQUEST ['START_TIMESTAMP'];
    $dt ['DOWNTIME'] ['END_TIMESTAMP'] = $_REQUEST ['END_TIMESTAMP'];
    
    $dt ['DOWNTIME'] ['DEFINE_TZ_BY_UTC_OR_SITE'] = 'utc'; //default 
    if(isset($_REQUEST ['DEFINE_TZ_BY_UTC_OR_SITE'])){
       $dt ['DOWNTIME'] ['DEFINE_TZ_BY_UTC_OR_SITE'] = $_REQUEST ['DEFINE_TZ_BY_UTC_OR_SITE']; // 'utc' or 'site' 
    } 
    
    $dt ['IMPACTED_IDS'] = $_REQUEST ['IMPACTED_IDS'];
    if (! isset($_REQUEST ['IMPACTED_IDS'])){
        throw new Exception('Error - No endpoints or services selected, downtime must affect at least one endpoint');
    }
    
    //Get the previous downtimes ID if we are doing an edit
    if(isset($_REQUEST['DOWNTIME_ID'])){
        $dt['DOWNTIME']['EXISTINGID'] = $_REQUEST['DOWNTIME_ID']; 
    }
    
    return $dt;
}

/**
 *  Gets the relevant fields from a user's web request
 *  ($_REQUEST) and returns an associative array.
 *  @global array $_REQUEST Downtime data submitted by the end user
 *  @return array an array representation of a downtime
 */
function getDtDataFromWebOld() {
    $dt['DOWNTIME']['SEVERITY'] = $_REQUEST['SEVERITY'];
    $dt['DOWNTIME']['DESCRIPTION'] = $_REQUEST['DESCRIPTION'];
    $dt['DOWNTIME']['START_TIMESTAMP'] = $_REQUEST['START_TIMESTAMP'];
    $dt['DOWNTIME']['END_TIMESTAMP'] = $_REQUEST['END_TIMESTAMP'];
    if(!isset($_REQUEST['Impacted_SEs'])){
        throw new Exception('Error - No service selected, downtime must affect at least one service'); 
    }
    $dt['Impacted_SEs'] = $_REQUEST['Impacted_SEs'];
    if(isset($_REQUEST['ID'])) {
        $dt['ID'] = $_REQUEST['ID'];
    }
    return $dt;
}

/**
 * Gets the site properties data passed by user *
 */
function getSpDataFromWeb() {
    $sp ['SITEPROPERTIES'] ['SITE'] = $_REQUEST ['SITE'];
    $sp ['SITEPROPERTIES'] ['NAME'] = $_REQUEST ['KEYPAIRNAME'];
    $sp ['SITEPROPERTIES'] ['VALUE'] = $_REQUEST ['KEYPAIRVALUE'];
    if (isset($_REQUEST ['PROP'])){
        $sp ['SITEPROPERTIES'] ['PROP'] = $_REQUEST ['PROP'];
    }
	
	if(isset($sp['SITEPROPERTIES']['NAME'])){
        $sp['SITEPROPERTIES']['NAME'] = trim($sp['SITEPROPERTIES']['NAME']); 
    }
    if(isset($sp['SITEPROPERTIES']['VALUE'])){
        $sp['SITEPROPERTIES']['VALUE'] = trim($sp['SITEPROPERTIES']['VALUE']); 
    }	
    return $sp;
}

/**
 * Gets the service properties data passed by user *
 */
function getSerPropDataFromWeb() {
    $sp ['SERVICEPROPERTIES'] ['SERVICE'] = $_REQUEST ['SERVICE'];
    $sp ['SERVICEPROPERTIES'] ['NAME'] = $_REQUEST ['KEYPAIRNAME'];
    $sp ['SERVICEPROPERTIES'] ['VALUE'] = $_REQUEST ['KEYPAIRVALUE'];
    if (isset($_REQUEST ['PROP'])){
        $sp ['SERVICEPROPERTIES'] ['PROP'] = trim($_REQUEST ['PROP']);
    }
    if(isset($sp['SERVICEPROPERTIES']['NAME'])){
        $sp['SERVICEPROPERTIES']['NAME'] = trim($sp['SERVICEPROPERTIES']['NAME']); 
    }
    if(isset($sp['SERVICEPROPERTIES']['VALUE'])){
         $sp['SERVICEPROPERTIES']['VALUE'] = trim($sp['SERVICEPROPERTIES']['VALUE']); 
    }	
    return $sp;
}

/**
 * Gets the endpoint properties data passed by user 
 */
function getEndpointPropDataFromWeb() {
    $sp = array(); 
    if (isset($_REQUEST ['PROP'])){
        $sp ['ENDPOINTPROPERTIES'] ['PROP'] = trim($_REQUEST ['PROP']);
    }
    if(isset($_REQUEST ['ENDPOINTID'])){
        $sp['ENDPOINTPROPERTIES']['ENDPOINTID'] = trim($_REQUEST ['ENDPOINTID']); 
    }
    if(isset($_REQUEST ['KEYPAIRNAME'])){
        $sp['ENDPOINTPROPERTIES']['NAME'] = trim($_REQUEST ['KEYPAIRNAME']); 
    }
    if(isset($_REQUEST ['KEYPAIRVALUE'])){
         $sp['ENDPOINTPROPERTIES']['VALUE'] = trim($_REQUEST ['KEYPAIRVALUE']); 
    }	
    return $sp;
}

/**
 * Gets the service group properties data passed by user *
 */
function getSerGroupPropDataFromWeb() {
    $sp ['SERVICEGROUPPROPERTIES'] ['SERVICEGROUP'] = $_REQUEST ['SERVICEGROUP'];
    $sp ['SERVICEGROUPPROPERTIES'] ['NAME'] = $_REQUEST ['KEYPAIRNAME'];
    $sp ['SERVICEGROUPPROPERTIES'] ['VALUE'] = $_REQUEST ['KEYPAIRVALUE'];
    if (isset($_REQUEST ['PROP'])){
        $sp ['SERVICEGROUPPROPERTIES'] ['PROP'] = $_REQUEST ['PROP'];
    }
    return $sp;
}

/**
 * Gets the service endpoint data passed by user *
 */
function getEndpointDataFromWeb() {
    $endpoint ['SERVICEENDPOINT'] ['SERVICE'] = $_REQUEST ['SERVICE'];
    $endpoint ['SERVICEENDPOINT'] ['NAME'] = $_REQUEST ['ENDPOINTNAME'];
    $endpoint ['SERVICEENDPOINT'] ['URL'] = $_REQUEST ['ENDPOINTURL'];
    $endpoint ['SERVICEENDPOINT'] ['INTERFACENAME'] = $_REQUEST ['ENDPOINTINTERFACENAME'];
    if(isset($_REQUEST ['DESCRIPTION'])){
        $endpoint ['SERVICEENDPOINT'] ['DESCRIPTION'] = trim($_REQUEST ['DESCRIPTION']);
    }
    if (isset($_REQUEST ['ENDPOINTID'])){
        $endpoint ['SERVICEENDPOINT'] ['ENDPOINTID'] = trim($_REQUEST ['ENDPOINTID']);
    }
    return $endpoint;
}

/**
 * Date format used by the calendar Javascript in downtime controllers *
 */
function getDateFormat() {
    return "d/m/Y H:i";
}

/**
 * Gets the submitted post data for the addition or editing of a scope
 * 
 * @global array $_REQUEST array containg the post data
 * @return array
 */
function getScopeDataFromWeb() {
    $scopeData ['Name'] = $_REQUEST ['Name'];
    $scopeData ['Description'] = $_REQUEST ['Description'];
    if (array_key_exists('Id', $_REQUEST)){
        $scopeData ['Id'] = $_REQUEST ['Id'];
    }
    
    return $scopeData;
}

/**
 * Gets the submitted post data for the addition or editing of a service type
 * 
 * @global array $_REQUEST array containg the post data
 * @return array $serviceTypeData an array containg the new site data
 */
function getSTDataFromWeb() {
    $serviceTypeData ['Name'] = $_REQUEST ['Name'];
    $serviceTypeData ['Description'] = $_REQUEST ['Description'];
    if (array_key_exists('ID', $_REQUEST)){
        $serviceTypeData ['ID'] = $_REQUEST ['ID'];
    }
    
    return $serviceTypeData;
}
