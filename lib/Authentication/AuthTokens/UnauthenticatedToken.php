<?php
namespace org\gocdb\security\authentication;

require_once __DIR__ . '/../IAuthentication.php';

//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;

/**
 * An implementation of <code>IAuthentication</code> to return to landing page to choose access path.
 *
 * @see IAuthentication
 * @author Sarah Byrne
 */
class UnauthenticatedToken implements IAuthentication {

    private $userDetails = null;
    private $authorities = array();

    public function __construct() {
        $this->chooseAuth();
    }

    /**
     * {@see IAuthentication::isStateless()}
     */
    public static function isStateless() {
        return true;
    }

    /**
     * {@see IAuthentication::isPreAuthenticating()}
     */
    public static function isPreAuthenticating() {
        return true;
    }

    /**
     * {@see IAuthentication::getCredentials()}
     * @return string An empty string as no password required.
     */
    public function getCredentials() {
        return "";
    }

    /**
     * {@see IAuthentication::eraseCredentials()}
     * Does nothing, no password required.
     */
    public function eraseCredentials() {
        // do nothing, no password required.
    }

    /**
     * {@see IAuthentication::getPrinciple())
     * Returns empty string - nothing to authenticate.
     */
    public function getPrinciple() {
        return "";
    }

    /**
     * {@see IAuthentication::validate()}
     */
    public function validate() {
        //do nothing, nothing to validate
    }

    private function chooseAuth() {
        //when no X509 authentication, re-direct user to landing page
        $hostname = $_SERVER['HTTP_HOST'];
        header("Location:https://" . $hostname);
    }

    /**
     * A custom object used to store additional user details.
     * Allows non-security related user information (such as email addresses,
     * telephone numbers etc) to be stored in a convenient location.
     * {@see IAuthentication::getDetails()}
     *
     * @return Object or null if not used
     */
    public function getDetails() {
        return $this->userDetails;
    }

    /**
     * {@see IAuthentication::getDetails()}
     * @param Object $userDetails
     */
    public function setDetails($userDetails) {
        $this->userDetails = $userDetails;
    }

    /**
     * {@see IAuthentication::getAuthorities()}
     * @return array
     */
    public function getAuthorities() {
        return $this->authorities;
    }

    /**
     * {@see IAuthentication::setAuthorities($authorities)}
     * @param array $authorities
     */
    public function setAuthorities($authorities) {
        $this->authorities = $authorities;
    }



}

