<?php
/*______________________________________________________
 *======================================================
 * File: Get_User_Principle.php
 * Author: David Meredith
 * Description: Returns the user's principle ID string or AuthToken for the user that's currently
 *				connected (for x509 this is a DN).
 *
 * License information
 *
 * Copyright 2013 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 /*====================================================== */

require_once __DIR__ . '/../../../lib/Authentication/_autoload.php';
/**
 * Holds the principle string of the authenticated user.
 * <p>
 * Saving the principle string in a singleton after authentication
 * allows fast lookups during current request processing. Calling code such as
 * {@link Get_User_Principle()} can callout for the stored value rather than
 * running through the authentication process again for each invocation
 * (for to the current request only).
 */
class MyStaticPrincipleHolder {
    private static $_instance;
    private $principleString = null;
    private function __construct() {
    }
    private function __clone() {
       // defining an empty clone closes small loophole in PHP that could make
       // a copy of the object and defeat singletone responsibility
    }
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function getPrincipleString(){
        return $this->principleString;
    }
    public function setPrincipleString($principleString){
        $this->principleString = $principleString;
    }
}
/**
 * Holds the AuthToken of the authenticated user.
 * <p>
 * Saving the token in a singleton after authentication
 * allows fast lookups during current request processing. Calling code such as
 * {@link Get_User_AuthToken()} can callout for the stored value rather than
 * running through the authentication process again for each invocation
 * (applies to the current request only).
 */
class MyStaticAuthTokenHolder {
    private static $_instance;
    private $authToken = null;
    private function __construct() {
    }
    private function __clone(){
       // defining an empty clone closes small loophole in PHP that could make
       // a copy of the object and defeat singletone responsibility
    }
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function getAuthToken(){
        return $this->authToken;
    }
    public function setAuthToken($authToken){
        $this->authToken = $authToken;
    }
}

/**
 * Get the IAuthenticationToken for the user.
 * <p>
 * Called from the portal to allow authentication.
 * This method serves as the global integration point for all authentication requests.
 * If you intend to support a different authentication mechanism, you will need
 * to modify this method to support your chosen authentication scheme or call another version.
 *
 * @return \org\gocdb\security\authentication\IAuthenticationToken or null if can't authenticate request
 */
function Get_User_AuthToken(){
    // The token may have already been set in the static holder,
    // if true/not-null, then return rather than going through slower auth process again
    if(MyStaticAuthTokenHolder::getInstance()->getAuthToken() != null){
        return MyStaticAuthTokenHolder::getInstance()->getAuthToken();
    }
    // Token has not yet been stored for the current request,
    // therefore we need to authenticate.
    $fwMan = \org\gocdb\security\authentication\FirewallComponentManager::getInstance();
    $firewallArray = $fwMan->getFirewallArray();
    /* @var $firewall \org\gocdb\security\authentication\IFirewallComponent */
    $firewall = $firewallArray['fwC1']; // select which firewall component you need
    $auth = $firewall->getAuthentication();  // invoke token resolution process
    if ($auth != null) {
        $principleString = $auth->getPrinciple();
        // update the static holder so we can quickly return the token
        // for the current request on repeat callouts to Get_User_AuthToken (is quicker than authenticating again).
        MyStaticPrincipleHolder::getInstance()->setPrincipleString($principleString);
        MyStaticAuthTokenHolder::getInstance()->setAuthToken($auth);
        return $auth;
    }
    return null;
}

/**
 * Get the user's principle string (x509 DN from certificate or from SAML attribute).
 * <p>
 * Called from the portal to allow authentication.
 * This method serves as the global integration point for all authentication requests.
 * If you intend to support a different authentication mechanism, you will need
 * to modify this method to support your chosen authentication scheme or call another version.
 *
 * @return string or null if can't authenticate request
 */
function Get_User_Principle(){
    // The principle may have already been set in the static holder,
    // if true/not-null, then return rather than going through slower auth process again
    if(MyStaticPrincipleHolder::getInstance()->getPrincipleString() != null){
        return MyStaticPrincipleHolder::getInstance()->getPrincipleString();
    }

    // Principle has not yet been stored for the current request,
    // therefore we need to authenticate.
    $fwMan = \org\gocdb\security\authentication\FirewallComponentManager::getInstance();
    $firewallArray = $fwMan->getFirewallArray();
    /* @var $firewall \org\gocdb\security\authentication\IFirewallComponent */
    $firewall = $firewallArray['fwC1']; // select which firewall component you need
    $auth = $firewall->getAuthentication();  // invoke token resolution process
    if ($auth != null) {
        $principleString = $auth->getPrinciple();
        // update the static holder so we can quickly return the principle
        // for the current request on repeat callouts to Get_User_Principle (is quicker than authenticating again).
        MyStaticPrincipleHolder::getInstance()->setPrincipleString($principleString);
        MyStaticAuthTokenHolder::getInstance()->setAuthToken($auth);

        // Is user registered/known in the DB? if true, update their last login time
        // once for the current request.
        $user = \Factory::getUserService()->getUserByPrinciple($principleString);
        if($user != null){
            \Factory::getUserService()->updateLastLoginTime($user);
        }
        return $principleString;
    }
    return null;
}

/**
 * Get the DN from an x509 cert or null if a user certificate can't be loaded.
 * Called from the PI to authenticate requests using certificates only.
 * @return string or null if can't authenticate request
 */
function Get_User_Principle_PI() {
    $fwMan = \org\gocdb\security\authentication\FirewallComponentManager::getInstance();
    $firewallArray = $fwMan->getFirewallArray();
    try {
       $x509Token = new org\gocdb\security\authentication\X509AuthenticationToken();
       $auth = $firewallArray['fwC1']->authenticate($x509Token);
       return $auth->getPrinciple();
    } catch(org\gocdb\security\authentication\AuthenticationException $ex){
       // failed auth, so return null and let calling page decide to allow
       // access or not (some PI methods don't need to be authenticated with a cert)
    }
    return null;
}




/*function Get_User_Principle_back()
{
    // Return hard wired user's principle string (DN) e.g. for testing
    // =======================================================
    //return '/C=UK/O=eScience/OU=CLRC/L=DL/CN=david meredith';

    // Check if an authentication token has been set in the SecurityContext class
    // by higher level code, eg Symfony Security which provides a Firewall component
    // may have been used to intercept the HTTP request and authenticate the
    // user (using whatever auth scheme was configured in the Firewall). A
    // Symfony controller can then subsequently set the token in the SecurityContext
    // before invoking the GOCDB code.
    // =======================================================
    require_once __DIR__.'/../../../lib/Gocdb_Services/SecurityContextSource.php';
    if(\SecurityContextSource::getContext() != null){
       $token = \SecurityContextSource::getContext()->getToken();
       return str_replace("emailAddress=", "Email=", $token->getUser()->getUserName());
    }

    // ================Use x509 Authentication=======================
    //if(!isset($_SERVER['SSL_CLIENT_CERT']))
    //	return "";
    //$Raw_Client_Certificate = $_SERVER['SSL_CLIENT_CERT'];
    //$Plain_Client_Cerfificate = openssl_x509_parse($Raw_Client_Certificate);
    //$User_DN = $Plain_Client_Cerfificate['name'];
    // harmonise display of the "email" field that can be different depending on
    // used version of SSL
    //$User_DN = str_replace("emailAddress=", "Email=", $User_DN);
    //return $User_DN;
    if (isset($_SERVER['SSL_CLIENT_CERT'])) {
        $Raw_Client_Certificate = $_SERVER['SSL_CLIENT_CERT'];
        if (isset($Raw_Client_Certificate)) {
            $Plain_Client_Cerfificate = openssl_x509_parse($Raw_Client_Certificate);
            $User_DN = $Plain_Client_Cerfificate['name'];
            if (isset($User_DN)) {
                // harmonise "email" field that can be different depending on version of SSL
                $dn = str_replace("emailAddress=", "Email=", $User_DN);
                if ($dn != null && $dn != '') {
                    return $dn;
                }
            }
        }
    }


    // Fall back to try saml authentication (simplesaml)
    // =======================================================
    if(false){ // disable by default - to use saml requires install of simplesamlphp and config below
        require_once('/var/simplesamlphp/lib/_autoload.php');
        $as = new SimpleSAML_Auth_Simple('default-sp');
        $as->requireAuth();
        \Factory::$properties['LOGOUTURL'] = $as->getLogoutURL('https://gocdb-test.esc.rl.ac.uk');
        $attributes = $as->getAttributes();
        if(!empty($attributes)){
            //return $attributes['eduPersonPrincipalName'][0];
            $dnAttribute = $attributes['urn:oid:1.3.6.1.4.1.11433.2.2.1.9'][0];
            if(!empty($dnAttribute)){
                return str_replace("emailAddress=", "Email=", $dnAttribute);
            } else {
                die('Did not retrieve a valid certificate DN from identify provider - your SSO '
                        . 'account needs to be associated with a certificate to login via this route');
            }
        }
    }

    // Couldn't authetnicate the user, so finally return null
    return null;
}*/



?>
