<?php
/*______________________________________________________
 *======================================================
 * File: Get_User_Principle.php
 * Author: David Meredith
 * Description: Returns the user's principle ID string or AuthToken for the user that's currently
 *				connected (for X.509 this is a DN).
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

        $authToken = validateRequest($auth, $principleString, true);

        if ($authToken !== null) {
            return $authToken;
        }
    }

    // We don't want the portal to be exposed without authentication (even
    // though no actual info is displayed to an unauthenticated user),
    // so if we have not set the principle/userDetails,
    // re-direct to our Discovery Service.
    redirectUserToDiscoveryPage();
}

/**
 * Get the auth type for the user.
 * @return string or null if can't authenticate request */
function Get_User_AuthType() {
    $authType = null;
    $auth = Get_User_AuthToken();
    if ($auth !== null) {
        $authType = $auth->getDetails()['AuthenticationRealm'][0];
    }
    return $authType;
}

/**
 * Get the user's principle string (X.509 DN from certificate or from SAML attribute).
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

    if ($auth !== null) {
        $principleString = $auth->getPrinciple();

        $authUserPrinciple = validateRequest($auth, $principleString, false);

        if ($authUserPrinciple !== null) {
            return $authUserPrinciple;
        }
    }

    if(session_start() === PHP_SESSION_NONE) {
        session_start();
    }

    // `SSL-Retry_login` is required to end the recursive calls to /portal.
    $_SESSION['SSL-Retry_login'] = true;

    // We don't want the portal to be exposed without authentication (even
    // though no actual info is displayed to an unauthenticated user),
    // so if we have not set the principle/userDetails,
    // re-direct to our Discovery Service.
    redirectUserToDiscoveryPage();
}

/**
 * Get the DN from an X.509 cert, Principle from oidc token, or null if neither can be loaded.
 * Called from the PI to authenticate requests using certificates or oidc.
 * @return string or null if can't authenticate request
 */
function Get_User_Principle_PI() {
    $fwMan = \org\gocdb\security\authentication\FirewallComponentManager::getInstance();
    $firewallArray = $fwMan->getFirewallArray();
    try{
       $x509Token = new org\gocdb\security\authentication\X509AuthenticationToken();
       $auth = $firewallArray['fwC1']->authenticate($x509Token);
       return $auth->getPrinciple();
    } catch(org\gocdb\security\authentication\AuthenticationException $ex){
       // failed auth, so attempt OIDC auth
        try{
            $token = new org\gocdb\security\authentication\IAMAuthToken();
            $auth = $firewallArray['fwC1']->authenticate($token);
            return $auth->getPrinciple();
        } catch(org\gocdb\security\authentication\AuthenticationException $ex){
       // failed auth, so return null and let calling page decide to allow
       // access or not (some PI methods don't need to be authenticated with a cert)
        }
    }

    // Returning null here is necessary, because parts of the API are exposed
    // publicly, without authentication.
    return null;
}

/*
 * Prevent the current page from being loaded and redirect the user
 * to the IdP discovery page (a.k.a the landing page).
 */
function redirectUserToDiscoveryPage()
{
    $url = \Factory::getConfigService()->getServerBaseUrl();
    header("Location: " . $url);
    die();
}

/**
 * Helper method to return the user principle string or authToken, if allowed.
 *
 * The authentication token object.
 * @param $auth \org\gocdb\security\authentication\IAuthenticationToken
 *
 * The user's principle string (X.509 DN or SAML attribute).
 * @param $principleString string
 *
 * If `$needAuthTokenOnly` is set to `true` -> It returns authToken
 * @param $needAuthTokenOnly bool 
 *
 * The user's principle string or
 * The IAuthenticationToken for the user or
 * NULL if processing fails.
 * @return string|\org\gocdb\security\authentication\IAuthenticationToken|null
 */
function validateRequest($auth, $principleString, $needAuthTokenOnly)
{
    $serv = \Factory::getUserService();

    // Get user by searching user identifiers
    $user = $serv->getUserByPrinciple($principleString);

    // Get type of auth logged in with (e.g., X.509)
    $authType = $auth->getDetails()['AuthenticationRealm'][0];

    // Admin's are allowed to use IGTF ?
    $canAdminAccessUsingIGTF = \Factory::getConfigService()->isAdminAllowedToUseIGTF();

    /**
     * Check if user exists and is either an admin who is allowed to use IGTF
     * or user is NOT using X.509 authentication.
     */
    if (!is_null($user)) {
        if (
            ($canAdminAccessUsingIGTF && $user->isAdmin())
            || $authType !== 'X.509'
        ) {
            /**
             * Update the static holder with the principle string
             * and auth token for quick retrieval.
             */
            MyStaticPrincipleHolder::getInstance()->setPrincipleString(
                $principleString
            );
            MyStaticAuthTokenHolder::getInstance()->setAuthToken($auth);

            if ($needAuthTokenOnly) {
                return $auth;
            }

            /**
             * If the user is registered/known in the DB,
             * update their last login time.
             */
            $serv->updateLastLoginTime($user);

            return $principleString;
        }
    }

    return null;
}

?>
