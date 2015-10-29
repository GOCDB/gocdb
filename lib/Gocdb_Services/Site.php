<?php
namespace org\gocdb\services;
/* Copyright © 2011 STFC
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
require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/Role.php';
require_once __DIR__ . '/RoleConstants.php'; 
require_once __DIR__ . '/NGI.php'; 
require_once __DIR__ . '/RoleActionAuthorisationService.php'; 

/**
 * GOCDB Stateless service facade (business routines) for Site objects.
 * The public API methods are transactional.
 * @todo Implement the ISiteService interface when ready.
 *
 * @author John Casson
 * @author George Ryall
 * @author David Meredith
 * @author James McCarthy
 */
class Site extends AbstractEntityService{
   
    private $roleActionAuthorisationService;
     
    function __construct(/*$roleActionAuthorisationService*/) {
        parent::__construct();
        //$this->roleActionAuthorisationService = $roleActionAuthorisationService;
    }


    public function setRoleActionAuthorisationService(RoleActionAuthorisationService $roleActionAuthService){
        $this->roleActionAuthorisationService = $roleActionAuthService; 
    }
    
    /*
     * Since all the service methods in a service facade are atomic and fully
     * isolated, we can call getNewConnection(TRUE|FALSE) from within the
     * methods to get a new db connection. If the tx needs to be propagated
     * across different service method calls, consider merging the multiple
     * calls into a new transactional service method.
     */

    /**
     * Finds a single site by ID and returns its entity
     * @param int $id the site ID
     * @return Site a site object
     */
    public function getSite($id) {
    	$dql = "SELECT s FROM Site s
				WHERE s.id = :id";

    	$site = $this->em
	    	->createQuery($dql)
	    	->setParameter('id', $id)
	    	->getSingleResult();

    	return $site;
    }

    /**
     * Edit a site entity (site object linked to a production status,
     * cert status, NGI) in the DB.
     * Returns the object id of the updated site
     *
     * Accepts an array $site_data as a parameter. $site_data's format is as follows:
     *  Array
     *   (
     *      [Scope] => EGI
     *      [ROC] => France
     *      [Country] => Cuba
     *      [Timezone] => UTC
     *      [ProductionStatus] => Production
     *      [Site] => Array
     *      (
     *              [SHORT_NAME] => SecondProperInsertedSite
     *              [OFFICIAL_NAME] => Second Proper inserted Site
     *              [HOME_URL] => https://home.url.com
     *              [GIIS_URL] => ldap://test.com
     *              [IP_RANGE] => 10.0.0.1/10.0.0.255
     *              [IP_V6_RANGE] => 0000:0000:0000:0000:0000:0000:0000:0000[/int]
     *              [LOCATION] => England
     *              [LATITUDE] =>
     *              [LONGITUDE] =>
     *              [DESCRIPTION] => My test site description
     *              [EMAIL] => JCasson@hithere.com
     *              [CONTACTTEL] => 0175675309
     *              [EMERGENCYTEL] => 08464636
     *              [CSIRTEMAIL] => JCasson@hithere.com
     *              [CSIRTTEL] => 018386
     *              [EMERGENCYEMAIL] => JCasson@hi.com
     *              [HELPDESKEMAIL] => JCasson@324.com
     *              [DOMAIN] => test.host.com
     *              [TIMEZONE] => Europe/London
     *      )
     *
     *      [COBJECTID] => 706
     *  )
     * @param Site $site The site entity to be updated
     * @param array $newValues Array of updated site data, specified above.
     * @param org\gocdb\services\User $user The current user
     * return Site The updated site entity
     */
    function editSite(\Site $site, $newValues, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        if($user == null){
            throw new \Exception("Null user can't edit site"); 
        }
        // Check to see whether the user has a role that covers this site
        //$this->edit Authorization($site, $user);
        //if(count($this->authorize Action(\Action::EDIT_OBJECT, $site, $user))==0){
        if($this->roleActionAuthorisationService->authoriseAction(\Action::EDIT_OBJECT, $site, $user)->getGrantAction()==FALSE){
            throw new \Exception("You don't have permission over ". $site->getShortName());
        }
        
        $this->validate($newValues['Site'], 'site');
        // TODO: Check the sitename is unique (reusable code in addSite())
        
        //check there are the required number of scopes specified
        $this->checkNumberOfScopes($newValues['Scope_ids']);

	// check childServiceScopeAction is a known value
	if($newValues['childServiceScopeAction'] != 'noModify' &&
		$newValues['childServiceScopeAction'] != 'inherit' && 
		$newValues['childServiceScopeAction'] != 'override' ){
	    throw new \Exception("Invalid scope update action"); 
	}
        
        $this->em->getConnection()->beginTransaction();

        try {

            // Set the site's member variables
            $site->setOfficialName($newValues['Site']['OFFICIAL_NAME']);
            $site->setShortName($newValues['Site']['SHORT_NAME']);
            $site->setDescription($newValues['Site']['DESCRIPTION']);
            $site->setHomeUrl($newValues['Site']['HOME_URL']);
            $site->setEmail($newValues['Site']['EMAIL']);
            $site->setTelephone($newValues['Site']['CONTACTTEL']);
            $site->setGiisUrl($newValues['Site']['GIIS_URL']);
            $site->setLatitude($newValues['Site']['LATITUDE']);
            $site->setLongitude($newValues['Site']['LONGITUDE']);
            $site->setCsirtEmail($newValues['Site']['CSIRTEMAIL']);
            $site->setIpRange($newValues['Site']['IP_RANGE']);
            $site->setIpV6Range($newValues['Site']['IP_V6_RANGE']);
            $site->setDomain($newValues['Site']['DOMAIN']);
            $site->setLocation($newValues['Site']['LOCATION']);
            $site->setCsirtTel($newValues['Site']['CSIRTTEL']);
            $site->setEmergencyTel($newValues['Site']['EMERGENCYTEL']);
            $site->setEmergencyEmail($newValues['Site']['EMERGENCYEMAIL']);
            $site->setAlarmEmail($newValues['Site']['EMERGENCYEMAIL']);
            $site->setHelpdeskEmail($newValues['Site']['HELPDESKEMAIL']);
            $site->setTimezoneId($newValues['Site']['TIMEZONE']); 

            // update the target infrastructure
            $dql = "SELECT i FROM Infrastructure i WHERE i.name = :name";
            $inf = $this->em->createQuery($dql)->setParameter('name', $newValues['ProductionStatus'])->getSingleResult();
            $site->setInfrastructure($inf);

            // Update the site's scope
            // firstly remove all existing scope links
            $scopes = $site->getScopes();
            foreach($scopes as $s) {
                $site->removeScope($s);
            }
          
            //find then link each scope specified to the site
            foreach($newValues['Scope_ids'] as $scopeId){
                $dql = "SELECT s FROM Scope s WHERE s.id = ?1";
                $scope = $this->em->createQuery($dql)
                             ->setParameter(1, $scopeId)
                             ->getSingleResult();
                $site->addScope($scope);
            }

	    // Update the child service scopes 
	    if($newValues['childServiceScopeAction'] == 'noModify'){
	        // do nothing to child service scopes, leave intact 	
	    } else if($newValues['childServiceScopeAction'] == 'inherit'){
		// iterate each child service and ensure it has all the site scopes 
		$services = $site->getServices();
		/* @var $service \Service */
		foreach($services as $service){
		    // for this service, see if it has each siteScope, if not add it  
		    foreach($site->getScopes() as $siteScope){
			$addScope = true; 
			foreach($service->getScopes() as $servScope){
			    if($siteScope == $servScope){
				$addScope = false; 
				break; 
			    }
			}
			if($addScope){
			   $service->addScope($siteScope);  
			}
		    }
		}
		
	    } else if($newValues['childServiceScopeAction'] == 'override'){
		$services = $site->getServices();
		/* @var $service \Service */
		foreach($services as $service){
		    // remove all service's existing scopes
		    foreach($service->getScopes() as $servScope){
			$service->removeScope($servScope); 
		    }
		    // add all site scopes 
		    foreach($site->getScopes() as $siteScope){
			$service->addScope($siteScope); 
		    }
		}
	        	
	    } else {
		throw new \Exception("Invalid scope update action"); 	
	    }
            
            // get / set the country
            $dql = "SELECT c FROM Country c WHERE c.name = ?1";
            $country = $this->em->createQuery($dql)
                             ->setParameter(1, $newValues['Country'])
                             ->getSingleResult();
            $site->setCountry($country);

            // deprecated 
//            $dql = "SELECT t FROM Timezone t WHERE t.name = ?1";
//            $timezone = $this->em->createQuery($dql)
//                            ->setParameter(1, $newValues['Timezone'])
//                            ->getSingleResult();
//            $site->setTimezone($timezone);

            $this->em->merge($site);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $ex){
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        return $site;
    }

    /**
     * Get an array of Role names granted to the user that permit the requested 
     * action on the given Site. If the user has no roles that 
     * permit the requested action, then return an empty array. 
     * <p>
     * Suppored actions: EDIT_OBJECT, SITE_EDIT_CERT_STATUS, 
     * SITE_ADD_SERVICE, SITE_DELETE_SERVICE, 
     * GRANT_ROLE, REJECT_ROLE, REVOKE_ROLE
     * 
     * @param string $action @see \Action 
     * @param \Site $site
     * @param \User $user
     * @return array of RoleName strings that grant the requested action  
     * @throws \LogicException if action is not supported or is unknown 
     */
    /*public function authorize Action($action, \Site $site, \User $user = null ) {
        if(is_null($user)){
            return array(); // empty array if null user 
        }
        if (!in_array($action, \Action::getAsArray())) {
            throw new \LogicException('Coding Error - Invalid action');
        }
        $roleService = new \org\gocdb\services\Role(); // to inject
        $roleService->setEntityManager($this->em);
        
        if ($action == \Action::EDIT_OBJECT || $action == \Action::SITE_ADD_SERVICE 
                || $action == \Action::SITE_DELETE_SERVICE) {
            // Site leve roles and parent NGI level roles can edit the site 
            $requiredRoles = array(
                // C
                \RoleTypeName::SITE_ADMIN,
                // C' 
                \RoleTypeName::SITE_SECOFFICER,
                \RoleTypeName::SITE_OPS_DEP_MAN,
                \RoleTypeName::SITE_OPS_MAN,
                // D
                \RoleTypeName::REG_FIRST_LINE_SUPPORT,
                \RoleTypeName::REG_STAFF_ROD,
                // D' 
                \RoleTypeName::NGI_SEC_OFFICER,
                \RoleTypeName::NGI_OPS_DEP_MAN,
                \RoleTypeName::NGI_OPS_MAN
            );
            // get the user's actual roles 
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($site, $user);  
            if($site->getNgi() != null){
                // A Site should always have a parent NGI, but this is not enforced
                // by the DB constraints as this may? be needed in future - also 
                // unit tests use orphan sites. Thus this method is defensive. 
                $usersActualRoleNames = array_merge($usersActualRoleNames, 
                         $roleService->getUserRoleNamesOverEntity($site->getNgi(), $user));
            }
            // return intersection between between required roles and user's actual roles
            $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));
            
        } else if($action == \Action::GRANT_ROLE || 
                $action == \Action::REJECT_ROLE || $action == \Action::REVOKE_ROLE){
            // Site managers and NGI managers can manage roles 
            $requiredRoles = array(
                // C' (note, SITE_ADMIN can't manage role requests)
                \RoleTypeName::SITE_SECOFFICER,
                \RoleTypeName::SITE_OPS_DEP_MAN,
                \RoleTypeName::SITE_OPS_MAN, 
                // D' (note, D can't manage a role requests)
                \RoleTypeName::NGI_SEC_OFFICER,
                \RoleTypeName::NGI_OPS_DEP_MAN,
                \RoleTypeName::NGI_OPS_MAN); 
            // get the user's actual roles 
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($site, $user); 
            if ($site->getNgi() != null) {
                // A Site should always have a parent NGI, but this is not enforced
                // by the DB constraints as this may? be needed in future - also 
                // unit tests use orphan sites. Thus this method is defensive. 
                $usersActualRoleNames = array_merge($usersActualRoleNames, 
                        $roleService->getUserRoleNamesOverEntity($site->getNgi(), $user));
            }
            // return intersection between between required roles and user's actual roles
            $enablingRoles = array_intersect($requiredRoles, $usersActualRoleNames); 
            
        } else if ($action == \Action::SITE_EDIT_CERT_STATUS){
            // only NGI manager and Project level roles can edit cert status 
            $requiredRoles = array(
                // D' 
                \RoleTypeName::NGI_SEC_OFFICER,
                \RoleTypeName::NGI_OPS_DEP_MAN,
                \RoleTypeName::NGI_OPS_MAN, 
                // E  
                //\RoleTypeName::CIC_STAFF, 
                \RoleTypeName::COD_STAFF,
                \RoleTypeName::COD_ADMIN,
                \RoleTypeName::EGI_CSIRT_OFFICER,
                \RoleTypeName::COO);
            
            $usersActualRoleNames = array(); 
            if($site->getNgi() != null){
                // A Site should always have a parent NGI, but this is not enforced
                // by the DB constraints as this may? be needed in future - also 
                // unit tests use orphan sites. Thus this method is defensive. 
                $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($site->getNgi(), $user);
                // Get all project level roles for all the projects that group the site's ngi
                if(count($site->getNgi()->getProjects()) > 0){
                    foreach ($site->getNgi()->getProjects() as $parentProject) {
                       $usersActualRoleNames = array_merge($usersActualRoleNames, 
                               $roleService->getUserRoleNamesOverEntity($parentProject, $user)); 
                    }
                }
            }
            // return intersection between required roles and user's actual roles 
            $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));
        } else {
            throw new \LogicException('Unsupported Action');  
        }
       
        if($user->isAdmin()){
           $enablingRoles[] = \RoleTypeName::GOCDB_ADMIN;  
        }
        return array_unique($enablingRoles);
    }*/

    
  

    /**
     * Validates the user inputted site data against the
     * checks in the gocdb_schema.xml and applies additional logic checks 
     * that can't be described in the gocdb_schema.xml. 
     *  
     * @param array $site_data containing all the fields for a GOCDB_SITE
     *                       object
     * @throws \Exception if the site data can't be
     *                   validated. The \Exception message will contain a human
     *                   readable description of which field failed validation.
     * @return null
     */
    private function validate($site_data, $type) {
        require_once __DIR__.'/Validate.php';
        $serv = new \org\gocdb\services\Validate(); 
        foreach($site_data as $field => $value) {
            $valid = $serv->validate($type, $field, $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }

        // Apply additional logic for validation that can't be captured solely using gocdb_schema.xml
        if (!empty($site_data['IP_V6_RANGE'])) {
            require_once __DIR__.'/validation/IPv6Validator.php';
            $validator = new \IPv6Validator();
            $errors = array();
            $errors = $validator->validate($site_data['IP_V6_RANGE'], $errors);
            if (count($errors) > 0) {
                throw new \Exception($errors[0]); // show the first message. 
            }
        }
    }
  
    /**
     * Return all {@see \Site}s that satisfy the specfied filter parameters. 
     * <p>  
     * $filterParams defines an associative array of optional parameters for 
     * filtering the sites. The supported Key => Value pairs include: 
     * <ul>
     *   <li>'sitename' => String site name</li>
     *   <li>'roc' => String name of parent NGI/ROC</li> 
     *   <li>'country' => String country name</li>
     *   <li>'certification_status' => String certification status value e.g. 'Certified'</li>
     *   <li>'exclude_certification_status' => String exclude sites with this certification status</li>
     *   <li>'production_status' => String site production status value</li>
     *   <li>'scope' => 'String,comma,sep,list,of,scopes,e.g.,egi,wlcg'</li>
     *   <li>'scope_match' => String 'any' or 'all' </li>
     *   <li>'extensions' => String extensions expression to filter custom key=value pairs</li>
     * <ul>
     * 
     * @param array $filterParams
     * @return array Site array
     */
    public function getSitesFilterByParams($filterParams){
        require_once __DIR__.'/PI/GetSite.php'; 
	$getSite = new GetSite($this->em); 
	//$params = array('sitename' => 'GRIDOPS-GOCDB');  
        //$params = array('scope' => 'EGI,DAVE', 'sitename' => 'GRIDOPS-GOCDB');  	
	//$params = array('scope' => 'EGI,Local', 'scope_match' => 'any', 'exclude_certification_status' => 'Closed');  
	//$params = array('scope' => 'EGI,Local', 'scope_match' => 'all');  
	//$params = array('scope' => 'EGI,DAVE', 'scope_match' => 'all');  
	//$params = array('extensions' => '(aaa=123)(dave=\(someVal with parethesis\))(test=test)'); 
	$getSite->validateParameters($filterParams); 
	$getSite->createQuery(); 
	$sites = $getSite->executeQuery(); 
	return $sites; 
    }

    
    /**
     * Returns all Sites filtered by the given parameters with the following  
     * joined relations: CertificationStatus, Scope, NGI, Infrastructure. 
     * 
     * Sites are matched using SQL 'like' statements so you can for e.g. specify
     * $ngiName = '%_ngi' to select all Sites whose parent NGI's name ends in '_ngi'.   
     * All parametes are nullable. Null params are not used for filtering results.   
     * 
     * @param string $ngiName NGI name   
     * @param string $prodStatus Production status/target infrastructure, usually Test or Production
     * @param string $certStatus Certification status value (Certified, Uncertified, Candidate, Suspended, Closed)
     * @param string $scopeName Name of a scope value
     * @param boolean $showClosed true or false
     * @param integer $siteId Site id or null
     * @param string $siteExtPropKeyName Site extension property name
     * @param string $siteExtPropKeyValue Site extension property value
     * @return array An array of site objects with joined entities. 
     */
    public function getSitesBy(
            $ngiName=NULL, $prodStatus=NULL, $certStatus=NULL,
            $scopeName=NULL, $showClosed=NULL, $siteId=NULL, 
            $siteExtPropKeyName=NULL, $siteExtPropKeyValue=NULL) {

        $qb = $this->em->createQueryBuilder();
        $qb ->select('DISTINCT s', 'sc', 'n', 'i')
            ->from('Site', 's')
            ->leftjoin('s.certificationStatus', 'cs')            
            ->leftjoin('s.scopes', 'sc')    
            ->leftjoin('s.ngi', 'n')    
            ->leftjoin('s.infrastructure', 'i')
            ->orderBy('s.shortName');
        
        if($scopeName != null && $scopeName != '%%'){
            $qb->andWhere($qb->expr()->like('sc.name', ':scope'))
                ->setParameter(':scope', $scopeName);
        }
        
        if($ngiName != null && $ngiName != '%%'){
            $qb->andWhere($qb->expr()->like('n.name', ':ngi'))
                ->setParameter(':ngi', $ngiName);
        }
        
        if($prodStatus != null && $prodStatus != '%%'){
            $qb->andWhere($qb->expr()->like('i.name', ':prodStatus'))
                ->setParameter(':prodStatus', $prodStatus);
        }
        
        if($certStatus != null && $certStatus != '%%'){
            $qb ->andWhere($qb->expr()->like('cs.name', ':certStatus'))
                ->setParameter(':certStatus', $certStatus);
        }
        
        if($siteId != null && $siteId != '%%'){
            $qb->andWhere($qb->expr()->like('s.id', ':siteId'))
                ->setParameter(':siteId', $siteId);
        }
        
        if($showClosed != 1){
            $qb->andWhere( $qb->expr()->not($qb->expr()->like('cs.name', ':closed')))
                ->setParameter(':closed', 'Closed');            
        }
        
        if($siteExtPropKeyName != null && $siteExtPropKeyName != '%%'){
            if($siteExtPropKeyValue == null || $siteExtPropKeyValue == ''){
                $siteExtPropKeyValue='%%';
            }
        
            $sQ = $this->em->createQueryBuilder();
            $sQ ->select('s1'.'.id')
            ->from('Site', 's1')
            ->join('s1.siteProperties', 'sp')
            ->andWhere($sQ->expr()->andX(
                    $sQ->expr()->eq('sp.keyName', ':keyname'),
                    $sQ->expr()->like('sp.keyValue', ':keyvalue')));
        
            $qb ->andWhere($qb->expr()->in('s', $sQ->getDQL()));
            $qb ->setParameter(':keyname', $siteExtPropKeyName)
            ->setParameter(':keyvalue', $siteExtPropKeyValue);
        
        }
        
        $query = $qb->getQuery();
        $sites = $query->execute();

        return $sites;
    }
    
    /**
     *
     * @return array of all properties for a site
     */
    public function getProperties($id) {
        $dql = "SELECT p FROM SiteProperty p WHERE p.parentSite = :ID";
        $properties = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $properties;
    }
    
    /**
     *
     * @return a single site property
     */
    public function getProperty($id) {
        $dql = "SELECT p FROM SiteProperty p WHERE p.id = :ID";
        $property = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $property;
    }
    
    /**
     *  @return array of NGIs
     */
    public function getNGIs() {
        $dql = "SELECT n from NGI n";
        $ngis = $this->em 
            ->createQuery($dql)
            ->getResult();
        return $ngis;
    }

    /**
     *  Returns all certification statues in GOCDB
     *  @return array An array of CertificationStatus objects
     */
    public function getCertStatuses() {
        $dql = "SELECT c from CertificationStatus c";
        $certStatuses = $this->em 
            ->createQuery($dql)
            ->getResult();
        return $certStatuses;
    }

    /**
     *  Return all production statuses in the DB
     *  @return Array an array of ProductionStatus objects
     */
    public function getProdStatuses() {
        $dql = "SELECT i from Infrastructure i";
        $prodStatuses = $this->em
            ->createQuery($dql)
            ->getResult();
        return $prodStatuses;
    }
    
    /**
     *  Return doctrine production status entity from name
     *  @return production status with sepcified name, throws error if multiple results
     */
    public function getProdStatusByName($name) {
        $dql = "SELECT i from Infrastructure i WHERE i.name = :Name";
        $prodStatuses = $this->em
            ->createQuery($dql)
            ->setParameter('Name', $name)
            ->getOneOrNullResult();
        return $prodStatuses;
    }

    /**
     *  Return all countries in the DB
     *  @return Array an array of Country objects
     */
    public function getCountries() {
    	$dql = "SELECT c from Country c
    			ORDER BY c.name";
    	$countries = $this->em 
    		->createQuery($dql)
    		->getResult();
    	return $countries;
    }

    /**
     *  Return all timezones in the DB
     *  @return Array an array of Timezone objects
     */
//    public function getTimezones() {
//    	$dql = "SELECT t from Timezone t
//    			ORDER BY t.name";
//    	$timezones = $this->em
//    		->createQuery($dql)
//    		->getResult();
//    	return $timezones;
//    }


    /**
     * Returns the downtimes linked to a site.
     * @param integer $id Site ID
     * @param integer $dayLimit Limit to downtimes that are only $dayLimit old (can be null) */
    public function getDowntimes($id, $dayLimit) {
    	if($dayLimit != null) {
    		$di = \DateInterval::createFromDateString($dayLimit . 'days');
    		$dayLimit = new \DateTime();
    		$dayLimit->sub($di);
    	}

        $dql = "SELECT d FROM Downtime d
				WHERE d.id IN (
					SELECT d2.id FROM Site s
					JOIN s.services ses
					JOIN ses.downtimes d2
					WHERE s.id = :siteId
				)
				AND (
					:dayLimit IS NULL
					OR d.startDate > :dayLimit
				)
    	        ORDER BY d.startDate DESC";

    	$downtimes = $this->em
	    	->createQuery($dql)
	    	->setParameter('siteId', $id)
	    	->setParameter('dayLimit', $dayLimit)
	    	->getResult();

    	return $downtimes;
    }

    /**
     * Adds a site. $values is in the following format:
     * Array
     * (
     *     [Scope] => 2
     *     [Country] => 6
     *     [Timezone] => 1
     *     [ProductionStatus] => 1
     *     [NGI] => 11
     *     [Certification_Status] => 1
     *     [Site] => Array
     *     (
     *                 [SHORT_NAME] => MyTestSite
     *                 [OFFICIAL_NAME] => TestSite
     *                 [HOME_URL] => https://test.host.com
     *                 [GIIS_URL] => ldap://giis_url:234
     *                 [IP_RANGE] => 0.0.0.0/255.255.255.234
     *                 [IP_V6_RANGE] => 0000:0000:0000:0000:0000:0000:0000:0000[/int]
     *                 [LOCATION] => Britain
     *                 [LATITUDE] => 234
     *                 [LONGITUDE] => 234
     *                 [DESCRIPTION] => Test
     *                 [EMAIL] => lcg@rl.ac.uk
     *                 [CONTACTTEL] => +44 01925 603762, +44 01235 44 5010234
     *                 [EMERGENCYTEL] => +44 01925 603762, +44 01235 44 5010, +44 01925 603513234
     *                 [CSIRTEMAIL] => gocdb-admins@mailtalk.ac.uk
     *                 [CSIRTTEL] => +44 01925 603762, +44 01235 44 5010, +44 01925 603513234
     *                 [EMERGENCYEMAIL] => jcasson@234.com
     *                 [HELPDESKEMAIL] => gocdb-admins@mailtalk.ac.uk
     *                 [DOMAIN] => Test.com
     *     )
     * )
     * @param array $values New Site Values
     * @param \User $user User making the request
     */
    public function addSite($values, \User $user =null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        
        if(is_null($user)){
            throw new Exception("Unregistered users may not add new sites");
        }
        
        if(!$user->isAdmin()){
            $ngiService = new \org\gocdb\services\NGI();
            $ngiService->setEntityManager($this->em);
            $usersNGIs = $ngiService->getNGIsBySupportedAction(\Action::NGI_ADD_SITE, $user);
            if (count($usersNGIs) == 0) {
                throw new \Exception("You do not have permission to add a new site."
                        . " To add a new site you require a managing role over an NGI");
            }
        }
         

    	// do as much validation before starting a new db tx
    	// check the site object data is valid
    	$this->validate($values['Site'], 'site');
        
        //check there are the required number of scopes specified
        $this->checkNumberOfScopes($values['Scope_ids']);
        
    	$this->uniqueCheck($values['Site']['SHORT_NAME']);

    	// Populate the entity
    	try {
    	    /* Create a PK for this site
    	     * This is persisted/flushed (but not committed) before the site 
    	     * so the PK is set by the database.
    	     * If the site insertion fails the PK can still be rolled back.  
    	     */
    	    $this->em->getConnection()->beginTransaction();
    	    $pk = new \PrimaryKey();
    	    $this->em->persist($pk);
            // flush synchronizes the in-memory state of managed objects with the database
            // but we can still rollback 
    	    $this->em->flush();
    	    //$this->em->getConnection()->commit();
    	    //$this->em->getConnection()->beginTransaction();
	    	$site = new \Site();
	    	$site->setPrimaryKey($pk->getId() . "G0");
	    	$site->setOfficialName($values['Site']['OFFICIAL_NAME']);
	    	$site->setShortName($values['Site']['SHORT_NAME']);
	    	$site->setDescription($values['Site']['DESCRIPTION']);
	    	$site->setHomeUrl($values['Site']['HOME_URL']);
	    	$site->setEmail($values['Site']['EMAIL']);
	    	$site->setTelephone($values['Site']['CONTACTTEL']);
	    	$site->setGiisUrl($values['Site']['GIIS_URL']);
	    	$site->setLatitude($values['Site']['LATITUDE']);
	    	$site->setLongitude($values['Site']['LONGITUDE']);
	    	$site->setCsirtEmail($values['Site']['CSIRTEMAIL']);
	    	$site->setIpRange($values['Site']['IP_RANGE']);	    	
	    	$site->setIpV6Range($values['Site']['IP_V6_RANGE']);
	    	$site->setDomain($values['Site']['DOMAIN']);
	    	$site->setLocation($values['Site']['LOCATION']);
	    	$site->setCsirtTel($values['Site']['CSIRTTEL']);
	    	$site->setEmergencyTel($values['Site']['EMERGENCYTEL']);
	    	$site->setEmergencyEmail($values['Site']['EMERGENCYEMAIL']);
	    	$site->setHelpdeskEmail($values['Site']['HELPDESKEMAIL']);
            $site->setTimezoneId($values['Site']['TIMEZONE']);
	    	
	    	// get the parent NGI entity
	    	$dql = "SELECT n FROM NGI n WHERE n.id = :id";
	    	$parentNgi = $this->em->createQuery($dql)
		    	->setParameter('id', $values['NGI'])
		    	->getSingleResult();
	    	$site->setNgiDoJoin($parentNgi);

	    	// get the target infrastructure
	    	$dql = "SELECT i FROM Infrastructure i WHERE i.id = :id";
	    	$inf = $this->em->createQuery($dql)
		    	->setParameter('id', $values['ProductionStatus'])
		    	->getSingleResult();
	    	$site->setInfrastructure($inf);

	    	// get the cert status
            if(!isset($values['Certification_Status']) || 
                    $values['Certification_Status'] == null || $values['Certification_Status'] == ''){
                throw new \LogicException(
                        "Missing seed data - No certification status values in the DB (required data)"); 
            }
	    	$dql = "SELECT c FROM CertificationStatus c WHERE c.id = :id";
	    	$certStatus = $this->em->createQuery($dql)
	    		->setParameter('id', $values['Certification_Status'])
	    		->getSingleResult();
	    	$site->setCertificationStatus($certStatus);
            $now = new \DateTime('now',  new \DateTimeZone('UTC')); 
            $site->setCertificationStatusChangeDate($now); 

            // create a new CertStatusLog 
            $certLog = new \CertificationStatusLog(); 
            $certLog->setAddedBy($user->getCertificateDn()); 
            $certLog->setNewStatus($certStatus->getName()); 
            $certLog->setOldStatus(null); 
            $certLog->setAddedDate($now); 
            $certLog->setReason('Initial creation'); 
            $this->em->persist($certLog); 
            $site->addCertificationStatusLog($certLog); 
            

	    	// Set the scopes
            foreach($values['Scope_ids'] as $scopeId){
                $dql = "SELECT s FROM Scope s WHERE s.id = :id";
                $scope = $this->em->createQuery($dql)
                    ->setParameter('id', $scopeId)
                    ->getSingleResult();
                $site->addScope($scope);
            }
            
	    	// get the country
	    	$dql = "SELECT c FROM Country c WHERE c.id = :id";
	    	$country = $this->em->createQuery($dql)
	    		->setParameter('id', $values['Country'])
	    		->getSingleResult();
	    	$site->setCountry($country);

	    	// deprecated - don't use the lookup DB entity  
//	    	$dql = "SELECT t FROM Timezone t WHERE t.id = :id";
//	    	$timezone = $this->em->createQuery($dql)
//	    		->setParameter('id', $values['Timezone'])
//	    		->getSingleResult();
//	    	$site->setTimezone($timezone);
	    	
	    	$this->em->persist($site);
	    	$this->em->flush();
	    	$this->em->getConnection()->commit();
    	} catch(\Exception $ex){
    		$this->em->getConnection()->rollback();
    		//$this->em->remove($pk);
    		//$this->em->flush();
    		$this->em->close();
    		throw $ex;
    	}
    	return $site;

    }


    /**
     * Is there a site in the database named $siteName?
     * @param string $siteName Site name
     */
    private function uniqueCheck($shortName) {
        $dql = "SELECT s from Site s
    			WHERE s.shortName = :name";
        $query = $this->em->createQuery($dql)
            ->setParameter('name', $shortName);
        if(count($query->getResult()) > 0) {
            throw new \Exception("A site named " . $shortName . " already exists.");
        }
    }
    
	/*
	 * Moves a site to a new NGI. Site to NGI is a many to one
	 * relationship, so moving the site from one NGI removes it
	 * from the other.
	 * 
	 * @param \site $site site to be moved
	 * @param \NGI $NGI NGI to which $site is to be moved
	 * @return null
	 */
	 public function moveSite(\Site $Site, \NGI $NGI, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);
        
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //If the NGI or site have no ID - throw logic exception
            $site_id = $Site->getId();
            if (empty($site_id)) {
                throw new LogicException('Site has no ID');
            }
            $ngi_id = $NGI->getId();
            if (empty($ngi_id)) {
                throw new LogicException('NGI has no ID');
            }
            //find old NGI
            $old_NGI = $Site->getNgi();

            //If the NGI has changed, then we move the site.
            if ($old_NGI != $NGI) {
                   
                 //Remove the site from the old NGI FIRST if it has an old NGI
                if (!empty($old_NGI)) {
                    $old_NGI->getSites()->removeElement($Site);
                }
                //Add site to new NGI
                $NGI->addSiteDoJoin($Site);
                //$Site->setNgiDoJoin($NGI); 
               
                //persist
                $this->em->merge($NGI);
                $this->em->merge($old_NGI);
            }//close if

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
    
    private function checkNumberOfScopes($scopeIds){
        require_once __DIR__ . '/Config.php';
        $configService = new \org\gocdb\services\Config();
        $minumNumberOfScopes = $configService->getMinimumScopesRequired('site');
        
        if(sizeof($scopeIds)<$minumNumberOfScopes){
            $s = "s";
            if($minumNumberOfScopes==1){
                $s="";
            }
            throw new \Exception("A site must have at least " . $minumNumberOfScopes . " scope".$s." assigned to it.");
        }
    }   
    
    /**
     * Delete a site. Only available to admins.
     * @param \Site $s site for deletion
     * @param \User $user user doing the deleting
     * @param $logSiteAndServicesInArchive Archive the site and its services or not.  
     * Useful for testing - an incomplete site or its service can easily cause errors when archiving.  
     * @throws \Exception
     */
    public function deleteSite(\Site $s, \User $user =null, $logSiteAndServicesInArchive=true) {
        require_once __DIR__ . '/../DAOs/SiteDAO.php';
        require_once __DIR__ . '/../DAOs/ServiceDAO.php';
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        
        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);
        
        $this->em->getConnection()->beginTransaction();
        try {
            $siteDAO = new \SiteDAO();
            $siteDAO->setEntityManager($this->em);
            $serviceDAO = new \ServiceDAO();
            $serviceDAO->setEntityManager($this->em);
            
            //Archive site 
            if($logSiteAndServicesInArchive){
                $siteDAO->addSiteToArchive($s, $user);
            }
            
            //delete each child service
            foreach($s->getServices() as $service){
                if($logSiteAndServicesInArchive){
                   //archive the srvice
                   $serviceDAO->addServiceToArchive($service, $user);
                }
                //remove the service (and any downtimes associated with it and only it)
                $serviceDAO->removeService($service);
            }

            //remove the site
            $siteDAO->removeSite($s);
            
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
	}
    
	/**
	 * This method will check that a user has edit permissions over a site before allowing a user to add, edit or delete 
	 * a site property. 
	 * 
	 * @param \User $user
	 * @param \Site $site	 
	 * @throws \Exception
	 */
	public function validatePropertyActions(\User $user, \Site $site) {
	    // Check to see whether the user has a role that covers this site
	    //if(count($this->authorize Action(\Action::EDIT_OBJECT, $site, $user))==0){
	    if ($this->roleActionAuthorisationService->authoriseAction(\Action::EDIT_OBJECT, $site, $user)->getGrantAction() == FALSE) {
		throw new \Exception("You don't have permission over " . $site->getShortName());
	    }
	}

    /**
	 * Adds a key value pair to a site
	 * @param $values
	 * @param \User $user
	 * @throws Exception
	 * @return \SiteProperty
	 */
	public function addProperty($values,\User $user = null) {
	    // Check the portal is not in read only mode, throws exception if it is
	    $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );
        
	    $this->validate($values['SITEPROPERTIES'], 'siteproperty');
	    
	    $keyname = $values ['SITEPROPERTIES'] ['NAME'];
	    $keyvalue = $values ['SITEPROPERTIES'] ['VALUE'];
	    $siteID = $values ['SITEPROPERTIES'] ['SITE'];
	    $site = $this->getSite($siteID);
        
	    //Validate User to perform this action
	    $this->validatePropertyActions($user, $site);

	    //TODO: Future development possibility to check keyname not reserved
	    //$this->checkNotReserved($user, $site, $keyname);
   	    
	    $this->em->getConnection ()->beginTransaction ();
	
	    try {
	        $siteProperty = new \SiteProperty ();
	        $siteProperty->setKeyName ( $keyname );
	        $siteProperty->setKeyValue ( $keyvalue );
	        $site = $this->em->find ( "Site", $siteID );
	        $site->addSitePropertyDoJoin ( $siteProperty );
	        $this->em->persist ( $siteProperty );
	        	
	        $this->em->flush ();
	        $this->em->getConnection ()->commit ();
	    } catch ( \Exception $e ) {
	        $this->em->getConnection ()->rollback ();
	        $this->em->close ();
	        throw $e;
	    }
	    return $siteProperty;
	}
	
	/**
	 * Deletes a site property
	 *
	 * @param \Site $site
	 * @param \User $user
	 * @param \SiteProperty $prop
	 */
	public function deleteSiteProperty(\Site $site,\User $user = null,\SiteProperty $prop) {
	    // Check the portal is not in read only mode, throws exception if it is
	    $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );
	   	     
	    // Validate the user has permission to delete a property	   
	    $this->validatePropertyActions($user, $site);
	   	    
	    $this->em->getConnection ()->beginTransaction ();
	    try {
	        // Site is the owning side so remove elements from site.
	        $site->getSiteProperties ()->removeElement ( $prop );
	        $this->em->remove ( $prop );
	        $this->em->flush ();
	        $this->em->getConnection ()->commit ();
	    } catch ( \Exception $e ) {
	        $this->em->getConnection ()->rollback ();
	        $this->em->close ();
	        throw $e;
	    }
	}
	
    /**
     * Edit a site's properties. 
     * 
     * @param \Site $site
     * @param \User $user
     * @param \SiteProperty $prop
     * @param array $newValues
     * @throws \Exception
     */
	public function editSiteProperty(\Site $site,\User $user,\SiteProperty $prop, $newValues) {
	    // Check the portal is not in read only mode, throws exception if it is
	    $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );	    
	    
	    //Validate User to perform this action
	    $this->validatePropertyActions($user, $site);
	    
	    $this->validate($newValues['SITEPROPERTIES'], 'siteproperty');
	    
	    $keyname=$newValues ['SITEPROPERTIES'] ['NAME'];
	    $keyvalue=$newValues ['SITEPROPERTIES'] ['VALUE'];

	    //$this->checkNotReserved($user, $site, $keyname);
	    
	    $this->em->getConnection ()->beginTransaction ();
	
	    try {
	        	
	        // Set the site propertys new member variables
	        $prop->setKeyName ( $keyname );
	        $prop->setKeyValue ( $keyvalue );
	        	
	        $this->em->merge ( $prop );
	        $this->em->flush ();
	        $this->em->getConnection ()->commit ();
	    } catch ( \Exception $ex ) {
	        $this->em->getConnection ()->rollback ();
	        $this->em->close ();
	        throw $ex;
	    }
	}
	
    /**
     * For a given site, returns an array containing the names of all the 
     * scopes that site has as keys and a boolean as value. The bool is true 
     * if the scope is shared with the parent ngi, false if not. 
     * @param \Service $service
     * @return associative array
     */
    public function getScopesWithParentScopeInfo(\Site $site){
        $parentNgi = $site->getNgi();
        $parentScopes = $parentNgi->getScopes();
        $childScopes = $site->getScopes();
        
        $parentScopesNames = array();
        foreach($parentScopes as $parentScope){
            $parentScopesNames[] =$parentScope->getName();
        }
        
        $childScopesNames = array();
        foreach($childScopes as $childScope){
            $childScopesNames[] =$childScope->getName();
        }
        
        $sharedScopesNames = array_intersect($childScopesNames, $parentScopesNames);
        
        $scopeNamesNotShared = array_diff($childScopesNames, $parentScopesNames);
        
        $ScopeNamesAndParentShareInfo = array();
        foreach($sharedScopesNames as $sharedScopesName){
            $ScopeNamesAndParentShareInfo[$sharedScopesName]=true;
        }
        foreach($scopeNamesNotShared as $scopeNameNotShared){
            $ScopeNamesAndParentShareInfo[$scopeNameNotShared]=false;
        }
        
        //can be replaced with ksort($ScopeNamesAndParentShareInfo, SORT_NATURAL); in php>=5.5
        uksort($ScopeNamesAndParentShareInfo, 'strcasecmp');
        
        return $ScopeNamesAndParentShareInfo;
    }
    
    /**
     * Returns those sites which have valid longitudes and latitudes specified
     * and are not closed
     * @return arraycollection collection of sites
     */
    public function getSitesWithGeoInfo() {
    	//Note - we remove any site with 0,0 as it's location, as we have no
        //sites in the middle of the pacific ocean. We also remove sites with 
        //invaid (too large) longs and lats (these are legacy values, new values
        // entered through the webportal have to be within expected range, 
        // historical values may not)
        $dql = "SELECT s 
                FROM Site s
                JOIN s.certificationStatus c
                WHERE s.latitude IS NOT NULL
                AND s.longitude IS NOT NULL
                AND c.name != 'Closed'
                AND (s.latitude != 0 OR s.longitude != 0)
                AND s.latitude <= 90
                AND s.latitude >= -90
                AND s.longitude <= 180
                AND s.longitude >= -180";

        $sites = $this->em
            ->createQuery($dql)
            ->getResult();

        return $sites;
    }
    
    /*
     * @return string xml string containing information required by google maps js
     */
    public function getGoogleMapXMLString(){
        $sites = $this->getSitesWithGeoInfo();
        $portalUrl = \Factory::getConfigService()->GetPortalURL();
        
        $xml = new \SimpleXMLElement("<map></map>");
		foreach($sites as $site) {
            $xmlSite = $xml->addChild('Site');
            $xmlSite->addAttribute('ShortName', $site->getShortName());
            $xmlSite->addAttribute('OfficialName', $site->getOfficialName());
            $sitePortalUrl = $portalUrl . '/index.php?Page_Type=Site&id=' . $site->getId();
            $xmlSite->addAttribute('PortalURL', htmlspecialchars($sitePortalUrl));
            $xmlSite->addAttribute('Description', htmlspecialchars($site->getDescription()));
            $xmlSite->addAttribute('Latitude', $site->getLatitude());
            $xmlSite->addAttribute('Longitude', $site->getLongitude());
		}

		$dom_sxe = dom_import_simplexml($xml);
		$dom = new \DOMDocument('1.0');
		$dom->encoding='UTF-8';
		$dom_sxe = $dom->importNode($dom_sxe, true);
		$dom_sxe = $dom->appendChild($dom_sxe);
		$dom->formatOutput = true;
		$xmlString = $dom->saveXML();
        
        return $xmlString;
    }
}