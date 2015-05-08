
Authentication Abstraction Framework
===================================
This Authentication package/framework provides generic abstractions and some selected 
implementations to support different authentication mechanisms such as x509, 
SAML and un/pw authentication. It is reusable in other php projects. All objects are defined within a namespace 
to prevent collision with other components. 

* Without modification, the framework is configured for x509 client certificate authentication. 
Therefore, you will probably need to configure your Apache instance for SSL with client
cert authentication.  

Please note, it will no doubt need to be extended and modified to suit the 
requirements of the particular deployment. The authentication abstractions can't be used 
'out of the box' without some integration/dev work to cater for your 
particular local credential store and the required auth-mechanisms.  
  
Inspired  by [Spring Security 3 framework](http://static.springsource.org/spring-security)
WARNING: This package is NO WAY a complete re-implementation for php, rather it is 
a rather naive micro-uber-simplification! 
There is plenty of scope to further develop this module to support 'out of the box' 
deployment for different auth mechanisms. 

###Summary - How does the framework authenticates a user? 
* Client code gets a FirewallComponentManager instance and select the required 
IFirewallComponent. 
* Client code invokes `$myfirewallCompoent->getAuthentication()` to get an IAutenticationToken
instance by invoking the automatic **Token Resolution Process** :

  1. An attempt is made to fetch a previously created token from HTTP session and return the token if available. 
     A token may not exist for the current request because: 

    * this is the initial request and a token has not be created yet, 
    * the configuration prevents session-creation and/or, 
    * the token is `stateless` which prevents it from being stored in session.  

  2. If a token is not available, then the configured **pre-authenticating** 
     tokens are iterated in order in an attempt to automatically create a token.  

    * The first successfully created and authenticated pre-auth token is returned. 

  3. If a pre-authenticating token could not be be created, `null` is returned.     
  4. If `null` is returned, client code can choose to manually create an auth token 
     for subsequent authentication using the AuthenticationManagerService. For 
     example, creating a UsernamePasswordAuthenticationToken which requires credentials are input 
     from the user (un/pw token is not a pre-authenticating token - it can't be 
     automatically created/resolved).   


Core Interfaces and (Implementations) 
=====================================
 
IAuthentication.php 
-------------------
(`X509AuthenticationToken.php`, `UsernamePasswordAuthenticationToken.php`, `SimpleSamlPhpAuthToken.php`) 

* Authentication tokens support different authentication mechanisms, see individual
tokens for details on their configuration. 
* A token is created and used to authenticate the current user/request (or not). 
* Pre-authenticating Tokens (e.g. X09AuthenticationToken and SimpleSamlPhpAuthToken) 
are created and authenticated automatically and then returned to client code by an IFirewallComponent.  
* Tokens that are not pre-authenticating (e.g. UsernamePasswordAuthenticationToken) 
need to be created manually in client code and then passed to an IFirwallComponent 
for subsequent authentication. 
* Tokens are used by client code and by the framework.  


FirewallComponentManager.php
----------------------------
Used by client code - Singleton class to get the list of configured 
IFirewallComponent instances for use within client code.  


IFirewallComponent.php  
----------------------
(`FirewallComponent.php`) 

Used by client code - Defines a top-level class intended for use by client code to authenticate 
HTTP requests by invoking the **Token Resolution Process**. This component 
can also be used manually from client code to authenticate/change the currently 
authenticated principal. Instances can be fetched using the FirewallComponentManager. 


IConfigFirewallComponent.php
------------------------------------
(`MyConfig1.php`)

Framework object - Class used to configure an individual IFirewallComponent. 
You will have to modify this file (or another implementation) for your authentication 
requirements. 


ISecurityContext.php
--------------------------
(`MySecurityContext.php`)

Framework object - Get or set an IAuthentication token for current thread of execution. 
Depending on the configuration, the token may be retrieved/stored in HTTP session 
which is necessary to prevent re-authentication across multiple page requests
(e.g. to prevent re-entering a un/pw for each page).
  
Calling `SecurityContext::getAuthentication()` invokes the automatic **Token Resolution Process** 


IAuthenticationManager.php 
--------------------------
(`MyAuthenticationManager.php`)

Framework object - Provides a single 'authenticate($anIAuthToken)' method for authenticating a 
given token. The manager will iterate all the configured IAuthenticationProviderS 
in an attempt to authenticate the given token. A token is returned on the first successful authentication. 


IAuthenticationProvider.php 
---------------------------
(`GOCDBAuthProvider.php`, `SampleAuthProvider.php`)  

Framework object - Used to authenticate IAuthentication tokens. A single provider can authenticate
different types of token as indicated by its `supports($token)` method. 
SampleAuthProvider.php is a demo sample and should NOT be used in production. 


IUserDetails.php 
----------------
(`GOCDBUserDetails.php`) 

Framework object - Stores information about a user and is a member variable of an IAuthentication object. 
This allows non-security related user information such as email addresses, 
telephone numbers etc to be stored in a convenient location inside the token. This object 
also encapsulates the user's granted authorities (i.e. roles). 


IUserDetailsService.php 
-----------------------
(`GOCDBUserDetailsService.php`)

Framework object - Abstracts the local credential store (typically a DB) for loading user-specific data.  
The interface defines a public method `loadUserByUsername($aUsernameString)` 
which simplifies support for new data-access strategies.



Typical Usage 
=============
The GocDB code calls the authentication framework code from a single file: 
`htdocs/web_portal/components/Get_User_Principle.php` (see this file for details).   
 
Sample usage is: 
```php
<?php
    // autoload the security component 
    require_once "path to Authentication lib/_autoload.php"; 

    /**
     * @return user principle string if request is authenticated or null of not 
     */
    function Get_User_Principle(){
        // get the FirewallComponentManager instance (singleton) 
        $fwMan = \org\gocdb\security\authentication\FirewallComponentManager::getInstance(); 

        // get the array of available/configured IFirewallComponent objects  
        $firewallArray = $fwMan->getFirewallArray(); 

        // select which IFirewallComponent you need (by array key)
        $firewall = $firewallArray['fwC1'];  

        // invoke 'automatic' token resolution process to authenticate user 
        $auth = $firewall->getAuthentication();   

        if ($auth == null) {
            // A token could not be automatically resolved for the current request, 
            // you could therefore manually create a token e.g. get a un/pw  
            // from the user and attempt authentication using a different token: 
            // try {
            //    $unPwToken = new org\gocdb\security\authentication\
            //             UsernamePasswordAuthenticationToken("test", "test");
            //    $auth = $fwComponents['fwC1']->authenticate($unPwToken);
            //    return $auth->getPrinciple()
            // } catch(org\gocdb\security\authentication\AuthenticationException $ex){ }
            
            return null; 
        } 
        // $auth->$auth->getAuthorities();  // roles 
        // $auth->getDetails();             // custom user details object 
        return $auth->getPrinciple();       // string that uniquely identifies user 
    }
```
 
An explicit authentication and logout (i.e. removal of the security context) 
can be achieved using the following: 

```php
    // get required IFirewallComponent instance as shown above 
    $firewall->authenticate($authToken);   // to authenticate 
       // or 
    $firewall->authenticate(null);   // to logout/remove token  
```


How do I support a new authentication mechanism?
================================================
* Edit an existing token class or provide a new `IAuthentication.php` implementation for your chosen auth-mechanism 
   * This object MUST implement IAuthentication.
   * Implementations go in the `AuthTokens` dir.  

* Edit `GOCDBAuthProvider.php` or provide a new `IAuthenticationProvider.php` implementation to authenticate your 
   IAuthentication object.   
  * Usually authentication is done by comparing the IUserDetails object that is returned 
    from your IUserDetailsService.  
  * Implementations go in the `AuthProviders` dir. 

* Optional: Edit `GOCDBUserDetailsService.php` or provide a new `IUserDetailsService.php` implementation to load user-specific data
from your local credential repository and return your IUserDetails object. 
  * Implementations should go in `UserDetailsServices` dir. 

* Optional: Edit `GOCDBUserDetails.php` or provide a new `IUserDetails.php` implementation to store your user-specific data. 
  * Implementations go in `UserDetails` dir. 
  * Note: Take care to correctly fulfil the contract of the public API, in particular, 
    you must ensure the non-null contracts as detailed for each method are correctly enforced! 

* Edit `MyConfig1.php` or define your own `IConfigFirewallComponent.php` to configure your setup 

* Modify `FirewallComponentManager.php` to build/return the required IFirewallComponent 
  instances to client code. 

* Use the sample code shown in the Typical Usage section to resolve/create/authenticate a token.  



TODO
=====
Use XML/YAML files to configure IConfigFirewallComponent and FirewallComponentManager
rather than manually creating these instances for your configuration.   

Create a top-level 'IFirewall.php' class that would use 'intercept-URL' pattern matching 
to return the correct IFirewallComponent (as an alternative to 'manually' 
requesting the required IFirewallComponent via the FirewallComponentManager).  
