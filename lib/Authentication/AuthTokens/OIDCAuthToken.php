<?php

namespace org\gocdb\security\authentication;

abstract class OIDCAuthToken implements IAuthentication
{
    private $userDetails = null;
    private $authorities = array();
    private $principal;
    protected $acceptedIssuers;
    protected $authRealm;
    protected $groupHeader;
    protected $groupSplitChar;
    protected $bannedGroups;
    protected $requiredGroups;
    protected $helpString;

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

    /**
     * Set principal/User details from the session and check group membership.
     */
    protected function setTokenFromSession()
    {
        if (in_array($_SERVER['OIDC_CLAIM_iss'], $this->acceptedIssuers, true)) {
            $this->principal = $_SERVER['REMOTE_USER'];
            $this->userDetails = array(
                'AuthenticationRealm' => array($this->authRealm)
            );

            // Check group membership is acceptable.
            $this->checkBannedGroups();
            $this->checkRequiredGroups();
        }
    }

    /**
     * Check the token lists all the required groups.
     */
    protected function checkRequiredGroups()
    {
        $groupArray = explode(
            $this->groupSplitChar,
            $_SERVER[$this->groupHeader]
        );

        // Build up a list of missing groups.
        $missingGoodGroups = [];
        foreach ($this->requiredGroups as $group) {
            if (!in_array($group, $groupArray)) {
                $missingGoodGroups[] = $group;
            }
        }

        // If the list of missing groups is not empty, reject the user.
        if (!empty($missingGoodGroups)) {
            $this->rejectUser(
                'You are missing the following group(s):',
                $missingGoodGroups
            );
        }
    }

    /**
     * Check the token lists non of the banned groups.
     */
    protected function checkBannedGroups()
    {
        $groupArray = explode($this->groupSplitChar, $_SERVER[$this->groupHeader]);

        $presentBadGroups = [];
        foreach ($this->bannedGroups as $group) {
            if (in_array($group, $groupArray)) {
                $presentBadGroups[] = $group;
            }
        }

        // If the list of present bad groups is not empty, reject the user.
        if (!empty($presentBadGroups)) {
            $this->rejectUser(
                'We do not grant access to GOCDB to members of the following group(s):',
                $presentBadGroups
            );
        }
    }

    /**
     * Craft a BadCredentialsException exception.
     *
     * Uses the given error message to provide the end user more context.
     *
     * @param string   $errorContext  Context for the error.
     * @param string[] $groupArray    An array of group memberships
     */
    protected function rejectUser($errorContext, $groupArray)
    {
        // For readability, when listing groups to the user,
        // start each one on a new line with a '-' character.
        $prependString = '<br />- ';
        $groupString = implode($prependString, $groupArray);
        throw new BadCredentialsException(
            null,
            'You do not belong to the correct group(s) ' .
            'to gain access to this site.<br /><br />' . $errorContext .
            $prependString . $groupString . '<br /><br />' . $this->helpString
        );
    }
}
