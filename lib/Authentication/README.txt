
Authentication Abstraction Package
===================================
This Authentication package provides the necessary abstractions to support 
different authentication mechanisms such as x509 and un/pw authentication. 
Without modification, it is configured for x509. This module can only support
a *single* configured auth mechanism (see TODO/Future Work below regarding a 
fallback strategy to support multiple auth mechanisms, e.g. 1st try x509cert, 
if no cert then fall back to try un/pw). 

********************************************************************************
*****Note, this version of GOCDB does not use this 'Authentication' module.*****
********************************************************************************
The EGI implementation uses x509 certificates and so we do not use the 
authentication abstractions in the EGI GOCDB5 instance to bypass the use 
of cookies and avoid EU cookie law regulations. 

This module is intended to be extended and modified as required to suit the 
requirements of the particular deployment. The auth abstractions can't be used 
'out of the box' without some development work to integrate with your 
particular local credential store, whatever that may be (e.g. DB containing 
salted hash of un/pw). 
If required by EGI, we may further develop this module to support 'out of the box' 
deployment for different auth mechanisms.   




In Brief:
==========
The code is largely based on core interfaces and classes copied 
from Spring Security 3 framework. http://static.springsource.org/spring-security
WARNING: This package is NO WAY a complete re-implementation for php, rather it is 
a rather naive micro-simplification! 

- The ISecurityContextService is used to store the users IAuthToken in HTTP session 
so that re-authentications are not necessary across different page requests 
(this requires cookies are enabled in the browser). 
- The IAuthenticationProvider interface authenticates the user if their IAuthToken is null. 
- The IUserDetailsService abstracts the local credential store (e.g. a local database that stores
user accounts identified by certificate DN or username). 


Core components (interfaces and services) 
==========================================
 
- IAuthentication.php and X509AuthenticationToken.php, UsernamePasswordAuthenticationToken.php 
Core interface and implementations for defining authentication tokens for  
different authentication mechanisms (x509 and username/password). 
 
- IAuthenticationManager.php and AuthenticationManagerService.php
Core service providing an authentication entry point.  
The interface, provides a single 'authenticate($anIAuthToken)'  method that 
authenticates the given IAuthentication token if it can. 

- IAuthenticationProvider.php and GOCDBAuthProvider.php, SampleAuthProvider.php  
Core interface and implementations providing authentication logic for  
different IAuthentication tokens.
SampleAuthProvider.php is a demo sample and should NOT be used in production. 

- SecurityContextService.php
Core service to (securely) store and fetch the user's IAuthentication token in HTTP session. 
This is necessary to prevent re-authentication across multiple page requests 
(otherwise the user would have to re-enter their un/pw for each page request).  

- IUserDetails.php and GOCDBUserDetails.php 
Interface and implementation for storing user information which is later 
encapsulated into an IAuthentication object. 
This allows non-security related user information (such as email addresses, 
telephone numbers etc) to be stored in a convenient location. This object 
also encapsulates the user's granted authorities (i.e. roles). 

- IUserDetailsService.php and GOCDBUserDetailsService.php
Core interface and implementation that loads user-specific data from the local credential repository/db.  
Interface defines a single public method 'loadUserByUsername($aUsernameString)' 
which simplifies support for new data-access strategies.

- ApplicationSecurityConfigService.php
Core service that defines different factory methods for returning different 
implementations of the core interfaces. This service defines the 'isPreAuthenticated()' 
method to indicate if the authentication mechanism in use relies on the 
server/hosting environment to pre-authenticate the user such as x509 
(unlike username/password authentication which relies solely on the web-application). 

Typical Usage (see 'htdocs/web_portal/components/Get_User_Principle.php' file) 
==============================================================================

    require_once __DIR__ . '<path_to>/lib/Gocdb_Services/Factory.php';
    $auth = \Factory::getAuthContextService()->getAuthentication();
    if($auth == null) {
        // Here you could re-direct the user to some form of auth/login page in order  
        // to construct an Auth token from the user, then call: 
        //AuthenticationManagerService::authenticate($anIAuthenticationObj);
    } else {
        $auth = SecurityContextService::getAuthentication();
        $auth->getDetails(); // to get an application specific user details object. 
        $auth->getAuthorities(); // to list granted roles 
        return $auth->getPrinciple(); // to get the authenticated principle object 
    }

 
An explicit authentication can be achieved using the following code: 
 SecurityContextService::setAuthentication($anIAuthenticationObj);
 
An explicit logout (removal of the security context) can be achieved using the 
following code (e.g. used on logout page): 
 SecurityContextService::setAuthentication(null);


How do I support a new authentication mechanism?
===================================================
x) Provide a new 'IAuthentication.php' implementation for your chosen auth-mechanism 
   - This object MUST implement IAuthentication.
   - Implementations go in the 'AuthTokens' dir.  

x) Provide a new 'IAuthenticationProvider.php' implementation to authenticate your 
   IAuthentication object.   
   - Usually auth is done by comparing the IUserDetails object that is returned 
     from your IUserDetailsService.  
   - Implementations go in the 'AuthProviders' dir. 

x) Provide a new 'IUserDetailsService.php' implementation to load user-specific data
   from your local credential repository and return your IUserDetails object. 
   - Implementations should go in 'UserDetailsServices' dir. 

x) Provide a new 'IUserDetails.php' implementation to store your user-specific data. 
   - Implementations go in 'UserDetails' dir. 

Note: Take care to correctly fulfill the contract of the public API, in particular, 
you must ensure the non-null contracts as detailed for each method are correctly enforced! 

x) Modify ApplicationSecurityConfigService.php to return your chosen auth impl 
  (see below for example)  

x) Integrate the sample code shown in the 'Typical Usage' section in the 
   'htdocs/web_portal/components/Get_User_Principle.php' file. This file serves 
   as the global integration point for all authentication requests. 






Configuring the SampleAuthProvider
===================================
The SampleAuthProvider.php is a IAuthenticationProvider.php implementation that 
is for demonstration purposes only - DO NOT use this in production. 
This auth provider will authenticate any user whose username and password are the same.
To use this provider, do the following: 

x) Modify ApplicationSecurityConfigService as follows: 
   - Update 'ApplicationSecurityConfigService::getAuthProvider()' to return a new SampleAuthProvider.php instance
   - Update 'ApplicationSecurityConfigService::isPreAuthenticated()' to return false

x) Use the following logic to perform a manual authentication (e.g. see index.php) 
   (note the username must be the same as the password).
 
        require_once dirname(__FILE__).'/../../lib/Authentication/AuthenticationManagerService.php'; 
        require_once dirname(__FILE__).'/../../lib/Authentication/AuthTokens/UsernamePasswordAuthenticationToken.php';
        require_once dirname(__FILE__).'/../../lib/Authentication/Exceptions/AuthenticationException.php';
        try { 
            $authToken = new UsernamePasswordAuthenticationToken('sampleUser', 'sampleUser');         
            $authToken = AuthenticationManagerService::authenticate($authToken); 
            echo 'Newly authenticated ['.$authToken->getPrinciple().']'; 
        } catch(AuthenticationException $ex){
            echo 'Failed Authentication';
        }


TODO
=======
 
If required by EGI, we may further develop this module to support 'out of the box' 
deployment for different auth mechanisms.
 
Spring allows multiple AuthProviders to be configured in conjunction with a 
strategy for iterating through the available providers to support more than 
one auth-mechanism, e.g. try x509 first, if fail fallback to un/pw, if fail then
throw AuthException. This may be useful as currently we can only configure a single 
auth-mechanism. In particular, the ApplicationSecurityConfigService.isPreAuthenticated()  
would need to cycle through the configured Auth tokens and return true if any are pre-auth. 
Also, the ApplicationSecurityConfigService.getPreAuth_AuthToken() would need to return
an ordered list of pre-auth tokens to attempt authentication with. Would need 
some work but is doable. 
