<?php
namespace org\gocdb\security\authentication;

require_once __DIR__.'/../IAuthenticationProvider.php'; 
require_once __DIR__.'/../IAuthentication.php'; 
require_once __DIR__.'/../Exceptions/AuthenticationException.php'; 
require_once __DIR__.'/../Exceptions/BadCredentialsException.php'; 
require_once __DIR__.'/../Exceptions/UsernameNotFoundException.php'; 
require_once __DIR__.'/../IUserDetails.php'; 
require_once __DIR__.'/../ApplicationSecurityConfigService.php'; 
require_once __DIR__.'/../AuthTokens/X509AuthenticationToken.php'; 


/**
 * The GOCDB Authentication provider.  
 *
 * @author David Meredith 
 */
class GOCDBAuthProvider implements IAuthenticationProvider {
    
    public function authenticate(IAuthentication $auth){
        if($auth == null){
            throw new BadCredentialsException(null, 'Bad credentials - null given'); 
        }
        
        // Perform Authentication: 
        // You may need to customize this logic. In Spring this is absracted 
        // using different ProviderManager implementations that must support:   
        // 'authenticate(IAuthentication)throws AuthenticationException' 
        try { 
            $username = $auth->getPrinciple(); 
            // Spring way...(if $auth.principle was previously updated to be a IUserDetails) 
            //if($username instanceof IUserDetails) $username = $username->getUsername(); 
           
            // Now attempt to load the user's details from the DB
            $userDetails = ApplicationSecurityConfigService::getUserDetailsService()->loadUserByUsername($username);  
        } catch(UsernameNotFoundException $ex){
           throw new AuthenticationException($ex, 'Username not found');  
        }
        // Auth is usually done by comparing principle and password value equality  
        // between the returned $userDetails object and the given $auth token.  
        // Note, getPassword() never returns null, even for auth mechanisms that 
        // don't use a password in which case an empty string is returned. This 
        // allows the same auth logic across different mechanisms (e.g. x509).  
        if($userDetails->getUsername() == $auth->getPrinciple() && 
                $userDetails->getPassword() == $auth->getCredentials()){
          
           // Spring way...(most spring auth providers update (re-set) the $auth->principle 
           // to be a IUserDetails implementation, e.g.  
           //$auth->setPrinciple($userDetails);  
            
           //$auth->setDetails($userDetails);
           // set UserDetails as Doctrine 'User' entity or null
           $auth->setDetails($userDetails->getGOCDBCustomVal());
           $auth->setAuthorities($userDetails->getAuthorities()); 
           return $auth; 
        }
        // We didn't manage to authenticate the user, so throw exception 
        throw new AuthenticationException(null, 'Authentication failed');  
    }


    public function supports(IAuthentication $auth){
        if($auth instanceof X509AuthenticationToken){
            return true; 
        }
        return false; 
    }

    
}

?>
