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
class ShibAuthToken implements IAuthentication {
    
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
        $hostname = gethostname(); // gocdb-test.esc.rl.ac.uk, goc.egi.eu 
        // specify location of the Shib Logout handler 
        \Factory::$properties['LOGOUTURL'] = 'https://'.$hostname.'/Shibboleth.sso/Logout';
        $idp = isset($_SERVER['Shib-Identity-Provider']) ? $_SERVER['Shib-Identity-Provider'] : '';
        if ($idp == 'https://unity.eudat-aai.fz-juelich.de:8443/saml-idp/metadata' 
                &&  $_SERVER['distinguishedName'] != null){
            $this->principal = $_SERVER['distinguishedName'];
            $this->userDetails = array('AuthenticationRealm' => array('EUDAT_SSO_IDP'));
            return; 
        } else if($idp == 'https://idp.ebi.ac.uk/idp/shibboleth' 
                &&  $_SERVER['eppn'] != null){
            $this->principal = hash('sha256', $_SERVER['eppn']);
            $this->userDetails = array('AuthenticationRealm' => array('UK_ACCESS_FED'));
            return; 
        }
        else if($idp == 'https://aai.egi.eu/proxy/saml2/idp/metadata.php'){
            if( empty($_SERVER['epuid'])){// || empty($_SERVER['displayName']) ){
                die('Did not recieve required attributes from the EGI Proxy Identity Provider to complete authentication, please contact gocdb-admins');
            }
            if(empty($_SERVER['assurance'])){
                die('Did not recieve the required assurance attribute from the EGI Proxy IdP, please contact gocdb-admins');
            }
            if($_SERVER['assurance'] != 'https://aai.egi.eu/LoA#Substantial'){
                 $HTML = '<ul><li>You authenticated to the EGI Identity Provider using a method that provides an inadequate Level of Assurance for GOCDB (weak user verification).</li><li>Login is required with an assurance level of [Substantial].</li><li>To gain access, you will need to login to the Proxy IdP using a scheme that provides [LoA#Substantial].</li><li>Please logout or restart your browser and attempt to login again.</li></ul>';
                 $HTML .= "<div style='text-align: center;'>";
                 $HTML .= '<a href="'.htmlspecialchars(\Factory::$properties['LOGOUTURL']).'"><b><font colour="red">Logout</font></b></a>';
                 $HTML .= "</div>";
                 echo ($HTML);
                 die();
            }
            $this->principal = $_SERVER['epuid'];
            $this->userDetails = array('AuthenticationRealm' => array('EGI Proxy IdP'));
            return;
        }



//        else {
//            die('Now go configure this AuthToken file ['.__FILE__.']');   
//        }
        // if we have not set the principle/userDetails, re-direct to our Discovery Service 
        $target = urlencode("https://" . $hostname . "/portal/");
        header("Location: https://" . $hostname . "/Shibboleth.sso/Login?target=" . $target);
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
     * Returns true, this token reads the ShibSP session attributes and so 
     * does not need to be stateful itself. 
     * {@see IAuthentication::isStateless()} 
     */
    public static function isStateless() {
        return true;         
    }

}
