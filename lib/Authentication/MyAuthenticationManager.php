<?php

/*
 * Copyright (C) 2012 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace org\gocdb\security\authentication;

/**
 * Description of AuthenticationManagerFwComp
 *
 * @author David Meredith
 */
class MyAuthenticationManager implements IAuthenticationManager{
    private $securityContext; 
    private $config; 


    public function setConfigFirewallComponent(IConfigFirewallComponent $configFwComp) {
        $this->config = $configFwComp;
    }

    public function setSecurityContext(ISecurityContext $securityContext) {
        $this->securityContext = $securityContext;
    }

    /**
     * @see IAuthenticationManager::authenticate($auth)
     */
    public function authenticate(IAuthentication $auth) {
        if($auth == null) {
            throw new BadCredentialsException(null, 'Coding error null IAuthentication given'); 
        }
        // First do an explicit logout to clear the clients security context.  
        $this->securityContext->setAuthentication(null);

        // Iterate through our configured AuthProviders (in a defined order) 
        // and call each to see if it supports the given IAuthentication 
        // token. If true, attempt authentication. 
        // Break/return on the first (or all depending on voter strategy?) authProvider
        // that can authenticate the user until all are tried.  
        $providers = $this->config->getAuthProviders();
        if(empty($providers)){
            throw new \LogicException("Configuration Error - "
                    . "No AuthenticationProviders are configured"); 
        } 
        
        $updatedAuth = null; 
        $authProviderFound = false; 
        foreach ($providers as $provider){
            if($provider->supports($auth)){
                $authProviderFound = TRUE;
                try {
                    $updatedAuth = $provider->authenticate($auth); 
                    if($updatedAuth != null){
                        break; // break on our first returned auth object  
                    }
                } catch(AuthenticationException $ex){
                    // Auth failed using provider 'n', so catch and try next 
                    // provider until configured providers are exhaused. 
                    // TODO - need to log failed auth attempts.  
                }
            }
        }
        if(!$authProviderFound){
            throw new BadCredentialsException(null, 
                    "Configuration error - AuthToken [". get_class($auth) ."] "
                    . "is not supported by any configured AuthProvider"); 
        }
        
        // Request the token is saved in session (prevented if token or global config is stateless) 
        if ($updatedAuth != null) {
            $this->securityContext->setAuthentication($updatedAuth);
            return $updatedAuth;
        } else {
            throw new AuthenticationException(null, 
                    'Configured AuthProviderS failed to return an '
                    . 'IAuthentication token');
        }
        
    }
}
