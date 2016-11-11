<?php
namespace org\gocdb\security\authentication;
/**
 * Provides core user information.
 * <p>
 * Stores user information which is later encapsulated
 * into {@link IAuthentication.php} objects. This allows non-security related user
 * information (such as email addresses, telephone numbers etc) to be stored
 * in a convenient location.
 * <p>
 * Concrete implementations must take particular care to ensure the non-null
 * contract detailed for each method is enforced.
 *
 * Largely based on Spring Security.
 * @link http://static.springsource.org/spring-security Spring Security
 * @author David Meredith
 */
interface IUserDetails {

    /**
     * Returns the username string used to authenticate the user. Cannot return <code>null</code>.
     * @return String The username (never <code>null</code>)
     */
    public function getUsername();

    /**
     * Returns the password used to authenticate the user. Cannot return <code>null</code>.
     * If a password is not required by the given auth mechanism, return an empty string.
     * @return String The password (never <code>null</code>)
     */
    public function getPassword();

    /**
     * Indicates whether the user is enabled or disabled. A disabled user cannot be authenticated.
     * @return <code>true</code> if the user is enabled, <code>false</code> otherwise
     */
    public function isEnabled();

    /**
     * Set the credentials (usually a password) to an empty string.
     */
    public function eraseCredentials();

    /**
     * Returns the authorities granted to the user. Cannot return <code>null</code>.
     * @return array The authorities, sorted by natural key (never <code>null</code>)
     */
    public function getAuthorities();
}

?>
