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
 * Service facade for querying the Role-Action mappings as defined in the 
 * RoleActionMappingRules XML file. 
 * <p>
 * The role action mappings xml file and XSD can be configured for non-default
 * locations. The XSD defines the tns as specified by ROLE_ACTION_MAPPING_NS. 
 * The XML file is used to define custom Roles and Role-to-Action mappings on 
 * a per-project basis.   
 *
 * @author David Meredith
 */
class RoleActionService /* extends AbstractEntityService */ {

    private $roleActionMappingsXmlPath;
    private $roleActionMappingsXsdPath;

    const ROLE_ACTION_MAPPING_NS = 'http://goc.egi.eu/2015/03/spec1.0_r1';

    /**
     * Service facade for validating and querying against a role action mappings XML file. 
     */
    function __construct() {
        //parent::__construct();
        $this->roleActionMappingsXmlPath = __DIR__ . '/../../config/RoleActionMappings.xml';
        $this->roleActionMappingsXsdPath = __DIR__ . '/../../config/RoleActionMappingsSchema.xsd';
    }

    /**
     * Set the path to the XML role action mapping file, the default is: 
     * <code><Path to RoleActionService.php>/../../config/RoleActionMappingsSchema.xsd</code>
     * @param string $xmlDocPath Path to role action mapping file. 
     */
    public function setRoleActionMappingsXmlPath($xmlDocPath) {
        $this->roleActionMappingsXmlPath = $xmlDocPath;
    }

    /**
     * Set the path to the XSD schema for the role action mapping file, the default is: 
     * <code><Path to RoleActionService.php>/../../config/RoleActionMappingsSchema.xsd</code>
     * @param string $xsdDocPath Path to XSD schema  
     */
    public function setRoleActionMappingsXsdPath($xsdDocPath) {
        $this->roleActionMappingsXsdPath = $xsdDocPath;
    }

    /**
     * Validate the class $roleActionMappingsXmlPath with $roleActionMappingsXsdPath
     * @return array an array with LibXMLError objects if there are any errors 
     * or an empty array otherwise.
     * @throws \LogicException if couldn't load the xml file
     */
    public function validateRoleActionMappingFileAgainstXsd() {
        $xml = new \DOMDocument();
        if (FALSE === $xml->load($this->roleActionMappingsXmlPath)) {
            throw new \LogicException("Couldn't load RoleActionMappings file, "
            . "invalid roleActionMappingsXmlPath: [$this->roleActionMappingsXmlPath]");
        }
        return $this->_validateRoleActionMappingFileAgainstXsd($xml);  
    }

    private function _validateRoleActionMappingFileAgainstXsd(\DOMDocument $xml){
        if (!$xml->schemaValidate($this->roleActionMappingsXsdPath)) {
            return libxml_get_errors();
        }
        return array(); 
    }

    /**
     * Get an associative array of unique Role names (array keys) and the object type
     * the role applies over (array values). 
     *  
     * @param string $projectName
     * @return array Associative array of unique Role names (keys) and the 
     *   object type the role applies over (value) 
     * @throws \LogicException if an invalid projectName, or if  
     *   document is invalid 
     */
    public function getRoleNamesForProject($projectName) {
        //$projectName = 'WLCG'; //'EGI'; 
        if (!is_string($projectName) || strlen(trim($projectName)) == 0) {
            throw new \LogicException('Invalid projectName');
        }

        // Load file 
        $roleActionMapXmlDom = new \DOMDocument();
        if (FALSE === $roleActionMapXmlDom->load($this->roleActionMappingsXmlPath)) {
            throw new \LogicException("Couldn't load RoleActionMappings file, "
            . "invalid roleActionMappingsXmlPath: [$this->roleActionMappingsXmlPath]");
        }
        
        $errors = $this->_validateRoleActionMappingFileAgainstXsd($roleActionMapXmlDom); 
        if(count($errors) > 0){
            throw new \LogicException("Invalid RoleActionMappingsFile "
                    . "[$this->roleActionMappingsXmlPath], use libxml_get_errors() "
                    . "for error details"); 
        }

        $xpath = new \DOMXPath($roleActionMapXmlDom);
        $xpath->registerNamespace("goc", RoleActionService::ROLE_ACTION_MAPPING_NS);

        // Throws a LogicException if there is no RoleActionMapping for the requested project 
        $roleActionMapping = $this->getRoleActionMappingForProject($xpath, $projectName);  
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
                    throw new \LogicException("Duplicate Role value detected");
                } 
            }
        } 
        return $roleNamesOver;
    }

    /**
     * Lookup which Role names enable the specified action on the target 
     * object type for the specified project. 
     * <p>
     * The returned id values are the idRef attribute values, 
     * i.e. </code>RoleMapping/Roles/RoleRef[@idRef]</code>
     * 
     * @param string $action The type of action, matches to element value:
     *   <code>//RoleMapping/EnabledActions/Actions</code>
     * @param string $objectType The type of entity, matches to element value: 
     *   <code>//RoleMapping/EnabledActions/Target</code>
     * @param string $projectName The projectName, matches to element value:
     *   <code>//RoleActionMapping/TargetProject</code>
     * @return array Associative array where Role names are keys and the object type
     * the role applies over is the value. Can be empty if no rules map to the requested action.  
     * @throws \LogicException If the role action mapping file is invalid.  
     */
    public function getRolesThatEnableActionOnTargetObjectType($action, $objectType, $projectName) {
        // fail early 
        if (!is_string($action) || strlen(trim($action)) == 0) {
            throw new \LogicException('Invalid action');
        }
        if (!is_string($objectType) || strlen(trim($objectType)) == 0) {
            throw new \LogicException('Invalid entityType');
        }
        if (!is_string($projectName) || strlen(trim($projectName)) == 0) {
            throw new \LogicException('Invalid projectName');
        }

        // Upper case the parameters so we can perform case-insensitive matches
        // when reading the XML file 
        $entityTypeU = strtoupper($objectType);
        $actionU = strtoupper($action);

        // Lets not use simplexml, DOMXPath is more powerful 
        //$xml = simplexml_load_file($this->roleActionMappingsXmlPath);
        //$xml->registerXPathNamespace('goc', RoleActionService::ROLE_ACTION_MAPPING_NS); 
        //$roleActionMapping = $xml->xpath("//goc:RoleActionMapping"); 

        // load dom 
        $roleActionMapXmlDom = new \DOMDocument();
        //print_r("Debug: [$this->roleActionMappingsXmlPath]"); 
        if (FALSE === $roleActionMapXmlDom->load($this->roleActionMappingsXmlPath)) {
            throw new \LogicException("Couldn't load RoleActionMappings file, "
            . "invalid roleActionMappingsXmlPath: [$this->roleActionMappingsXmlPath]");
        }
        
        // validate dom 
        $errors = $this->_validateRoleActionMappingFileAgainstXsd($roleActionMapXmlDom); 
        if(count($errors) > 0){
            throw new \LogicException("Invalid RoleActionMappingsFile "
                    . "[$this->roleActionMappingsXmlPath], use libxml_get_errors() "
                    . "for error details"); 
        }

        // create xpath 
        $xpath = new \DOMXPath($roleActionMapXmlDom);
        $xpath->registerNamespace("goc", RoleActionService::ROLE_ACTION_MAPPING_NS);

        // Throws a LogicException if there is no RoleActionMapping for the requested project 
        $roleActionMapping = $this->getRoleActionMappingForProject($xpath, $projectName);  

        /* @var $roleMappings DOMNodeList */
        $roleMappings = $xpath->query(
                "goc:RoleMapping[goc:EnabledActions/goc:Target['$entityTypeU'="
                . "translate(text(), 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]]", $roleActionMapping);

        //foreach ($roleMappings as $roleMapping) { print_r("[" . $roleMapping->nodeValue . "]\n"); }
        // Iterate RoleMapping elements and identify/collect those that have a child 
        // 'EnabledActions' element that groups together BOTH the required  Target $entityType
        // AND the required $action listed in <Actions> e.g. 
        // if $action = ACtion_EDIT_OBJECT and $entityType = SERviceGroup a 
        // conforming element would be: 
        // 
        //  <EnabledActions>
        //      <Actions>ACtion_EDIT_OBJECT,ACTION_GRANT_ROLE, ACTION_REJECT_ROLE, ACTION_REVOKE_ROLE</Actions>
        //      <Target>SERviceGroup</Target> 
        //  </EnabledActions> 
        // 
        $inScopeRoleMappings = array();
        foreach ($roleMappings as $roleMapping) {
            //print_r("[" . $roleMapping->nodeValue . "]\n");
            // Filter the child <EnabledActions> to those that have the required <Target> entityType
            $enabledActionsNodes = $xpath->query(
                    "goc:EnabledActions[goc:Target['$entityTypeU'=" // .'text()', 
                    . "translate(text(), 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]]", $roleMapping);

            // Filter the <EnabledActions> to those that have the required action   
            foreach ($enabledActionsNodes as $enabledAction) {
                $actionsTextNodes = $xpath->query('goc:Actions[text()]', $enabledAction);
                foreach ($actionsTextNodes as $actionsText) {
                    //print_r($actionsText->nodeValue. "\n");  
                    $explodedActionsMixedCase = explode(",", $actionsText->nodeValue);
                    $explodedActionsNotTrimmed = array_map('strtoupper', $explodedActionsMixedCase);
                    $explodedActions = array_map('trim', $explodedActionsNotTrimmed);
                    //print_r($explodedActions);  
                    // Collect this RoleMapping, it has required action and target 
                    if (in_array($actionU, $explodedActions)) {
                        $inScopeRoleMappings[] = $roleMapping;
                        //print_r($explodedActions); 
                    }
                }
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
                        throw new \LogicException("Duplicate Role value detected");
                    }
                }
            }
        }
        return $enablingRoleNamesOver; 
    }



    private function getRoleActionMappingForProject(\DOMXPath $xpath, $projectName){
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
            throw new \LogicException("Coding error - invalid xpath query");
        }

        //print_r("ram Size: ".($roleActionMappings->length));
        //foreach($roleActionMappings as $ram){
        //    print_r("[".$ram->nodeValue."]\n");     
        //} 

        if ($roleActionMappings->length > 1) {
            throw new \LogicException("There are multiple RoleActionMapping elements for the "
            . "specified project when there should only be one - please check "
            . "your RoleActionMapping XML file", 20);
        }

        // 0 or 1 roleActionMapping DOMNodes 
        if ($roleActionMappings->length == 1) {
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
                . "- check your RoleActionMapping XML file", 21);
            }
            if ($roleActionMappings->length > 1) {
                throw new \LogicException("There are multiple default RoleActionMapping elements "
                . "and there should only be one (default mappings have no TargetProject elements) "
                . "- check your RoleActionMapping XML file", 22);
            }
            $roleActionMapping = $roleActionMappings->item(0);
        }

        if ($roleActionMapping == NULL) {
            throw new \LogicException("Couldn't find a RoleActionMapping element for "
            . "specified project [$projectName] - check your RoleActionMapping XML file", 30);
        }
        return $roleActionMapping; 
    }

}
