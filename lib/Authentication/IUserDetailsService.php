<?php
namespace org\gocdb\security\authentication;

/**
 * Core interface which loads user-specific data.
 * The interface requires only one read-only method, which simplifies support for new data-access strategies.
 *
 * @author David Meredith
 */
interface IUserDetailsService {

    /**
     * Locates the user based on the given username string.
     * The username string must uniquely identify the user (its format can differ
     * depending on the authentication mechanism e.g. could be a DN string for X.509).
     *
     * @param string $username the user string identifying the user whose data is required.
     * @return IUserDetails implementation (never <code>null</code>)
     * @throws UsernameNotFoundException if the user could not be found or the user has no GrantedAuthority
     */
    public function loadUserByUsername($username);
}

?>
