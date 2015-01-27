
Authentication Abstraction Package
===================================
David Meredith
This Authentication package provides abstractions to support different authentication 
mechanisms such as x509, SAML and un/pw authentication. 
Without modification, it is configured for x509. 

It will no doubt need to be extended and modified to suit the 
requirements of the particular deployment. The auth abstractions can't be used 
'out of the box' without some integration/dev work to cater for your 
particular local credential store and required auth-mechanism.  
  
Inspired  by Spring Security 3 framework http://static.springsource.org/spring-security
WARNING: This package is NO WAY a complete re-implementation for php, rather it is 
a rather naive micro-uber-simplification! 
There is plenty of scope to further develop this module to support 'out of the box' 
deployment for different auth mechanisms. 


Core interfaces and (implementations) 
=====================================
 
IAuthentication.php 
-------------------
Defines authentication tokens for different authentication mechanisms. 
A token is created to authenticate the current user/request.  
(X509AuthenticationToken.php, UsernamePasswordAuthenticationToken.php, SimpleSamlPhpAuthToken.php) 
 
SecurityContextService.php
--------------------------
Entry point service to request an authentication token for the current request. 
Depending on the configuration, the token may be retrieved/stored in HTTP session 
which is necessary to prevent re-authentication across multiple page requests
(e.g. to prevent re-entering a un/pw for each page).
  
Calling 'SecurityContextService::getAuthentication()' invokes the automatic 'Token Resolution Process' :
  1. Attempt to fetch a previously created token from session and return the token if available. 
     A token may not exist for the current request because: a) this is the 
     initial request, b) the configuration prevents session-creation and/or 
     c) the token is 'stateless' which prevents it from being stored in session.  
  2. If a token is not available, then the configured 'pre-authenticating' 
     tokens are iterated in order in an attempt to automatically create a token.  
     The first successfully created and authenticated pre-auth token is returned. 
  3. If a pre-auth token could not be be created, null is returned.     
  4. If null is returned, client code can choose to manually create an auth token 
     for subsequent authentication using the AuthenticationManagerService. For 
     example, creating a un/pw auth token which requires credentials are input 
     from the user (un/pw is not a pre-authenticating token - it can't be 
     automatically created/resolved).  

IAuthenticationManager.php 
--------------------------
Entry point service providing a single 'authenticate($anIAuthToken)' method.   
Used for manually authenticating a given token. The manager will iterate all the 
configured IAuthenticationProviderS in an attempt to authenticate the given 
IAuthentication token. A token is returned on the first successful authentication. 
(AuthenticationManagerService.php)

IAuthenticationProvider.php 
---------------------------
Used to authenticate IAuthentication tokens. A single provider can authenticate
different types of token as indicated by its 'supports($token)' method. 
SampleAuthProvider.php is a demo sample and should NOT be used in production. 
(GOCDBAuthProvider.php, SampleAuthProvider.php)  

IUserDetails.php 
----------------
Stores user information about a user and is a member var of an IAuthentication object. 
This allows non-security related user information (such as eaddresses, 
telephone numbers etc) to be stored in a convenient location inside the token. This object 
also encapsulates the user's granted authorities (i.e. roles). 
(GOCDBUserDetails.php) 

IUserDetailsService.php 
-----------------------
Abstracts the local credential store (typically a DB) for loading user-specific data.  
The interface defines a single public method 'loadUserByUsername($aUsernameString)' 
which simplifies support for new data-access strategies.
(GOCDBUserDetailsService.php)

ApplicationSecurityConfigService.php
------------------------------------
Configuration service - defines different factory methods for returning different 
configurations/implementations of the core interfaces. 


Typical Usage 
=============
See 'htdocs/web_portal/components/Get_User_Principle.php' for example: 
 
<code>
    function Get_User_Principle(){
        // Automatically resolve a token: Gets the token stored in session (if available) 
        // or automatically creates a configured 'pre-authenticating' token.
        require_once 'path to Auth lib'.'/Authentication/SecurityContextService.php'; 
        require_once 'path to Auth lib'.'/lib/Authentication/AuthenticationManagerService.php'; 
        $auth = org\gocdb\security\authentication\SecurityContextService::getAuthentication();

        // A token could not be automatically resolved (no token exists in 
        // session and/or pre-authenticating token could not be automatically created).   
        // Optional: Manually create token and re-attempt authentication:
        if ($auth == null) {
            try{ 
               // Here get username and password from user...then create and authenticate token
               $unPwToken = new org\gocdb\security\authentication\UsernamePasswordAuthenticationToken("test", "test");
               $auth = org\gocdb\security\authentication\AuthenticationManagerService::authenticate($unPwToken);
            } catch(org\gocdb\security\authentication\AuthenticationException $ex){
                // log failed authentication 
            }
        } 
        return $auth->getPrinciple();
    }
</code>
 
An explicit authentication and logout (removal of the security context) 
can be achieved using the following: 
<code>
   SecurityContextService::setAuthentication($anIAuthenticationObj); // to authenticate
   SecurityContextService::setAuthentication(null);                  // to logout 
</code>


How do I support a new authentication mechanism?
================================================
x. Provide a new 'IAuthentication.php' implementation for your chosen auth-mechanism 
   - This object MUST implement IAuthentication.
   - Implementations go in the 'AuthTokens' dir.  

x. Provide a new 'IAuthenticationProvider.php' implementation to authenticate your 
   IAuthentication object.   
   - Usually auth is done by comparing the IUserDetails object that is returned 
     from your IUserDetailsService.  
   - Implementations go in the 'AuthProviders' dir. 

x. Optional: Provide a new 'IUserDetailsService.php' implementation to load user-specific data
   from your local credential repository and return your IUserDetails object. 
   - Implementations should go in 'UserDetailsServices' dir. 

x. Optional: Provide a new 'IUserDetails.php' implementation to store your user-specific data. 
   - Implementations go in 'UserDetails' dir. 
    Note: Take care to correctly fulfil the contract of the public API, in particular, 
    you must ensure the non-null contracts as detailed for each method are correctly enforced! 

x. Modify ApplicationSecurityConfigService.php to return your chosen auth impl 
  (see below for example)  

x. Use the sample code shown in the 'Typical Usage' section to create and authenticate a token.  



TODO
=====
Plenty - to update  

