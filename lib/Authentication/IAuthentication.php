<?php
namespace org\gocdb\security\authentication;

/**
 * Represents the token for an authentication request or for an authenticated 
 * principal once the request has been processed by the 
 * <code>AuthenticationManager.authenticate(Authentication)</code> method. 
 * <p>
 * Once the request has been authenticated, the IAuthentication will be 
 * stored in the SecurityContext. An explicit authentication can be achieved
 * using the following code: 
 * <code>
 * SecurityContextService.setAuthentication(anIAuthentication);
 * </code>
 * Largely based on Spring Security. 
 * @link http://static.springsource.org/spring-security Spring Security 
 * 
 * @author David Meredith 
 */
interface IAuthentication {
    
   
    /**
     * The identity of the principal being authenticated. 
     * Note, this will vary according to differet authentication mechansisms. 
     * In the case of an authentication request (<code>IAuthenticationManager.authentication($authToken)</code>) 
     * with username and password, this would be the username. For X509 
     * it would be the certificate DN. Callers are expected to populate the 
     * principal for an authentication request (<code>IAuthenticationManager.authentication($authToken)</code>).
     * <p>
     * The <code>IAuthenticationManager</code> implementation will often return 
     * an <code>IAuthentication</code> containing richer information as the principal 
     * for use by the application. Authentication providers may create a <code>IUserDetails</code> 
     * object as the principal after authentication. 
     * 
     * @return object
     */
    public function getPrinciple(); 
    
    /**
     * Get current principal's credentials (usually a password). Never null. When a password is not 
     * used for the chosen auth scheme (e.g. X509), return an empty string. 
     * @return object never null 
     */
    public function getCredentials(); 
    
    /**
     * Sets the current principal's credentails (usually a password) to an empty string. 
     */
    public function eraseCredentials(); 
    
    /**
     * A custom object used to store additional user details.  
     * Allows non-security related user information (such as email addresses, 
     * telephone numbers, IP address, certificate serial number etc) to be stored in a convenient location. 
     * @return Object or null if not used 
     */
    public function getDetails(); 
    
    /** 
     * @see getDetails()
     * @param Object $userDetails
     */
    public function setDetails($userDetails); 

    /**
     * Set by <code>AuthenticationManagerService</code> to indicate the authorities that the principal has been
     * granted. Note that classes should not rely on this value as being valid unless it has been set by a trusted
     * <code>AuthenticationManager</code>.
     *
     * @return array the authorities as strings granted to the principal, or an empty array if the token has not been authenticated.
     * Never null.
     */
    public function getAuthorities(); 
    
    /**
     * Called by <code>AuthenticationManagerService</code> to indicate the authorities that the principal has been
     * granted after authentication. 
     * @param array $authorities as a string array
     */
    public function setAuthorities($authorities); 

   /**
    * Validates the current state of the IAuth instance and is called internally by the framework. 
    * Implementations MUST throw an AuthenticationException if the token's internal state becomes 
    * invalid due to whatever change (required since instances are mutable). 
    * <p>
    * For example, in the case of x509 the client may freely change 
    * their certificate in their browser by clearing the browser ssl 
    * cache and refreshing the page. In this case, the DN may change from the  
    * initial DN used when constructing the object. 
    * 
    * @throws AuthenticationException if authenitcation fails 
    */
    public function validate(); 
}

?>
