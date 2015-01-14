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
 * Get the user's principle ID string.  
 * If using x509 to authenticate users, this is the DN string of the client certificate.  
 * This method serves as the global integration point for all authentication requests. 
 * If you intend to support a different authentication mechanism, you will need 
 * to modify this method to support your chosen authentication scheme. 
 * 
 * @return string That authenticates the user, or null if user is not authenticated. 
 */
function Get_User_Principle()
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
    
    
    // If no authentication token was set in the SecurityContext class, fall
    // back to extract a certificate directly from the browser. 
    // =======================================================
    if(!isset($_SERVER['SSL_CLIENT_CERT']))
    	return "";
    $Raw_Client_Certificate = $_SERVER['SSL_CLIENT_CERT'];
    $Plain_Client_Cerfificate = openssl_x509_parse($Raw_Client_Certificate);
    $User_DN = $Plain_Client_Cerfificate['name'];
    // harmonise display of the "email" field that can be different depending on
    // used version of SSL
    return  str_replace("emailAddress=", "Email=", $User_DN);
    // ================Use Custom x509 Authentication=======================
    

    // To try the Auth Module with x509 (configured as default), comment out 
    // above block '===Use Custom x509 Auth===' and uncomment block below  
     
    // ================Use x509 Authentication Module=======================
    /*require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';
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
    }*/
    // ================Use x509 Authentication Module=======================
    
    
}


?>
