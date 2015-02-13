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
 * GOCDB Stateless service for GOCDB and GOCDB-PROM object configuration.
 *
 * @author David Meredith
 * @author John Casson
 */
class Config {

    public function __construct() {
    }

    /**
     * Get the full path to the gocdb_schema.xml config file.
     * @return string
     */
    public function getSchemaFileLocation(){
       return  __DIR__."/../../config/gocdb_schema.xml";
    }

    /**
     * Get the full path to the local_info.xml config file.
     * @return string
     */
    public function getLocalInfoFileLocation(){
      return __DIR__."/../../config/local_info.xml";
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
    
    public function getDefaultEndpointName(){
        return $this->GetLocalInfoXML()->local_info->endpoints->default_name;
    }
        
    public function getEndpointRenderStatus(){
        $status = $this->GetLocalInfoXML()->local_info->endpoints->render;
        if($status == 'true'){
            return true;
        }else{
            return false;
        }
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
