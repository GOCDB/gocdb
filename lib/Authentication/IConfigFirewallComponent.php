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
 * Used to define a configuration for a new FirewallComponent
 * @author David Meredith
 */
interface IConfigFirewallComponent {

    /**
     * Return a list of <code>IAuthenticationProvider</code> implementations.
     * @return array
     */
    public function getAuthProviders();

    /**
     * Return the <code>IUserDetailsService</code> implementation.
     * @return IUserDetailsService implementation
     */
    public function getUserDetailsService();

    /**
     * Return an array of fully qualified class name strings that lists the
     * supported <code>IAuthentication</code> tokens.
     * <p>
     * The class names are used to dynamically build preAuth class instances during
     * the token resolution process. Only preAuthenticating tokens are automatically
     * created during token resolution. Ordering is significant - tokens are
     * created in the order they appear in the array.
     *
     * @return array of class name strings.
     */
    public function getAuthTokenClassList();

    /**
     * Allow HTTP session creation for the security context.
     * <p>
     * If true, <code>IAuthentication</code> tokens may be stored
     * in the session (provided the token declares that it can be
     * persisted with <code>$token.isStateless() == false)</code>.
     * <p>
     * If true, stateful tokens can be stored in HTTP session for
     * subsequent retrieval across page requests (e.g. a username/pw token).
     * <p>
     * Typically, REST style applications are stateless using only stateless auth tokens,
     * while portals are normally stateful requiring session tracking.
     *
     * @return boolean
     */
    public function getCreateSession();
}
