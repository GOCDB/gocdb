<?php

namespace org\gocdb\services;

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
//require_once __DIR__ . '/AbstractEntityService.php';

/**
 * Service for querying the Role-Action mapping definitions defined in a
 * RoleActionMappings.xml file.
 * <p>
 * The role action mappings xml file and its XSD can be configured for non-default
 * locations. The XSD defines the TNS specified by <code>ROLE_ACTION_MAPPING_NS</code>.
 * <p>
 * The mappings file is used to define which {@see \RoleType}s enable which actions on
 * a target object(s) on a per-project basis. The role type names specified in
 * the XML mappings file (mapped as <code>//RoleActionMapping/RoleNames/Role</code>)
 * need to already exist in the DB as {@see \RoleType} entities.
 *
 * @author David Meredith
 */
class RoleActionMappingService /* extends AbstractEntityService */ {

    private $roleActionMappingsXmlPath; // path to xml data file
    private $roleActionMappingsXsdPath; // path to xsd schema file
    private $roleActionMapXmlDom;       // DOM object loaded from xml data path

    const ROLE_ACTION_MAPPING_NS = 'http://goc.egi.eu/2015/03/spec1.0_r1';

    const FAILED_TO_READ_XML_DATA = 1;   // Constants to distinguish exception cases
    const FAILED_TO_READ_XML_SCHEMA = 2;

    /**
     * Service for querying the Role-Action mappings defined in the RoleActionMappingRules XML file.
     */
    function __construct() {
        //parent::__construct();
        $this->roleActionMappingsXmlPath = __DIR__ . '/../../config/RoleActionMappings.xml';
        $this->roleActionMappingsXsdPath = __DIR__ . '/../../config/RoleActionMappingsSchema.xsd';
        $this->roleActionMapXmlDom = null;
    }

    /**
     * Set the path to the XML role action mapping file, the default is:
     * <code>GOCDB_HOME/config/RoleActionMappingsSchema.xsd</code>
     * @param string $xmlDocPath Path to role action mapping file.
     */
    public function setRoleActionMappingsXmlPath($xmlDocPath) {
        $this->roleActionMappingsXmlPath = $xmlDocPath;
        $this->roleActionMapXmlDom = null;
    }

    /**
     * Set the path to the XSD schema for the role action mapping file, the default is:
     * <code>GOCDB_HOME/config/RoleActionMappingsSchema.xsd</code>
     * @param string $xsdDocPath Path to XSD schema
     */
    public function setRoleActionMappingsXsdPath($xsdDocPath) {
        $this->roleActionMappingsXsdPath = $xsdDocPath;
        $this->roleActionMapXmlDom = null;
    }

    /**
     * Validate the XML file located at $roleActionMappingsXmlPath with the XSD
     * located at $roleActionMappingsXsdPath.
     * Only used for debug and testing schema and validation logic, use
     * loadRoleActionsXmlFile for most purposes
     *
     * @return array Array of LibXMLError objects, or empty
     * @throws \LogicException if XML file can't be loaded
     */
    public function validateRoleActionMappingFileAgainstXsd() {

        try {
            $this->loadRoleActionsXmlFile();
        } catch (\LogicException $e) {
            // Trap the schema validation error and return the error stack.
            if ($e->getCode() == $this->FAILED_TO_READ_XML_SCHEMA) {
                return libxml_get_errors();
            }
            throw $e;
        }
        return array();
    }
    /**
     * Helper function for xml schema validation
     * @param \DOMDocument $dom DOM object to validate
     * @param string $xmlDocPath Path to data used to generate the DOM (for error message only).
     * @param string $xsdDocPath Path to schema file to be used for data validation
     * @throws \LogicException with code FAILED_TO_READ_XML_SCHEMA on failure to validate
     */
    private function _validateRoleActionMappingFileAgainstXsd(\DOMDocument $dom, $xmlDocPath, $xsdDocPath) {

        if (!$dom->schemaValidate($xsdDocPath)) {
            throw new \LogicException("Failed validate RoleActionMappings against schema. xml:[{$xmlDocPath}] xsd:[{$xsdDocPath}]",
                        $this->FAILED_TO_READ_XML_SCHEMA);
        }
    }
    /**
     * Helper function for xml schema validation
     * @param \DOMDocument $dom DOM object to validate
     * @param string $xmlDocPath Path to data used to generate the DOM (for error message only).
     * @param string $xsdDocPath Path to schema file to be used for data validation
     * @throws \LogicException Exception with code FAILED_TO_READ_XML_DATA on failure to validate
     * @return \DOMDocument
     */
    private function _loadRoleActionsXmlFile($xmlDocPath) {

        $dom = new \DOMDocument();

        if (!$dom->load($xmlDocPath)) {
            throw new \LogicException("Failed to load RoleActionMappings. xml:[{$xmlDocPath}]",
                            $this->FAILED_TO_READ_XML_DATA);
        }
        return $dom;
    }

    /**
     * Load and validate the role actions xml file referenced from $roleActionMappingsXmlPath;
     * @return \DOMDocument
     * @throws \LogicException
     */
    private function loadRoleActionsXmlFile()
    {
        if (is_null($this->roleActionMapXmlDom)) {
            // Throws LogicException if the file can't be loaded
            // Exception code can distinguish between them if wanted.
            $this->roleActionMapXmlDom = $this->_loadRoleActionsXmlFile($this->roleActionMappingsXmlPath);

            // Throws LogicException is the schema doesn't validate
            $this->_validateRoleActionMappingFileAgainstXsd($this->roleActionMapXmlDom,
                                                            $this->roleActionMappingsXmlPath,
                                                            $this->roleActionMappingsXsdPath);
        }

        return $this->roleActionMapXmlDom;
    }


    /**
     * Query the role action mapping rules for the list of actions that are
     * enabled over different target object types by possession of a Role with
     * the specified type.
     * <p>
     * Each element in the returned 2D array is a child array that holds two
     * strings of the form: <code>array('ENABLED_ACTION', 'TargetObjectType')</code>.
     * For example:
     * <code>
     *   $resultArray[0] = array('ACTION_EDIT_OBJECT', 'Site');
     *   $resultArray[1] = array('ACTION_GRANT_ROLE, 'Site');
     *   $resultArray[2] = array('ACTION_GRANT_ROLE, 'Ngi');
     * </code>
     *
     * @param string $targetRoleTypeName The name of a role type, e.g. 'Site Administrator'
     * @return array 2D array, each element holds a 2 element string array
     */
    public function getEnabledActionsForRoleType($targetRoleTypeName){
        // load file
        $roleActionMapXmlDom = $this->loadRoleActionsXmlFile();

        // create xpath and register namespace
        $xpath = new \DOMXPath($roleActionMapXmlDom);
        $xpath->registerNamespace("goc", RoleActionMappingService::ROLE_ACTION_MAPPING_NS);

        // get the <RoleActionMapping> element
        $roleActionMapping = $this->getRoleActionMapping($xpath);

        // Iterate all the <Role> elements and get the value 'id' attribute value
        // of the <Role> element corresponds to the given targetRoleTypeName
        //
         /* @var $roleNamesList DOMNodeList */
        $roleNamesList = $xpath->query("goc:RoleNames", $roleActionMapping);
        $roleId = NULL;
        foreach ($roleNamesList as $roleNames) {
            //print_r($roleNames->nodeValue);
            //$over = $roleNames->getAttribute('over');
            $roles = $xpath->query("goc:Role", $roleNames);
            foreach ($roles as $role) {
                //print_r($role->nodeValue);
                if($role->nodeValue == $targetRoleTypeName){
                    // get the Role id and break
                    $roleId = $role->getAttribute('id');
                    break;
                }
            }
        }

        // if the roleId wasn't found, then return an emtpy array - this means the
        // targetRoleTypeName isn't configured/defined in the role action mappings file,
        // which is perfectly normal e.g. to remove privilages for legacy role types.
        if($roleId == NULL){
            return array();
        }
        //print_r("roleId: ".$roleId."<br>");

        // xpath query all the <RoleMapping> elements that reference the $roleId
        /* @var $roleMappings DOMNodeList */
        $roleMappings = $xpath->query(
                "goc:RoleMapping[goc:Roles/goc:RoleRef[@idRef='$roleId']]",
                $roleActionMapping);

        // collect results
        $enabledActionsOnTargets = array();

        foreach($roleMappings as $roleMapping){
            /* @var $enabledActions DOMNodeList */
            $enabledActions = $xpath->query("goc:EnabledActions", $roleMapping);
            foreach($enabledActions as $enabledAction){
                /* @var $actions DOMNodeList */
                $actions = $xpath->query("goc:Actions/goc:Action", $enabledAction);
                /* @var $targets DOMNodeList */
                $targets = $xpath->query("goc:Target", $enabledAction);
                foreach($actions as $action){
                    foreach($targets as $target){
                       $enabledActionsOnTargets[] = array($action->nodeValue, $target->nodeValue);
                    }
                }
            }
        }
        return $enabledActionsOnTargets;
    }

    /**
     * Return all of the Role type names defined for the named project.
     * <p>
     * Note, the projectName is ignored and is nullable - the same role type names
     * are returned for all projects (in future, different role types may need
     * to be declared for differnt projects, but this is not yet implemented).
     * <p>
     * Returns an associative array where keys map to unique role type names
     * {@see \RoleType::getName()} and values define the name of the
     * {@see \OwnedEntity::getType()} type that the role is over. For example:
     * <code>
     *   $resultArray['Site Adminstrator'] = 'Site';
     *   $resultArray['Site Security Officer'] = 'Site';
     *   $resultArray['NGI Operations Manager'] = 'Ngi';
     *   $resultArray['Service Group Administrator'] = 'ServiceGroup';
     *   ...
     * </code>
     * Note, array values are NOT the target object type over which actions are enabled.
     * <p>
     * Keys are looked up from: <code>//RoleActionMapping/RoleNames/Role</code>.
     * <p>
     * Values are looked up from: <code>//RoleActionMapping/RoleNames[@over]</code>.
     *
     * @param string $projectName Currently ignored and is nullable
     * @return array Associative array of unique Role type names (keys) and the
     *   owned object type the role is over (value)
     * @throws \LogicException if XSD/XML files can't be loaded,
     *   or if the role action mappings can't be resolved.
     * @throws \Exception If the role action mapping file is invalid against its XSD.
     */
    public function getRoleTypeNamesForProject($projectName) {
        //$projectName = 'WLCG'; //'EGI';
        if ($projectName !== NULL && (!is_string($projectName) || strlen(trim($projectName)) == 0)) {
            throw new \LogicException('Invalid projectName');
        }

        // Load file
        //http://stackoverflow.com/questions/12368453/domdocumentschemavalidate-throwing-warning-errors
        //libxml_use_internal_errors(true);
         $roleActionMapXmlDom = $this->loadRoleActionsXmlFile();

        $xpath = new \DOMXPath($roleActionMapXmlDom);
        $xpath->registerNamespace("goc", RoleActionMappingService::ROLE_ACTION_MAPPING_NS);

        // In future, may need to fetch different role action mappings on a per
        // project basis - needs finishing if required.
        //$roleActionMapping = $this->getRoleActionMappingForProject($xpath, $projectName);
        $roleActionMapping = $this->getRoleActionMapping($xpath);
        //print_r($roleActionMapping);

        $roleNamesOver = array();
         /* @var $roleNamesList DOMNodeList */
        $roleNamesList = $xpath->query("goc:RoleNames", $roleActionMapping);
        foreach ($roleNamesList as $roleNames) {
            //print_r($roleNames->nodeValue);
            $over = $roleNames->getAttribute('over');
            $roles = $xpath->query("goc:Role", $roleNames);
            foreach ($roles as $role) {
                //print_r("[".$role->getAttribute('id'). "] [".$role->nodeValue."] [".$over."]\n");
               if (!array_key_exists($role->nodeValue, $roleNamesOver)) {
                    $roleNamesOver[$role->nodeValue] = $over; // associative array
                } else {
                    throw new \LogicException("Duplicate Role value detected.");
                }
            }
        }
        return $roleNamesOver;
    }

    /**
     * Return the Role type names that enable the specified action on the target
     * object type.
     * <p>
     * Note, the projectName param is ignored and is nullable - the same results
     * are returned for all projects (in future, different role types may need
     * to be declared for differnt projects, but this is not yet implemented).
     * <p>
     * Returns an associative array where keys map to unique role type names
     * {@see \RoleType::getName()} and values define the name of the
     * {@see \OwnedEntity::getType()} type that the role is over. For example:
     * <code>
     *   $resultArray['Site Administrator'] => 'Site';
     *   $resultArray['NGI Operations Manager']  => 'Ngi';
     *   $resultArray['Chief Operations Officer'] => 'Project';
     *   ...
     * </code>
     *
     * @param string $action The type of action, case-insensitive, matches to element value:
     *   <code>//RoleActionMapping/RoleMapping/EnabledActions/Actions</code>
     * @param string $objectType The type of entity, case-insensitive, matches to element value:
     *   <code>//RoleMapping/EnabledActions/Target</code>
     * @param string $projectName Currently ignored and is nullable.
     * @return array Associative array where Role type names are keys and the
     * owned object type the role applies over is the value. Can be empty if no roles map to the requested action.
     * @throws \LogicException If role action mappings can't be resolved, or if the XML/XSD files are logically invalid.
     */
    public function getRoleTypeNamesThatEnableActionOnTargetObjectType($action, $objectType, $projectName) {
        // fail early
        if (!is_string($action) || strlen(trim($action)) == 0) {
            throw new \LogicException('Invalid action');
        }
        if (!is_string($objectType) || strlen(trim($objectType)) == 0) {
            throw new \LogicException('Invalid entityType');
        }

//         IGNORE THIS COMMENT BLOCK - DECLARING DIFFERENT ROLE ACTION MAPPINGS PER
//       PROJECT MAY BE NEEDED IN FUTURE, BUT RIGHT NOW IT ADDS UNNECESSARY COMPLEXITY.
//       IF THE NEED ARISES, THIS STUFF WILL BE USEFUL:
//       =======================================================================
//       if ($projectName !== NULL && (!is_string($projectName) || strlen(trim($projectName)) == 0)) {
//            throw new \LogicException('Invalid projectName');
//       }
//     * The returned Role type names (keys) are looked up from:
//     * </code>RoleActionMapping/RoleNames/Role</code> for the specified project.
//     * The returned object-type names that the roles are over (values) are looked up from:
//     * <code>RoleActionMapping/RoleNames[@over]</code> for the specified project.
//     *
//     * @param string $projectName The projectName, case-insensitive, nullable,
//     * if specified it matches to element value:
//     *   <code>//RoleActionMapping/TargetProject</code>.
//     * If null, the default RoleActionMapping is queried.
//       =======================================================================

        // Upper case the parameters so we can perform case-insensitive matches
        // when reading the XML file
        $entityTypeU = strtoupper($objectType);
        $actionU = strtoupper($action);

        // Lets not use simplexml, DOMXPath is more powerful
        //$xml = simplexml_load_file($this->roleActionMappingsXmlPath);
        //$xml->registerXPathNamespace('goc', RoleActionMappingService::ROLE_ACTION_MAPPING_NS);
        //$roleActionMapping = $xml->xpath("//goc:RoleActionMapping");

        // load dom
        $roleActionMapXmlDom  = $roleActionMapXmlDom = $this->loadRoleActionsXmlFile();

        // create xpath
        $xpath = new \DOMXPath($roleActionMapXmlDom);
        $xpath->registerNamespace("goc", RoleActionMappingService::ROLE_ACTION_MAPPING_NS);

        // In future, may need to fetch different role action mappings on a per
        // project basis - needs finishing if required.
        //$roleActionMapping = $this->getRoleActionMappingForProject($xpath, $projectName);
        $roleActionMapping = $this->getRoleActionMapping($xpath);

        /* @var $roleMappings DOMNodeList */
        $roleMappings = $xpath->query(
                "goc:RoleMapping[goc:EnabledActions/goc:Target['$entityTypeU'="
                . "translate(text(), 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]]", $roleActionMapping);

        //foreach ($roleMappings as $roleMapping) { print_r("[" . $roleMapping->nodeValue . "]\n"); }
        // Iterate RoleMapping elements and identify/collect those that have a child
        // 'EnabledActions' element that groups together BOTH the required  Target $entityType
        // AND the required $action listed in <Actions> e.g.
        // if $action = ACTION_EDIT_OBJECT and $entityType = ServiceGroup a
        // conforming element would be:
        //
        //  <EnabledActions>
        //      <Actions>
        //            <Action>ACTION_EDIT_OBJECT</Action>
        //            <Action>ACTION_GRANT_ROLE</Action>
        //            <Action>ACTION_REJECT_ROLE</Action>
        //            <Action>ACTION_REVOKE_ROLE</Action>
        //      </Actions>
        //      <Target>ServiceGroup</Target>
        //  </EnabledActions>
        //
        $inScopeRoleMappings = array();
        foreach ($roleMappings as $roleMapping) {
            //print_r("[" . $roleMapping->nodeValue . "]\n");
            // Filter the child <EnabledActions> to those that have the required <Target> entityType
            $enabledActionsNodes = $xpath->query(
                    "goc:EnabledActions[goc:Target['$entityTypeU'=" // .'text()',
                    . "translate(text(), 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]]", $roleMapping);

            // Filter the <EnabledActions> to those that have the required <Action>
            foreach ($enabledActionsNodes as $enabledAction) {
                $actionsNodes = $xpath->query("goc:Actions", $enabledAction);
                foreach ($actionsNodes as $actionsNode){
                    //print_r($actionsNode);
                    $actionsUpper = array();
                    $actionNodes = $xpath->query('goc:Action', $actionsNode);
                    foreach($actionNodes as $actionNode){
                        //print_r($actionNode);
                        $actionVal = trim($actionNode->nodeValue);
                        //print_r($actionVal);
                        $actionsUpper[] = strtoupper($actionVal);
                    }
                    //print_r($actionsUpper);
                    // Collect this RoleMapping, it has required action and target
                    if(in_array($actionU, $actionsUpper)){
                        $inScopeRoleMappings[] = $roleMapping;
                        //print_r($explodedActions);
                    }
                }
//                // when actions were listed as a single text node value using comma sep list e.g.
//                //  <Actions>ACTION_EDIT_OBJECT,ACTION_GRANT_ROLE, ACTION_REJECT_ROLE, ACTION_REVOKE_ROLE</Actions>
//                $actionsNodes = $xpath->query('goc:Actions[text()]', $enabledAction);
//                foreach ($actionsTextNodes as $actionsText) {
//                    //print_r($actionsText->nodeValue. "\n");
//                    $explodedActionsMixedCase = explode(",", $actionsText->nodeValue);
//                    $explodedActionsNotTrimmed = array_map('strtoupper', $explodedActionsMixedCase);
//                    $explodedActions = array_map('trim', $explodedActionsNotTrimmed);
//                    //print_r($explodedActions);
//                    // Collect this RoleMapping, it has required action and target
//                    if (in_array($actionU, $explodedActions)) {
//                        $inScopeRoleMappings[] = $roleMapping;
//                        //print_r($explodedActions);
//                    }
//                }
            }
        }

        // collect the idRef val from RoleRef elements (these role Ids enable
        // the specifed actions on the target object.
        $enablingRoleIds = array();
        foreach ($inScopeRoleMappings as $inScopeRoleMapping) {
           $roleRef_Elements = $xpath->query("goc:Roles/goc:RoleRef", $inScopeRoleMapping);
           foreach($roleRef_Elements as $roleRef){
               $idRef_Att = $roleRef->getAttribute('idRef');
               $enablingRoleIds[] = $idRef_Att;
           }
        }

        // Remove duplicates, its possible that someone specified two duplicate
        // <RoleRef> elements in a <Roles>, e.g:
        // <Roles>
        //    <RoleRef idRef="RA"/>
        //    <RoleRef idRef="RA"/>
        //    <RoleRef idRef="RB"/>
        // <Roles>
        $enablingRoleIds = array_unique($enablingRoleIds);
        //print_r($enablingRoleIds);
        //return $enablingRoleIds;


        $enablingRoleNamesOver = array();
        $roleNamesList = $xpath->query("goc:RoleNames", $roleActionMapping);
        foreach ($roleNamesList as $roleNames) {
            //print_r($roleNames->nodeValue);
            $over = $roleNames->getAttribute('over');
            $roles = $xpath->query("goc:Role", $roleNames);
            foreach ($roles as $role) {
                //print_r("[".$role->getAttribute('id'). "] [".$role->nodeValue."] [".$over."]\n");
                if (in_array($role->getAttribute('id'), $enablingRoleIds)) {
                    if (!array_key_exists($role->nodeValue, $enablingRoleNamesOver)) {
                        $enablingRoleNamesOver[$role->nodeValue] = $over; // associative array
                    } else {
                        throw new \LogicException("Duplicate Role value detected.");
                    }
                }
            }
        }
        return $enablingRoleNamesOver;
    }


    /**
     * Get the (single) RoleActionMapping element from the XML file.
     * @param \DOMXPath $xpath
     * @return DOMNode The RoleActionMapping element
     * @throws \LogicException
     */
    private function getRoleActionMapping(\DOMXPath $xpath) {
        // Query for Single/Default RoleActionMapping element
        $roleActionMappings = $xpath->query(
                "/goc:RoleActionMappingRules/goc:RoleActionMapping");

        if ($roleActionMappings->length == 0) {
            throw new \LogicException("Can't find a default RoleActionMapping element "
            . "- check your RoleActionMapping XML file.", 21);
        }
        if ($roleActionMappings->length > 1) {
            throw new \LogicException("There are multiple default RoleActionMapping elements "
            . "and there should only be one  "
            . "- check your RoleActionMapping XML file.", 22);
        }
        $roleActionMapping = $roleActionMappings->item(0);

        if ($roleActionMapping == NULL) {
            throw new \LogicException("Couldn't find a RoleActionMapping element "
            . "- check your RoleActionMapping XML file.", 30);
        }
        return $roleActionMapping;
    }



    /**
     * NOT NEEDED (YET) - DECLARING DIFFERENT ROLE ACTION MAPPINGS PER PROJECT MAY BE
     * NEEDED IN FUTURE, BUT RIGHT NOW IT ADDS UNNECESSARY COMPLEXITY.
     *
     * Get the (single) RoleActionMapping element for the optional projectName.
     * <p>
     * Returns either a RoleActionMapping that specifies the named project
     * using a nested TargetProject child element, or the default RoleActionMapping
     * that applies to all projects (if defined). If null is specified for the
     * projectName, then the default RoleActionMapping is returned (if defined).
     *
     * @param \DOMXPath $xpath
     * @param string $projectName case-insensitive, nullable
     * @return DOMNode The RoleActionMapping element
     * @throws \LogicException If a RoleActionMapping element can't be resolved
     */
    /*private function getRoleActionMappingForProject(\DOMXPath $xpath, $projectName){
        $roleActionMappings = NULL;

        if($projectName !== NULL){
            $projectNameU = strtoupper($projectName);

            // Query for all RoleMapping elements where there is a child
            // 'EnabledActions/Target' element with a text val == $entity type
            // This version is case-sensitive version:
            //$roleMappings = $xpath->query("goc:RoleMapping[goc:EnabledActions/goc:Target[text()='$entityType']]", $roleActionMapping);

            // Use xpath to populate the roleActionMappings array with
            // the relevant elements for the requested action
            // First, query for any RoleActionMapping elements for the named project
            // i.e. those that have a child <TargetProject> with text value == $projectName
            //
            // Next comment-line shows case-sensitive version:
            // $roleActionMappings = $xpath->query("/goc:RoleActionMappingRules/goc:RoleActionMapping[goc:TargetProject[text()='$projectName']]");
            // Need to use translate() with XPath1.0 in order to perform a
            // case-insensitive comparison, see:
            // http://stackoverflow.com/questions/2893551/case-insensitive-matching-in-xpath
            $roleActionMappings = $xpath->query(
                    "/goc:RoleActionMappingRules/goc:RoleActionMapping"
                    . "[goc:TargetProject['$projectNameU'="
                    . "translate(text(), 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]]");

            if (FALSE === $roleActionMappings) {
                throw new \LogicException("Coding error - invalid xpath query.");
            }

            //print_r("ram Size: ".($roleActionMappings->length));
            //foreach($roleActionMappings as $ram){
            //    print_r("[".$ram->nodeValue."]\n");
            //}

            if ($roleActionMappings->length > 1) {
                throw new \LogicException("There are multiple RoleActionMapping elements for the "
                . "specified project when there should only be one - please check "
                . "your RoleActionMapping XML file.", 20);
            }

            // Lets not throw if a RAM can't be found for the project because
            // its plausable that old/legacy Projects exist in the DB but are
            // not mapped in the XML file. In this scenario, attempt to
            // query the default RAM instead.
//            if ($roleActionMappings->length == 0) {
//                throw new \LogicException("Can't find a RoleActionMapping elements for the "
//                . "specified project [$projectName] - please check "
//                . "your RoleActionMapping XML file.", 20);
//            }
        }

        // 0 or 1 roleActionMapping DOMNodes
        if ($roleActionMappings !== NULL && $roleActionMappings->length == 1) {
            $roleActionMapping = $roleActionMappings->item(0);
            //print_r($roleActionMapping->nodeValue);
        } else {
            // 0 found for specified project
            // Fallback to Query for any Default RoleActionMapping elements that have no
            // child <TargetProject> nodes (used as default)
            $roleActionMappings = $xpath->query(
                    "/goc:RoleActionMappingRules/goc:RoleActionMapping[not (goc:TargetProject)]");

            if ($roleActionMappings->length == 0) {
                throw new \LogicException("Can't find a default RoleActionMapping element "
                . "or a RoleActionMapping for the specified project [$projectName] "
                . "- check your RoleActionMapping XML file.", 21);
            }
            if ($roleActionMappings->length > 1) {
                throw new \LogicException("There are multiple default RoleActionMapping elements "
                . "and there should only be one (default mappings have no TargetProject elements) "
                . "- check your RoleActionMapping XML file.", 22);
            }
            $roleActionMapping = $roleActionMappings->item(0);
        }

        if ($roleActionMapping == NULL) {
            throw new \LogicException("Couldn't find a RoleActionMapping element for "
            . "specified project [$projectName] - check your RoleActionMapping XML file.", 30);
        }
        return $roleActionMapping;
    }*/

}
