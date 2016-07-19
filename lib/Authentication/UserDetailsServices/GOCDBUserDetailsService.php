<?php
namespace org\gocdb\security\authentication;
require_once __DIR__ . '/../../Doctrine/entities/User.php';
require_once __DIR__ . '/../../Gocdb_Services/Factory.php';
//require_once __DIR__ . '/../Exceptions/UsernameNotFoundException.php';
//require_once __DIR__ . '/../IUserDetailsService.php';
//require_once __DIR__ . '/../UserDetails/GOCDBUserDetails.php';

/**
 * A IUserDetailService implemenation for GOCDB for querying the Doctrine database for
 * user details.
 *
 * @author David Meredith
 */
class GOCDBUserDetailsService implements IUserDetailsService {

    /**
     * Locates the user based on the given username string.
     * The username string must uniquely identify the user (its format can differ
     * depending on the authentication mechanism e.g. could be a DN string for x509).
     *
     * @param string $username the user string identifying the user whose data is required.
     * @return \IUserDetails implementation (never <code>null</code>)
     * @throws UsernameNotFoundException if the user could not be found or the user has no GrantedAuthority
     */
    public function loadUserByUsername($username) {
        throw new \LogicException('not implemted yet');

        if ($username == null) {
            throw new UsernameNotFoundException(null, 'null username');
            //throw new \RuntimeException('null username');
        }

        $roles = array();
        // We have a choice here depending on our implementation:
        // At this point we know the user has a valid IGTF certificate and therefore
        // we could add a role such as 'ROLE_CERTOWNER.' In doing this, user would
        // have a granted authority (even if they have not registered) and we
        // would not throw a UsernameNotFoundException below. We would thus
        // regard them as authenticated - this is our implementation choice.
        $roles[] = "ROLE_CERTOWNER";

        // Add extra logic to lookup user and assign roles accordingly
        //$Results = get_xml('Get_User_By_DN', array($username));
        $user = \Factory::getUserService()->getUserByPrinciple($username);
        if ($user != null) {
            $roles[] = 'ROLE_REGISTERED_USER';
            //foreach($user->getRoles() as $role){
            //   if($role->getRoleType()->getName() == 'GocdbAdmin'){
            //        $roles[] = 'ROLE_GOCDB_ADMIN';
            //   }
            //}
            // we can add extra roles here....
        }
        // If the user is not found or has no granted authorities then
        // we need to honour the contract of the public API and throw ex.
        if(count($roles)==0){
            throw new UsernameNotFoundException(null, 'username [' . $username . '] not found');
        }
        // return our custom IUserDetails implementation
        $userDetails = new GOCDBUserDetails($username, true, $roles, $user, '');
        return $userDetails;
        //$userDetails = new GOCDBUserDetails($username, true, $roles, null, '');
        //return $userDetails;
    }
}

?>
