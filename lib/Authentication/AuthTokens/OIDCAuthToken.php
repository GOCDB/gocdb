<?php

namespace org\gocdb\security\authentication;

abstract class OIDCAuthToken implements IAuthentication
{
    private $userDetails = null;
    private $authorities = array();
    private $principal;

    /**
     * {@see IAuthentication::eraseCredentials()}
     */
    public function eraseCredentials()
    {
    }

    /**
     * {@see IAuthentication::getAuthorities()}
     */
    public function getAuthorities()
    {
        return $this->authorities;
    }

    /**
     * {@see IAuthentication::getCredentials()}
     * @return string An empty string as passwords are not used by this token.
     */
    public function getCredentials()
    {
        return ""; // none used in this token, handled by IdP
    }

    /**
     * A custom object used to store additional user details.
     * Allows non-security related user information (such as email addresses,
     * telephone numbers etc) to be stored in a convenient location.
     * {@see IAuthentication::getDetails()}
     *
     * @return Object or null if not used
     */
    public function getDetails()
    {
        return $this->userDetails;
    }

    /**
     * {@see IAuthentication::getPrinciple()}
     * @return string unique principle string of user
     */
    public function getPrinciple()
    {
        return $this->principal;
    }

    /**
     * {@see IAuthentication::setAuthorities($authorities)}
     */
    public function setAuthorities($authorities)
    {
        $this->authorities = $authorities;
    }

    /**
     * {@see IAuthentication::setDetails($userDetails)}
     * @param Object $userDetails
     */
    public function setDetails($userDetails)
    {
        $this->userDetails = $userDetails;
    }

    /**
     * {@see IAuthentication::validate()}
     */
    public function validate()
    {
    }

    /**
     * {@see IAuthentication::isPreAuthenticating()}
     */
    public static function isPreAuthenticating()
    {
        return true;
    }

    /**
     * Returns true, this token reads the session attributes and so
     * does not need to be stateful itself.
     * {@see IAuthentication::isStateless()}
     */
    public static function isStateless()
    {
        return true;
    }
}
