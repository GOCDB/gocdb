<?php
namespace org\gocdb\security\authentication;

//require_once __DIR__.'/Exceptions/AuthenticationException.php'; 
require_once __DIR__.'/IAuthentication.php'; 


/**
 * Indicates that a class can process a specific IAuthentication implementation. 
 *  
 * @author David Meredith
 */
interface IAuthenticationProvider {

    /**
     * Performs authentication with the same contract as 
     * <code>IAuthenticationManager.authenticate($anIAuthentication)</code>
     * except that the implementation may return null if the AuthenticationProvider is unable to support 
     * authentication of the passed Authentication object. In such a case, 
     * the next AuthenticationProvider that supports the presented IAuthentication implementation will be tried. 
     * 
     * @param IAuthentication $auth Token
     * @return IAuthentication Fully authenticated object including credentials. 
     * @throws AuthenticationException if authentication fails. 
     */
    public function authenticate(IAuthentication $auth) ; 
   

    /*
     * Returns true if this AuthenticationProvider supports the indicated Authentication object.
     * Returning true does not guarantee an AuthenticationProvider will be able to 
     * authenticate the presented IAuthentication instance. It simply 
     * indicates it can support closer evaluation of it. An AuthenticationProvider 
     * can still return null from the authenticate(IAuthentication) method to indicate 
     * another AuthenticationProvider should be tried.
     * <p> 
     * Used by the configured IAuthenticationManager at runtime for selecting an IAuthenticationProvider 
     * that is capable of authenticating the given IAuthentication instance.
     * 
     * @return boolean true if the implementation can more closely evaluate the given IAuthentication object. 
     */
    public function supports(IAuthentication $auth) ; 
}

?>
