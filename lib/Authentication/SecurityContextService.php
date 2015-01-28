<?php
namespace org\gocdb\security\authentication;
include_once __DIR__.'/_autoload.php';
//require_once __DIR__ . '/AuthenticationManagerService.php';
//require_once __DIR__ . '/IUserDetails.php';
//require_once __DIR__ . '/Exceptions/AuthenticationException.php';
//require_once __DIR__ . '/ApplicationSecurityConfigService.php'; 


/**
 * Service to fetch the current user's IAuthentication token. 
 * The token MAY be stored and managed in the HTTP session (provided 
 * it is not stateless and the configuration allows session-creation). 
 * Cached tokens prevent the need to re-authenticate across page requests.
 * You should never need to interact with the HTTP session directly. 
 * <p>
 * This service updates the session id if the session is older than 30 secs to 
 * guard against session fixation attacks. The service never destroys 
 * the session as the it may be in use in other code paths. Rather, this service 
 * creates/destroys the <code>$_SESSION[SecurityContextService::authSessionKey]</code> 
 * variable. 
 * <p>
 * Typical usage is:
 * <code> 
 * $auth = SecurityContextService::getAuthentication();
 * if($auth == null) {
 *    // Here you could re-direct the user to some form of auth/login page in order  
 *    // to create an Auth token from the user's input:  
 *    $token = new org\gocdb\security\authentication\UsernamePasswordAuthenticationToken("test", "test");
 *    // then call: 
 *    AuthenticationManagerService::authenticate($token); 
 * }
 * </code>
 * 
 * An explicit authentication (or logout) can be achieved using the following: 
 * <code>
 * SecurityContextService::setAuthentication($anIAuthenticationObj); //authenticate
 * SecurityContextService::setAuthentication(null); // logout
 * </code>
 * 
 * Inspired by Spring Security. 
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
    //public static $debug = false;

    

    /**
     * Invoke the token resolution process to obtain the auth token for the 
     * current user/request, or null if an authentication token can't be resolved. 
     * <p>
     * The token resolution process is as follows: 
     * <ol>
     *    <li>Return the token previously stored in http session if available 
     *    (the security configuration must allow session-creation).</li>
     *    <li>If no token can be returned from session, iterate the configured 
     *    pre-authenticating tokens in order, and return the first token that 
     *    successfully authenticates the user. </li>
     *    <li>If no token can be automatically created and successfully authenticated, 
     *    return null</li>
     * </ol>
     * 
     * @return IAuthentication or null if not authenticated
     */
    public static function getAuthentication() {
        // 1) If this configuration allows session creation, try to extract token 
        // from session first 
        if( ApplicationSecurityConfigService::getCreateSession() ) {  
            $authToken = SecurityContextService::getHttpSessionAuth();
            if ($authToken != null) {
                // if $scheme param is requested, first check that auth supports 
                // requested scheme. If not, continue on with below
                // if($authToken->supports('scheme'){
                    // since we are returning a cached credential, we must 
                    // call validate first
                    $authToken->validate();
                    return $authToken;
                //}
            }
        }
        
        // 2) Try to authenticate with available pre-Auth tokens (if any are configured) 
        // Iterate configured PRE_AUTH tokens in order (skip those that are not preAuthenticating)  
        // If specific scheme is requested, skip those that don't support scheme 
        // Attempt to create new pre-Auth token and authenitcate  
        $authTokenClassList = ApplicationSecurityConfigService::getAuthTokenClassList();
        foreach ($authTokenClassList as $tokenClass){
            // preAuthenitcated token only 
            if(call_user_func($tokenClass.'::isPreAuthenticating')){
                // call_user_func($tokenClass.'::supports')
                $authImpl = new $tokenClass; 
                //echo 'created auth token [' . get_class($authImpl).']'; 
                try {
                    // Iterate all configured AuthProviders  
                    // and attempts to authenticate the token and if authenticated ok, calls 
                    // SecurityContextService::setAuthentication($auth)
                    $returnToken = AuthenticationManagerService::authenticate($authImpl); 
                    if($returnToken != null){
                        return $returnToken; 
                    }
                }catch (AuthenticationException $ex){
                    // We don't want AuthenticationException being part of this 
                    // methods public API, but continue to next token for auth attempt.  
                    // TODO - log failed token auth attempt 
                }
            }
        }
        
        return null;  
    }

    /**
     * Changes the currently authenticated principal, or removes the authentication 
     * information from session if null is given. 
     * 
     * @param IAuthentication $auth The new Authentication token, 
     *   or null if http sessino token should be cleared.  
     */
    public static function setAuthentication($auth = null) {
        // If can't create session, return - don't touch/create the session !  
        if( ApplicationSecurityConfigService::getCreateSession() === FALSE ) { 
            return; 
        }
         
        // Only set the auth token in session if the token is not stateless ! 
        if ($auth != null && !$auth->isStateless()) {
            
            if (!($auth instanceof IAuthentication)) {
                throw new \RuntimeException('Argument 1 passed to '
                        . 'SecurityContextService::setAuthentication() must '
                        . 'implement interface IAuthentication');
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
            
            if(!self::is_session_started()){
                // You must call session_start() before you'll have access to any variables in $_SESSION.
                session_start();
            }
            $_SESSION[SecurityContextService::$authSessionKey] =
                    $serializedAuth .
                    SecurityContextService::$delim .
                    md5($serializedAuth . SecurityContextService::$salt);
        }
        
        if($auth == null){
            if (!self::is_session_started()){
                session_start(); 
            }
            // Clear the session variable. We can't just call session_destroy, 
            // we need to explicitly clear the session variable. 
            if(isset($_SESSION[SecurityContextService::$authSessionKey])){
                unset($_SESSION[SecurityContextService::$authSessionKey]);
            }
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
        // If can't create session, return null - don't touch/create a session !  
        if( ApplicationSecurityConfigService::getCreateSession() === FALSE ) { 
            return null; 
        }
       
        // I want to check for a php session without starting one - but it seems 
        // i can only resume a session or start a new one (either way, a session is started!) 
        //http://stackoverflow.com/questions/13114185/how-can-you-check-if-a-php-session-exists
        //http://stackoverflow.com/questions/1780736/checking-for-php-session-without-starting-one?rq=1
        if (!self::is_session_started()) {
        //if(!isset($_SESSION)){
        //if(!isset($_COOKIE)){
        //if(!isset($_REQUEST['PHPSESSID'] )){
        //if(!isset($_COOKIE[session_name()])){
            // You must call session_start() before you'll have access to variables in $_SESSION.
            // This always results in a session being started (or existing 
            // session is resumed). This is not ideal - i want to check for a 
            // php session without starting one - but this don't seem possible. 
            session_start();
            //echo 'here, why? - I know session cookie exists! but apparently session has not been started?'; 
            //return null; 
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
            //if (SecurityContextService::$debug){
            //    echo('debug_5');
            //}

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



    /**
     * @return bool
     */
    private static function is_session_started() {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }

}

?>
