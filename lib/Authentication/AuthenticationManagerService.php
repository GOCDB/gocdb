<?php
namespace org\gocdb\security\authentication;
require_once __DIR__.'/SecurityContextService.php'; 
require_once __DIR__.'/Exceptions/AuthenticationException.php'; 
require_once __DIR__.'/Exceptions/BadCredentialsException.php'; 
require_once __DIR__.'/IAuthentication.php'; 
require_once __DIR__.'/IAuthenticationManager.php';
require_once __DIR__.'/ApplicationSecurityConfigService.php'; 

                                 
/**
 * Service providing an authentication entry point for authenticating different <code>IAuthentication</code> 
 * implementations. This service works in conjunction with <code>SecurityContextService</code>. 
 *
 * Largely based on Spring Security. 
 * @link http://static.springsource.org/spring-security Spring Security 
 * 
 * @see IAuthentication
 * @author David Meredth 
 */
class AuthenticationManagerService implements IAuthenticationManager {

    // This class is our equivalent of the Spring ProviderManager.
    
    /**
     * Attempts to authenticate the given <code>IAuthentication<code> object, returning the same 
     * fully populated/updated <code>IAuthentication</code> object (including granted authorities) 
     * if successful. The returned token is usually updated with a IUserDetails 
     * object returned from UserDetailsService.
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
    public static function authenticate(IAuthentication $auth) {
        if($auth == null) {
            throw new BadCredentialsException(null, 'Coding error null IAuthentication given'); 
        }
        // First do an explicit logout to clear the clients security context.  
        SecurityContextService::setAuthentication(null);

        // TODO - Here we could iterate through our configured AuthProviders (in a defined order) 
        // and call each to see if it supports the given IAuthentication 
        // token. If true, attempt authentication. Break/return on the first authProvider
        // that can authenticate the user until all are tried. Requires:  
        // ApplicationSecurityConfigService::getAuthProviders()  
        if(!ApplicationSecurityConfigService::getAuthProvider()->supports($auth)){
            throw new \RuntimeException('No AuthenticationProvider found that supports IAuthentication token'); 
        }
        
        // Authenticate with our configured AuthProvider(s)
        //$updatedAuth = self::getAuthProvider()->authenticate($auth); 
        $updatedAuth = ApplicationSecurityConfigService::getAuthProvider()->authenticate($auth); 
        
        // Update the security context with the returned IAuth 
        if($updatedAuth != null){
            SecurityContextService::setAuthentication($updatedAuth);
            return $updatedAuth; 
        } else {
            throw new AuthenticationException(null, 
                    'Configured AuthProvider failed to return an IAuthentication instance');         
        }
    }

}

?>
