<?php
namespace org\gocdb\security\authentication;

require_once __DIR__ . '/../IUserDetails.php';

/**
 * A custom Implementation of {@link IUserDetails.php}
 *
 * @author David Meredith
 */
class GOCDBUserDetails implements IUserDetails {

    private $username;
    private $isEnabled;
    private $password = "";
    private $roles;
    private $val;


    public function __construct($username, $isEnabled, $roles, $val, $password="" ) {
        $this->username = $username;
        $this->isEnabled = $isEnabled;
        $this->password = $password;
        $this->roles = $roles;
        $this->val = $val;
    }

    public function getUsername() {
        return $this->username;
    }

    public function isEnabled() {
        return $this->isEnabled;
    }

    public function getPassword() {
        return $this->password;
    }

    public function eraseCredentials() {
        $this->password = "";
    }

    public function getAuthorities(){
        return $this->roles;
    }

    public function getGOCDBCustomVal(){
        return $this->val;
    }
}

?>
