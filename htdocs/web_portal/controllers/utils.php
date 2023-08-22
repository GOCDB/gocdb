<?php

require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';

use Exception;

/**
 * Parse properties file
 *
 * @param string $txtProperties String containing the contents of a .properties
 * @return array $results Associative array of key value pairs
 */
function parse_properties($txtProperties)
{
        $value = '';
        $result = array();

        $lines = explode("\n", $txtProperties);
        $key = "";

        $isWaitingOtherLine = false;
    foreach ($lines as $i => $line) {
        $trimedLine = trim($line);
        if (empty($trimedLine) || (!$isWaitingOtherLine && strpos($line, "#") === 0)) {
            continue;
        }

        if (!$isWaitingOtherLine) {
            $key = substr($line, 0, strpos($line, '='));
            $value = substr($line, strpos($line, '=') + 1, strlen($line));
        } else {
            $value .= $line;
        }

        /* Check if ends with single '\' */
        if (strpos($value, "\\") === strlen($value) - strlen("\\")) {
            $value = substr($value, 0, strlen($value) - 1) . "\n";
            $isWaitingOtherLine = true;
        } else {
            $isWaitingOtherLine = false;
        }

        if ($key == null) {
            $line = $i + 1;
            throw new \Exception("Property name on line {$line} is null");
        }
        if ($value == null) {
            $line = $i + 1;
            throw new \Exception("Property value on line {$line} is null");
        }

        //we can't use the prop key as the key due to key duplicates [PREVIOUSLY] being allowed
        //we are using an indexed array of indexed arrays TODO: use prop key as array key
        $result[] = array($key, $value);

        unset($lines[$i]);
    }

        return $result;
}


/**
 * Builds a JSON string that lists scope tags in different categories based
 * on the provided arguments - used when editing or adding a IScopedEntity.
 * <p>
 * Different JSON keys are used to define the scope tag categories:
 * <ul>
 *   <li>'optional' - Lists optional tags that are freely assignable.</li>
 *   <li>'reserved_optional' - Lists reserved tags that have already been directly
 *      assigned to the $targetScopedEntity, but CAN'T be inherited from the $parentScopedEntity.</li>
 *   <li>'reserved_optional_inheritable' - Lists reserved tags that CAN be inherited
 *      from the $parentScopedEntity (the tag may/may-not be already assigned to the target).</li>
 *   <li>'reserved' - The remaining Reserved tags.</li>
 *   <li>'disableReserved' - Defines a boolean rather than a tag list -
 *                              true to disable the 'reserved' tags or false to enable.</li>
 * </ul>
 * <p>
 * For each scope value, the attributes are ["PK/ID", "tagValue", "boolCheckedOrNot"].
 * <p>
 * If both target and parent scopedEntities are null, then 'reserved_optional' and
 * 'reserved_optional_inheritable' lists will be empty.
 * <p>
 * Sample output:
 * <code>
 * {
 * "optional":[[2,"EGI",true],[1,"Local",false]],
 * "reserved_optional":[[24,"atlas",true],[27,"wlcg",true]],
 * "reserved_optional_inheritable":[[25,"lhcb",true]],
 * "reserved":[[26,"alice",false],[23,"cms",false],[22,"tier1",false],[21,"tier2",false]],
 * "disableReserved":true
 * }
 * </code>
 * @param \IScopedEntity $targetScopedEntity Optional, use Null if creating a new IScopedEntity
 * @param \IScopedEntity $parentScopedEntity Optional, the parent to inherit tags from
 * @param bool $noReservedScopes True to disable 'reserved' tags
 * @param bool $inheritScopeChecked True to set the checked status of each scope value
 *   according to whether the parent has the same scope checked (every scope will always be
 *   false if the $parentScopedEntity is null)
 * @return string
 * @throws \LogicException
 */
function getEntityScopesAsJSON2(
    $targetScopedEntity = null,
    $parentScopedEntity = null,
    $noReservedScopes = true,
    $inheritScopeChecked = false
) {

    $targetScopes = array();
    if ($targetScopedEntity != null) {
        if (!($targetScopedEntity instanceof \IScopedEntity)) {
            throw new \LogicException('Invalid $scopedEntityChild, does not implement IScopedEntity');
        }
        $targetScopes =  $targetScopedEntity->getScopes()->toArray();
    }
    $parentScopes = array();
    if ($parentScopedEntity != null) {
        if (!($parentScopedEntity instanceof \IScopedEntity)) {
            throw new \LogicException('Invalid scopedEntityParent, does not implement IScopedEntity');
        }
        $parentScopes = $parentScopedEntity->getScopes()->toArray();
    }

    // $reservedScopeNames = \Factory::getConfigService()->getReservedScopeList();
    $allScopes = \Factory::getScopeService()->getScopes();
    $optionalScopeIds = array();
    // Reserved optional scope ids
    $resOptScopeIds = array();
    // Reserved optional inherited scope ids
    $resOptHeritScopeIds = array();
    $reservedScopeIds = array();

    /* @var $scope \Scope */
    foreach ($allScopes as $scope) {
        $targetChecked = false;
        $parentChecked = false;
        // is scope already joined to target
        if (in_array($scope, $targetScopes)) {
            $targetChecked = true;
        }
        // is scope already joined to parent
        if (in_array($scope, $parentScopes)) {
            $parentChecked = true;
        }
        // Determine if this tag should be checked = t/f
        $isChecked = $targetChecked;
        if ($inheritScopeChecked) {
            $isChecked = $parentChecked;
        }

        // Is scope tag in the reserved list ?
        if ($scope->getReserved()) {
            // A reserved scope tag:
            if ($parentChecked || $targetChecked) {
                if ($parentChecked) {
                    // tag CAN be inherited from parent, so put in relevant array
                    $resOptHeritScopeIds[] = array($scope->getId(), $scope->getName(), $isChecked);
                } else {
                    // tag CAN'T be inherited from parent, but it has already been directly assigned,
                    // so put in relevant array
                    $resOptScopeIds[] = array($scope->getId(), $scope->getName(), $isChecked);
                }
            } else {
                // tag is not inheritable and has not been directly assigned, so its reserved/protected
                $reservedScopeIds[] = array($scope->getId(), $scope->getName(), $isChecked);
            }
        } else {
            // An optional scope tag:
            $optionalScopeIds[] = array($scope->getId(), $scope->getName(), $isChecked);
        }
    }
    // build the response
    $scopeCategories = array();
    $scopeCategories['optional'] = $optionalScopeIds;
    $scopeCategories['reserved_optional'] = $resOptScopeIds;
    $scopeCategories['reserved_optional_inheritable'] = $resOptHeritScopeIds;
    $scopeCategories['reserved'] = $reservedScopeIds;
    $scopeCategories['disableReserved'] = $noReservedScopes ? true : false;

    return json_encode($scopeCategories);
}

/**
 * Checks with the config service if the portal is in read only mode and if
 * it is throws an exception (except when the user is a GOCDB admin)
 *
 * @throws \Exception
 */
function checkPortalIsNotReadOnlyOrUserIsAdmin(\User $user = null)
{
    if (portalIsReadOnlyAndUserIsNotAdmin($user)) {
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
function portalIsReadOnlyAndUserIsNotAdmin(\user $user = null)
{
    require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';

    // this block is required to deal with unregistered users (where $user is null)
    $userIsAdmin = false;
    if (! is_null($user)) {
        if ($user->isAdmin()) { // sub query required becauser ->isAdmin can't be called on null
            $userIsAdmin = true;
        }
    }

    if (\Factory::getConfigService()->IsPortalReadOnly() and ! $userIsAdmin) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks the user has permission to perform/view admin functionality
 *
 * @return null
 *
 */
function checkUserIsAdmin()
{
    require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';
    $dn = Get_User_Principle();
    /**
     * @var \User $user
     */
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if ($user == null) {
        throw new Exception("Unregistered users may not carry out this operation");
    }
    if (! $user->isAdmin()) {
        throw new Exception("Only GOCDB administrators can perform this action.");
    }
}
function CheckCurrentUserCanEditProject(\Project $project)
{
    require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //$enablingRoles = \Factory::getProjectService()->authorize Action('ACTION_EDIT_OBJECT', $project, $user);
    //if (count($enablingRoles) == 0){
    if (
        \Factory::getRoleActionAuthorisationService()->authoriseAction(
            \Action::EDIT_OBJECT,
            $project,
            $user
        )->getGrantAction() == false
    ) {
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
function getSiteDataFromWeb()
{
    // Fields that are used to link other objects to the site
    $fields = array (
            'Country',
            'ProductionStatus'
    );

    $siteData = [];
    foreach ($fields as $field) {
        $siteData[$field] = $_REQUEST [$field];
    }

    if (isset($_REQUEST['childServiceScopeAction'])) {
        $siteData['childServiceScopeAction'] = $_REQUEST['childServiceScopeAction'];
    } else {
        $siteData['childServiceScopeAction'] = 'noModify';
    }

    // get non-reserved scopes if any are selected, if not set as empty array
    if (isset($_REQUEST ['Scope_ids'])) {
        $siteData ['Scope_ids'] = $_REQUEST ['Scope_ids'];
    } else {
        $siteData ['Scope_ids'] = array ();
    }
    // get reserved scopes if any are selected, if not set as empty array
    if (isset($_REQUEST ['ReservedScope_ids'])) {
        $siteData ['ReservedScope_ids'] = $_REQUEST ['ReservedScope_ids'];
    } else {
        $siteData ['ReservedScope_ids'] = array ();
    }

    /*
     * Certification status is only set during the add_site procedure.
     * Editing an existing site's cert status uses a separate form
     */
    if (isset($_REQUEST ['Certification_Status'])) {
        $siteData ['Certification_Status'] = $_REQUEST ['Certification_Status'];
    }

    /*
     * ROC is only set during the add_site procedure. A site's ROC can't be edited in the web portal
     */
    if (isset($_REQUEST ['NGI'])) {
        $siteData ['NGI'] = $_REQUEST ['NGI'];
    }

    // Fields specific to the site object and not linked to other entities
    $siteObjectFields = array (
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

    foreach ($siteObjectFields as $field) {
        $siteData ['Site'] [$field] = trim($_REQUEST [$field]);
    }

    //Notifcations
    $siteData ['NOTIFY'] = $_REQUEST ['NOTIFY'];

    /*
     * If the user is updating a site the optional cobjectid parameter will be set.
     * If it is set we return it as part of the array
     */
    if (! empty($_REQUEST ['ID'])) {
        $siteData ['ID'] = $_REQUEST ['ID'];
    }

    //

    return $siteData;
}

/**
 * Gets the relevant fields from a user's web request
 * ($_REQUEST)
 *
 * @global array $_REQUEST site data submitted by the end user
 * @return array An array of service group data
 */
function getSGroupDataFromWeb()
{
    /*
     * $_REQUEST['monitored'] is set by the "Should this Virtual Site be monitored?" tick box
     */
    if (isset($_REQUEST ['monitored'])) {
        $monitored = 'Y';
    } else {
        $monitored = 'N';
    }

    $sGroup = [];
    $sGroup['MONITORED'] = $monitored;

    if (isset($_REQUEST ['objectId'])) {
        $sGroup['ID'] = $_REQUEST ['objectId'];
    }

    $sGroup['SERVICEGROUP'] ['NAME'] = trim($_REQUEST ['name']);
    $sGroup['SERVICEGROUP'] ['DESCRIPTION'] = trim($_REQUEST ['description']);
    $sGroup['SERVICEGROUP'] ['EMAIL'] = trim($_REQUEST ['email']);

    // get scopes if any are selected, if not set as null
    if (isset($_REQUEST ['Scope_ids'])) {
        $sGroup['Scope_ids'] = $_REQUEST ['Scope_ids'];
    } else {
        $sGroup['Scope_ids'] = array ();
    }
    if (isset($_REQUEST ['ReservedScope_ids'])) {
        $sGroup['ReservedScope_ids'] = $_REQUEST ['ReservedScope_ids'];
    } else {
        $sGroup['ReservedScope_ids'] = array ();
    }

    return $sGroup;
}

/**
 * Gets the relevant fields from a user's web request
 * ($_REQUEST) and returns an associative array compatible with
 * add_new_service() or editService.
 *
 * @global array $_REQUEST SE data submitted by the end user
 * @return array an array representation of a service
 */
function getSeDataFromWeb()
{

    $fields = array (
            'serviceType',
            'IS_MONITORED',
            'NOTIFY',
            'PRODUCTION_LEVEL'
    );
    $seData = [];
    foreach ($fields as $field) {
        $seData [$field] = $_REQUEST [$field];
    }

    /*
     * If the user is adding a new service the optional HOSTING_SITE parameter will be set.
     * If it is set we return it as part of the array
     */
    if (! empty($_REQUEST ['hostingSite'])) {
        $seData ['hostingSite'] = $_REQUEST ['hostingSite'];
    }

    // $seData['SE']['ENDPOINT'] = $_REQUEST['HOSTNAME'] . $_REQUEST['serviceType'];
    $seData ['SE'] ['HOSTNAME'] = trim($_REQUEST ['HOSTNAME']);
    $seData ['SE'] ['HOST_IP'] = trim($_REQUEST ['HOST_IP']);
    $seData ['SE'] ['HOST_IP_V6'] = trim($_REQUEST['HOST_IP_V6']);
    $seData ['SE'] ['HOST_DN'] = trim($_REQUEST ['HOST_DN']);
    $seData ['SE'] ['DESCRIPTION'] = trim($_REQUEST ['DESCRIPTION']);
    $seData ['SE'] ['HOST_OS'] = trim($_REQUEST ['HOST_OS']);
    $seData ['SE'] ['HOST_ARCH'] = trim($_REQUEST ['HOST_ARCH']);
    $seData ['SE'] ['EMAIL'] = trim($_REQUEST ['EMAIL']);
    $seData ['SE'] ['URL'] = trim($_REQUEST ['endpointUrl']);
    $seData ['BETA'] = $_REQUEST ['HOST_BETA'];

    /*
    * If the user is updating a service the optional cobjectid parameter will be set.
    * If it is set we return it as part of the array
    */
    if (! empty($_REQUEST ['ID'])) {
        $seData ['ID'] = $_REQUEST ['ID'];
    }

    // get scopes if any are selected, if not set as null
    if (isset($_REQUEST ['Scope_ids'])) {
        $seData ['Scope_ids'] = $_REQUEST ['Scope_ids'];
    } else {
        $seData ['Scope_ids'] = array ();
    }

    if (isset($_REQUEST ['ReservedScope_ids'])) {
        $seData ['ReservedScope_ids'] = $_REQUEST ['ReservedScope_ids'];
    } else {
        $seData ['ReservedScope_ids'] = array ();
    }

    return $seData;
}

/**
 *
 * @return array
 */
function getProjectDataFromWeb()
{
    $projectValues = [];
    // new projects won't have an id
    if (isset($_REQUEST ['ID'])) {
        $projectValues ['ID'] = $_REQUEST ['ID'];
    }

    // Get the rest of the project post data into an array
    $fields = array (
            'Name',
            'Description'
    );

    foreach ($fields as $field) {
        $projectValues [$field] = trim($_REQUEST [$field]);
    }
    return $projectValues;
}
function getNGIDataFromWeb()
{
    // Get the NGI post data into an array
    $fields = array (
            'EMAIL',
            'HELPDESK_EMAIL',
            'ROD_EMAIL',
            'SECURITY_EMAIL',
            'GGUS_SU'
    );
    $ngiValues = [];
    foreach ($fields as $field) {
        $ngiValues [$field] = trim($_REQUEST [$field]);
    }

    if (isset($_REQUEST ['NAME'])) {
        $ngiValues ['NAME'] = $_REQUEST ['NAME'];
    }

//    $scopes = array ();
//    if (isset($_REQUEST ['SCOPE_IDS'])){
//        $scopes = $_REQUEST ['SCOPE_IDS'];
//    }

    // get scopes if any are selected, if not set as null
    $optionalScopes = array();
    if (isset($_REQUEST ['Scope_ids'])) {
        $optionalScopes['Scope_ids'] = $_REQUEST ['Scope_ids'];
    } else {
        $optionalScopes['Scope_ids'] = array ();
    }
    $reservedScopes = array();
    if (isset($_REQUEST ['ReservedScope_ids'])) {
        $reservedScopes['ReservedScope_ids'] = $_REQUEST ['ReservedScope_ids'];
    } else {
        $reservedScopes['ReservedScope_ids'] = array ();
    }

    $id = null;
    if (isset($_REQUEST ['ID'])) {
        $id = $_REQUEST ['ID'];
    }

    $values = array (
            'NGI' => $ngiValues,
            //'SCOPES' => $scopes,
            'Scope_ids' => $optionalScopes['Scope_ids'],
            'ReservedScope_ids' => $reservedScopes['ReservedScope_ids'],
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
function getDowntimeFormData()
{
    $downTime = [];
    $downTime['DOWNTIME'] ['SEVERITY'] = $_REQUEST ['SEVERITY'];
    $downTime['DOWNTIME'] ['DESCRIPTION'] = trim($_REQUEST ['DESCRIPTION']);
    $downTime['DOWNTIME'] ['START_TIMESTAMP'] = $_REQUEST ['START_TIMESTAMP'];
    $downTime['DOWNTIME'] ['END_TIMESTAMP'] = $_REQUEST ['END_TIMESTAMP'];

    $downTime['DOWNTIME'] ['DEFINE_TZ_BY_UTC_OR_SITE'] = 'utc'; //default
    if (isset($_REQUEST ['DEFINE_TZ_BY_UTC_OR_SITE'])) {
        // 'utc' or 'site'
        $downTime['DOWNTIME'] ['DEFINE_TZ_BY_UTC_OR_SITE'] =
            $_REQUEST ['DEFINE_TZ_BY_UTC_OR_SITE'];
    }

    if (! isset($_REQUEST ['IMPACTED_IDS'])) {
        throw new Exception(
            'Error - No endpoints or services selected,
            downtime must affect at least one endpoint'
        );
    }
    $downTime['IMPACTED_IDS'] = $_REQUEST ['IMPACTED_IDS'];


    // Get the existing downtime ID, if we are doing an edit.
    if (isset($_REQUEST['DOWNTIME_ID'])) {
        $downTime['DOWNTIME']['EXISTINGID'] = $_REQUEST['DOWNTIME_ID'];
    }

    return $downTime;
}

/**
 * Gets the site properties data passed by user *
 */
function getSpDataFromWeb()
{
    $siteProp = [];
    $siteProp['SITEPROPERTIES'] ['SITE'] = $_REQUEST ['SITE'];
    $siteProp['SITEPROPERTIES'] ['NAME'] = $_REQUEST ['KEYPAIRNAME'];
    $siteProp['SITEPROPERTIES'] ['VALUE'] = $_REQUEST ['KEYPAIRVALUE'];
    if (isset($_REQUEST ['PROP'])) {
        $siteProp['SITEPROPERTIES'] ['PROP'] = $_REQUEST ['PROP'];
    }

    if (isset($siteProp['SITEPROPERTIES']['NAME'])) {
        $siteProp['SITEPROPERTIES']['NAME'] = $siteProp['SITEPROPERTIES']['NAME'];
    }
    if (isset($siteProp['SITEPROPERTIES']['VALUE'])) {
        $siteProp['SITEPROPERTIES']['VALUE'] = $siteProp['SITEPROPERTIES']['VALUE'];
    }
    return $siteProp;
}

/**
 * Gets the service properties data passed by user *
 */
function getSerPropDataFromWeb()
{
    $serviceProp = [];
    $serviceProp['SERVICEPROPERTIES'] ['SERVICE'] = $_REQUEST ['SERVICE'];
    $serviceProp['SERVICEPROPERTIES'] ['NAME'] = $_REQUEST ['KEYPAIRNAME'];
    $serviceProp['SERVICEPROPERTIES'] ['VALUE'] = $_REQUEST ['KEYPAIRVALUE'];
    if (isset($_REQUEST ['PROP'])) {
        $serviceProp['SERVICEPROPERTIES'] ['PROP'] = trim($_REQUEST ['PROP']);
    }
    if (isset($serviceProp['SERVICEPROPERTIES']['NAME'])) {
        $serviceProp['SERVICEPROPERTIES']['NAME'] = $serviceProp['SERVICEPROPERTIES']['NAME'];
    }
    if (isset($serviceProp['SERVICEPROPERTIES']['VALUE'])) {
         $serviceProp['SERVICEPROPERTIES']['VALUE'] = $serviceProp['SERVICEPROPERTIES']['VALUE'];
    }
    return $serviceProp;
}

/**
 * Gets the endpoint properties data passed by user
 */
function getEndpointPropDataFromWeb()
{
    $endpointProp = array();
    if (isset($_REQUEST ['PROP'])) {
        $endpointProp['ENDPOINTPROPERTIES'] ['PROP'] = trim($_REQUEST ['PROP']);
    }
    if (isset($_REQUEST ['ENDPOINTID'])) {
        $endpointProp['ENDPOINTPROPERTIES']['ENDPOINTID'] = trim($_REQUEST ['ENDPOINTID']);
    }
    if (isset($_REQUEST ['KEYPAIRNAME'])) {
        $endpointProp['ENDPOINTPROPERTIES']['NAME'] = $_REQUEST ['KEYPAIRNAME'];
    }
    if (isset($_REQUEST ['KEYPAIRVALUE'])) {
         $endpointProp['ENDPOINTPROPERTIES']['VALUE'] = $_REQUEST ['KEYPAIRVALUE'];
    }
    return $endpointProp;
}

/**
 * Gets the service group properties data passed by user *
 */
function getSerGroupPropDataFromWeb()
{
    $serGroupProp = [];
    $serGroupProp['SERVICEGROUPPROPERTIES'] ['SERVICEGROUP'] = $_REQUEST ['SERVICEGROUP'];
    $serGroupProp['SERVICEGROUPPROPERTIES'] ['NAME'] = $_REQUEST ['KEYPAIRNAME'];
    $serGroupProp['SERVICEGROUPPROPERTIES'] ['VALUE'] = $_REQUEST ['KEYPAIRVALUE'];
    if (isset($_REQUEST ['PROP'])) {
        $serGroupProp['SERVICEGROUPPROPERTIES'] ['PROP'] = $_REQUEST ['PROP'];
    }
    return $serGroupProp;
}

/**
 * Gets the service endpoint data passed by user *
 */
function getEndpointDataFromWeb()
{
    $endpoint = [];
    $endpoint['SERVICEENDPOINT'] ['SERVICE'] = $_REQUEST ['SERVICE'];
    $endpoint['SERVICEENDPOINT'] ['NAME'] = trim($_REQUEST ['ENDPOINTNAME']);
    $endpoint['SERVICEENDPOINT'] ['URL'] = trim($_REQUEST ['ENDPOINTURL']);
    $endpoint['SERVICEENDPOINT'] ['INTERFACENAME'] = trim($_REQUEST ['ENDPOINTINTERFACENAME']);
    if (isset($_REQUEST ['DESCRIPTION'])) {
        $endpoint['SERVICEENDPOINT'] ['DESCRIPTION'] = trim($_REQUEST ['DESCRIPTION']);
    }
    if (isset($_REQUEST ['ENDPOINTID'])) {
        $endpoint['SERVICEENDPOINT'] ['ENDPOINTID'] = trim($_REQUEST ['ENDPOINTID']);
    }
    $endpoint['SERVICEENDPOINT']['EMAIL'] = trim($_REQUEST ['EMAIL']);
    //The value comes from a checkbox, which wiill  not return a value when unchecked
    if (isset($_REQUEST['IS_MONITORED'])) {
        $endpoint['IS_MONITORED'] = $_REQUEST ['IS_MONITORED'];
    } else {
        $endpoint['IS_MONITORED'] = false;
    }

    return $endpoint;
}

/**
 * Date format used by the calendar Javascript in downtime controllers *
 */
function getDateFormat()
{
    return "d/m/Y H:i";
}

/**
 * Gets the submitted post data for the addition or editing of a scope
 *
 * @global array $_REQUEST array containg the post data
 * @return array
 */
function getScopeDataFromWeb()
{
    $scopeData = [];
    $scopeData['Name'] = trim($_REQUEST ['Name']);
    $scopeData['Description'] = trim($_REQUEST ['Description']);
    // 'Reserved' value is a checkbox ==>> absent if not checked
    if (array_key_exists('Reserved', $_REQUEST)) {
        $scopeData['Reserved'] = ($_REQUEST ['Reserved'] == '1');
    }
    if (array_key_exists('Id', $_REQUEST)) {
        $scopeData['Id'] = $_REQUEST ['Id'];
    }

    return $scopeData;
}

/**
 * Gets the submitted post data for the addition or editing of a service type
 *
 * @global array $_REQUEST array containg the post data
 * @return array $serviceTypeData an array containg the new site data
 */
function getSTDataFromWeb()
{
    $serviceTypeData = [];
    $serviceTypeData['Name'] = trim($_REQUEST ['Name']);
    $serviceTypeData['Description'] = trim($_REQUEST ['Description']);
    if (isset($_REQUEST['AllowMonitoringException'])) {
        $serviceTypeData['AllowMonitoringException'] = ($_REQUEST ['AllowMonitoringException'] == "checked");
    } else {
        $serviceTypeData['AllowMonitoringException'] = false;
    }
    if (array_key_exists('ID', $_REQUEST)) {
        $serviceTypeData['ID'] = $_REQUEST ['ID'];
    }

    return $serviceTypeData;
}

/**
 * Gets the submitted post data for the addition or editing of API Authentication Entities
 *
 * @global array $_REQUEST array containg the post data
 * @return array
 */
function getAPIAuthenticationFromWeb()
{
    $authEntityData = [];
    $authEntityData['TYPE'] = $_REQUEST['TYPE'];
    $authEntityData['IDENTIFIER'] = trim($_REQUEST['IDENTIFIER']);
    $authEntityData['ALLOW_WRITE'] = key_exists('ALLOW_WRITE', $_REQUEST) ?
                                        trim($_REQUEST['ALLOW_WRITE']) == 'checked' :
                                        false;

    return $authEntityData;
}
/**
 * Return information message text
 *
 * @return string short message, a dash, supplementary text
 * e.g. "PROTECTED - Registration required"
 */
function getInfoMessage($code = null)
{

    if ($code == null) {
        $code = 'privacy-1';
    }

    $messages = array();

    switch (\Factory::getConfigService()->isRestrictPDByRole()) {
        case true:
            $messages['privacy-1'] = "PROTECTED - Role required";
            break;
        case false:
            $messages['privacy-1'] = "PROTECTED - Registration required";
            break;
    }

    if (!array_key_exists($code, $messages)) {
        throw new LogicException("Information message code $code has not been defined. " .
                                 "Please contact GOCDB administrators.");
    }

    return $messages[$code];
}
/**
 * Helper function to set view parameters for deciding to show personal data
 *
 * @return array parameter array
 */
function getReadPDParams($user)
{
    require_once __DIR__ . '/../../../lib/Doctrine/entities/User.php';

    $userIsAdmin = false;
    $authenticated = false;

    /*  */
    if (!is_null($user)) {
        // User will only see personal data if they have a role somewhere
        // ToDo: should this be restricted to role at a site?

        if (!$user instanceof \User) {
            throw new LogicException("Personal data read authorisation expected User object as input. Received " .
                                     get_class($user) . "'.");
        }

        if ($user->isAdmin()) {
            $userIsAdmin = true;
            $authenticated = true;
        } elseif (\Factory::getUserService()->isAllowReadPD($user)) {
            $authenticated = true;
        }
    }
    return array($userIsAdmin, $authenticated);
}
