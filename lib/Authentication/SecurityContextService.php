<?php
namespace org\gocdb\security\authentication;
require_once __DIR__ . '/AuthenticationManagerService.php';
require_once __DIR__ . '/IUserDetails.php';
require_once __DIR__ . '/Exceptions/AuthenticationException.php';
require_once __DIR__ . '/ApplicationSecurityConfigService.php'; 


/**
 * Service to store/fetch the current user's Authentication/security context. 
 * The IAuthentication will be stored and managed in the HTTP session. 
 * This means that re-authentications are not necessary between page requests.
 * You should never need to interact with the HTTP session directly. 
 * <p>
 * This service updates the session id if the session is older than 30 secs to 
 * guard against session fixation attacks. The service never destroys 
 * the session as the it may be in use in other code paths. Rather, this service 
 * creates/destroys the <code>$_SESSION[SecurityContextService::authSessionKey]</code> 
 * variable. 
 * <p>
 * Typical usage is:
 * <pre> 
 * $auth = SecurityContextService::getAuthentication();
 * if($auth == null) {
 *    // Here you could re-direct the user to some form of auth/login page in order  
 *    // to fetch an Auth token from the user, then call: 
 *    AuthenticationManagerService::authenticate($anIAuthenticationObj); 
 * }
 * // An explicit authentication can be achieved using the following code: 
 * SecurityContextService::setAuthentication($anIAuthenticationObj);
 * //An explicit logout (removal of the security context) can be achieved using the following code: 
 * SecurityContextService::setAuthentication(null);
 * </pre>
 * Largely based on Spring Security. 
 * @link http://static.springsource.org/spring-security Spring Security 
 * 
 * @see AuthenticationManagerService
 * @version 0.1 
 * @author David Meredith
 */
class SecurityContextService {

    //private static $authType = 'X509'; //UsernamePassword
    public static $authSessionKey = 'gocdb_IAuthentication_Impl';
    private static $salt = 'secretSaltValue';
    private static $delim = '[splitonthisdelimitervalueplease]';
    private static $debug = false;

    

    /**
     * Obtains the currently authenticated principal or null if no authentication 
     * information is available (e.g. if user cannot be authenticated). 
     * 
     * @return IAuthentication or null if not authenticated
     */
    public static function getAuthentication() {
        $auth = self::getAuthenticationImpl();
        try {
            // Always validate the auth token before returning
            if($auth != null) {
                $auth->validate();
                return $auth;
            }
        } catch (AuthenticationException $ex) {
            // Can be thrown by $auth->validate() if the auth token is no longer 
            // valid. This can occur for example when using pre-auth token and 
            // the client's state changes, e.g. if the browser is closed and 
            // then reopened using a different certificate, the auth token stored 
            // in php _SESSION will no longer be valid (e.g. different cert DN).  
            if (SecurityContextService::$debug) {
                print_r('First validation failure'); 
            }
            // Token is invalid so clear the security context.  
            self::setAuthentication(null); 
            // Try once more to authenticate as the client may be using a different
            // (but valid) pre-auth token.  
            $auth = self::getAuthenticationImpl();
            try {
                // Always validate the auth token before returning
                if($auth != null) {
                    $auth->validate();
                    return $auth;
                }
            } catch(AuthenticationException $e){
                if (SecurityContextService::$debug) {
                    print_r('Second validation failure'.$ex); 
                }    
            }
        }
        return null;
    }
    
    private static function getAuthenticationImpl() {
        if (!ApplicationSecurityConfigService::isPreAuthenticated()) {
            // e.g. Un/Pw.... 
            if (SecurityContextService::$debug){
                echo('debug_0');
            }
            // If this Auth scheme does not support pre-authentication, 
            // attempt to return the Auth token stored in session. If user has
            // not authenticated return null (the webapp will then need to 
            // present a form login page in order to get un/pw and call:
            // AuthenticationManagerService::authenticate($newUnPwAuthToken);
            return SecurityContextService::getHttpSessionAuth();
            
        } else {
            // e.g. X509....
            if (SecurityContextService::$debug){
                echo('debug_1');
            }
            $sessionAuth = SecurityContextService::getHttpSessionAuth();
            if ($sessionAuth == null) {
                if (SecurityContextService::$debug) {
                    echo('debug_2');
                }
                // If session Auth is null, then attempt to automatically authenticate 
                // the user (since we have a pre-auth token, e.g. a cert, we do 
                // not need to present login details as we do if the auth scheme
                // does not support pre-authentication).
                try {
                    // TODO - if we configure multiple pre-auth tokens, cycle 
                    // through the tokens in order and authenticate (either first or all 
                    // depending on strategy used).   
                    // ApplicationSecurityConfigService::getPreAuth_AuthTokens(); 
                    $authImpl = ApplicationSecurityConfigService::getPreAuth_AuthToken(); 
                    if($authImpl == null){
                        throw new \RuntimeException('null returned from ApplicationSecurityConfigService::getPreAuth_AuthToken()');
                    }
                    // Authenticate and return 
                    return AuthenticationManagerService::authenticate($authImpl);
                    
                } catch (AuthenticationException $ex) {
                    // We don't want AuthenticationException being part of this 
                    // methods public API. To be consistent when isPreAuth() == false, 
                    // we log and return null instead.
                    if (SecurityContextService::$debug) {
                        print_r($ex);
                    }
                }
                return null;
            } else {
                // $sessionAuth was fetched from session and is not null 
                if (SecurityContextService::$debug) {
                    echo('debug_3');
                }
                return $sessionAuth;
            }
        }
        return null;
    }

    /**
     * Changes the currently authenticated principal, or removes the authentication information. 
     * @param IAuthentication $auth The new Authentication token, 
     *   or null if no authentication information should be stored. 
     */
    public static function setAuthentication($auth = null) {
        if (session_id() == '') {
            session_start();
        }
            
        if ($auth != null) {
            if (!($auth instanceof IAuthentication)) {
                throw new \RuntimeException('Argument 1 passed to SecurityContextService::setAuthentication() must implement interface IAuthentication');
            }
            // Always unset the password as this auth object may be stored in 
            // e.g. HTTP session and the password MUST be opaque. 
            $auth->eraseCredentials();
            // The object stored in getDetails() may/may-not be an IUserDetails impl, 
            // so check if it is and erase if true:  
            if($auth->getDetails() instanceof IUserDetails){
                $auth->getDetails()->eraseCredentials();  
            }
            // On successful authentication, some custom auth provider implementations 
            // may set the auth->principle object to be a IUserDetails object 
            // therefore we must test for this and clear if true: 
            if($auth->getPrinciple() instanceof IUserDetails){
                $auth->getPrinciple()->eraseCredentials(); 
            }
            // Serialise $auth into a string so that it can be stored in 
            // session (can't use references in session variables). In addition, 
            // save a salted md5 hash of the auth so that we can ensure that the 
            // session has not been tampered with when later callling getAuthentication()
            $serializedAuth = serialize($auth);
            $_SESSION[SecurityContextService::$authSessionKey] =
                    $serializedAuth .
                    SecurityContextService::$delim .
                    md5($serializedAuth . SecurityContextService::$salt);
        } else {
            // Clear the session variable. We can't just call session_destroy, 
            // we need to explicitly clear the session variable. 
            unset($_SESSION[SecurityContextService::$authSessionKey]);
            // Lets not kill the session completely - it may be used for other 
            // session related stuff and we are only interested in the session 
            // 'SecurityContextService::$authSessionKey' var (i.e. our custom security context).  
            //session_destroy();
        }
    }

    /**
     * Fetch IAuthentication impl from HTTP session or null if not present. 
     * 
     * @return IAuthentication or null 
     */
    private static function getHttpSessionAuth() {
        // Start or resume the session.  
        // Returns the session id for the current session or an empty string ("") 
        // if there is no current session (no current session id exists)
        if (session_id() == '') {
            // Create a session or resume the current one 
            // (based on a session identifier passed via a GET or POST request, or passed via a cookie).
            session_start();
        }
        // Deal with Session Fixation attacks: 
        // Maintain a value that will track the last time the session ID was 
        // updated. Here we ensure a new session ID is is generated frequently 
        // (every 30secs) so that the opportunity for an attacker to obtain a 
        // valid session ID is dramatically reduced. 
        if (!isset($_SESSION['generated']) || $_SESSION['generated'] < (time() - 30)) {
            // Replace the current session id with a new one, and keep the current session information
            session_regenerate_id();
            $_SESSION['generated'] = time();
        }
        
        // Get our session variable that serializes the AuthToken
        if (isset($_SESSION[SecurityContextService::$authSessionKey])) {
            if (SecurityContextService::$debug){
                echo('debug_5');
            }

            // Get the session value and split into strings based on our delimiter
            list($serializedAuth, $serializedAuthHash) = explode(
                    SecurityContextService::$delim, $_SESSION[SecurityContextService::$authSessionKey]);

            // Reproduce the salted md5 hash of the Auth token and compare
            if (md5($serializedAuth . SecurityContextService::$salt) == $serializedAuthHash) {
                return unserialize($serializedAuth);
            } else {
                // Lets not kill the session completely - it may be used for other 
                // session related stuff and we are only interested in the session 
                // 'SecurityContextService::$authSessionKey' var (i.e. our custom security context).  
                //session_destroy();
                die('Your session appears to have been tampered with');
            }
        }
        return null;
    }

}

?>
