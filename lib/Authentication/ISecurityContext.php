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
 * Get/set an IAuthentication token for current thread of execution. 
 * @author David Meredith 
 */
interface ISecurityContext {

    /**
     * Changes the currently authenticated principal, or removes the authentication
     * information from session if null is given.
     *
     * @param IAuthentication $auth The new Authentication token,
     *   or null if http sessino token should be cleared.
     */
    public function setAuthentication($auth = null); 

    /**
     * Invoke the token resolution process to obtain the auth token for the
     * current user/request, or null if an authentication token can't be resolved.
     * <p>
     * The token resolution process is as follows:
     * <ol>
     *    <li>Return the token previously stored in http session if available
     *    (the security configuration must allow session-creation).</li>
     *    <li>If no token can be returned from session, iterate the configured
     *    pre-authenticating tokens in order, and return the first token that
     *    successfully authenticates the user. </li>
     *    <li>If no token can be automatically created and successfully authenticated,
     *    return null</li>
     * </ol>
     *
     * @return IAuthentication or null if not authenticated
     */
    public function getAuthentication(); 
}
