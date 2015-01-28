<?php
namespace org\gocdb\security\authentication;

include_once __DIR__.'/_autoload.php';
//require_once __DIR__.'/AuthProviders/GOCDBAuthProvider.php';
//require_once __DIR__.'/UserDetailsServices/GOCDBUserDetailsService.php';
//require_once __DIR__.'/AuthTokens/X509AuthenticationToken.php';
//require_once __DIR__.'/AuthTokens/SimpleSamlPhpAuthToken.php';
//require_once __DIR__.'/AuthProviders/SampleAuthProvider.php';

/**
 * Service class to return the different implementations of the core abstractions. 
 * If you require different auth mechanisms, then you will need to update the 
 * class factory methods to return the correct implementations.  
 *
 * @author David Meredith
 */
class ApplicationSecurityConfigService {

    /**
     * Return a list of <code>IAuthenticationProvider</code> implementations. 
     * @return array 
     */
    public static function getAuthProviders(){
        $providerList = array(); 
        $providerList[] = new GOCDBAuthProvider(); 
        //$providerList[] = new SampleAuthProvider(); 
        return $providerList; 
    }


    /**
     * Return the <code>IUserDetailsService</code> implementation.  
     * @return IUserDetailsService implementation 
     */
    public static function getUserDetailsService(){
        return new GOCDBUserDetailsService();  
    }
 
    /**
     * Return an array of supported <code>IAuthentication</code> fully qualified token class names.
     * <p>
     * The class names are used to dynamically build preAuth class instances during 
     * the token resolution process. Only preAuthenticating tokens are automatically 
     * created during token resolution. Ordering is significant - tokens are 
     * created in the order they appear in the array. 
     * 
     * @return array of class name strings. 
     */ 
    public static function getAuthTokenClassList(){
        $tokenClassList = array(); 
        $tokenClassList[] = 'org\gocdb\security\authentication\X509AuthenticationToken'; 
        $tokenClassList[] = 'org\gocdb\security\authentication\SimpleSamlPhpAuthToken'; 
        //$tokenClassList[] = 'org\gocdb\security\authentication\UsernamePasswordAuthenticationToken'; 
        return $tokenClassList; 
    }

    /**
     * Allow HTTP session creation for the security context. 
     * <p>
     * If true, <code>IAuthentication</code> tokens may be stored
     * in the session (provided the token declares that it can be 
     * persisted with <code>$token.isStateless() == false)</code>. 
     * <p>
     * If true, stateful tokens can be stored in HTTP session for 
     * subsequent retrieval across page requests (e.g. a username/pw token). 
     * <p>
     * Typically, REST style applications are stateless using only stateless auth tokens, 
     * while portals are normally stateful requiring session tracking. 
     * 
     * @return boolean
     */
    public static function getCreateSession(){
        return false; 
    }
   
}

?>
