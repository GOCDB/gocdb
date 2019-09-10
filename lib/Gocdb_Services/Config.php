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
    private $local_info_xml = NULL;
    private $local_info_override = NULL;


    public function __construct() {
        $this->gocdb_schemaFile = __DIR__."/../../config/gocdb_schema.xml";
        
        $this->setLocalInfoFileLocation(__DIR__."/../../config/local_info.xml");
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
     * @throws \ErrorException If not a string
     */
    public function setLocalInfoFileLocation($filePath){
        if(!is_string($filePath)){
            throw new \ErrorException("Invalid filePath given for local_info.xml file");
        }
        // If this is the first time in or the path has changed save the filepath
        // and force any existing cached object to be discarded.
            if ($this->local_infoFile == NULL or $this->local_infoFile !== $filePath){
            $this->local_infoFile = $filePath;
            $this->local_info_xml = NULL;
        }

    }
    
    /**
     * Set the url parameter overrides. THe url is used to match the xml section
     * containing override values for the default local_info.
     * 
     * @param string $filePath
     * @throws \LogicException If not a string     
     */
    public function setLocalInfoOverride ($url){
        if(!is_string($url)){
            throw new \LogicException("Invalid url given for local_info override.");
        }      
        $this->local_info_override = $url;
        /**
         *  Force the cached object to be discarded as the file has changed
         */
        $this->local_info_xml = NULL;
    }

    /**
     * Opens the gocdb_schema.xml file for reading, returning a simplexml object.
     * @return simple xml object
     */
    public function GetSchemaXML() {
        return simplexml_load_file($this->getSchemaFileLocation());
    }

    /**
     * Returns a simplexml object representing the local_info.xml file
     * @return simple xml object
     */
    private function GetLocalInfoXML() {
        if ($this->local_info_xml == NULL) {
            if (!($this->local_info_xml = $this->readLocalInfoXML($this->getLocalInfoFileLocation(), $this->local_info_override))) {
                throw new \ErrorException("Failed to load xml configuration file: ".$this->getLocalInfoFileLocation());
            } 
        } 
        return $this->local_info_xml;
    }

    /**
     * Reads the local_info.xml, returning a simplexml object. The contents of the file are
     * adjusted based on the url attribute provided. 
     * @return simple xml object
     */
    private function readLocalInfoXML ($path, $url = NULL) {

        libxml_use_internal_errors(true);

        if (($base = simplexml_load_file($path)) == FALSE) {
            $this->throwXmlErrors('Failed to load configuration file '.$path);
        }

        // Search the input XML for a 'local_info' section that does NOT have a url attribute
        // specified. This is the default spec.
        if (($unqualified = $base->xpath("//local_info[not(@url)]")) == FALSE) {
            throw new \ErrorException('Failed to find local_info section without url in configuration file '.$path);
        }

        if (count($unqualified) != 1) {
            throw new \ErrorException('Only one local_info element without url attribute is allowed in configuration file '.$path);
        }

        $default_info = $unqualified[0];
        
        if (!is_null($url)) {
            // Find any elements matching the given url
            $qualified = $base->xpath("//local_info[@url=\"$url\"]");

            if (count($qualified) != 0) {
                if (count($qualified) != 1) { 
                    throw new \ErrorException('Duplicate local_info elements with same url attribute found in configuration file '.$path);
                }

                $iterator = new \SimpleXmlIterator ($qualified[0]->asXML());

                $keys = array();

                $this->descendXml($iterator, $keys, $default_info);

            }
        }
        return $default_info;
    }
    /**
     * Throws an ErrorException after appending libxml errors to the input message.
     */
    private function throwXmlErrors ($message) {
        foreach (libxml_get_errors() as $err) {
            $message .= " ".$err->message;
        }
        libxml_clear_errors();
        throw new \ErrorException($message);
    }
    /**
     * Iteratively passes through a given SimpleXmlIterator object overwriting the values in an input
     * SimpleXmlElement with the values found in the iterator. 
     * Note: The elements in the iterator MUST exist in the input element.
     */
    private function descendXml (\SimpleXmlIterator $iterator, $keys, \SimpleXmlElement $default_info) {      
        
        for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {

            $keys[] = $iterator->key();
 
            if ($iterator->hasChildren()) {
                $this->descendXml($iterator->getChildren(), $keys, $default_info);
            } else {
                $p = implode("/",$keys);
                if (($elem = $default_info->xpath($p)) == FALSE) {
                    throw new \ErrorException("Did not find elements $p in input configuration file.");
                }
                if (count($elem)) {
                    if (count($elem) != 1) {
                        throw new \ErrorException('Duplicate input configuration file element specifications ('.count($elem).') for "'.implode('/',$keys).'"');
                    }
                    # ???? How else to force a self-reference rather than override the array value ????
                    $elem[0][0] = (string)$iterator->current();
                } else {
                    // Do we want to create it here ??
                    throw new \ErrorException("Input configuration file override element $p not found in default section: ".$iterator->key().' => '.$iterator->current());
                }

            }
            array_pop($keys);
        }
    }

    /**
     * returns true if the portal has ben set to read only mode in local_info.xml
     * @return boolean
     */
    public function IsPortalReadOnly(){
        $localInfo = $this->GetLocalInfoXML();
        if (strtolower($localInfo->read_only) == 'true'){
            return true;
        }

        return false;
    }

    /**
     * returns true if the given menu is to be shown according to local_info.xml
     * @return boolean
     */
    public function showMenu($menuName) {

        if (empty($this->GetLocalInfoXML()->menus->$menuName)) { 
            return true;
        }

        switch (strtolower((string) $this->GetLocalInfoXML()->menus->$menuName)) {
            case 'false';
            case 'hide';
            case 'no';
                return false;
        }
        return true;
    }
    
    /**
     * Determine if the requested feature is set in the local_info.xml file.
     * @param type $featureName The feature name which should correspond to an
     *  XML child element under <local_info><optional_features>
     * @return boolean true if the element is present, otherwise false.
     */
    public function IsOptionalFeatureSet($featureName) {
        $localInfo = $this->GetLocalInfoXML();
        $feature= $localInfo->optional_features->$featureName;
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
        $url = $localInfo->web_portal_url;
        return strval($url);
    }

    /**
     * The base server URL as recorded in local_info.xml. This URL is used with the
     * PI query output, e.g. for building paging/HATEOAS links.
     */
    public function getServerBaseUrl(){
        $localInfo = $this->GetLocalInfoXML();
        $url = $localInfo->server_base_url;
        return strval($url);
    }

    /**
     * The wtite API documentation URL as recorded in local_info.xml.
     * This URL is given to users of the write API in error messages
     */
    public function getWriteApiDocsUrl(){
        $localInfo = $this->GetLocalInfoXML();
        $url = $localInfo->write_api_user_docs_url;
        return strval($url);
    }

    public function getDefaultScopeName(){
        //$scopeName = $this->GetLocalInfoXML()->local_info->default_scope->name;
        $scopeName = $this->GetLocalInfoXML()->default_scope->name;

        if (empty($scopeName)){
            $scopeName = '';
        }

        return strval($scopeName);
    }

    public function getDefaultScopeMatch(){
        $scopeMatch = $this->GetLocalInfoXML()->default_scope_match;

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

        $numScopesRequired = $this->GetLocalInfoXML()->minimum_scopes->$entityType;

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
        $reserved_scopes = $this->GetLocalInfoXML()->reserved_scopes;
        if ($reserved_scopes != null) {
            /* @var $scope \SimpleXMLElement */
            foreach ( $reserved_scopes->children () as $scope ) {
                $reservedScopes [] = ( string ) $scope;
            }
        }
        return $reservedScopes;
    }

    public function getShowMapOnStartPage(){
        $showMapString = $this->GetLocalInfoXML()->show_map_on_start_page;

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

    public function getExtensionsLimit(){
        return $this->GetLocalInfoXML()->extensions->max;
    }


    public function getSendEmails(){
        $sendEmailString = $this->GetLocalInfoXML()->send_email;
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
