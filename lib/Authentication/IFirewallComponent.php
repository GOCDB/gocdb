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
 * Defines a top-level class intended to be used by client code to authenticate 
 * HTTP requests by invoking the 'Token Resolution Process' 
 * or to authenticate/change the currently authenticated principal.  
 * <p>
 * Multiple instances may be needed each with their own configuration 
 * to cater for different deployment scenarios.   
 *    
 * @see IAuthenticationManager 
 * @see ISecurityContext  
 * @author David Meredith
 */
interface IFirewallComponent extends IAuthenticationManager, ISecurityContext {
  
    /**
     * Does this compoenent support the given authentication token. 
     * @param \org\gocdb\security\authentication\IAuthentication $auth
     * @return boolean true or false 
     */
    public function supports(IAuthentication $auth); 
    
}
