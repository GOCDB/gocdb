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
 * @see IFirewallComponent
 * @author David Meredith
 */
class FirewallComponent implements IFirewallComponent {
    private $authManager; 
    private $securityContext; 
    private $config; 
            
    function __construct(
            IAuthenticationManager $authManager, 
            ISecurityContext $securityContext, 
            IConfigFirewallComponent $config) {
        
        $this->authManager = $authManager;
        $this->securityContext = $securityContext;
        $this->config = $config;
    }

    /**
     * @see ISecurityContext::getAuthentication() 
     */
    public function getAuthentication(){
        return $this->securityContext->getAuthentication(); 
    }

    /**
     * @see ISecurityContext::setAuthentication($auth) 
     */
    public function setAuthentication($auth = null){
        return $this->securityContext->setAuthentication($auth);
    }

    /**
     * @see IAuthenticationManager::authenticate($auth)   
     */
    public function authenticate(IAuthentication $auth){
        return $this->authManager->authenticate($auth);  
    }

    /**
     * @see IFirewallComponent  
     * @throws \LogicException if no providers configured 
     */
    public function supports(IAuthentication $auth) {
        $providers = $this->config->getAuthProviders();
        if (empty($providers)) {
            throw new \LogicException("Configuration Error - "
            . "No AuthenticationProviders are configured");
        }
        foreach ($providers as $provider) {
            if ($provider->supports($auth)) {
                return true;
            }
        }
        return false;
    }

}
