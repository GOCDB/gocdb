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
 * Requires install of SimpleSamlPhp lib before use. 
 * You will probably need to modify this class for the SAML attribute 
 * that is used as the principle string. 
 * 
 * @author David Meredith 
 */
class SimpleSamlPhpAuthToken implements IAuthentication {
 
    private $userDetails = null;
    private $authorities;
   

    public function __construct() {
          
    }

    
    public function eraseCredentials() {
        
    }

    public function getAuthorities() {
       return $this->authorities;  
    }

    /**
     * @return string An empty string as passwords are not used in this token. 
     */
    public function getCredentials() {
        return "";
    }

    /**
     * A custom object used to store additional user details.  
     * Allows non-security related user information (such as email addresses, 
     * telephone numbers etc) to be stored in a convenient location. 
     * @return Object or null if not used 
     */
    public function getDetails() {
        return $this->userDetails;
    }

    public function getPrinciple() {
        if (true) {  
            require_once('/var/simplesamlphp/lib/_autoload.php');
            $as = new \SimpleSAML_Auth_Simple('default-sp');
            $as->requireAuth();
            \Factory::$properties['LOGOUTURL'] = $as->getLogoutURL('https://'.  gethostname());
            $attributes = $as->getAttributes();
            if (!empty($attributes)) {
                //return $attributes['eduPersonPrincipalName'][0];
                $dnAttribute = $attributes['urn:oid:1.3.6.1.4.1.11433.2.2.1.9'][0];
                if (!empty($dnAttribute)) {
                    return str_replace("emailAddress=", "Email=", $dnAttribute);
                } else {
                    //die('Did not retrieve a valid certificate DN from identify provider - your SSO '
                    //        . 'account needs to be associated with a certificate to login via this route');
                    return null; 
                }
            }
        }
    }

    public function setAuthorities($authorities) {
       $this->authorities = $authorities;  
    }

    /**
     * @see getDetails()
     * @param Object $userDetails
     */
    public function setDetails($userDetails) {
        $this->userDetails = $userDetails;
    }

    public function validate() {
        
    }

    public static function isPreAuthenticating() {
        return true;         
    }

    public static function isStateless() {
        return true;         
    }

}
