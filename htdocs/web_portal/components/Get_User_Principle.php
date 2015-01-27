<?php
/*______________________________________________________
 *======================================================
 * File: Get_User_Principle.php
 * Author: John Casson, David Meredith
 * Description: Returns the user's principle ID string for the user that's currently
 *				connected (for x509 this is a DN).
 *
 * License information
 *
 * Copyright 2013 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 /*====================================================== */

/**
 * Get the DN from an x509 cert or null if a user certificate can't be loaded. 
 * <p>
 * Called from the PI to authenticate requests using certificates only. 
 * 
 * @return string or null if can't authenticate request 
 */
function Get_User_Principle_PI()
{
   require_once __DIR__ . '/../../../lib/Authentication/AuthenticationManagerService.php';  
   require_once __DIR__ . '/../../../lib/Authentication/AuthTokens/X509AuthenticationToken.php';  
   try { 
       $x509Token = new org\gocdb\security\authentication\X509AuthenticationToken(); 
       $auth = org\gocdb\security\authentication\AuthenticationManagerService::authenticate($x509Token); 
       return $auth->getPrinciple(); 
   } catch(org\gocdb\security\authentication\AuthenticationException $ex){
       // failed auth, so return null and let calling page decide to allow 
       // access or not (some PI methods don't need to be authenticated with a cert) 
   }
   return null; 
}

/**
 * Get the x509 DN from certificate or from SAML attribute. 
 * <p>
 * Called fromt the portal to allow authentication via x509 or SSO/SAML.  
 * This method serves as the global integration point for all authentication requests. 
 * If you intend to support a different authentication mechanism, you will need 
 * to modify this method to support your chosen authentication scheme. 
 * 
 * @return string or null if can't authenticate request 
 */
function Get_User_Principle()
{
    require_once __DIR__ . '/../../../lib/Authentication/SecurityContextService.php'; 
    $auth = org\gocdb\security\authentication\SecurityContextService::getAuthentication();
    if ($auth == null) {
        //require_once __DIR__ . '/../../../lib/Authentication/AuthenticationManagerService.php'; 
        //$unPwToken = new org\gocdb\security\authentication\UsernamePasswordAuthenticationToken("test", "test");
        //$auth = org\gocdb\security\authentication\AuthenticationManagerService::authenticate($unPwToken);
        return null; 
    } 
    return $auth->getPrinciple();
}



/*function Get_User_Principle_back()
{
    // Return hard wired user's principle string (DN) e.g. for testing     
    // =======================================================
    //return '/C=UK/O=eScience/OU=CLRC/L=DL/CN=david meredith';

    // Check if an authentication token has been set in the SecurityContext class  
    // by higher level code, eg Symfony Security which provides a Firewall component
    // may have been used to intercept the HTTP request and authenticate the 
    // user (using whatever auth scheme was configured in the Firewall). A 
    // Symfony controller can then subsequently set the token in the SecurityContext
    // before invoking the GOCDB code. 
    // =======================================================
    require_once __DIR__.'/../../../lib/Gocdb_Services/SecurityContextSource.php';
    if(\SecurityContextSource::getContext() != null){
       $token = \SecurityContextSource::getContext()->getToken(); 
       return str_replace("emailAddress=", "Email=", $token->getUser()->getUserName()); 
    }
    
    // ================Use x509 Authentication=======================
    //if(!isset($_SERVER['SSL_CLIENT_CERT']))
    //	return "";
    //$Raw_Client_Certificate = $_SERVER['SSL_CLIENT_CERT'];
    //$Plain_Client_Cerfificate = openssl_x509_parse($Raw_Client_Certificate);
    //$User_DN = $Plain_Client_Cerfificate['name'];
    // harmonise display of the "email" field that can be different depending on
    // used version of SSL
    //$User_DN = str_replace("emailAddress=", "Email=", $User_DN);
    //return $User_DN;
    if (isset($_SERVER['SSL_CLIENT_CERT'])) {
        $Raw_Client_Certificate = $_SERVER['SSL_CLIENT_CERT'];
        if (isset($Raw_Client_Certificate)) {
            $Plain_Client_Cerfificate = openssl_x509_parse($Raw_Client_Certificate);
            $User_DN = $Plain_Client_Cerfificate['name'];
            if (isset($User_DN)) {
                // harmonise "email" field that can be different depending on version of SSL
                $dn = str_replace("emailAddress=", "Email=", $User_DN);
                if ($dn != null && $dn != '') {
                    return $dn;
                }
            }
        }
    }


    // Fall back to try saml authentication (simplesaml)
    // =======================================================
    if(false){ // disable by default - to use saml requires install of simplesamlphp and config below 
        require_once('/var/simplesamlphp/lib/_autoload.php');
        $as = new SimpleSAML_Auth_Simple('default-sp');
        $as->requireAuth();
        \Factory::$properties['LOGOUTURL'] = $as->getLogoutURL('https://gocdb-test.esc.rl.ac.uk');
        $attributes = $as->getAttributes();
        if(!empty($attributes)){
            //return $attributes['eduPersonPrincipalName'][0];
            $dnAttribute = $attributes['urn:oid:1.3.6.1.4.1.11433.2.2.1.9'][0];
            if(!empty($dnAttribute)){
                return str_replace("emailAddress=", "Email=", $dnAttribute); 
            } else {
                die('Did not retrieve a valid certificate DN from identify provider - your SSO '
                        . 'account needs to be associated with a certificate to login via this route'); 
            }
        }
    }

    // Couldn't authetnicate the user, so finally return null 
    return null; 
}*/




/*function Get_User_Principle__()
{

    // To try the Auth Module with x509 (configured as default), comment out 
    // above block '===Use Custom x509 Auth===' and uncomment block below  
     
    // ================Use x509 Authentication Module=======================
    require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';
    $auth = \Factory::getAuthContextService()->getAuthentication();
    if($auth == null) {
        die('Certificate not found - please restart your browser or clear your browser SSL cache');
        // If auth == null AND IF you are NOT using x509 but instead using a 
        // non-pre auth token such as un/pw (where isPreAuthenticated == false), 
        // normally here you would do a manual authentication, i.e. 
        // re-direct the user to a form/login page in order to input un/pw and 
        // to construct an Auth token, then call: 
        //AuthenticationManagerService::authenticate($anIAuthenticationObj);
    } else {
        if(!in_array('ROLE_REGISTERED_USER', $auth->getAuthorities())) {
            echo('<b><font color="red">Please register</font></b><br/>');
        }
        //echo('Your DN: ['.$auth->getPrinciple().']<br/>');
        //echo('Granted Roles: ');
        // foreach($auth->getAuthorities() as $role){
        //      echo('['.$role.'] ');
        //}
        if ($auth->getDetails() != null && !($auth->getDetails() instanceof User)) {
            // MUST do an instanceof check to determine if we support the
            // Details object for this GOCDB instance ! (they can be custom
            // according to the GOCDB instance type, Doctrine version uses a
            // Doctrine User object)
            throw new RuntimeException('Expected a Doctrine User entity');
        }
        return $auth->getPrinciple();
    }
    // ================Use x509 Authentication Module=======================
    
    
}*/


?>
