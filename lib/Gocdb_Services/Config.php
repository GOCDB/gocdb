<?php
namespace org\gocdb\services;
/* Copyright © 2011 STFC
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


/**
 * GOCDB service for GOCDB configuration settings in config files:  
 * <code>config/local_info.xml</code> and <code>config/gocdb_schema.xml</code>.
 *
 * @author David Meredith <david.meredith@stfc.ac.uk> 
 * @author John Casson
 */
class Config {
    private $gocdb_schemaFile;   
    private $local_infoFile; 
    

    public function __construct() {
        $this->gocdb_schemaFile = __DIR__."/../../config/gocdb_schema.xml";
        $this->local_infoFile =  __DIR__."/../../config/local_info.xml"; 
    }

    /**
     * Get the full path to the gocdb_schema.xml config file.
     * @return string
     */
    public function getSchemaFileLocation(){
       return $this->gocdb_schemaFile;
    }

    /**
     * Set the full path to the 'gocdb_schema.xml' config file. 
     * <p>
     * Useful for testing when creating a sample seed configuration. If not 
     * set, then defaults to <src>__DIR__."/../../config/gocdb_schema.xml</src> 
     * 
     * @param string $filePath
     * @throws \LogicException If not a string
     */
    public function setSchemaFileLocation($filePath){
        if(!is_string($filePath)){
            throw new \LogicException("Invalid filePath given for gocdb_schema.xml file"); 
        }
        $this->gocdb_schemaFile = $filePath; 
    }

    /**
     * Get the full path to the local_info.xml config file.
     * @return string
     */
    public function getLocalInfoFileLocation(){
        return $this->local_infoFile;
    }

    /**
     * Set the full path to the 'local_info.xml' config file. 
     * <p>
     * Useful for testing when creating a sample seed configuration. If not 
     * set, then defaults to <src>__DIR__."/../../config/local_info.xml</src> 
     * 
     * @param string $filePath
     * @throws \LogicException If not a string
     */
    public function setLocalInfoFileLocation($filePath){
        if(!is_string($filePath)){
            throw new \LogicException("Invalid filePath given for local_info.xml file"); 
        }
        $this->local_infoFile = $filePath;  
    }

    /**
     * Opens the gocdb_schema.xml file for reading, returning a simplexml object.
     * @return simple xml object
     */
    public function GetSchemaXML() {
        return simplexml_load_file($this->getSchemaFileLocation());
    }

    /**
     * Opens the local_info.xml file for reading, returning a simplexml object.
     * @return simple xml object
     */
    private function GetLocalInfoXML() {
        return simplexml_load_file($this->getLocalInfoFileLocation());
    }

    
    /**
     * returns true if the portal has ben set to read only mode in local_info.xml
     * @return boolean
     */
    public function IsPortalReadOnly(){
        $localInfo = $this->GetLocalInfoXML();
        if (strtolower($localInfo->local_info->read_only) == 'true'){
            return true;
        }
        
        return false;
    }
    
    /**
     * Determine if the requested feature is set in the local_info.xml file.
     * @param type $featureName The feature name which should correspond to an
     *  XML child element under <local_info><optional_features>
     * @return boolean true if the element is present, otherwise false.
     */
    public function IsOptionalFeatureSet($featureName) {
        $localInfo = $this->GetLocalInfoXML();
        $feature= $localInfo->local_info->optional_features->$featureName;
        if((string) $feature == "true") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * The base portal URL as recorded in local_info.xml. This URL is used
     * within the PI query output.
     * @return string
     */
    public function GetPortalURL() {
        $localInfo = $this->GetLocalInfoXML();
        $url = $localInfo->local_info->web_portal_url;
        return strval($url);
    }
    
    public function getDefaultScopeName(){
        $scopeName = $this->GetLocalInfoXML()->local_info->default_scope->name;

        if (empty($scopeName)){
            $scopeName = '';
        }

        return strval($scopeName);
    }
    
    public function getDefaultScopeMatch(){
        $scopeMatch = $this->GetLocalInfoXML()->local_info->default_scope_match;

        if (empty($scopeMatch)){
            $scopeMatch = 'all';
        }

        return strval($scopeMatch);
    }

    public function  getMinimumScopesRequired($entityType){
        $supportedEntities = array('ngi', 'site', 'service', 'service_group');
        
        if(!in_array($entityType, $supportedEntities)){
            throw new \LogicException("Function does not support entity type");
        }
        
        $numScopesRequired = $this->GetLocalInfoXML()->local_info->minimum_scopes->$entityType;
                
        if (empty($numScopesRequired)){
            $numScopesRequired = 0;
        }
        
        return intval($numScopesRequired);
    }
    
    /**
     * Get an array of 'reserved' scope strings or an empty array if non are configured.
     *
     * <p>
     * Reserved scopes can only be assiged by gocdb admin (and in future by selected roles);
     * they can't be freely assigned to resources by their users/owners.
     * 
     * @return array Reserved scopes as Strings
     */
    public function getReservedScopeList() {
        $reservedScopes = array ();
        /* @var $reserved_scopes \SimpleXMLElement */
        $reserved_scopes = $this->GetLocalInfoXML()->local_info->reserved_scopes;
        if ($reserved_scopes != null) {
            /* @var $scope \SimpleXMLElement */
            foreach ( $reserved_scopes->children () as $scope ) {
                $reservedScopes [] = ( string ) $scope;
            }
        }
        return $reservedScopes;
    }
     
    public function getShowMapOnStartPage(){
        $showMapString = $this->GetLocalInfoXML()->local_info->google->show_map_on_start_page;
                
        if(empty($showMapString)){
            $showMap = false;
        }
        elseif(strtolower($showMapString) == 'true'){
            $showMap = true;
        }
        else{
            $showMap = false;
        }

        return $showMap;
    }
    
    public function getGoogleAPIKey(){
        $apiKey = $this->GetLocalInfoXML()->local_info->google->google_API_key;
                
        if(empty($apiKey)){
            $apiKey = '';
        }

        return $apiKey;
    }
    
    public function getExtensionsLimit(){
        return $this->GetLocalInfoXML()->local_info->extensions->max;        
    }
    
        
    public function getSendEmails(){
        $sendEmailString = $this->GetLocalInfoXML()->local_info->send_email;  
         if(empty($sendEmailString)){
            $sendEmail = false;
        }
        elseif(strtolower($sendEmailString) == 'true'){
            $sendEmail = true;
        }
        else{
            $sendEmail = false;
        }
        return $sendEmail;
    }
    
}


?>
