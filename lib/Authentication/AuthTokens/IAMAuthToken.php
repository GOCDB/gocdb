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
 * SAML attribute from the IdP that is used as the principle string.
 * <p>
 * The token is stateless because it relies on the ShibSP session and simply
 * reads the attributes stored in the ShibSP session.
 *
 * @see IAuthentication
 * @author David Meredith
 */
class IAMAuthToken implements IAuthentication {

    private $userDetails = null;
    private $authorities = array();
    private $principal;

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
        $hostname = $_SERVER['HTTP_HOST']; // don't use $_SERVER['SERVER_NAME'] as this don't support DNS 
        $this->principal = $_SERVER["REMOTE_USER"];
        $this->userDetails = array('AuthenticationRealm' => array('IRIS IAM - OIDC'));
        //print_r($_SERVER);
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
     * Returns true, this token reads the ShibSP session attributes and so
     * does not need to be stateful itself.
     * {@see IAuthentication::isStateless()}
     */
    public static function isStateless() {
        return true;
    }

}
