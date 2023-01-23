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
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 *
 */
class Config
{
    private $gocdbSchemaFile;
    private $localInfoFile;
    private $localInfoXml = null;
    private $localInfoOverride = null;


    public function __construct()
    {
        $this->gocdbSchemaFile = __DIR__ . "/../../config/gocdb_schema.xml";

        $this->setLocalInfoFileLocation(__DIR__ . "/../../config/local_info.xml");
    }

    /**
     * Get the full path to the gocdb_schema.xml config file.
     * @return string
     */
    public function getSchemaFileLocation()
    {
        return $this->gocdbSchemaFile;
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
    public function setSchemaFileLocation($filePath)
    {
        if (!is_string($filePath)) {
            throw new \LogicException("Invalid filePath given for gocdb_schema.xml file");
        }
        $this->gocdbSchemaFile = $filePath;
    }

    /**
     * Get the full path to the local_info.xml config file.
     * @return string
     */
    public function getLocalInfoFileLocation()
    {
        return $this->localInfoFile;
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
    public function setLocalInfoFileLocation($filePath)
    {
        if (!is_string($filePath)) {
            throw new \ErrorException("Invalid filePath given for local_info.xml file");
        }
        // If this is the first time in or the path has changed save the filepath
        // and force any existing cached object to be discarded.
        if ($this->localInfoFile == null or $this->localInfoFile !== $filePath) {
            $this->localInfoFile = $filePath;
            $this->localInfoXml = null;
        }
    }

    /**
     * Set the url parameter overrides. THe url is used to match the xml section
     * containing override values for the default local_info.
     *
     * @param string $filePath
     * @throws \LogicException If not a string
     */
    public function setLocalInfoOverride($url)
    {
        if (!is_string($url)) {
            throw new \LogicException("Invalid url given for local_info override.");
        }
        $this->localInfoOverride = $url;
        /**
         *  Force the cached object to be discarded as the file has changed
         */
        $this->localInfoXml = null;
    }

    /**
     * Opens the gocdb_schema.xml file for reading, returning a simplexml object.
     * @return simple xml object
     */
    public function GetSchemaXML()
    {
        return simplexml_load_file($this->getSchemaFileLocation());
    }

    /**
     * Returns a simplexml object representing the local_info.xml file
     * @return simple xml object
     */
    private function GetLocalInfoXML()
    {
        if ($this->localInfoXml == null) {
            $this->localInfoXml = $this->readLocalInfoXML($this->getLocalInfoFileLocation(), $this->localInfoOverride);
            if (!$this->localInfoXml) {
                throw new \ErrorException("Failed to load xml configuration file: " .
                                            $this->getLocalInfoFileLocation());
            }
        }
        return $this->localInfoXml;
    }

    /**
     * Reads the local_info.xml, returning a simplexml object. The contents of the file are
     * adjusted based on the url attribute provided.
     * @return simple xml object
     */
    private function readLocalInfoXML($path, $url = null)
    {

        libxml_use_internal_errors(true);

        $base = simplexml_load_file($path);

        if (!$base) {
            $this->throwXmlErrors('Failed to load configuration file ' . $path);
        }

        // Search the input XML for a 'local_info' section that does NOT have a url attribute
        // specified. This is the default spec.
        $unqualified = $base->xpath("//local_info[not(@url)]");
        if (!$unqualified) {
            throw new \ErrorException('Failed to find local_info section without url in configuration file ' . $path);
        }

        if (count($unqualified) != 1) {
            throw new \ErrorException(
                'Only one local_info element without url attribute is ' .
                'allowed in configuration file ' .
                $path
            );
        }

        $defaultInfo = $unqualified[0];

        if (!is_null($url)) {
            // Find any elements matching the given url
            $qualified = $base->xpath("//local_info[@url=\"$url\"]");

            if (count($qualified) != 0) {
                if (count($qualified) != 1) {
                    throw new \ErrorException(
                        'Duplicate local_info elements with same url ' .
                        'attribute found in configuration file ' .
                         $path
                    );
                }

                $iterator = new \SimpleXmlIterator($qualified[0]->asXML());

                $keys = array();

                $this->descendXml($iterator, $keys, $defaultInfo);
            }
        }
        return $defaultInfo;
    }
    /**
     * Throws an ErrorException after appending libxml errors to the input message.
     */
    private function throwXmlErrors($message)
    {
        foreach (libxml_get_errors() as $err) {
            $message .= " " . $err->message;
        }
        libxml_clear_errors();
        throw new \ErrorException($message);
    }
    /**
     * Iteratively passes through a given SimpleXmlIterator object overwriting the values in an input
     * SimpleXmlElement with the values found in the iterator.
     * Note: The elements in the iterator MUST exist in the input element.
     */
    private function descendXml(\SimpleXmlIterator $iterator, $keys, \SimpleXmlElement $defaultInfo)
    {

        for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
            $keys[] = $iterator->key();

            if ($iterator->hasChildren()) {
                $this->descendXml($iterator->getChildren(), $keys, $defaultInfo);
            } else {
                $elemPath = implode("/", $keys);
                $elem = $defaultInfo->xpath($elemPath);

                if (!$elem) {
                    throw new \ErrorException(
                        'Did not find elements ' .
                        $elemPath .
                        ' in input configuration file.'
                    );
                }

                if (count($elem)) {
                    if (count($elem) != 1) {
                        throw new \ErrorException(
                            'Duplicate input configuration file element specifications (' .
                            count($elem) .
                            ') for "' .
                            implode('/', $keys) .
                            '"'
                        );
                    }
                    # ???? How else to force a self-reference rather than override the array value ????
                    $elem[0][0] = (string)$iterator->current();
                } else {
                    // Do we want to create it here ??
                    throw new \ErrorException(
                        'Input configuration file override element ' .
                        $elemPath .
                        ' not found in default section: ' .
                        $iterator->key() .
                        ' => ' .
                        $iterator->current()
                    );
                }
            }
            array_pop($keys);
        }
    }

    /**
     * returns true if the portal has ben set to read only mode in local_info.xml
     * @return boolean
     */
    public function IsPortalReadOnly()
    {
        $portalReadOnly = (string) $this->GetLocalInfoXML()->read_only;
        if (strtolower($portalReadOnly) == 'true') {
            return true;
        }

        return false;
    }

    /**
     * returns the url of the Acceptable Use Policy for display on the landing page
     * @return string
     */
    public function getAUP()
    {
        $aup = (string) $this->GetLocalInfoXML()->aup;

        return $aup;
    }

    /**
     * returns the title string describing the Acceptable Use Policy for display on the landing page
     * @return string
     */
    public function getAUPTitle()
    {
        $aupTitle = (string) $this->GetLocalInfoXML()->aup_title;

        return $aupTitle;
    }

    /**
     * returns the url of the Privacy Notice for display on the landing page
     * @return string
     */
    public function getPrivacyNotice()
    {
        $privacyNotice = (string) $this->GetLocalInfoXML()->privacy_notice;

        return $privacyNotice;
    }

    /**
     * returns the title string describing the Privacy Notice for display on the landing page
     * @return string
     */
    public function getPrivacyNoticeTitle()
    {
        $privacyNoticeTitle = (string) $this->GetLocalInfoXML()->privacy_notice_title;

        return  $privacyNoticeTitle;
    }

    /**
     * returns true if the given menu is to be shown according to local_info.xml
     * @return boolean
     */
    public function showMenu($menuName)
    {
        $menuItem = (string) $this->GetLocalInfoXML()->menus->$menuName;
        if (empty($menuItem)) {
            return true;
        }

        switch (strtolower($menuItem)) {
            case 'false':
            case 'hide':
            case 'no':
                return false;
        }
        return true;
    }

    /**
     * returns the relevant name mapping according to local_info.xml
     * @return string
     */
    public function getNameMapping($entityType, $key)
    {
        $nameMapping = (string) $this->GetLocalInfoXML()->name_mapping->$entityType;
        if (empty($nameMapping)) {
            return $key;
        }
        switch ($entityType) {
            case 'Service':
                $service = (string) $this->GetLocalInfoXML()->name_mapping->$entityType->{str_replace(' ', '', $key)};
                return $service;
        }
    }

    /**
     * accessor function for css colour values from local_info.xml
     * @return string
     */
    public function getBackgroundDirection()
    {
        $backgroundDirection = (string) $this->GetLocalInfoXML()->css->backgroundDirection;

        return $backgroundDirection;
    }

    public function getBackgroundColour1()
    {
        $backgroundColour1 = (string) $this->GetLocalInfoXML()->css->backgroundColour1;

        return $backgroundColour1;
    }

    public function getBackgroundColour2()
    {
        $backgroundColour2 = (string) $this->GetLocalInfoXML()->css->backgroundColour2;

        return $backgroundColour2;
    }

    public function getBackgroundColour3()
    {
        $backgroundColour3 = (string) $this->GetLocalInfoXML()->css->backgroundColour3;

        return $backgroundColour3;
    }

    public function getHeadingTextColour()
    {
        $headingTextColour = $this->GetLocalInfoXML()->css->headingTextColour;

        return $headingTextColour;
    }

    /**
     * Determine if the requested feature is set in the local_info.xml file.
     * @param type $featureName The feature name which should correspond to an
     *  XML child element under <local_info><optional_features>
     * @return boolean true if the element is present, otherwise false.
     */
    public function IsOptionalFeatureSet($featureName)
    {
        $feature = (string) $this->GetLocalInfoXML()->optional_features->$featureName;
        if ($feature == "true") {
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
    public function GetPortalURL()
    {
        $portalUrl = (string) $this->GetLocalInfoXML()->web_portal_url;
        return $portalUrl;
    }
    /**
     * How Personal Data is restricted;
     * See description in local_info.xml but in brief:
     * @param boolean $forceStrict If true, restriction of personal data
     *                             is forced.
     * @returns false for legacy behaviour, true for role-based personal data restriction
     */
    public function isRestrictPDByRole($forceStrict = false)
    {
        if ($forceStrict === true)
            return true;

        $value = (string) $this->GetLocalInfoXML()->restrict_personal_data;
        if ($value == "true") {
            return true;
        } else {
            return false;
        }
    }
    /**
     * The PI URL as recorded in local_info.xml.
     */
    public function getPiUrl()
    {
        $piUrl = (string) $this->GetLocalInfoXML()->pi_url;

        return $piUrl;
    }

    /**
     * The base server URL as recorded in local_info.xml. This URL is used with the
     * PI query output, e.g. for building paging/HATEOAS links.
     */
    public function getServerBaseUrl()
    {
        $serverBaseUrl = (string) $this->GetLocalInfoXML()->server_base_url;

        return $serverBaseUrl;
    }

    /**
     * The write API documentation URL as recorded in local_info.xml.
     * This URL is given to users of the write API in error messages
     */
    public function getWriteApiDocsUrl()
    {
        $writeApiDocsUrl = (string) $this->GetLocalInfoXML()->write_api_user_docs_url;

        return $writeApiDocsUrl;
    }

    public function getDefaultScopeName()
    {
        //$scopeName = (string) $this->GetLocalInfoXML()->local_info->default_scope->name;
        $scopeName = (string) $this->GetLocalInfoXML()->default_scope->name;

        if (empty($scopeName)) {
            $scopeName = '';
        }

        return $scopeName;
    }

    public function getDefaultScopeMatch()
    {
        $scopeMatch = (string) $this->GetLocalInfoXML()->default_scope_match;

        if (empty($scopeMatch)) {
            $scopeMatch = 'all';
        }

        return $scopeMatch;
    }

    public function getMinimumScopesRequired($entityType)
    {
        $supportedEntities = array('ngi', 'site', 'service', 'service_group');

        if (!in_array($entityType, $supportedEntities)) {
            throw new \LogicException("Function does not support entity type");
        }

        $numScopesRequired = (int) $this->GetLocalInfoXML()->minimum_scopes->$entityType;

        if (empty($numScopesRequired)) {
            $numScopesRequired = 0;
        }

        return $numScopesRequired;
    }

    public function getDefaultFilterByScope()
    {

        if (strtolower($this->GetLocalInfoXML()->default_filter_by_scope) == 'true') {
            return true;
        }

        return false;
    }

    public function getShowMapOnStartPage()
    {
        $showMapString = (string) $this->GetLocalInfoXML()->show_map_on_start_page;

        if (empty($showMapString)) {
            $showMap = false;
        } elseif (strtolower($showMapString) == 'true') {
            $showMap = true;
        } else {
            $showMap = false;
        }

        return $showMap;
    }

    public function getExtensionsLimit()
    {
        $extensionsLimit = (int) $this->GetLocalInfoXML()->extensions->max;

        return $extensionsLimit;
    }


    public function getSendEmails()
    {
        $sendEmailString = (string) $this->GetLocalInfoXML()->send_email;
        if (empty($sendEmailString)) {
            $sendEmail = false;
        } elseif (strtolower($sendEmailString) == 'true') {
            $sendEmail = true;
        } else {
            $sendEmail = false;
        }
        return $sendEmail;
    }

    public function getAPIAllAuthRealms()
    {
        if (strtolower($this->GetLocalInfoXML()->API_all_auth_realms) === 'true') {
            return true;
        }
        return false;
    }

    public function getPageBanner()
    {
        $bannerText = (string) $this->GetLocalInfoXML()->page_banner;

        return $bannerText;
    }

    public function getEmailFrom()
    {
        $emailFrom = (string) $this->GetLocalInfoXML()->email_from;

        return $emailFrom;
    }

    public function getEmailTo()
    {
        $emailTo = (string) $this->GetlocalInfoXML()->email_to;

        return $emailTo;
    }
}
