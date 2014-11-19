<?php
namespace org\gocdb\security\authentication;

require_once __DIR__.'/AuthProviders/GOCDBAuthProvider.php';
require_once __DIR__.'/UserDetailsServices/GOCDBUserDetailsService.php';
require_once __DIR__.'/AuthTokens/X509AuthenticationToken.php';
require_once __DIR__.'/AuthProviders/SampleAuthProvider.php';

/**
 * Service class to return the different implementations of the core abstractions. 
 * If you require different auth mechanisms, then you will need to update the 
 * class factory methods to return the correct implementations.  
 * <p>
 * You should not need to modify the core Service classes in the above package 
 * or the core inteface abstractions. 
 *
 * @author David Meredith
 */
class ApplicationSecurityConfigService {

    /**
     * Get an instance of the supported <code>IAuthenticationProvider</code> implementation. 
     * @return IAuthenticationProvider implementation object 
     */
    public static function getAuthProvider(){
        return new GOCDBAuthProvider();  

        // To use the SampleAuthProvider, comment out above and use below. 
        // Also set isPreAuthenticated() to return false.   
        //return new SampleAuthProvider(); 
    }


    /**
     * Get an instance of the supported <code>IUserDetailsService</code> implementation.  
     * @return IUserDetailsService implementation object 
     */
    public static function getUserDetailsService(){
        return new GOCDBUserDetailsService();  
    }
 
    /**
     * Specify whether pre-authentication is is supported by the configured IAuth token (or not). 
     * <p>
     * X509 is an example of a pre-authentication token - the server must establish
     * that the user has provided a valid and trustworthy certificate before reaching the app.
     * (Note, client is authenticated but NOT authorized - this requires subseqent 
     * authZ to determine if that certificate DN is known or not). 
     * <p>
     * An exmple of isPreAuthenticated() == false is un/pw form based login as 
     * the server performs no pre-auth and the app is solely responsible for authentication. 
     * <p>
     * Important: NO subsequent authorization descision should be implemented here, 
     * that should be left to the <code>AuthenticationManager.authenticate(anIAuthentication)</code>
     * method and subsequent authZ code. 
     * 
     * @return boolean True or False
     */ 
    public static function isPreAuthenticated(){
        // TODO - here we should cycle through our configured auth tokens and return true 
        // if any is a pre-auth token. Should then be renamed to isPreAuthTokenAvailable();  
        return true; 
        //return false; 
    }

    /**
     * If we are supporing pre-auth as defined by <code>isPreAuthenticated()</code>, 
     * create and return a new instance of the <code>IAuthentication</code> implementation. 
     * <b>Impotant:</b> this simply returns a new/empty instance - the object has 
     * not been passed to authenticate and no authentication has occurred yet. 
     *    
     * @return IAuthentication implementation object 
     */
    public static function getPreAuth_AuthToken() {
        if(!self::isPreAuthenticated()){
            throw new \RuntimeException('Invalid state - preAuthenticated is false. 
                This function should only be called when preAuthenticated is true.'); 
        }
        // TODO - here we should cycle through our configured auth tokens and 
        // return the all configured pre-auth tokens in order. This method should 
        // then be called getPreAuth_AuthTokens(). 
        return new X509AuthenticationToken();
    }

   
}

?>
