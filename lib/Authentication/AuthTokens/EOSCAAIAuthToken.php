<?php

namespace org\gocdb\security\authentication;

/**
 * AuthToken for use with the EOSC AAI
 *
 * Requires installation/config of mod_auth_openidc before use.
 *
 * The token is stateless because it relies on the mod_auth_openidc
 * session and simply reads the attributes stored in the session.
 */
class EOSCAAIAuthToken extends OIDCAuthToken
{
    public function __construct()
    {
        $this->acceptedIssuers = array("https://aai-demo.eosc-portal.eu/auth/realms/core");
        $this->authRealm = "EOSC Proxy IdP";
        $this->groupHeader = "OIDC_CLAIM_eduperson_entitlement";
        $this->groupSplitChar = ',';
        $this->bannedGroups = array();
        $this->requiredGroups = array("urn:geant:eosc-portal.eu:res:gocdb.eosc-portal.eu");
        $this->helpString = 'Please seek assistance by opening a ticket against the ' .
            '"EOSC AAI: Core Infrastructure Proxy" group in ' .
            '<a href=https://eosc-helpdesk.eosc-portal.eu/>https://eosc-helpdesk.eosc-portal.eu/</a>';

        if (isset($_SERVER['OIDC_access_token'])) {
            $this->setTokenFromSession();
        }
    }
}
