<?php
namespace org\gocdb\security\authentication;

//require_once __DIR__.'/Exceptions/AuthenticationException.php'; 
require_once __DIR__.'/IAuthentication.php'; 


/**
 * Processes an Authentication request.
 *  
 * @author David Meredith
 */
interface IAuthenticationManager {

    /**
     * Attempts to authenticate the given <code>IAuthentication<code> object, returning the same 
     * fully populated/updated <code>IAuthentication</code> object (including granted authorities) 
     * if successful. The returned token is usually updated with a IUserDetails 
     * object returned from UserDetailsService. The manager will iterate all the 
     * configured IAuthenticationProviderS in an attempt to authenticate the given 
     * IAuthentication token.
     * <p> 
     * If the user can't be authenticated, an AuthenticationException is thrown.   
     * A BadCredentialsException (which extends AuthenticationException) must be 
     * thrown if incorrect credentials are presented as an AuthenticationManager must always test credentials.
     * <p>
     * On successful authentication <code>SecurityContextService::setAuthentication($auth);</code> 
     * is called to (re)persist the auth token in the SecurityContext. 
     * 
     * @param IAuthentication $auth Token
     * @return IAuthentication Fully populated Auth token on successful authentication. 
     * @throws AuthenticationException if authentication fails. 
     */
    public static function authenticate(IAuthentication $auth) ; 
   

}

?>
