<?php
namespace org\gocdb\security\authentication;
require_once __DIR__.'/../IAuthentication.php'; 

/*
 * Copyright (C) 2012 STFC
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
 * AuthToken that supports SAML2. 
 * <p>
 * Requires installation of SimpleSamlPhp lib before use. 
 * You will almost certainly need to modify this class to request the necessary 
 * SAML attribute from the IdP that is used as the principle string. 
 * <p>
 * The token is stateless because it relies on the SSPhp session and simply 
 * reads the attributes stored in the SSPhp session. 
 *
 * @see IAuthentication 
 * @author David Meredith 
 */
class SimpleSamlPhpAuthToken implements IAuthentication {
 
    private $userDetails = null;
    private $authorities = array();
    private $principal; 
  
    /*
     * Implementation note:  
     * There is a bug with SimpleSamlPhp in that it throws an exception if 
     * an IdP does not return a NameID in the Subject (of a response). 
     * "Missing saml:NameID or saml:EncryptedID in saml:Subject".
     * 
     * IdpS only have to set a NameID in the Subject if it supports the Browser Single Logout 
     * Profile, otherwise its optional (although recommended). I have therefore reported this to 
     * the SSPhp project and have used the following hack to get around this issue:  
     * https://github.com/simplesamlphp/simplesamlphp/issues/143
     */
/*    
// '/var/simplesamlphp/vendor/simplesamlphp/saml2/src/SAML2/Assertion.php'   
// DMHack: orignal:
//        if (empty($nameId)) {
//            throw new Exception('Missing <saml:NameID> or <saml:EncryptedID> in <saml:Subject>.');
//
//        } elseif (count($nameId) > 1) {
//            throw new Exception('More than one <saml:NameID> or <saml:EncryptedD> in <saml:Subject>.');
//        }
//        $nameId = $nameId[0];
//        if ($nameId->localName === 'EncryptedData') {
//            // The NameID element is encrypted.
//            $this->encryptedNameId = $nameId;
//        } else {
//            $this->nameId = SAML2_Utils::parseNameId($nameId);
//        }
//        // end original, start hack:
        if (!empty($nameId)){
                if (count($nameId) > 1) {
                    throw new Exception('More than one <saml:NameID> or <saml:EncryptedD> in <saml:Subject>.');
                }
                $nameId = $nameId[0];
                if ($nameId->localName === 'EncryptedData') {
                    // The NameID element is encrypted.
                    $this->encryptedNameId = $nameId;
                } else {
                    $this->nameId = SAML2_Utils::parseNameId($nameId);
                }
        } else {
                $this->nameId = array();
                //$this->nameId = array('Value' => trim('davidm'));
        }
        // end DMHack
 */

    public function __construct() {
       $this->getAttributesInitToken();   
    }

    /**
     * {@see IAuthentication::eraseCredentials()}
     */ 
    public function eraseCredentials() {
        
    }

    /**
     * {@see IAuthentication::getAuthorities()} 
     */
    public function getAuthorities() {
       return $this->authorities;  
    }

    /**
     * {@see IAuthentication::getCredentials()}
     * @return string An empty string as passwords are not used by this token. 
     */
    public function getCredentials() {
        return ""; // none used in this token, handled by SSO/SAML 
    }

    /**
     * A custom object used to store additional user details.  
     * Allows non-security related user information (such as email addresses, 
     * telephone numbers etc) to be stored in a convenient location. 
     * {@see IAuthentication::getDetails()}
     * 
     * @return Object or null if not used 
     */
    public function getDetails() {
        return $this->userDetails;
    }

    /**
     * {@see IAuthentication::getPrinciple()}
     * @return string unique principle string of user  
     */
    public function getPrinciple() {
       return $this->principal;  
    }

    private function getAttributesInitToken(){
        require_once('/var/simplesamlphp/lib/_autoload.php');
        $auth = new \SimpleSAML_Auth_Simple('default-sp');
        $auth->requireAuth();
        \Factory::$properties['LOGOUTURL'] = $auth->getLogoutURL('https://'.  gethostname());
        $attributes = $auth->getAttributes();
        if (!empty($attributes)) {
            $idp = $auth->getAuthData('saml:sp:IdP');
            if($idp == 'https://www.egi.eu/idp/shibboleth'){ // EGI IdP 
                $nameID = $auth->getAuthData('saml:sp:NameID');
                $this->principal = $nameID['Value'];
                $this->userDetails = array('AuthenticationRealm' => array('EGI_SSO_IDP'));
                // For EGI federated id:
                //$dnAttribute = $attributes['urn:oid:1.3.6.1.4.1.11433.2.2.1.9'][0];
                //if (!empty($dnAttribute)) {
                //    $this->principle = str_replace("emailAddress=", "Email=", $dnAttribute);
                //    $this->userDetails = array('AuthenticationRealm' => array('EGI_SSO_IDP'));
                //}
                // iterate the attributes and store in the userDetails
                // Each attribute name can be used as an index into $attributes to obtain the value. 
                // Every attribute value is an array - a single-valued attribute is an array of a single element.
                foreach($attributes as $key => $valArray){
                   $this->userDetails[$key] = $valArray;  
                }
            }
            // EUDAT IdP 
            else if($idp == 'https://unity.eudat-aai.fz-juelich.de:8443/saml-idp/metadata'){
                // For EUDAT federated id:
                //$dnAttribute = $attributes['urn:oid:2.5.4.49'][0];
                //$dnAttribute = $attributes['unity:identity:persistent'][0];
                //print_r($attributes);
                $nameID = $auth->getAuthData('saml:sp:NameID');
                $this->principal = $nameID['Value'];
                $this->userDetails = array('AuthenticationRealm' => array('EUDAT_SSO_IDP'));
                // iterate the attributes and store in the userDetails
                // Each attribute name can be used as an index into $attributes to obtain the value. 
                // Every attribute value is an array - a single-valued attribute is an array of a single element.
                foreach($attributes as $key => $valArray){
                   $this->userDetails[$key] = $valArray;  
                }
            }
        }
    }

    /**
     * {@see IAuthentication::setAuthorities($authorities)} 
     */
    public function setAuthorities($authorities) {
       $this->authorities = $authorities;  
    }

    /**
     * {@see IAuthentication::setDetails($userDetails)}
     * @param Object $userDetails
     */
    public function setDetails($userDetails) {
        $this->userDetails = $userDetails;
    }
 
    /**
     * {@see IAuthentication::validate()}
     */
    public function validate() {
        
    }

    /**
     * {@see IAuthentication::isPreAuthenticating()}
     */
    public static function isPreAuthenticating() {
        return true;         
    }

    /**
     * Returns true, this token reads the SSPhp session attributes and so 
     * does not need to be stateful itself.  
     * {@see IAuthentication::isStateless()} 
     */
    public static function isStateless() {
        return true;         
    }

}
