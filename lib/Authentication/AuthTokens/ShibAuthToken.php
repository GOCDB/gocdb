<?php
namespace org\gocdb\security\authentication;
require_once __DIR__.'/../IAuthentication.php'; 

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

/**
 * AuthToken for use with ShibSP. 
 * <p>
 * Requires installation/config of ShibSP before use. 
 * You will almost certainly need to modify this class to request the necessary 
 * SAML attribute that is used as the principle string and configure other 
 * attributes for the userDetails. 
 *
 * @see IAuthentication 
 * @author David Meredith 
 */
class ShibAuthToken implements IAuthentication {
    
    private $userDetails = null;
    private $authorities = array();
    private $principle;  

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
       return $this->principle;  
    }

    private function getAttributesInitToken(){
        // specify location of the Shib Logout handler 
        \Factory::$properties['LOGOUTURL'] = 'https://' . gethostname() . '/Shibboleth.sso/Logout';
        $idp = $_SERVER['Shib-Identity-Provider'];
        if ($idp == 'https://unity.eudat-aai.fz-juelich.de:8443/saml-idp/metadata') {
            //&&  $_SERVER['distinguishedName'] != null){
            $this->principle = $_SERVER['distinguishedName'];
            //$this->principle = "/C=DE/L=Juelich/O=FZJ/OU=JSC/CN=someone";
            $this->userDetails = array('AuthenticationRealm' => array('EUDAT_SSO_IDP'));
            return; 
            //die($_SERVER['distinguishedName']);
        } else {
            die('Now go configure this AuthToken file ['.__FILE__.']');   
        }
        // if we have not set the principle/userDetails, re-direct to our Discovery Service 
        $target = urlencode("https://" . gethostname() . "/portal/");
        header("Location: https://" . gethostname() . "/Shibboleth.sso/Login?target=" . $target);
        die();
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
     * {@see IAuthentication::isStateless()} 
     */
    public static function isStateless() {
        return true;         
    }

}
