<?php
namespace org\gocdb\services;
require_once __DIR__ . '/../Authentication/SecurityContextService.php';
require_once __DIR__ . '/../Authentication/UserDetails/GOCDBUserDetails.php';
use org\gocdb\security\authentication\SecurityContextService;
/**
 * Description of GocdbAuthContextService
 *
 * @author David Meredith
 */
class GocdbAuthContext {

    public function __construct() {

    }

    /**
     * Get our IAuthentication instance or null if the user is not authenticated.
     * @return \org\gocdb\security\authentication\IAuthentication
     */
    public function getAuthentication(){
        $auth = SecurityContextService::getAuthentication();
        if($auth == null){
            SecurityContextService::setAuthentication(null);
        }
        return $auth;
    }


}

?>
