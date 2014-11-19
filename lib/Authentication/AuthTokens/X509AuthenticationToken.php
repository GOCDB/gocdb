<?php
namespace org\gocdb\security\authentication;

require_once __DIR__ . '/../IAuthentication.php';

/**
 * An implementation of <code>IAuthentication</code> for use with X509 certificates. 
 *
 * @see IAuthentication
 * @author David Meredith 
 */
class X509AuthenticationToken implements IAuthentication {

    private $userDetails = null;
    private $authorities;
    private $initialDN = null;
    //private $principle = null;

    public function __construct() {
        $this->initialDN = $this->getDN();
        //$this->principle = $this-initialDN; 
    }

    

    /**
     * @return string An empty string as passwords are not used in X509. 
     */
    public function getCredentials() {
        return "";
    }

    /**
     * Does nothing, passwords not ussed in X509. 
     */
    public function eraseCredentials() {
        // do nothing, password not used for X509
    }

    /**
     * Return the user's DN string from the client's certificate stored in their 
     * browser. The DN is fetched once and cached on object creation. Subsquent 
     * invocations return the cached value.  
     *  
     * @return String Certifiate DN. 
     * @throws \RuntimeException if DN can't be extracted. 
     */
    public function getPrinciple() {
        return $this->initialDN;
        // return $this->principle; 
    }
    /*
     public function setPrinciple($principle){
        $this->principle = $principle; 
     }  
     */

    public function validate() {
        // if current DN is not the same as intial DN, if not raise hue and cry ! 
        if (strcmp($this->initialDN, $this->getDN()) != 0) {
            throw new AuthenticationException(null, 'Invalid state, DN is now different');
        }
    }

    private function getDN() {
        if (!isset($_SERVER['SSL_CLIENT_CERT'])) {
            throw new \RuntimeException('Invalid state');
        }
        $Raw_Client_Certificate = $_SERVER['SSL_CLIENT_CERT'];
        $Plain_Client_Cerfificate = openssl_x509_parse($Raw_Client_Certificate);
        $User_DN = $Plain_Client_Cerfificate['name'];
        // harmonise "email" field that can be different depending on SSL version
        $User_DN = str_replace("emailAddress=", "Email=", $User_DN);
        return $User_DN;
    }

    /**
     * A custom object used to store additional user details.  
     * Allows non-security related user information (such as email addresses, 
     * telephone numbers etc) to be stored in a convenient location. 
     * @return Object or null if not used 
     */
    public function getDetails() {
        return $this->userDetails;
    }

    /**
     * @see getDetails()
     * @param Object $userDetails
     */
    public function setDetails($userDetails) {
        $this->userDetails = $userDetails;
    }

    public function getAuthorities() {
        return $this->authorities;
    }

    public function setAuthorities($authorities) {
        $this->authorities = $authorities;
    }

}

?>
