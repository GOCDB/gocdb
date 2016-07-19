<?php
namespace org\gocdb\security\authentication;

/**
 * A token for a) authenticating the initial request or b) to represent an
 * authenticated principal after the request has been processed by
 * <code>AuthenticationManager.authenticate(Authentication)</code>.
 * <p>
 * An explicit authentication request can be executed using the following:
 * <code>SecurityContextService.setAuthentication(anIAuthentication);</code>.
 * Once the request has been authenticated, the token may be optionally
 * stored in the http session's SecurityContext.
 *
 * Inspired by Spring Security.
 * @link http://static.springsource.org/spring-security Spring Security
 *
 * @author David Meredith
 */
interface IAuthentication {

    /**
     * Determins if this token can be stored in the http session.
     * If true, the token can be stored in http session and re-used across page
     * requests. If false, then the token will not be stored in session.
     * @return boolean True or False
     */
     public static function isStateless();

    /**
     * Does this token support a 'pre-authentication' scenario.
     * Pre-auth is when the user can be authenticated by some external system
     * allowing the tokens to be automatically created during the token
     * resolution process for automatic submission to
     * <code>AuthenticationManager.authenticate(Authentication)</code>.
     * <p>
     * X509 is an example of a pre-authentication token - the server establishes
     * that the user has provided a valid and trustworthy certificate before
     * reaching the underlying app (which means the token can be automatically
     * created during token resolution and authenticated).
     * <p>
     * An exmple of a non pre-auth token is un/pw - the token will not be automatically
     * created during the token resolution process and must be created in client
     * code and and submitted to <code>AuthenticationManager.authenticate(IAuthentication)</code>.
     *
     * @return boolean True or False
     */
     public static function isPreAuthenticating();

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
    * Validates the current state of the IAuth instance and is called internally
    * by the framework when returning credentials that have been cached in the
    * security context's http session.
    * Implementations MUST throw an AuthenticationException if the token's internal state becomes
    * invalid due to whatever change (required since instances are mutable).
    * <p>
    * For example, in the case of a cached x509 token, the client may freely change
    * their certificate in their browser by clearing the browser ssl
    * cache and refreshing the page. In this case, the DN may change from the
    * initial DN used when constructing the token.
    *
    * @throws AuthenticationException if authenitcation fails
    */
    public function validate();
}

?>
