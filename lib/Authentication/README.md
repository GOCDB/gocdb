
Authentication Abstraction Package
===================================
David Meredith
This Authentication package provides generic abstractions and some selected 
implementations to support different authentication mechanisms such as x509, 
SAML and un/pw authentication. Without modification, it is configured for x509. 
It is reusable in other php projects, all objects are defined within a namespace 
to prevent collision with other components. 

However, it will no doubt need to be extended and modified to suit the 
requirements of the particular deployment. The auth abstractions can't be used 
'out of the box' without some integration/dev work to cater for your 
particular local credential store and required auth-mechanisms.  
  
Inspired  by Spring Security 3 framework http://static.springsource.org/spring-security
WARNING: This package is NO WAY a complete re-implementation for php, rather it is 
a rather naive micro-uber-simplification! 
There is plenty of scope to further develop this module to support 'out of the box' 
deployment for different auth mechanisms. 


Core interfaces and (implementations) 
=====================================
 
IAuthentication.php 
-------------------
(X509AuthenticationToken.php, UsernamePasswordAuthenticationToken.php, SimpleSamlPhpAuthToken.php) 
Used by client code (and by the framework - Defines authentication tokens for 
different authentication mechanisms. A token authenticates the current user/request. 
A token can be automatically created and returned by an IFirewallComponent, 
or manually created and authenticated by an IFirewallComponent.   


FirewallComponentManager.php
----------------------------
Used by client code - Singleton class to get the list of configured 
IFirewallComponent instances for use within client code.  


IFirewallComponent.php  
----------------------
(FirewallComponent.php) 
Used by client code - Defines a top-level class intended for use by client code to authenticate 
HTTP requests by invoking the 'Token Resolution Process' or to authenticate/change 
the currently authenticated principal. Instances can be fetched using the FirewallComponentManager. 


IConfigFirewallComponent.php
------------------------------------
(MyConfig1.php)
Framework object - Class used to configure an individual IFirewallComponent. 


ISecurityContext.php
--------------------------
(MySecurityContext.php)
Framework object - Get or set an IAuthentication token for current thread of execution. 
Depending on the configuration, the token may be retrieved/stored in HTTP session 
which is necessary to prevent re-authentication across multiple page requests
(e.g. to prevent re-entering a un/pw for each page).
  
Calling 'SecurityContext::getAuthentication()' invokes the automatic 'Token Resolution Process' :
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
(MyAuthenticationManager.php)
Framework object - Provides a single 'authenticate($anIAuthToken)' method for authenticating a 
given token. The manager will iterate all the configured IAuthenticationProviderS 
in an attempt to authenticate the given token. A token is returned on the first successful authentication. 


IAuthenticationProvider.php 
---------------------------
(GOCDBAuthProvider.php, SampleAuthProvider.php)  
Framework object - Used to authenticate IAuthentication tokens. A single provider can authenticate
different types of token as indicated by its 'supports($token)' method. 
SampleAuthProvider.php is a demo sample and should NOT be used in production. 


IUserDetails.php 
----------------
(GOCDBUserDetails.php) 
Framework object - Stores user information about a user and is a member var of an IAuthentication object. 
This allows non-security related user information (such as eaddresses, 
telephone numbers etc) to be stored in a convenient location inside the token. This object 
also encapsulates the user's granted authorities (i.e. roles). 


IUserDetailsService.php 
-----------------------
(GOCDBUserDetailsService.php)
Framework object - Abstracts the local credential store (typically a DB) for loading user-specific data.  
The interface defines a single public method 'loadUserByUsername($aUsernameString)' 
which simplifies support for new data-access strategies.



Typical Usage 
=============
See 'htdocs/web_portal/components/Get_User_Principle.php' for example: 
 
`
    // autoload the security component 
    require_once 'path to Authentication lib'.'/Authentication/_autoload.php'; 

    function Get_User_Principle(){
        // get the FWCManager instance (singleton) 
        $fwMan = \org\gocdb\security\authentication\FirewallComponentManager::getInstance(); 
        // get an array of configured IFirewallComponent objects (one or more depending on config) 
        $firewallArray = $fwMan->getFirewallArray(); 
        $firewall = $firewallArray['fwC1']; // select which IFirewallComponent you need by array key 
        $auth = $firewall->getAuthentication();  // invoke 'automatic' token resolution process 
        if ($auth == null) {
            // A token could not be automatically resolved for the current request, 
            // you could therefore manually create a token e.g. get a un/pw  
            // from the user and attempt authentication using a different token: 
            // try {
            //    $unPwToken = new org\gocdb\security\authentication\UsernamePasswordAuthenticationToken("test", "test");
            //    $auth = $fwComponents['fwC1']->authenticate($unPwToken);
            //    return $auth->getPrinciple()
            // } catch(org\gocdb\security\authentication\AuthenticationException $ex){ }
            
            return null; 
        } 
        return $auth->getPrinciple(); 
    }
`
 
An explicit authentication and logout (i.e. removal of the security context) 
can be achieved using the following: 

`
    // get required IFirewallComponent instance as shown above 
    $firewall->authenticate($authToken);   // to authenticate 
       // or 
    $firewall->authenticate(null);   // to logout/remove token  
`


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

x. Define your own IConfigFirewallComponent.php to configure your setup 

x. Modify FirewallComponentManager.php to build/return the required IFirewallComponent 
  instances to client code. 

x. Use the sample code shown in the 'Typical Usage' section to resolve/create/authenticate a token.  



TODO
=====
Use XML/YAML files to configure IConfigFirewallComponent and FirewallComponentManager
rather than manually creating these instances for your configuration.   

Create a top-level 'IFirewall.php' class that would use 'intercept-URL' pattern matching 
to return the correct IFirewallComponent (as an alternative to 'manually' 
requesting the required IFirewallComponent via the FirewallComponentManager).  
