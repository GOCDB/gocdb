<?php
namespace org\gocdb\services;

/*______________________________________________________
 *======================================================
 * File: index.php
 * Author: John Casson
 * Description: Entry point for the programmatic interface
 *
 * License information
 *
 * Copyright 2009 STFC
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
require_once __DIR__.'/../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../lib/Doctrine/bootstrap.php';
require_once __DIR__.'/../web_portal/components/Get_User_Principle.php';

//Require_once all files in PI directory
#$files = glob(__DIR__ . '/../../lib/Gocdb_Services/PI/*.php');
#foreach ($files as $file) {
#	require_once($file);
#}

// The default is 30secs, but some queries can take longer so we may need to 
// up the limit. This should only be necessary for certain PI queries such as 
// get_downtime and should not be used in the GUI/portal scripts. 
//set_time_limit(500); 


$piReq = new PIRequest();
$piReq->process();

class PIRequest {
    private $method = null;
    private $output = null;
    private $params = array();
    private $dn = null;

    function process(){
        header('Content-Type: application/xml');
        //Type is GET request for XML info
        $this->parseGET();            
        $xml = $this->getXml();       
        // returns the portal URL as defined in conf file 
        $portal_url = \Factory::getConfigService()->GetPortalURL();    
        //$portal_url = "https://URL/";
        $xml = str_replace("#GOCDB_BASE_PORTAL_URL#", $portal_url, $xml);
        echo($xml);       
        //echo('<test>val</test>');
    }

    /* Copy the values from the URL into local variables */
    function parseGET() {
       
        if(isset($_GET['method'])){               
            $this->method = $_GET['method'];
            unset($_GET['method']);
        }

        if(isset($_GET['output'])){
            $this->output = $_GET['output'];
            unset($_GET['output']);
        }
        
        $testDN = Get_User_Principle();
        if(empty($testDN) == FALSE){
            $this->dn = $testDN; 
        }
       
        if(count($_GET) > 0)
            $this->params = $_GET;
    }
    
    /* executes a query using the appropriate service layer function */
    function getXml() {
        try {
            $directory = __DIR__ . '/../../lib/Gocdb_Services/PI/';  
            $em = \Factory::getEntityManager();
            
            switch($this->method) {            	
                case "get_site":
                    require_once($directory.'GetSite.php');
                	$getSite =  new GetSite($em);  
                	$getSite->validateParameters($this->params);                	           	 
                    $getSite->createQuery();
                    $getSite->executeQuery();
                    $xml = $getSite->getXML();
                    break;                    
                case "get_site_list":
                    require_once($directory.'GetSite.php');
                	$getSite =  new GetSite($em);  
                	$getSite->validateParameters($this->params);              	 
                    $getSite->createQuery();
                    $getSite->executeQuery();                    
                    $xml = $getSite->getXMLShort();
                    break;
                case "get_site_contacts":
                    require_once($directory.'GetSiteContacts.php');
                    $this->authAnyCert();
					$getSiteContacts =  new GetSiteContacts($em);  
                	$getSiteContacts->validateParameters($this->params);              	 
                    $getSiteContacts->createQuery();
                    $getSiteContacts->executeQuery();
                    $xml = $getSiteContacts->getXML();                    
                    break;
                case "get_site_security_info":
                    require_once($directory.'GetSiteSecurityInfo.php');
                    $this->authAcl();
                    $getSiteSecurityInfo =  new GetSiteSecurityInfo($em);
                    $getSiteSecurityInfo->validateParameters($this->params);
                    $getSiteSecurityInfo->createQuery();
                    $getSiteSecurityInfo->executeQuery();
                    $xml = $getSiteSecurityInfo->getXML();                    
                    break;
                case "get_roc_list":                    
                    require_once($directory.'GetNGIList.php');
                    $getNGIList =  new GetNGIList($em);
                    $getNGIList->validateParameters($this->params);
                    $getNGIList->createQuery();
                    $getNGIList->executeQuery();
                    $xml = $getNGIList->getXML();
                    break;
                case "get_subgrid_list":
                    require_once($directory.'GetSubGridList.php');
                    $getSubGrid =  new GetSubGridList($em);
                    $getSubGrid->validateParameters($this->params);
                    $getSubGrid->createQuery();
                    $getSubGrid->executeQuery();
                    $xml = $getSubGrid->getXML();                    
                    break;
                case "get_roc_contacts":
                    require_once($directory.'GetNGIContacts.php');
                    $this->authAnyCert();
                    $getNGIContacts =  new GetNGIContacts($em);
                    $getNGIContacts->validateParameters($this->params);
                    $getNGIContacts->createQuery();
                    $getNGIContacts->executeQuery();
                    $xml = $getNGIContacts->getXML();                    
                    break;
                case "get_service":
                    require_once($directory.'GetService.php');
                    $getSE = new GetService($em);
                    $getSE->validateParameters($this->params);
                    $getSE->createQuery();
                    $getSE->executeQuery();
                    $xml = $getSE->getXML();
                    break;
                case "get_service_endpoint":
                    require_once($directory.'GetService.php');
					$getSE = new GetService($em);  
                	$getSE->validateParameters($this->params);              	 
                    $getSE->createQuery();
                    $getSE->executeQuery();
                    $xml = $getSE->getXML();
                    break;                    
                case "get_service_types":                    
                    require_once($directory.'GetServiceTypes.php');
                    $getST = new GetServiceTypes($em);
                    $getST->validateParameters($this->params);
                    $getST->createQuery();
                    $getST->executeQuery();
                    $xml = $getST->getXML();
                    break;
                case "get_downtime_to_broadcast":
                    require_once($directory.'GetDowntimesToBroadcast.php');
                	$getDTTBroadcast =  new GetDowntimeToBroadcast($em);  
                	$getDTTBroadcast->validateParameters($this->params);              	 
                    $getDTTBroadcast->createQuery();
                    $getDTTBroadcast->executeQuery();
                    $xml = $getDTTBroadcast->getXML();
                    break;
                case "get_downtime":
                    require_once($directory.'GetDowntime.php');
                	$getDowntime =  new GetDowntime($em);  
                	$getDowntime->validateParameters($this->params);              	 
                    $getDowntime->createQuery();
                    $getDowntime->executeQuery();
                    $xml = $getDowntime->getXML();
                    break;
                case "get_downtime_nested_services":
                    require_once($directory.'GetDowntime.php');
                    $getDowntime =  new GetDowntime($em, true);
                    $getDowntime->validateParameters($this->params);
                    $getDowntime->createQuery();
                    $getDowntime->executeQuery();
                    $xml = $getDowntime->getXML();
                    break;                    
				case "get_user":
                    require_once($directory.'GetUser.php');
                    $this->authAnyCert();
					$getUser = new GetUser($em);  
                	$getUser->validateParameters($this->params);              	 
                    $getUser->createQuery();
                    $getUser->executeQuery();
                    $xml = $getUser->getXML();                    
                    break;
                case "get_project_contacts":
                    require_once($directory.'GetProjectContacts.php');
                    $this->authAnyCert();
					$getProjCon =  new GetProjectContacts($em);  
                	$getProjCon->validateParameters($this->params);              	 
                    $getProjCon->createQuery();
                    $getProjCon->executeQuery();
                    $xml = $getProjCon->getXML();                    
                    break;                    
                case "get_ngi":
                    require_once($directory.'GetNGI.php');
                    $this->authAnyCert();
					$getNGI =  new GetNGI($em);  
                	$getNGI->validateParameters($this->params);              	 
                    $getNGI->createQuery();
                    $getNGI->executeQuery();
                    $xml = $getNGI->getXML();
                    break;
                case "get_service_group" :
                    require_once($directory.'GetServiceGroup.php');
					$this->authAnyCert ();
					$getServiceGroup = new GetServiceGroup ( $em );
					$getServiceGroup->validateParameters ( $this->params );
					$getServiceGroup->createQuery ();
					$getServiceGroup->executeQuery();
					$xml = $getServiceGroup->getXML ();
					break;
				case "get_service_group_role" :
                    require_once($directory.'GetServiceGroupRole.php');
					$this->authAnyCert ();
					$getServiceGroupRole = new GetServiceGroupRole ( $em );
					$getServiceGroupRole->validateParameters ( $this->params );
					$getServiceGroupRole->createQuery ();
					$getServiceGroupRole->executeQuery();
					$xml = $getServiceGroupRole->getXML ();
					break;
				case "get_cert_status_date" :
                    require_once($directory.'GetCertStatusDate.php');
					$this->authAnyCert ();
					$getCertStatusDate = new GetCertStatusDate ( $em );
					$getCertStatusDate->validateParameters ( $this->params );
					$getCertStatusDate->createQuery ();
					$getCertStatusDate->executeQuery();
					$xml = $getCertStatusDate->getXML ();
					break; 
                case "get_cert_status_changes":
                    require_once($directory.'GetCertStatusChanges.php');
                   	$this->authAnyCert();
                    $getCertStatusChanges =  new GetCertStatusChanges($em);
                    $getCertStatusChanges->validateParameters($this->params);
                    $getCertStatusChanges->createQuery();
                    $getCertStatusChanges->executeQuery();
                    $xml = $getCertStatusChanges->getXML();                     
                    break;
                case "get_site_count_per_country":
                    require_once($directory.'GetSiteCountPerCountry.php');                    
                    $GetSiteCountPerCountry =  new GetSiteCountPerCountry($em);
                    $GetSiteCountPerCountry->validateParameters($this->params);
                    $GetSiteCountPerCountry->createQuery();
                    $GetSiteCountPerCountry->executeQuery();
                    $xml = $GetSiteCountPerCountry->getXML();
                    break;
                default:
                   	die("Unable to find method: {$this->method}");
                   	break;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
            die("An error has occured, please contact the GOCDB administrators at gocdb-admins@mailtalk.ac.uk");
        }
        return $xml;

    }
    
 

    /* Authorise a user against an access control list */
    function authAcl() {
        $accessList = simplexml_load_file(__DIR__.'/../../config/PI/access_control_list.xml');

        $users = $accessList->children();
        foreach($users as $user) {
            if((string) $user->dn == $this->dn)
                return;
        }

        die("Your Certificate DN is not authorized to access this resource.".
                " Certificate DN: <b>$this->dn</b><br />");
    }

    /* Authorize a user based on their certificate */
    function authAnyCert() {
        if(empty($this->dn))
            die("<No valid certificate found. A trusted certificate is ".
                    "required to access this resource. Try accessing the ".
                    "resource through the private interface.");
    }
}
?>
