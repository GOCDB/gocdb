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
require_once __DIR__.  '/Scope.php';
require_once __DIR__.  '/Config.php';

/**
 * GOCDB service for Site object business routines.
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
    private $scopeService;
    private $configService;

    function __construct(/*$roleActionAuthorisationService, $scopeService*/) {
        parent::__construct();
        //$this->roleActionAuthorisationService = $roleActionAuthorisationService;
        //$this->scopeService = $scopeService;
        $this->configService = \Factory::getConfigService();
    }

    /**
     * Set class dependency (REQUIRED).
     * @todo Mandatory objects should be injected via constructor.
     * @param \org\gocdb\services\RoleActionAuthorisationService $roleActionAuthService
     */
    public function setRoleActionAuthorisationService(RoleActionAuthorisationService $roleActionAuthService){
        $this->roleActionAuthorisationService = $roleActionAuthService;
    }

    /**
     * Set class dependency (REQUIRED).
     * @todo Mandatory objects should be injected via constructor.
     * @param \org\gocdb\services\Scope $scopeService
     */
    public function setScopeService(Scope $scopeService){
        $this->scopeService = $scopeService;
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
        $dql = "SELECT s FROM Site s WHERE s.id = :id";
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
     * Accepts an array $siteData as a parameter. $siteData's format is as follows:
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
     *              [NOTIFY] => Y
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
        if($this->roleActionAuthorisationService->authoriseAction(
                \Action::EDIT_OBJECT, $site, $user)->getGrantAction()==FALSE){
            throw new \Exception("You don't have permission over ". $site->getShortName());
        }

        $this->validate($newValues['Site'], 'site');
        // TODO: Check the sitename is unique (reusable code in addSite())

        // EDIT SCOPE TAGS:
        // collate selected scopeIds (reserved and non-reserved)
        $scopeIdsToApply = array();
        foreach($newValues['Scope_ids'] as $sid){
            $scopeIdsToApply[] = $sid;
        }
        foreach($newValues['ReservedScope_ids'] as $sid){
            $scopeIdsToApply[] = $sid;
        }
        $selectedScopesToApply = $this->scopeService->getScopes($scopeIdsToApply);

        // If not admin, Check user edits to the site's Reserved scopes:
        // Required to prevent users manually crafting a POST request in an attempt
        // to select reserved scopes, this is unlikely but it is a possible hack.
        // Site can only have reserved tags that are already assigned or assigned to the parent.
        if (!$user->isAdmin()) {
            $selectedReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $selectedScopesToApply);

            $existingReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $site->getScopes()->toArray());

            $existingReservedScopesParent = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $site->getNgi()->getScopes()->toArray());

            foreach($selectedReservedScopes as $sc){
                // Reserved scopes must already be assigned to site or parent
                if(!in_array($sc, $existingReservedScopes) && !in_array($sc, $existingReservedScopesParent)){
                    throw new \Exception("A reserved Scope Tag was selected that is "
                            . "not assigned to the Site or to the Parent NGI");
                }
            }
        }

        //check there are the required number of optional scopes specified
        $this->checkNumberOfScopes($this->scopeService->getScopesFilterByParams(
               array('excludeReserved' => true), $selectedScopesToApply));

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


            //Set notify flag for site
            if (!isset($newValues['NOTIFY'])){
                $notify = false;
            }
            elseif ($newValues['NOTIFY'] == "Yes") {
                $notify = true;
            } else {
                $notify = false;
            }
            $site->setNotify ($notify);

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

            // Link the requested scopes to the site
            foreach($selectedScopesToApply as $scope){
                $site->addScope($scope);
            }

            // Remove reserved scopes from child services that are not applied on site
            $siteReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $site->getScopes()->toArray());
            foreach ($site->getServices() as $service) {
                $serviceReservedScopes = $this->scopeService->getScopesFilterByParams(
                        array('excludeNonReserved' => true), $service->getScopes()->toArray());
                foreach ($serviceReservedScopes as $seReservedScope) {
                    if (!in_array($seReservedScope, $siteReservedScopes)) {
                        $service->removeScope($seReservedScope);
                    }
                }
            }


            // Optionally update the child service scopes
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
                // force child service scopes to be same as site
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
     * Validates the user inputted site data against the
     * checks in the gocdb_schema.xml and applies additional logic checks
     * that can't be described in the gocdb_schema.xml.
     *
     * @param array $siteData containing all the fields for a GOCDB_SITE
     *                       object
     * @throws \Exception if the site data can't be
     *                   validated. The \Exception message will contain a human
     *                   readable description of which field failed validation.
     * @return null
     */
    private function validate($siteData, $type) {
        require_once __DIR__.'/Validate.php';
        $serv = new \org\gocdb\services\Validate();
        foreach($siteData as $field => $value) {
            $valid = $serv->validate($type, $field, $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }

        // Apply additional logic for validation that can't be captured solely using gocdb_schema.xml
        if (!empty($siteData['IP_V6_RANGE'])) {
            require_once __DIR__.'/validation/IPv6Validator.php';
            $validator = new \IPv6Validator();
            $errors = array();
            $errors = $validator->validate($siteData['IP_V6_RANGE'], $errors);
            if (count($errors) > 0) {
                throw new \Exception($errors[0]); // show the first message.
            }
        }
    }

    /**
     * Return all {@see \Site}s that satisfy the specfied filter parameters.
     *
     * $filterParams defines an associative array of optional parameters for
     * filtering the sites. The supported Key => Value pairs include:
     *   'sitename' => String site name
     *   'roc' => String name of parent NGI/ROC
     *   'country' => String country name
     *   'certification_status' => String certification status value e.g. 'Certified'
     *   'exclude_certification_status' => String exclude sites with this certification status
     *   'production_status' => String site production status value
     *   'scope' => 'String,comma,sep,list,of,scopes,e.g.,egi,wlcg'
     *   'scope_match' => String 'any' or 'all'
     *   'extensions' => String extensions expression to filter custom key=value pairs
     *
     * @param array $filterParams
     * @return array Site array
     */
    public function getSitesFilterByParams($filterParams){
        require_once __DIR__.'/PI/GetSite.php';
        $getSite = new GetSite($this->em);
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
     * @return array of all properties for a site
     */
    public function getProperties($id) {
        $dql = "SELECT p FROM SiteProperty p WHERE p.parentSite = :ID";
        $properties = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $properties;
    }

    /**
     * @return a single site property
     */
    public function getProperty($id) {
        $dql = "SELECT p FROM SiteProperty p WHERE p.id = :ID";
        $property = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $property;
    }

    /**
     * @return \SiteProperty a single site property
     */
    public function getPropertyByKeyAndParent($key, $parentSite) {
        $parentSiteID = $parentSite->getId();

        $dql = "SELECT p FROM SiteProperty p WHERE p.keyName = :KEY AND p.parentSite = :PARENTSITEID";
        $property = $this->em
                    ->createQuery ($dql)
                    ->setParameter ('KEY', $key)
                    ->setParameter ('PARENTSITEID', $parentSiteID)
                    ->getOneOrNullResult ();
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
     *                 [NOTIFY] => Y
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

        // get the parent NGI entity
        /* @var $parentNgi \NGI */
        $parentNgi = $this->em->createQuery("SELECT n FROM NGI n WHERE n.id = :id")
                ->setParameter('id', $values['NGI'])
                ->getSingleResult(); // throws NonUniqueResultException throws, NoResultException

        if(!$user->isAdmin()){
            // Check that user has permission to add site to the chosen NGI
            if(!$this->roleActionAuthorisationService->authoriseAction(
                    \Action::NGI_ADD_SITE, $parentNgi, $user)->getGrantAction()){
                throw new \Exception("You do not have permission to add a new site to the selected NGI"
                        . " To add a new site you require a managing role over an NGI");
            }
        }


        // do as much validation before starting a new db tx
        // check the site object data is valid
        $this->validate($values['Site'], 'site');
        $this->uniqueCheck($values['Site']['SHORT_NAME']);

        // ADD SCOPE TAGS:
        // collate selected reserved and non-reserved scopeIds
        $allSelectedScopeIds = array();
        foreach($values['Scope_ids'] as $sid){
            $allSelectedScopeIds[] = $sid;
        }
        foreach($values['ReservedScope_ids'] as $sid){
            $allSelectedScopeIds[] = $sid;
        }

        $selectedScopesToApply = $this->scopeService->getScopes($allSelectedScopeIds);

        // If not admin, check that requested reserved scopes are already implemented by the parent NGI.
        // Required to prevent users manually crafting a POST request in an attempt
        // to select reserved scopes, this is unlikely but it is a possible hack.
        if (!$user->isAdmin()) {
            $selectedReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $selectedScopesToApply);

            $existingReservedScopesParent = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $parentNgi->getScopes()->toArray());

            foreach($selectedReservedScopes as $sc){
                // Reserved scopes must already be assigned to parent
                if(!in_array($sc, $existingReservedScopesParent)){
                    throw new \Exception("A reserved Scope Tag was selected that is not assigned to the Parent NGI");
                }
            }
        }

        //check there are the required number of OPTIONAL scopes specified
        $this->checkNumberOfScopes($values['Scope_ids']);

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

            //Set notify flag for site
            if (!isset($values['NOTIFY'])){
                $notify = false;
            }
            elseif ($values['NOTIFY'] == "Yes") {
                $notify = true;
            } else {
                $notify = false;
            }
            $site->setNotify ($notify);

            // join the site to the parent NGI
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
            foreach($selectedScopesToApply as $scope){
                $site->addScope($scope);
            }

            // get the country
            $dql = "SELECT c FROM Country c WHERE c.id = :id";
            $country = $this->em->createQuery($dql)
                    ->setParameter('id', $values['Country'])
                    ->getSingleResult();
            $site->setCountry($country);

            $this->em->persist($site);
            // flush synchronizes the in-memory state of managed objects with the database
            // but we can still rollback
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
     * @param \NGI $ngi NGI to which $site is to be moved
     * @return null
     */
     public function moveSite(\Site $site, \NGI $ngi, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //If the NGI or site have no ID - throw logic exception
            $siteId = $site->getId();
            if (empty($siteId)) {
                throw new \LogicException('Site has no ID');
            }
            $ngiId = $ngi->getId();
            if (empty($ngiId)) {
                throw new \LogicException('NGI has no ID');
            }
            //find old NGI
            $oldNgi = $site->getNgi();

            //If the NGI has changed, then we move the site.
            if ($oldNgi != $ngi) {

                 //Remove the site from the old NGI FIRST if it has an old NGI
                if (!empty($oldNgi)) {
                    $oldNgi->getSites()->removeElement($site);
                }
                //Add site to new NGI
                $ngi->addSiteDoJoin($site);
                //$site->setNgiDoJoin($ngi);

                //persist
                $this->em->merge($ngi);
                $this->em->merge($oldNgi);
            }//close if

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    private function checkNumberOfScopes(array $myArray){
        require_once __DIR__ . '/Config.php';
        $configService = new \org\gocdb\services\Config();
        $minumNumberOfScopes = $configService->getMinimumScopesRequired('site');
        if(sizeof($myArray)<$minumNumberOfScopes){
            throw new \Exception("A site must have at least " . $minumNumberOfScopes . " optional scope(s) assigned to it.");
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
        if (!$this->userCanEditSite($user, $site)) {
            throw new \Exception("You don't have permission over " . $site->getShortName());
        }
    }

    /**
    * Returns true if the user has permission to edit the Site
    *
    * @param \User $user
    * @param \Site $site
    * @return boolian
    */
    public function userCanEditSite(\User $user, \Site $site) {
        if ($this->roleActionAuthorisationService->authoriseAction(\Action::EDIT_OBJECT, $site, $user)->getGrantAction() == FALSE) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Adds sets of extension property key/value pairs to a site.
     * @param \Site $site
     * @param \User $user
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    public function addProperties(\Site $site, \User $user, array $propArr, $preventOverwrite = false) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Validate the user has permission to add properties
        $this->validatePropertyActions($user, $site);

        //Add the properties
        $this->em->getConnection()->beginTransaction();
        try {
            $this->addPropertiesLogic($site, $propArr, $preventOverwrite);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Adds sets of extension property key/value pairs to a site, following a request through the API
     * @param \Site $site
     * @param array $propArr
     * @param bool $preventOverwrite
     * @param string $authenticationType
     * @param string $authenticationIdentifier
     * @throws \Exception
     */
    public function addPropertiesAPI(\Site $site, array $propKVArr, $preventOverwrite, $authenticationType, $authenticationIdentifier) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkGOCDBIsNotReadOnly();

        // Validate the user has permission to add properties
        $this->checkAuthorisedAPIIdentifier($site, $authenticationIdentifier, $authenticationType);

        //Convert the property array into the format used by the webportal logic
        #TODO: make the web portal use a more sensible format (e.g. array(key=> value), rather than array([1]=>key,array[2]=>value))
        $propArr=array();
        foreach ($propKVArr as $key => $value) {
            $propArr[]= array(0=>$key,1=>$value);
        }

        //Add the properties
        $this->em->getConnection()->beginTransaction();
        try {
            $this->addPropertiesLogic($site, $propArr, $preventOverwrite);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }



    /**
     * Logic to sets of extension property key/value pairs to a site.
     * @param \Site $site
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    protected function addPropertiesLogic(\Site $site, array $propArr, $preventOverwrite = false) {
        $existingProperties = $site->getSiteProperties();

        //We will use this variable to track the keys as we go along, this will be used check they are all unique later
        $keys=array();

        //We will use this variable to track teh final number of properties and ensure we do not exceede the specified limit
        $propertyCount = sizeof($existingProperties);

        foreach ($propArr as $i => $prop) {
            /*Trim off trailing and leading whitspace - as we currently don't want this.
            *The input array is awkwardly formatted as keys didn't use to have to be unique.
            */
            $key = trim($prop[0]);
            $value = trim($prop[1]);

            /*Find out if a property with the provided key already exists, if
            *we are preventing overwrites, this will be a problem. If we are not,
            *we will want to edit the existing property later, rather than create it.
            */
            $property = null;
            foreach ($existingProperties as $existProp) {
                if ($existProp->getKeyName() == $key) {
                    $property = $existProp;
                }
            }

            /*If the property doesn't already exist, we add it. If it exists
            *and we are not preventing overwrites, we edit the existing one.
            *If it exists and we are preventing overwrites, we throw an exception
            */
            if (is_null($property)) {
                //validate key value
                $validateArray['NAME'] = $key;
                $validateArray['VALUE'] = $value;
                $validateArray['SITE'] = $site->getId();
                $this->validate($validateArray, 'siteproperty');

                $property = new \SiteProperty();
                $property->setKeyName($key);
                $property->setKeyValue($value);
                $site->addSitePropertyDoJoin($property);
                $this->em->persist($property);

                //increment the property counter to enable check against property limit
                $propertyCount++;
            } elseif (!$preventOverwrite) {
                $this->editSitePropertyLogic($site, $property, array('SITEPROPERTIES'=>array('NAME'=>$key,'VALUE'=>$value)));
            } else {
                throw new \Exception("A property with name \"$key\" already exists for this object, no properties were added.");
            }

            //Add the key to the keys array, to enable unique check
            $keys[]=$key;
        }


        //Keys should be unique, create an exception if they are not
        if(count(array_unique($keys))!=count($keys)) {
            throw new \Exception(
                "Property names should be unique. The requested new properties include multiple properties with the same name."
            );
        }

        //Check to see if adding the new properties will exceed the max limit defined in local_info.xml, and throw an exception if so
        $extensionLimit = \Factory::getConfigService()->getExtensionsLimit();
        if ($propertyCount > $extensionLimit){
            throw new \Exception("Property(s) could not be added due to the property limit of $extensionLimit");
        }
    }

    /**
     * Deletes site properties: validates the user has permission then calls the
     * required logic
     * @param \Site $site
     * @param \User $user
     * @param array $propArr
     */
    public function deleteSiteProperties(\Site $site, \User $user, array $propArr) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Validate the user has permission to delete a property
        $this->validatePropertyActions($user, $site);

        //Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->deleteSitePropertiesLogic($site, $propArr);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Deletes site properties: validates the user has permission then calls the
     * required logic
     * @param \Site $site
     * @param \User $user
     * @param array $propArr
     */
    public function deleteSitePropertiesAPI(\Site $site, array $propArr, $authIdentifierType, $authIdentifier) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkGOCDBIsNotReadOnly();

        // Validate the user has permission to delete a property
        foreach ($propArr as $prop) {
            if ($prop->getParentSite() != $site) {
                throw new \Exception("Internal error: property parent site and site do not match.");
            }
        }
        $this->checkAuthorisedAPIIdentifier($site, $authIdentifier, $authIdentifierType);

        //Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->deleteSitePropertiesLogic($site, $propArr);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * All the logic to delete a site's properties, before deletion a check is done to confirm the property
     * is from the parent site specified by the request, and an exception is thrown if this is
     * not the case
     * @param \Site $site
     * @param array $propArr
     */
    protected function deleteSitePropertiesLogic(\Site $site, array $propArr) {
        foreach ($propArr as $prop) {
            //Check that the properties parent site the same as the one given
            if ($prop->getParentSite() != $site){
                $id = $prop->getId();
                throw new \Exception("Property {$id} does not belong to the specified site");
            }
            // Site is the owning side so remove elements from the site
            $site->getSiteProperties()->removeElement($prop);
            // Once relationship is removed delete the actual element
            $this->em->remove($prop);
        }
    }

    /**
     * Edit a site's property. The user is validated then the logic to make the
     * change called
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

        //Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->editSitePropertyLogic($site, $prop, $newValues);
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $ex ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $ex;
        }
    }

    /**
     * All the logic to edit a site's property, without the user validation.
     * A check is performed to confirm the given property is from the parent site
     * specified by the request, and an exception is thrown if this is not the case.
     *
     * @param \Site $site
     * @param \SiteProperty $prop
     * @param array $newValues
     * @throws \Exception
     */
    protected function editSitePropertyLogic(\Site $site,\SiteProperty $prop, $newValues) {

        $this->validate($newValues['SITEPROPERTIES'], 'siteproperty');

        //We don't currently want trailing or leading whitespace, so we trim it
        $keyname = trim($newValues['SITEPROPERTIES']['NAME']);
        $keyvalue = trim($newValues ['SITEPROPERTIES'] ['VALUE']);

        //Check that the prop is from the site
        if ($prop->getParentSite() != $site){
            $id = $prop->getId();
            throw new \Exception("Property {$id} does not belong to the specified site");
        }

        //If the properties key has changed, check there isn't an existing property with that key
        if ($keyname != $prop->getKeyName()){
            $existingProperties = $site->getSiteProperties();
            foreach ($existingProperties as $existingProp) {
                if ($existingProp->getKeyName() == $keyname) {
                    throw new \Exception("A property with that name already exists for this object");
                }
            }
        }

        // Set the site propertys new member variables
        $prop->setKeyName ( $keyname );
        $prop->setKeyValue ( $keyvalue );

        $this->em->merge ( $prop );
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

        $scopeNamesAndParentShareInfo = array();
        foreach($sharedScopesNames as $sharedScopesName){
            $scopeNamesAndParentShareInfo[$sharedScopesName]=true;
        }
        foreach($scopeNamesNotShared as $scopeNameNotShared){
            $scopeNamesAndParentShareInfo[$scopeNameNotShared]=false;
        }

        //can be replaced with ksort($scopeNamesAndParentShareInfo, SORT_NATURAL); in php>=5.5
        uksort($scopeNamesAndParentShareInfo, 'strcasecmp');

        return $scopeNamesAndParentShareInfo;
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
     * @return string xml string containing information required by the front map
     */
    public function getMapXMLString(){
        $sites = $this->getSitesWithGeoInfo();
        $portalUrl = $this->configService->GetPortalURL();

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

                $domXmlMapElement = dom_import_simplexml($xml);
                $dom = new \DOMDocument('1.0');
                $dom->encoding='UTF-8';
                $domXmlMapElement = $dom->importNode($domXmlMapElement, true);
                $domXmlMapElement = $dom->appendChild($domXmlMapElement);
                $dom->formatOutput = true;
                $xmlString = $dom->saveXML();

        return $xmlString;
    }

    private function uniqueAPIAuthEnt(\Site $site, $identifier, $type) {
        //TODO: This would probably be more effecient as a DQL query
        $existingAuthEnts = $site->getAPIAuthenticationEntities();

        foreach ($existingAuthEnts as $authEnt) {
            if($authEnt->getIdentifier()==$identifier && $authEnt->getType() == $type) {
                throw new \Exception(
                    "An authentication object of type \"$type\" and with identifier " .
                    "\"$identifier\" already exists for" . $site->getName()
                );
            }
        }
    }

    /**
     * Finds a single API authentication entity by ID and returns its entity
     * @param int $id the authentication entity ID
     * @return APIAuthentication an API authentcation entity
     */
    public function getAPIAuthenticationEntity($id) {
        $dql = "SELECT a FROM APIAuthentication a WHERE a.id = :id";
        $authEnt = $this->em
                ->createQuery($dql)
                ->setParameter('id', $id)
                ->getSingleResult();
        return $authEnt;
    }

    public function addAPIAuthEntity(\Site $site, \User $user, $newValues) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Validate the user has permission to add properties
        if (!$this->userCanEditSite($user, $site)) {
            throw new \Exception("You don't have permission to add authentication entties to " . $site->getShortName());
        }

        $identifier = $newValues['IDENTIFIER'];
        $type = $newValues['TYPE'];

        //Check that an identifier ha been provided
        if(empty($identifier)){
            throw new \Exception("A value must be provided for the identifier");
        }

        //validate the values against the schema
        $this->validate($newValues,'APIAUTHENTICATION');

        //If the entity is of type X509, do a more thorough check than the validate service (as we know the type)
        //Note that we are allowing ':' as they can appear in robot DN's
        if ($type == 'X509' && !preg_match("/^(\/[A-Za-z]+=[a-zA-Z0-9\/\-\_\s\.,'@:\/]+)*$/", $identifier)) {
            throw new \Exception("Invalid x509 DN");
        }

        //Check there isn't already a identifier of that type with that identifier for that Site
        $this->uniqueAPIAuthEnt($site, $identifier, $type);

        //Add the properties
        $this->em->getConnection()->beginTransaction();
        try {
            $authEnt = new \APIAuthentication();
            $authEnt->setIdentifier($identifier);
            $authEnt->setType($type);
            $site->addAPIAuthenticationEntitiesDoJoin($authEnt);
            $this->em->persist($authEnt);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $authEnt;
    }

    public function deleteAPIAuthEntity(\APIAuthentication $authEntity, \User $user) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Validate the user has permission to delete properties
        if (!$this->userCanEditSite($user, $authEntity->getParentSite())) {
            throw new \Exception("You don't have permission to add authentication entties to " . $site->getShortName());
        }

        //delete the entity
        $this->em->getConnection()->beginTransaction();
        try {
            $parentSite = $authEntity->getParentSite();

            //Remove the authentication entity from the site then remove the entity
            $parentSite->getAPIAuthenticationEntities()->removeElement($authEntity);
            $this->em->remove($authEntity);

            $this->em->persist($parentSite);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    public function editAPIAuthEntity(\APIAuthentication $authEntity, \User $user, $newValues) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        $site = $authEntity->getParentSite();

        // Validate the user has permission to edit properties
        if (!$this->userCanEditSite($user, $site)) {
            throw new \Exception("You don't have permission to add authentication entties to " . $site->getShortName());
        }

        $identifier = $newValues['IDENTIFIER'];
        $type = $newValues['TYPE'];

        //Check that an identifier ha been provided
        if(empty($identifier)){
            throw new \Exception("A value must be provided for the identifier");
        }

        //validate the values against the schema
        $this->validate($newValues,'APIAUTHENTICATION');


        //If the entity is of type X509, do a more thorough check than the validate service (as we know the type)
        //Note that we are allowing ':' as they can appear in robot DN's
        if ($type == 'X509' && !preg_match("/^(\/[A-Za-z]+=[a-zA-Z0-9\/\-\_\s\.,'@:\/]+)*$/", $identifier)) {
            throw new \Exception("Invalid x509 DN");
        }

        /**
        * As long as something has changed, check there isn't already a
        * identifier of that type with that identifier for that Site
        */
        if (!($authEntity->getIdentifier()==$identifier && $authEntity->getType() == $type)) {
            $this->uniqueAPIAuthEnt($site, $identifier, $type);
        }

        //Edit the property
        $this->em->getConnection()->beginTransaction();
        try {
            $authEntity->setIdentifier($identifier);
            $authEntity->setType($type);
            $this->em->persist($authEntity);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $authEntity;
    }
}
