<?php

namespace org\gocdb\services;

/*
 * Copyright © 2011 STFC
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
require_once __DIR__ . '/Site.php';
require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/RoleActionAuthorisationService.php';
require_once __DIR__ . '/Config.php';

/**
 * GOCDB Stateless service facade (business routines) for Service objects.
 * The public API methods are atomic and transactional.
 *
 * @todo Implement the ServiceEndpont interface when ready.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 * @author James McCarthy
 */
class ServiceService extends AbstractEntityService {
    private $roleActionAuthorisationService;
    private $configService;
    private $scopeService;
    function __construct(/* $roleActionAuthorisationService */) {
        parent::__construct ();
        // $this->roleActionAuthorisationService = $roleActionAuthorisationService;
        $this->configService = \Factory::getConfigService();
    }

    /**
     * Set class dependency (REQUIRED).
     *
     * @todo Mandatory objects should be injected via constructor.
     * @param \org\gocdb\services\RoleActionAuthorisationService $roleActionAuthService
     */
    public function setRoleActionAuthorisationService(RoleActionAuthorisationService $roleActionAuthService) {
        $this->roleActionAuthorisationService = $roleActionAuthService;
    }

    /**
     * Set class dependency (REQUIRED).
     *
     * @todo Mandatory objects should be injected via constructor.
     * @param \org\gocdb\services\Scope $scopeService
     */
    public function setScopeService(Scope $scopeService) {
        $this->scopeService = $scopeService;
    }

    /**
     * Finds a single service by ID and returns its entity
     *
     * @param int $id the service ID
     * @return \Service a service object
     */
    public function getService($id) {
        $dql = "SELECT s FROM Service s
                WHERE s.id = :id";

        $service = $this->em->createQuery ( $dql )->setParameter ( 'id', $id )->getSingleResult ();

        return $service;
    }
    public function getEndpoint($id) {
        $dql = "SELECT el FROM EndpointLocation el
                WHERE el.id = :id";

        $endpoint = $this->em->createQuery ( $dql )->setParameter ( 'id', $id )->getSingleResult ();

        return $endpoint;
    }

    /**
     * Return all {@link \Service} entities that satisfy the specfied search parameters.
     * <p>
     * A null search parameter is not used to narrow the query.
     *
     * @param string $term An optional search string which can be part-of (like)
     *        the Service hostname or description.
     * @param string $serviceType Limit returned services to the specified type.
     * @param string $production The string TRUE or FALSE
     * @param string $monitored The string TRUE or FALSE
     * @param string $scope A single scope value (does not support comma-sep list of scopes)
     * @param string $ngi Limit to services that have the parent NGI with this name
     * @param string $certStatus A cert status string
     * @param boolean $showClosed 1 is true, 0 is false
     * @param int $startRecord Optional pagination start offset
     * @param int $endRecord Optional pagination max results
     * @param boolean $returnArray True to return a hydrated Doctrine array graph
     *        false to return a hydrated Doctine object graph
     * @return type array graph or object graph of {@link \Service} entities
     */
    public function getSes($term = null, $serviceType = null, $production = null, $monitored = null, $scope = null, $ngi = null, $certStatus = null, $showClosed = null, $servKeyNames = null, $servKeyValues = null, $startRecord = null, $endRecord = null, $returnArray = false) {

        // this method needs to be dropped in favor of getServicesFilterByParams,
        // but it is still needed when adding services to serviceGroups.
        return $this->getServicesHelper ( $term, $serviceType, $production, $monitored, $scope, $ngi, $certStatus, $showClosed, $servKeyNames, $servKeyValues, $startRecord, $endRecord, $returnArray, false );
    }

    /**
     * Count the number of {@link \Service} entities that satisfy the specified
     * search parameters.
     * {@link getSes}
     */
    public function getSesCount($term = null, $serviceType = null, $production = null, $monitored = null, $scope = null, $ngi = null, $certStatus = null, $showClosed = null, $servKeyNames = null, $servKeyValues = null, $startRecord = null, $endRecord = null, $returnArray = false) {
        return $this->getServicesHelper ( $term, $serviceType, $production, $monitored, $scope, $ngi, $certStatus, $showClosed, $servKeyNames, $servKeyValues, $startRecord, $endRecord, $returnArray, true );
    }

    private function getServicesHelper($term = null, $serviceType = null, $production = null, $monitored = null, $scope = null, $ngi = null, $certStatus = null, $showClosed = null, $servKeyNames = null, $servKeyValues = null, $startRecord = null, $endRecord = null, $returnArray = false, $count = false) {
        // this method can be dropped when getSes and getSesCount have been dropped.
        if ($production == "TRUE") {
            $production = "1";
        } else if ($production == "FALSE") {
            $production = "0";
        }

        if ($monitored == "TRUE") {
            $monitored = "1";
        } else if ($monitored == "FALSE") {
            $monitored = "0";
        }

        $qb = $this->em->createQueryBuilder ();

        if ($count) {
            $qb->select ( 'count(DISTINCT se)' );
        } else {
            $qb->select ( 'DISTINCT se', 'si', 'st', 'cs', 'n' );
        }

        $qb->from ( 'Service', 'se' )->leftjoin ( 'se.serviceType', 'st' )->leftjoin ( 'se.scopes', 's' )->leftjoin ( 'se.parentSite', 'si' )->leftjoin ( 'si.certificationStatus', 'cs' )->leftjoin ( 'si.ngi', 'n' )->orderBy ( 'se.hostName' );

        // For use with search function, convert all terms to upper and do a like query
        if ($term != null && $term != '%%') {
            $qb->andWhere ( $qb->expr ()->orX ( $qb->expr ()->like ( $qb->expr ()->upper ( 'se.hostName' ), ':term' ), $qb->expr ()->like ( $qb->expr ()->upper ( 'se.description' ), ':term' ) ) )->setParameter ( ':term', strtoupper ( $term ) );
        }

        if ($serviceType != null && $serviceType != '%%') {
            $qb->andWhere ( $qb->expr ()->like ( $qb->expr ()->upper ( 'st.name' ), ':serviceType' ) )->setParameter ( ':serviceType', strtoupper ( $serviceType ) );
        }

        if ($production != null && $production != '%%') {
            $qb->andWhere ( $qb->expr ()->like ( $qb->expr ()->upper ( 'se.production' ), ':production' ) )->setParameter ( ':production', $production );
        }

        if ($monitored != null && $monitored != '%%') {
            $qb->andWhere ( $qb->expr ()->like ( $qb->expr ()->upper ( 'se.monitored' ), ':monitored' ) )->setParameter ( ':monitored', $monitored );
        }

        if ($scope != null && $scope != '%%') {
            $qb->andWhere ( $qb->expr ()->like ( 's.name', ':scope' ) )->setParameter ( ':scope', $scope );
        }

        if ($certStatus != null && $certStatus != '%%') {
            $qb->andWhere ( $qb->expr ()->like ( 'cs.name', ':certStatus' ) )->setParameter ( ':certStatus', $certStatus );
        }

        if ($showClosed != 1) {
            $qb->andWhere ( $qb->expr ()->not ( $qb->expr ()->like ( 'cs.name', ':closed' ) ) )->setParameter ( ':closed', 'Closed' );
        }

        if ($ngi != null && $ngi != '%%') {
            $qb->andWhere ( $qb->expr ()->like ( 'n.name', ':ngi' ) )->setParameter ( ':ngi', $ngi );
        }

        if ($servKeyNames != null && $servKeyNames != '%%') {
            if ($servKeyValues == null || $servKeyValues == '') {
                $servKeyValues = '%%';
            }

            $sQ = $this->em->createQueryBuilder ();
            $sQ->select ( 'se1' . '.id' )->from ( 'Service', 'se1' )->join ( 'se1.serviceProperties', 'sp' )->andWhere ( $sQ->expr ()->andX ( $sQ->expr ()->eq ( 'sp.keyName', ':keyname' ), $sQ->expr ()->like ( 'sp.keyValue', ':keyvalue' ) ) );

            $qb->andWhere ( $qb->expr ()->in ( 'se', $sQ->getDQL () ) );
            $qb->setParameter ( ':keyname', $servKeyNames )->setParameter ( ':keyvalue', $servKeyValues );
        }

        $query = $qb->getQuery ();

        if ($count) {
            $count = $query->getSingleScalarResult ();
            return $count;
        } else {

            if (! empty ( $startRecord )) {
                $query->setFirstResult ( $startRecord );
            }

            if (! empty ( $endRecord )) {
                $query->setMaxResults ( $endRecord );
            }

            if ($returnArray) {
                $results = $query->getResult ( \Doctrine\ORM\Query::HYDRATE_ARRAY );
                return $results;
            } else {
                $results = $query->getResult ();
                return $results;
            }
            // print_r($results);
        }
    }

    public function getAllSesJoinParentSites() {
        $dql = "SELECT se, st, si
            FROM Service se
            JOIN se.serviceType st
            JOIN se.parentSite si";
        $query = $this->em->createQuery ( $dql );
        return $query->getResult ();
    }

    /**
     * Return all {@link \Service} entities OR a count of the services that
     * satisfy the specfied filter parameters.
     *
     * <p>
     * $filterParams defines an associative array of optional parameters for
     * filtering the services. The supported Key => Value pairs include:
     * <ul>
     * <li>'searchTerm' => 'StringSearchTerm'</li>
     * <li>'serviceType' => 'StringServiceType'</li>
     * <li>'production' => String 'TRUE' or 'FALSE'</li>
     * <li>'monitored' => String 'TRUE' or 'FALSE'</li>
     * <li>'scope' => 'String,comma,sep,list,of,scopes,e.g.,egi,wlcg'</li>
     * <li>'ngi' => 'String ngi name'</li>
     * <li>'certStatus' => String Site Certification status value e.g. 'Certified' or 'Candidate'</li>
     * <li>'showClosed' => boolean</li>
     * <li>'servKeyNames' => Single string service extension property name </li>
     * <li>'servKeyValue' => Single string extension property value</li>
     * <li>'startRecord' => integer for the query start/offset postion</li>
     * <li>'maxResults' => integer for the maximum number of results</li>
     * <li>'returnArray' => boolean, if 'count' is not true, returns a hydrated
     * Doctrine array when true {@see \Doctrine\ORM\Query::HYDRATE_ARRAY}
     * otherwise an array of hydrated Doctrine objects {@see \Doctrine\ORM\Query::HYDRATE_OBJECT} </li>
     * <li>'count' => boolean, if true returns a count of the result set rather than services</li>
     * </ul>
     *
     * @param array $filterParams Associative array of key => value pairs.
     * @return mixed Array of {@link \Service} entities or int representing
     *         the service count
     */
    public function getServicesFilterByParams($filterParams) {
        $searchTerm = null;
        $serviceType = null;
        $production = null;
        $monitored = null;
        $scope = null;
        $ngi = null;
        $certStatus = null;
        $showClosed = false;
        $servKeyNames = null;
        $servKeyValue = null;
        $startRecord = null;
        $maxResults = null;
        $returnArray = false;
        $count = false;

        if (isset ( $filterParams ['searchTerm'] )) {
            $searchTerm = $filterParams ['searchTerm'];
        }
        if (isset ( $filterParams ['serviceType'] )) {
            $serviceType = $filterParams ['serviceType'];
        }
        if (isset ( $filterParams ['production'] )) {
            if (strtoupper ( $filterParams ['production'] ) == "TRUE") {
                $production = "1";
            } else if (strtoupper ( $filterParams ['production'] ) == "FALSE") {
                $production = "0";
            }
        }
        if (isset ( $filterParams ['monitored'] )) {
            if (strtoupper ( $filterParams ['monitored'] ) == "TRUE") {
                $monitored = "1";
            } else if (strtoupper ( $filterParams ['monitored'] ) == "FALSE") {
                $monitored = "0";
            }
        }
        if (isset ( $filterParams ['scope'] )) {
            $scope = $filterParams ['scope'];
            $scopeMatch = 'all';
        }
        if (isset ( $filterParams ['scopeMatch'] )) {
            $scopeMatch = $filterParams ['scopeMatch'];
        }
        if (isset ( $filterParams ['ngi'] )) {
            $ngi = $filterParams ['ngi'];
        }
        if (isset ( $filterParams ['certStatus'] )) {
            $certStatus = $filterParams ['certStatus'];
        }
        if (isset ( $filterParams ['showClosed'] ) && $filterParams ['showClosed'] == TRUE) {
            $showClosed = $filterParams ['showClosed'];
        }
        if (isset ( $filterParams ['servKeyNames'] )) {
            $servKeyNames = $filterParams ['servKeyNames'];
        }
        if (isset ( $filterParams ['servKeyValue'] )) {
            $servKeyValue = $filterParams ['servKeyValue'];
        }
        if (isset ( $filterParams ['startRecord'] )) {
            $startRecord = $filterParams ['startRecord'];
        }
        if (isset ( $filterParams ['maxResults'] )) {
            $maxResults = $filterParams ['maxResults'];
        }
        if (isset ( $filterParams ['returnArray'] )) {
            $returnArray = $filterParams ['returnArray'];
        }
        if (isset ( $filterParams ['count'] ) && $filterParams ['count'] != null) {
            $count = $filterParams ['count'];
        }

        // bind count - used to create positional bind params.
        $bc = - 1;
        $qb = $this->em->createQueryBuilder ();

        if ($count) {
            $qb->select ( 'count(DISTINCT se)' );
        } else {
            $qb->select ( 'DISTINCT se', 'si', 'st', 'cs', 'n' );
        }

        $qb->from ( 'Service', 'se' )->leftjoin ( 'se.serviceType', 'st' )->leftjoin ( 'se.scopes', 's' )->leftjoin ( 'se.parentSite', 'si' )->leftjoin ( 'si.certificationStatus', 'cs' )->leftjoin ( 'si.ngi', 'n' )->orderBy ( 'se.hostName' );

        // For use with search function, convert all terms to upper and do a like query
        if ($searchTerm != null) {
            $qb->andWhere ( $qb->expr ()->orX ( $qb->expr ()->like ( $qb->expr ()->upper ( 'se.hostName' ), '?' . ++ $bc ), $qb->expr ()->like ( $qb->expr ()->upper ( 'se.description' ), '?' . $bc ) ) )->setParameter ( $bc, strtoupper ( $searchTerm ) );
        }

        if ($serviceType != null) {
            $qb->andWhere ( $qb->expr ()->like ( $qb->expr ()->upper ( 'st.name' ), '?' . ++ $bc ) )->setParameter ( $bc, strtoupper ( $serviceType ) );
        }

        if ($production != null) {
            $qb->andWhere ( $qb->expr ()->like ( $qb->expr ()->upper ( 'se.production' ), '?' . ++ $bc ) )->setParameter ( $bc, $production );
        }

        if ($monitored != null) {
            $qb->andWhere ( $qb->expr ()->like ( $qb->expr ()->upper ( 'se.monitored' ), '?' . ++ $bc ) )->setParameter ( $bc, $monitored );
        }

        // Create WHERE clauses for multiple scopes using positional bind params
        if ($scope != null) {
            require_once __DIR__ . '/PI/QueryBuilders/ScopeQueryBuilder.php';
            $scopeQueryBuilder = new ScopeQueryBuilder ( $scope, $scopeMatch, $qb, $this->em, $bc, 'Service', 'se' );
            // Get the result of the scope builder
            /* @var $qb \Doctrine\ORM\QueryBuilder */
            $qb = $scopeQueryBuilder->getQB ();
            $bc = $scopeQueryBuilder->getBindCount ();
            // Get the binds and store them in the local bind array only if any binds are fetched from scopeQueryBuilder
            $binds = ( array ) $scopeQueryBuilder->getBinds ();
            foreach ( $binds as $bind ) {
                $binds [] = $bind;
            }
            foreach ( $binds as $bindIdValue ) {
                $qb->setParameter ( $bindIdValue [0], $bindIdValue [1] ); // , \Doctrine\DBAL\Types\Type::STRING );
            }
        }

        if ($certStatus != null) {
            $qb->andWhere ( $qb->expr ()->like ( 'cs.name', '?' . ++ $bc ) )->setParameter ( $bc, $certStatus );
        }

        if ($showClosed) {
            // don't add the extra where clause
        } else {
            // add a where clause to drop Closed certStatus, i.e. 'WHERE cs.name IS NOT LIKE Closed'
            $qb->andWhere ( $qb->expr ()->not ( $qb->expr ()->like ( 'cs.name', '?' . ++ $bc ) ) )->setParameter ( $bc, 'Closed' );
        }

        if ($ngi != null) {
            $qb->andWhere ( $qb->expr ()->like ( 'n.name', '?' . ++ $bc ) )->setParameter ( $bc, $ngi );
        }

        if ($servKeyNames != null) {
            if ($servKeyValue == null || $servKeyValue == '') {
                $servKeyValue = '%%';
            }
            $sQ = $this->em->createQueryBuilder ();
            $sQ->select ( 'se_p1' . '.id' )->from ( 'Service', 'se_p1' )->join ( 'se_p1.serviceProperties', 'sp' )->andWhere ( $sQ->expr ()->andX ( $sQ->expr ()->eq ( 'sp.keyName', '?' . ++ $bc ), $sQ->expr ()->like ( 'sp.keyValue', '?' . ++ $bc ) ) );

            $qb->andWhere ( $qb->expr ()->in ( 'se', $sQ->getDQL () ) );
            $qb->setParameter ( $bc - 1, $servKeyNames )->setParameter ( $bc, $servKeyValue );
        }

        $query = $qb->getQuery ();

        if ($count) {
            $count = $query->getSingleScalarResult ();
            return $count;
        } else {
            if (! empty ( $startRecord )) {
                $query->setFirstResult ( $startRecord );
            }
            if (! empty ( $maxResults )) {
                $query->setMaxResults ( $maxResults );
            }
            if ($returnArray) {
                $results = $query->getResult ( \Doctrine\ORM\Query::HYDRATE_ARRAY );
                return $results;
            } else {
                $results = $query->getResult ();
                return $results;
            }
            // print_r($results);
        }
    }

    /**
     * Returns the downtimes linked to a Service.
     *
     * @param integer $id Service ID
     * @param integer $dayLimit
     */
    public function getDowntimes($id, $dayLimit) {
        if ($dayLimit != null) {
            $di = \DateInterval::createFromDateString ( $dayLimit . 'days' );
            $dayLimit = new \DateTime ();
            $dayLimit->sub ( $di );
        }

        // Simplified and updated query for MEPs model by JM&DM - 18/06/2014
        $dql = "SELECT d
                FROM Downtime d
                JOIN d.services se
                WHERE se.id = :id
                AND ( :dayLimit IS NULL OR d.startDate > :dayLimit)
                ORDER BY d.startDate DESC";

        $downtimes = $this->em->createQuery ( $dql )->setParameter ( 'id', $id )->setParameter ( 'dayLimit', $dayLimit )->getResult ();

        // $downtimes = $this->getService($id)->getDowntimes();
        return $downtimes;
    }

    /**
     * Returns all service types
     *
     * @return array $types An array of all ServiceType entities
     */
    public function getServiceTypes() {
        $dql = "SELECT st FROM ServiceType st
                ORDER BY st.name";
        $types = $this->em->createQuery ( $dql )->getResult ();
        return $types;
    }

    /**
     * Validates the the 'production => monitored' rule for the user inputted
     * service data.
     *
     * @param  string $serviceTypeName name of type of service being checked
     * @param  string     $production  proposed production value
     * @param  string     $monitored   proposed monitored value
     * @throws \Exception If the serviceValues production/monitored combination
     * is invalid. The \Exception's message will contain a human readable error
     * message.
     */
    public function validateProductionMonitoredCombination($serviceTypeName, $production, $monitored) {
        // Service types that are exceptions to the
        // 'production => monitored' rule.
        $ruleExceptions = array('VOMS', 'emi.ARGUS', 'org.squid-cache.Squid');

        // Check that the service type is not an exception to the
        // 'production => monitored'.
        if (!in_array ($serviceTypeName, $ruleExceptions)) {
            if ($production && !$monitored) {
                throw new \Exception(
                    "For the '".$serviceTypeName."' service type, if the ".
                    "Production flag is set to True, the Monitored flag must ".
                    "also be True.");
            }
        }
    }

    /**
     * Updates a Service.
     * Returns the updated SE
     *
     * Accepts an array $se_data as a parameter. $se_data's format is as follows:
     * <pre>
     * Array
     * (
     * [COBJECTID] => 1345
     * [HOSTING_SITE] => SiteName
     * [SERVICE_TYPE] => UNICORE6.service
     * [ENDPOINT_URL] => https://unicore.testsite.com/182
     * [SE] => Array
     * (
     * [ENDPOINT] => unicore.testsite.comUNICORE6.service
     * [HOSTNAME] => unicore.testsite.com
     * [HOST_IP] => 10.39.28.2
     * [HOST_DN] => /C=TW/O=AP/OU=GRID/CN=My.Test
     * [HOST_IP_V6] => 0000:0000:0000:0000:0000:0000:0000:0000[/int]
     * [DESCRIPTION] => Test endpoint
     * [HOST_OS] => Centos
     * [HOST_ARCH] => x86_64
     * [BETA] => N
     * [PRODUCTION_LEVEL] => Y
     * [IS_MONITORED] => N
     * [NOTIFY] => N
     * )
     * )
     * </pre>
     *
     * @param array $se_data Array of updated service data, specified above.
     *        return Service The updated service entity
     */
    public function editService(\Service $se, $newValues, \User $user = null) {
        require_once __DIR__ . '/../../htdocs/web_portal/components/Get_User_Principle.php';

        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );

        // Authorise the change
        if ($this->roleActionAuthorisationService->authoriseAction ( \Action::EDIT_OBJECT, $se->getParentSite (), $user )->getGrantAction () == FALSE) {
            throw new \Exception ( "You do not have permission over this service." );
        }

        $st = $this->getServiceType ( $newValues ['serviceType'] );

        $this->validate ( $newValues ['SE'], 'service' );
        $this->uniqueCheck ( $newValues ['SE'] ['HOSTNAME'], $st, $se->getParentSite () );
        // validate production/monitored combination
        $this->validateProductionMonitoredCombination(
          $this->getServiceType($newValues['serviceType']),
          $this->ptlTexToBool($newValues['PRODUCTION_LEVEL']),
          $this->ptlTexToBool($newValues['IS_MONITORED'])
        );

        // EDIT SCOPE TAGS:
        // collate selected scopeIds (reserved and non-reserved)
        $scopeIdsToApply = array ();
        foreach ( $newValues ['Scope_ids'] as $sid ) {
            $scopeIdsToApply [] = $sid;
        }
        foreach ( $newValues ['ReservedScope_ids'] as $sid ) {
            $scopeIdsToApply [] = $sid;
        }
        $selectedScopesToApply = $this->scopeService->getScopes ( $scopeIdsToApply );

        // If not admin, Check user edits to the service's Reserved scopes:
        // Required to prevent users manually crafting a POST request in an attempt
        // to select reserved scopes, this is unlikely but it is a possible hack.
        if (! $user->isAdmin ()) {
            $selectedReservedScopes = $this->scopeService->getScopesFilterByParams ( array (
                    'excludeNonReserved' => true
            ), $selectedScopesToApply );

            $existingReservedScopes = $this->scopeService->getScopesFilterByParams ( array (
                    'excludeNonReserved' => true
            ), $se->getScopes ()->toArray () );

            $existingReservedScopesParent = $this->scopeService->getScopesFilterByParams ( array (
                    'excludeNonReserved' => true
            ), $se->getParentSite ()->getScopes ()->toArray () );

            foreach ( $selectedReservedScopes as $sc ) {
                // Reserved scopes must already be assigned to se or parent
                if (! in_array ( $sc, $existingReservedScopes ) && ! in_array ( $sc, $existingReservedScopesParent )) {
                    throw new \Exception ( "A reserved Scope Tag was selected that " . "is not assigned to the Service or to the Parent Site" );
                }
            }
        }

        // check there are the required number of optional scopes specified
        $this->checkNumberOfScopes ( $this->scopeService->getScopesFilterByParams ( array (
                'excludeReserved' => true
        ), $selectedScopesToApply ) );

        $updatedServiceValues =array();
        $updatedServiceValues['hostname'] = $newValues ['SE'] ['HOSTNAME'];
        $updatedServiceValues['description'] = $newValues ['SE'] ['DESCRIPTION'];
        $updatedServiceValues['url'] = $newValues['SE']['URL'];
        $updatedServiceValues['dn'] = $newValues ['SE']['HOST_DN'];
        $updatedServiceValues['ip'] = $newValues['SE']['HOST_IP'];
        $updatedServiceValues['ip6'] = $newValues['SE']['HOST_IP_V6'];
        $updatedServiceValues['os'] = $newValues['SE']['HOST_OS'];
        $updatedServiceValues['email'] = $newValues['SE']['EMAIL'];
        $updatedServiceValues['arch'] = $newValues['SE']['HOST_ARCH'];
        $updatedServiceValues['monitored'] = $this->ptlTexToBool($newValues['IS_MONITORED']);
        $updatedServiceValues['beta'] = $this->ptlTexToBool($newValues['BETA']);
        $updatedServiceValues['production'] = $this->ptlTexToBool($newValues['PRODUCTION_LEVEL']);
        $updatedServiceValues['notify'];

        if (!isset($newValues['NOTIFY'])){
            $updatedServiceValues['notify'] = false;
        }
        else {
            $updatedServiceValues['notify'] = $this->ptlTexToBool($newValues['NOTIFY']);
        }

        $this->editServiceLogic($se, $selectedScopesToApply, $st, $updatedServiceValues);

        return $se;
    }

    /**
     * Function called by write API to edit a service. Provides API specific
     * authorisation on top of shared logic with web portal
     * @param Service $service            service being updated
     * @param  string $hostname           service hostname
     * @param  string $description        service description
     * @param  string $url                service url
     * @param  string $dn                 service dn
     * @param  string $ip                 service IP
     * @param  string $ip6                serviec IP (ipV6)
     * @param  string $os                 service OS
     * @param  string $email              service email
     * @param  string $arch               service archetecture
     * @param  string $monitored
     * @param  boolean $beta
     * @param  boolean $production
     * @param  boolean $notify
     * @param  string $authIdentifierType
     * @param  string $authIdentifier
     */
    public function editServiceApi(\Service $service, $hostname, $description, $url, $dn, $ip, $ip6, $os, $email, $arch, $monitored, $beta, $production, $notify, $authIdentifierType, $authIdentifier) {
      //Check the portal is not in read only mode, throws exception if it is
      $this->checkGOCDBIsNotReadOnly();

      $this->checkAuthorisedAPIIdentifier($service->getParentSite(), $authIdentifier, $authIdentifierType);

      $scopes = clone $service->getScopes();
      $sType = $service->getServiceType();
      $updatedServiceValues = array (
        'hostname'=>$hostname,
        'description'=>$description,
        'url'=>$url,
        'dn'=>$dn,
        'ip'=>$ip,
        'ip6'=>$ip6,
        'os'=>$os,
        'email'=>$email,
        'arch'=>$arch,
        'monitored'=>$monitored,
        'beta'=>$beta,
        'production'=>$production,
        'notify'=>$notify,
      );

      $this->validateProductionMonitoredCombination($service->getServiceType()->getName(), $production, $monitored);

      $this->editServiceLogic($service, $scopes, $sType, $updatedServiceValues);
    }


    /**
     * The logic of editing a service, without the authorisation or validation.
     * Private function as there should always be authorisation anad validation
     * steps within the service before calling this function.
     *
     * @param  Service $service       service to be updated
     * @param  array  $scopes         scopes of service being updated
     * @param         $sType          service type of service
     * @param array $updatedServiceValues values being updated for $service. Should contain:
     *                ['hostname']    hostname of service being updated
     *                ['description'] description of service being updated
     *                ['url']         url of service being updated
     *                ['dn']          dn of service being updated
     *                ['$ip6']        ip V6 of service being updated
     *                ['ip']          ip of service being updated
     *                ['$os']         os of service being updated
     *                ['email']       email of service being updated
     *                ['$arch']       architecture  of service being updated
     *                ['$monitored']  boolean monitored of service being updated
     *                ['$beta']       boolean beta of service being updated
     *                ['$production'] boolean production of service being updated
     *                ['$notify']     boolean notify value of service being updated
     * @throws Exception
     */
    private function editServiceLogic(\Service $service, $scopes, $sType, $updatedServiceValues){
      // Explicitly demarcate our tx boundary
      $this->em->getConnection ()->beginTransaction ();
      try {
          // Set the service's member variables
          $service->setHostName($updatedServiceValues['hostname']);
          $service->setDescription($updatedServiceValues['description']);
          $service->setUrl($updatedServiceValues['url']);
          $service->setDn($updatedServiceValues['dn']);
          $service->setIpAddress($updatedServiceValues['ip']);
          $service->setIpV6Address($updatedServiceValues['ip6']);
          $service->setOperatingSystem($updatedServiceValues['os']);
          $service->setEmail($updatedServiceValues['email']);
          $service->setArchitecture($updatedServiceValues['arch']);
          $service->setMonitored($updatedServiceValues['monitored']);
          $service->setBeta($updatedServiceValues['beta']);
          $service->setProduction($updatedServiceValues['production']);
          $service->setNotify($updatedServiceValues['notify']);

          $service->setServiceType($sType);

          // Update the scope of the service
          // firstly remove all existing scope links
          $oldScopes = $service->getScopes ();
          foreach($oldScopes as $s ) {
              $service->removeScope($s);
          }

          // find each specified scope and then link it to the specified site
          foreach($scopes as $scope) {
              $service->addScope($scope);
          }

          $this->em->merge($service);
          $this->em->flush ();
          $this->em->getConnection ()->commit ();
      } catch ( \Exception $e ) {
          $this->em->getConnection ()->rollback ();
          $this->em->close ();
          throw $e;
      }
    }

    /**
     * Validates user inputted service data against the
     * checks in the gocdb_schema.xml.
     *
     * @param array $se_data containing all the fields for a Service
     * @throws \Exception If the SE data can't be
     *         validated. The \Exception message will contain a human
     *         readable description of which field failed validation.
     * @return null
     */
    private function validate($se_data, $type) {
        require_once __DIR__ . '/Validate.php';
        $serv = new \org\gocdb\services\Validate ();
        foreach ( $se_data as $field => $value ) {
            $valid = $serv->validate ( $type, $field, $value );
            if (! $valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception ( $error );
            }
        }

        // Apply additional logic for validation that can't be captured solely using gocdb_schema.xml
        if (! empty ( $se_data ['HOST_IP_V6'] )) {
            require_once __DIR__ . '/validation/IPv6Validator.php';
            $validator = new \IPv6Validator ();
            $errors = array ();
            $errors = $validator->validate ( $se_data ['HOST_IP_V6'], $errors );
            if (count ( $errors ) > 0) {
                throw new \Exception ( $errors [0] ); // show the first message.
            }
        }
    }


    /**
     * Array
     * (
     * [Service_Type] => 21
     * [EndpointURL] => Testing://host.com
     * [Scope] => 2
     * [Hosting_Site] => 377
     * [SE] => Array
     * (
     * [ENDPOINT] => my.new.host.com21
     * [HOSTNAME] => my.new.host.com
     * [HOST_IP] => 10.0.0.1
     * [HOST_DN] => /cn=JCasson
     * [HOST_IP_V6] => 0000:0000:0000:0000:0000:0000:0000:0000[/int]
     * [DESCRIPTION] => hithere
     * [HOST_OS] =>
     * [HOST_ARCH] =>
     * [BETA] => Y
     * [PRODUCTION_LEVEL] => Y
     * [IS_MONITORED] => Y
     * [EMAIL] =>
     * [NOTIFY] => Y
     * )
     * )
     *
     * @param Array $values Balues for the new SE (defined above)
     * @param org\gocdb\services\User $user The user adding the SE
     */
    public function addService($values, \User $user = null) {

        // get the parent site
        $dql = "SELECT s from Site s WHERE s.id = :id";
        /* @var $site \Site */
        $site = $this->em->createQuery ( $dql )->setParameter ( 'id', $values ['hostingSite'] )->getSingleResult ();
        // get the service type
        $st = $this->getServiceType ( $values ['serviceType'] );

        if ($this->roleActionAuthorisationService->authoriseAction ( \Action::SITE_ADD_SERVICE, $site, $user )->getGrantAction () == FALSE) {
            throw new \Exception ( "You don't have permission to add a service to this site." );
        }

        $this->validate ( $values ['SE'], 'service' );
        $this->uniqueCheck ( $values ['SE'] ['HOSTNAME'], $st, $site );

        // validate production/monitored combination
        $this->validateProductionMonitoredCombination(
          $this->getServiceType($values['serviceType']),
          $this->ptlTexToBool($values['PRODUCTION_LEVEL']),
          $this->ptlTexToBool($values['IS_MONITORED'])
        );

        // ADD SCOPE TAGS:
        // collate selected reserved and non-reserved scopeIds.
        // Note, Reserved scopes can be inherited from the parent Site.
        $allSelectedScopeIds = array ();
        foreach ( $values ['Scope_ids'] as $sid ) {
            $allSelectedScopeIds [] = $sid;
        }
        foreach ( $values ['ReservedScope_ids'] as $sid ) {
            $allSelectedScopeIds [] = $sid;
        }

        $selectedScopesToApply = $this->scopeService->getScopes ( $allSelectedScopeIds );

        // If not admin, check that requested reserved scopes are already implemented by the parent Site.
        // Required to prevent users manually crafting a POST request in an attempt
        // to select reserved scopes, this is unlikely but it is a possible hack.
        if (! $user->isAdmin ()) {
            $selectedReservedScopes = $this->scopeService->getScopesFilterByParams ( array (
                    'excludeNonReserved' => true
            ), $selectedScopesToApply );

            $existingReservedScopesParent = $this->scopeService->getScopesFilterByParams ( array (
                    'excludeNonReserved' => true
            ), $site->getScopes ()->toArray () );

            foreach ( $selectedReservedScopes as $sc ) {
                // Reserved scopes must already be assigned to parent
                if (! in_array ( $sc, $existingReservedScopesParent )) {
                    throw new \Exception ( "A reserved Scope Tag was selected that is not assigned to the Parent Site" );
                }
            }
        }

        // check there are the required number of OPTIONAL scopes specified
        $this->checkNumberOfScopes ( $values ['Scope_ids'] );

        $this->em->getConnection ()->beginTransaction ();
        try {
            $se = new \Service ();
            $se->setParentSiteDoJoin ( $site );
            $se->setServiceType ( $st );

            // Set production
            $se->setProduction($this->ptlTexToBool($values['PRODUCTION_LEVEL']));

            // Set Beta
            $se->setBeta($this->ptlTexToBool($values['BETA']));

            // Set monitored
            $se->setMonitored($this->ptlTexToBool($values['IS_MONITORED']));

            //Set notify flag for site
            if (!isset($values['NOTIFY'])){
                $se->setNotify(false);
            }
            else{
                $se->setNotify($this->ptlTexToBool($values['NOTIFY']));
            }

            // Set the scopes
            // foreach ($allSelectedScopeIds as $scopeId) {
            // $dql = "SELECT s FROM Scope s WHERE s.id = :id";
            // $scope = $this->em->createQuery($dql)
            // ->setParameter('id', $scopeId)
            // ->getSingleResult();
            // $se->addScope($scope);
            // }
            foreach ( $selectedScopesToApply as $scope ) {
                $se->addScope ( $scope );
            }

            $se->setDn ( $values ['SE'] ['HOST_DN'] );
            $se->setIpAddress ( $values ['SE'] ['HOST_IP'] );
            $se->setOperatingSystem ( $values ['SE'] ['HOST_OS'] );
            $se->setArchitecture ( $values ['SE'] ['HOST_ARCH'] );
            $se->setHostName ( $values ['SE'] ['HOSTNAME'] );
            $se->setDescription ( $values ['SE'] ['DESCRIPTION'] );
            $se->setEmail ( $values ['SE'] ['EMAIL'] );
            $se->setUrl ( $values ['SE'] ['URL'] );

            $this->em->persist ( $se );
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $e ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $e;
        }
        return $se;
    }

    /**
     * Does a newly submitted SE have the same hostname+service type
     * as an existing service linked to another site.
     *
     * The ACE operations tool chokes if GOCDB has two services with the same
     * hostname+service_type under two different sites.
     * Note that a duplicate host+service_type is allowed under the one site as this
     * is potentially required for UNICORE SEs with multiple URLs.
     * (as explained by Rajesh Kalmady)
     *
     * @param string $hostName New hostname
     * @param ServiceType $serviceType New service type
     * @param Site $site The site to add the SE to
     * @return null
     */
    private function uniqueCheck($hostName, \ServiceType $serviceType, \Site $site) {
        // Get all existing services with this hostname and service type, not under this site
        $dql = "SELECT se from Service se
                JOIN se.serviceType st
                JOIN se.parentSite s
                WHERE se.hostName = :hostName
                AND st.id = :stId
                AND s.id != :siteId";
        $ses = $this->em->createQuery ( $dql )->setParameter ( 'hostName', $hostName )->setParameter ( 'stId', $serviceType->getId () )->setParameter ( 'siteId', $site->getId () )->getResult ();

        if (sizeof ( $ses ) != 0) {
            throw new \Exception ( "A $serviceType service named $hostName already exists." );
        }
    }

    /**
     * Check that if if the selected scope for this SE is EGI, the parent site
     * is also EGI
     *
     * @param Site $site The SE's parent site
     * @param Scope $scope The SE's new scope
     * @return null
     */
    private function scopeCheck(\Site $site, \Scope $scope) {
        // If the scope isn't EGI then don't raise an error
        if ($scope->getName () != 'EGI') {
            return;
        }

        if ($site->getScopes ()->first ()->getName () != "EGI") {
            throw new \Exception ( "For this service to be EGI scoped, $site must also be EGI scoped." );
        }
    }

    /**
     * Gets a service type by ID
     *
     * @param integer $id The service type ID
     * @return \ServiceType
     */
    private function getServiceType($id) {
        $dql = "SELECT st FROM ServiceType st WHERE st.id = :id";
        $st = $this->em->createQuery ( $dql )->setParameter ( 'id', $id )->getSingleResult ();
        return $st;
    }

    /**
     *
     * @return array of all properties for a service
     */
    public function getProperties($id) {
        $dql = "SELECT p FROM ServiceProperty p WHERE p.parentSite = :ID";
        $properties = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $properties;
    }

    /**
     *
     * @return a single service property or null if not found
     */
    public function getProperty($id) {
        $dql = "SELECT p FROM ServiceProperty p WHERE p.id = :ID";
        $property = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $property;
    }

    /**
     *
     * @return a single service endpoint property or null if not foud
     */
    public function getEndpointProperty($id) {
        $dql = "SELECT p FROM EndpointProperty p WHERE p.id = :ID";
        $property = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $property;
    }

    /**
     * @return \ServiceProperty a single service property
     */
    public function getServicePropertyByKeyAndParent($key, $parentService) {
        $parentServiceID = $parentService->getId();

        $dql = "SELECT p FROM ServiceProperty p WHERE p.keyName = :KEY AND p.parentService = :PARENTSERVICEID";
        $property = $this->em
                    ->createQuery ($dql)
                    ->setParameter ('KEY', $key)
                    ->setParameter ('PARENTSERVICEID', $parentServiceID)
                    ->getOneOrNullResult ();
        return $property;
    }

    /**
     * @return \SiteProperty a single site property
     */
    public function getEndpointPropertyByKeyAndParent($key, $parentEndpoint) {
        $parentEndpointID = $parentEndpoint->getId();

        $dql = "SELECT p FROM EndpointProperty p WHERE p.keyName = :KEY AND p.parentEndpoint = :PARENTENDPOINTID";
        $property = $this->em
                    ->createQuery ($dql)
                    ->setParameter ('KEY', $key)
                    ->setParameter ('PARENTENDPOINTID', $parentEndpointID)
                    ->getOneOrNullResult ();
        return $property;
    }

    /**
     * This method will check that a user has edit permissions over a service before allowing a user to add, edit or delete
     * any service information.
     *
     * @param \User $user
     * @param \Service $service
     * @throws \Exception
     */
    public function validateAddEditDeleteActions(\User $user, \Service $service) {
        // Check to see whether the user has a role that covers this service
        // if(count($this->authorize Action(\Action::EDIT_OBJECT, $service, $user))==0){
        // throw new \Exception("You don't have permission over ". $service->getHostName());
        // }
        if ($this->roleActionAuthorisationService->authoriseAction ( \Action::EDIT_OBJECT, $service->getParentSite (), $user )->getGrantAction () == FALSE) {
            throw new \Exception ( "You don't have permission over service." );
        }
    }

    /**
     * Adds extension properties to a service
     *
     * @param \Service $service
     * @param \User $user
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    public function addProperties(\Service $service, \User $user, array $propArr, $preventOverwrite = false) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Check that the user has the requisite permissions
        $this->validateAddEditDeleteActions ($user,$service);

        //Make the change
        $this->em->getConnection ()->beginTransaction ();
        try {
            $this->addPropertiesLogic($service,$propArr,$preventOverwrite);
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $e ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Adds sets of extension property key/value pairs to a service, following a request through the API
     * @param \Service $service
     * @param array $propArr
     * @param bool $preventOverwrite
     * @param string $authenticationType
     * @param string $authenticationIdentifier
     * @throws \Exception
     */
    public function addServicePropertiesAPI(\Service $service, array $propKVArr, $preventOverwrite, $authenticationType, $authenticationIdentifier) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkGOCDBIsNotReadOnly();

        // Validate the user has permission to add properties
        $this->checkAuthorisedAPIIdentifier($service->getParentSite(), $authenticationIdentifier, $authenticationType);

        //Convert the property array into the format used by the webportal logic
        #TODO: make the web portal use a more sensible format (e.g. array(key=> value), rather than array([1]=>key,array[2]=>value))
        $propArr=array();
        foreach ($propKVArr as $key => $value) {
            $propArr[]= array(0=>$key,1=>$value);
        }

        //Add the properties
        $this->em->getConnection()->beginTransaction();
        try {
            $this->addPropertiesLogic($service, $propArr, $preventOverwrite);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Logic to add extension properties to a service
     *
     * @param \Service $service
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    protected function addPropertiesLogic(\Service $service, array $propArr, $preventOverwrite = false) {
        $existingProperties = $service->getServiceProperties ();

        //We will use this variable to track the keys as we go along, this will be used check they are all unique later
        $keys=array();

        //We will use this variable to track teh final number of properties and ensure we do not exceede the specified limit
        $propertyCount = sizeof($existingProperties);

        foreach ( $propArr as $i => $prop ) {
            /*Trim off trailing and leading whitspace - as we currently don't want this.
            *The input array is awkwardly formatted as keys didn't use to have to be unique.
            */
            $key = trim($prop[0]);
            $value = trim($prop[1]);

            /**
            *Find out if a property with the provided key already exists, if
            *we are preventing overwrites, this will be a problem. If we are not,
            *we will want to edit the existing property later, rather than create it.
            */
            $property = null;
            foreach ( $existingProperties as $existProp ) {
                if ($existProp->getKeyName () == $key) {
                    $property=$existProp;
                }
            }

            /*If the property key doesn't already exist, we add it. If it exists
            *and we are not preventing overwrites, we edit the existing one.
            *If it exists and we are preventing overwrites, we throw an exception
            */
            if (is_null($property)) {
                // validate key value
                $validateArray ['NAME'] = $key;
                $validateArray ['VALUE'] = $value;
                $this->validate ( $validateArray, 'serviceproperty' );

                $serviceProperty = new \ServiceProperty ();
                $serviceProperty->setKeyName ( $key );
                $serviceProperty->setKeyValue ( $value );
                $service->addServicePropertyDoJoin ( $serviceProperty );
                $this->em->persist ( $serviceProperty );

                //increment the property counter to enable check against property limit
                $propertyCount++;
            } elseif (!$preventOverwrite) {
                $this->editServicePropertyLogic($service, $property, array('SERVICEPROPERTIES'=>array('NAME'=>$key,'VALUE'=>$value)));
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
     * Adds extension properties to a service endpoint
     *
     * @param \EndpointLocation $endpoint
     * @param \User $user
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    public function addEndpointProperties(\EndpointLocation $endpoint, \User $user, array $propArr, $preventOverwrite = false) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ($user);

        //Check the user has the requisite permissions
        $this->validateAddEditDeleteActions ($user, $endpoint->getService());

        //Make the change
        $this->em->getConnection ()->beginTransaction ();
        try {
            $this->addEndpointPropertiesLogic($endpoint,$propArr,$preventOverwrite);
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $e ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Adds sets of extension property key/value pairs to a service, following a request through the API
     * @param \EndpointLocation $service
     * @param array $propArr
     * @param bool $preventOverwrite
     * @param string $authenticationType
     * @param string $authenticationIdentifier
     * @throws \Exception
     */
    public function addEndpointPropertiesAPI(\EndpointLocation $endpoint, array $propKVArr, $preventOverwrite, $authenticationType, $authenticationIdentifier) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkGOCDBIsNotReadOnly();

        // Validate the user has permission to add properties
        $this->checkAuthorisedAPIIdentifier($endpoint->getService()->getParentSite(), $authenticationIdentifier, $authenticationType);

        //Convert the property array into the format used by the webportal logic
        #TODO: make the web portal use a more sensible format (e.g. array(key=> value), rather than array([1]=>key,array[2]=>value))
        $propArr=array();
        foreach ($propKVArr as $key => $value) {
            $propArr[]= array(0=>$key,1=>$value);
        }

        //Add the properties
        $this->em->getConnection()->beginTransaction();
        try {
            $this->addEndpointPropertiesLogic($endpoint, $propArr, $preventOverwrite);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Logic to add extension properties to a service endpoint
     *
     * @param \EndpointLocation $endpoint
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    protected function addEndpointPropertiesLogic(\EndpointLocation $endpoint, array $propArr, $preventOverwrite = false) {
        $existingProperties = $endpoint->getEndpointProperties ();

        //We will use this variable to track the keys as we go along, this will be used check they are all unique later
        $keys=array();

        //We will use this variable to track teh final number of properties and ensure we do not exceede the specified limit
        $propertyCount = sizeof($existingProperties);

        foreach ( $propArr as $i => $prop ) {
            /*Trim off trailing and leading whitspace - as we currently don't want this.
            *The input array is awkwardly formatted as keys didn't use to have to be unique.
            */
            $key = trim($prop[0]);
            $value = trim($prop[1]);

            /**
            *Find out if a property with the provided key already exists, if
            *we are preventing overwrites, this will be a problem. If we are not,
            *we will want to edit the existing property later, rather than create it.
            */
            $property = null;
            foreach ( $existingProperties as $existProp ) {
                if ($existProp->getKeyName () == $key) {
                    $property = $existProp;
                }
            }

            /*If the property doesn't already exist, we add it. If it exists
            *and we are not preventing overwrites, we edit the existing one.
            *If it exists and we are preventing overwrites, we throw an exception
            */
            if (is_null($property)) {
                // validate key value
                $validateArray ['NAME'] = $key;
                $validateArray ['VALUE'] = $value;
                $validateArray ['ENDPOINTID'] = $endpoint->getId ();
                $this->validate ( $validateArray, 'endpointproperty' );

                $property = new \EndpointProperty ();
                $property->setKeyName ( $key );
                $property->setKeyValue ( $value );
                $endpoint->addEndpointPropertyDoJoin ( $property );
                $this->em->persist ( $property );

                //increment the property counter to enable check against property limit
                $propertyCount++;
            } elseif (!$preventOverwrite) {
                $this->editEndpointPropertyLogic($endpoint->getService(), $property, array('ENDPOINTPROPERTIES'=>array('NAME'=>$key,'VALUE'=>$value)));
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
     * Deletes service properties
     *
     * Checks user permissions and then calls required logic
     *
     * @param \Service $service
     * @param \User $user
     * @param array $propArr
     */
    public function deleteServiceProperties(\Service $service, \User $user, array $propArr) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );

        //Ensure the user has the requisite permissions
        $this->validateAddEditDeleteActions ( $user, $service );

        //Make the change
        $this->em->getConnection ()->beginTransaction ();
        try {
            $this->deleteServicePropertiesLogic($service, $propArr);
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $e ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Deletes service properties: validates the user has permission then calls the
     * required logic
     * @param \Service $service
     * @param \User $user
     * @param array $propArr
     */
    public function deleteServicePropertiesAPI(\Service $service, array $propArr, $authIdentifierType, $authIdentifier) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkGOCDBIsNotReadOnly();

        // Validate the user has permission to delete a property
        foreach ($propArr as $prop) {
            if ($prop->getParentService() != $service) {
                throw new \Exception("Internal error: property parent service and service do not match.");
            }
        }
        $this->checkAuthorisedAPIIdentifier($service->getParentSite(), $authIdentifier, $authIdentifierType);

        //Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->deleteServicePropertiesLogic($service, $propArr);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Logic to delete service properties
     *
     * @param \Service $service
     * @param array $propArr
     */
    protected function deleteServicePropertiesLogic(\Service $service, array $propArr) {
        foreach ( $propArr as $prop ) {
            // throw new \Exception(var_dump($prop));
            // check property is in service
            if ($prop->getParentService () != $service) {
                $id = $prop->getId ();
                throw new \Exception ( "Property {$id} does not belong to the specified service" );
            }

            // Service is the owning side so remove elements from service.
            $service->getServiceProperties ()->removeElement ( $prop );
            // Once relationship is removed delete the actual element
            $this->em->remove ( $prop );
        }
    }

    /**
     * Deletes the given EndpointProperties in the array from their parent Endpoints (if set).
     *
     * First the users permissions are checked
     *
     * @param \User $user
     * @param array $propArr
     */
    public function deleteEndpointProperties(\Service $service, \User $user, array $propArr) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );

        //Check the user has the rquisite permissions
        $this->validateAddEditDeleteActions ($user, $service);

        // Carry out the change
        $this->em->getConnection ()->beginTransaction ();
        try {
            $this->deleteEndpointPropertiesLogic($service, $propArr);
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $e ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Deletes endpoint properties: validates the user has permission then calls the
     * required logic
     * @param \EndpointLocation $endpoint
     * @param \User $user
     * @param array $propArr
     */
    public function deleteEndpointPropertiesAPI(\EndpointLocation $endpoint, array $propArr, $authIdentifierType, $authIdentifier) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkGOCDBIsNotReadOnly();

        $parentService = $endpoint->getService();

        // Validate the user has permission to delete a property
        foreach ($propArr as $prop) {
            if ($prop->getParentEndpoint() != $endpoint) {
                throw new \Exception("Internal error: property endpoint and endpoint do not match");
            }
        }
        $this->checkAuthorisedAPIIdentifier($parentService->getParentSite(), $authIdentifier, $authIdentifierType);

        //Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->deleteEndpointPropertiesLogic($parentService, $propArr);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Logic to delete the given EndpointProperties in the array from their parent Endpoints (if set).
     * If the parent Endpoint has not been set (<code>$prop->getParentEndpoint()</code> returns null
     * then the function throws an exception because the user permissions to delete
     * the EP can't be determined on a null Endpoint.
     *
     * @param \Service $service
     * @param array $propArr
     */
    protected function deleteEndpointPropertiesLogic(\Service $service, array $propArr) {
        foreach ( $propArr as $prop ) {

            // check endpoint property has a parent endpoint
            $endpoint = $prop->getParentEndpoint ();
            if ($endpoint == null) {
                $id = $prop->getId ();
                throw new \Exception ( "Property {$id} does not have a parent endpoint" );
            }

            if ($endpoint->getService() != $service) {
                $id = $prop->getId ();
                throw new \Exception (
                    "Property {$id} does not belong to an endpoint of the specified service"
                );
            }

            // Endoint is the owning side so remove elements from endpoint.
            $endpoint->getEndpointProperties ()->removeElement ( $prop );
            // Once relationship is removed delete the actual element
            $this->em->remove ( $prop );
        }
    }

    /**
     * Edits an existing service property that already belongs to the service.
     *
     * A check is made to ensure the user has the requred permissions then the
     * required logic is called.
     *
     * @param \Service $service
     * @param \User $user
     * @param \ServiceProperty $prop
     * @param array $newValues
     */
    public function editServiceProperty(\Service $service, \User $user, \ServiceProperty $prop, $newValues) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );

        // Validate the user has permission to edit properties
        $this->validateAddEditDeleteActions ( $user, $service );

        //Make the change
        $this->em->getConnection ()->beginTransaction ();
        try {
            $this->editServicePropertyLogic($service, $prop, $newValues);
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $ex ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $ex;
        }
    }

    /**
     * Logic to edit an existing service property that already belongs to the service.
     *
     * A check is performed to confirm the given property is from the parent
     * service, and an exception is thrown if not.
     *
     * @param \Service $service
     * @param \ServiceProperty $prop
     * @param array $newValues
     */
    protected function editServicePropertyLogic(\Service $service, \ServiceProperty $prop, $newValues) {

        $this->validate ( $newValues ['SERVICEPROPERTIES'], 'serviceproperty' );

        //We don't currently want trailing or leading whitespace, so we trim it
        $keyname = trim($newValues['SERVICEPROPERTIES']['NAME']);
        $keyvalue = trim($newValues['SERVICEPROPERTIES']['VALUE']);

        // Check that the prop is from the service
        if ($prop->getParentService () != $service) {
            $id = $prop->getId ();
            throw new \Exception ( "Property {$id} does not belong to the specified service" );
        }

        //If the properties key has changed, check there isn't an existing property with that key
        if ($keyname != $prop->getKeyName()){
            $existingProperties = $service->getServiceProperties();
            foreach ($existingProperties as $existingProp) {
                if ($existingProp->getKeyName() == $keyname) {
                    throw new \Exception("A property with that name already exists for this object");
                }
            }
        }

        // Set the service propertys new member variables
        $prop->setKeyName ( $keyname );
        $prop->setKeyValue ( $keyvalue );

        $this->em->merge ( $prop );
    }

    /**
     * Edits an existing endpoint property that already belongs to the endpoint.
     *
     * A check is made to ensure the user has the requred permissions then the
     * required logic is called.
     *
     * @param \Service $service
     * @param \User $user
     * @param \EndpointProperty $prop
     * @param array $newValues
     * @throws \Exception
     */
    public function editEndpointProperty(\Service $service, \User $user, \EndpointProperty $prop, $newValues) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ($user);

        // Validate the user has permission to edit properties
        $this->validateAddEditDeleteActions ($user, $service);

        //Make the change
        $this->em->getConnection ()->beginTransaction ();
        try {
            $this->editEndpointPropertyLogic($service, $prop, $newValues);
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $ex ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $ex;
        }
    }

    /**
     * Logic to edit an existing endpoint property that already belongs to the endpoint.
     *
     * A check is performed to confirm the given property is from the endpoint's
     * parent service, and an exception is thrown if not.
     *
     * @param \Service $service
     * @param \EndpointProperty $prop
     * @param array $newValues
     * @throws \Exception
     */
    protected function editEndpointPropertyLogic(\Service $service, \EndpointProperty $prop, $newValues) {

        $this->validate ( $newValues ['ENDPOINTPROPERTIES'], 'endpointproperty' );

        //We don't currently want trailing or leading whitespace, so we trim it
        $keyname = trim($newValues['ENDPOINTPROPERTIES']['NAME']);
        $keyvalue = trim($newValues['ENDPOINTPROPERTIES']['VALUE']);

        // Check that the prop is from the endpoint
        if ($prop->getParentEndpoint ()->getService () != $service) {
            $id = $prop->getId ();
            throw new \Exception ( "Property {$id} does not belong to the specified service endpoint" );
        }

        //If the properties key has changed, check there isn't an existing property with that key
        if ($keyname != $prop->getKeyName()){
            $existingProperties = $prop->getParentEndpoint()->getEndpointProperties();
            foreach ($existingProperties as $existingProp) {
                if ($existingProp->getKeyName() == $keyname) {
                    throw new \Exception("A property with that name already exists for this object");
                }
            }
        }

        // Set the endpoints propertys new member variables
        $prop->setKeyName ( $keyname );
        $prop->setKeyValue ( $keyvalue );

        $this->em->merge ( $prop );
    }

    /**
     * Deletes a service
     *
     * @param \Service $s To be deleted
     * @param \User $user Making the request
     * @param $isTest when unit testing this allows for true to be supplied and this method
     *        will not attempt to archive the service which can easily cause errors for service objects without
     *        a full set of information
     * @throws \Exception If user can't be authorized
     */
    public function deleteService(\Service $s, \User $user = null, $isTest = false) {
        require_once __DIR__ . '/../DAOs/ServiceDAO.php';
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );

        if ($this->roleActionAuthorisationService->authoriseAction ( \Action::EDIT_OBJECT, $s->getParentSite (), $user )->getGrantAction () == FALSE) {
            throw new \Exception ( "You don't have permission to delete service." );
        }

        $this->em->getConnection ()->beginTransaction ();
        try {
            $serviceDAO = new \ServiceDAO ();
            $serviceDAO->setEntityManager ( $this->em );

            // Archive site - if this is a test then don't archive
            if ($isTest == false) {
                // Create entry in audit table
                $serviceDAO->addServiceToArchive ( $s, $user );
            }

            // Break links with downtimes and remove downtimes only associated
            // with this service, then remove service
            $serviceDAO->removeService ( $s );

            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $e ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Move the service to the given site.
     *
     * If the service is already a child of the site, no move is attempted.
     *
     * @param \Service $Service Service to move.
     * @param \Site $Site Target site to move the service to.
     * @param \User $user
     * @throws \Exception
     * @throws \LogicException
     */
    public function moveService(\Service $Service, \Site $Site, \User $user = null) {
        // Throws exception if user is not an administrator
        $this->checkUserIsAdmin ( $user );

        $this->em->getConnection ()->beginTransaction (); // suspend auto-commit

        try {
            // If the site or service have no ID - throw logic exception
            $site_id = $Site->getId ();
            if (empty ( $site_id )) {
                throw new \LogicException ( 'Site has no ID' );
            }
            $Service_id = $Service->getId ();
            if (empty ( $Service_id )) {
                throw new \LogicException ( 'Service has no ID' );
            }

            // find old site
            $old_Site = $Service->getParentSite ();

            // If the Site has changed, then we move the service.
            if ($old_Site != $Site) {

                // Remove the service from the old site if it has an old site
                if (! empty ( $old_Site )) {

                    $old_Site->getServices ()->removeElement ( $Service );
                }

                // Add Service to new Site
                $Site->addServiceDoJoin ( $Service );

                // persist
                $this->em->merge ( $Site );
                $this->em->merge ( $old_Site );
            } // close if
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $e ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Function to establish if a service already has a value set
     *
     * @returns boolean
    */
    public function servicePropSet (\Service $service, $servicePropName) {
      switch (strtolower($servicePropName)) {
        case 'hostname':{
          $propValue = $service->getHostName();
          break;
        }
        case 'description':{
          $propValue = $service->getDescription();
          break;
        }
        case 'url':{
          $propValue = $service->getUrl();
          break;
        }
        case 'host_dn':{
          $propValue = $service->getDn();
          break;
        }
        case 'host_ip':{
          $propValue = $service->getIpAddress();
          break;
        }
        case 'host_ip_v6':{
          $propValue = $service->getIpV6Address();
          break;
        }
        case 'host_os':{
          $propValue = $service->getOperatingSystem();
          break;
        }
        case 'email':{
          $propValue = $service->getEmail();
          break;
        }
        case 'host_arch':{
          $propValue = $service->getArchitecture();
          break;
        }
        case 'monitored':
        case 'beta':
        case 'production':
        case 'notify':
        {
          #booleans are always set
          return true;
        }
        default:{
          throw new \Exception("Internal error: service property name ($servicePropName) not ".
          "recognised. Please contact a GOCDB administrator and report this error.");
        }
      }

      return !empty($propValue);
    }

    /**
     * Adds an endpoint to a service
     *
     * @param $values
     * @param \User $user
     * @throws Exception
     * @return \Endpoint
     */
    public function addEndpoint($values, \User $user = null) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );
        $this->validate ( $values ['SERVICEENDPOINT'], 'endpoint' );

        $serviceID = $values ['SERVICEENDPOINT'] ['SERVICE'];
        $service = $this->getService ( $serviceID );

        // check user has permission to edit endpoint's service
        $this->validateAddEditDeleteActions ( $user, $service );

        $name = $values ['SERVICEENDPOINT'] ['NAME'];
        $url = $values ['SERVICEENDPOINT'] ['URL'];
        $description = $values ['SERVICEENDPOINT'] ['DESCRIPTION'];
        $email = $values['SERVICEENDPOINT']['EMAIL'];


        if ($values ['SERVICEENDPOINT'] ['INTERFACENAME'] != '') {
            $interfaceName = $values ['SERVICEENDPOINT'] ['INTERFACENAME'];
        } else {
            $interfaceName = ( string ) $service->getServiceType ();
        }

        if($values['IS_MONITORED']) {
            $monitored = true;
        } else {
            $monitored = false;
        }

        $endpoint = $this->addEndpointLogic($service, $name, $url, $interfaceName, $description, $email, $monitored);
        return $endpoint;
    }

    /**
     * Add an endpoint with the values from the API to the given service
     *
     * @param \Service $service           service to which the new endpoint will be added
     * @param  string            $name               name of endpoint
     * @param  string            $url                url of endpoint
     * @param  string            $interfaceName      interface name of endpoint
     * @param  string            $description        description of endpoint
     * @param  string            $email              email of endpoint
     * @param  boolean           $monitored          whether endpoint is monitored
     * @param  string            $authIdentifier     Authentication string from API
     * @param  string            $authIdentifierType Type of Authentication string
     */
    public function addEndpointApi(\Service $service, $name, $url, $interfaceName, $description, $email, $monitored, $authIdentifier, $authIdentifierType) {
      //Check the portal is not in read only mode, throws exception if it is
      $this->checkGOCDBIsNotReadOnly();

      $this->checkAuthorisedAPIIdentifier($service->getParentSite(), $authIdentifier, $authIdentifierType);

      $this->addEndpointLogic($service, $name, $url, $interfaceName, $description, $email, $monitored);
    }

    /**
     * Function containing the logic to add an endpoint to a sevice, but with
     * none of the authorisation or validation
     *
     * @param \Service $service           service to which the new endpoint will be added
     * @param  string            $name               name of endpoint
     * @param  string            $url                url of endpoint
     * @param  string            $interfaceName      interface name of endpoint
     * @param  string            $description        description of endpoint
     * @param  string            $email              email of endpoint
     * @param  boolean           $monitored          whether endpoint is monitored
     * @throws \Exception
     */
    private function addEndpointLogic (\Service $service, $name, $url, $interfaceName, $description, $email, $monitored){

      if (empty ( $name )) {
          throw new \Exception ( "An endpoint must have a name." );
      }
      // check endpoint's name is unique under the service
      if($this->endpointWithNameExists($service, $name)){
        throw new \Exception ( "Please provide a unique name for this Service Endpoint." );
      }

      $this->em->getConnection ()->beginTransaction ();
      try {
          $endpoint = new \EndpointLocation ();
          $endpoint->setName($name);
          $endpoint->setUrl($url);
          $endpoint->setInterfaceName($interfaceName);
          $endpoint->setDescription($description);
          $endpoint->setEmail($email);
          $endpoint->setMonitored($monitored);
          $service->addEndpointLocationDoJoin($endpoint);
          $this->em->persist($endpoint);

          $this->em->flush();
          $this->em->getConnection()->commit();
      } catch(\Exception $e) {
          $this->em->getConnection()->rollback();
          $this->em->close();
          throw $e;
      }
      return $endpoint;
    }

    /**
     * Update the given endpoint with the given newValues.
     *
     * @param \Service $service
     * @param \User $user
     * @param \EndpointLocation $endpoint
     * @param unknown $newValues
     * @throws \Exception
     * @throws Exception
     */
    public function editEndpoint(\User $user, \EndpointLocation $endpoint, $newValues) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );
        $this->validate ( $newValues ['SERVICEENDPOINT'], 'endpoint' );

        //We shouldn't rely on the service being given to the function - this allows bugs to be introduced that feed the wrong service
        //TODO: remove the $service from the endpoint parameters
        $service = $endpoint->getService ();

        // check user has permission to edit endpoint's service
        $this->validateAddEditDeleteActions ( $user, $service );

        $name = $newValues ['SERVICEENDPOINT'] ['NAME'];
        $url = $newValues ['SERVICEENDPOINT'] ['URL'];
        $description = $newValues ['SERVICEENDPOINT'] ['DESCRIPTION'];
        $email = $newValues['SERVICEENDPOINT']['EMAIL'];
        $interfaceName = $newValues ['SERVICEENDPOINT'] ['INTERFACENAME'];

        if($newValues['IS_MONITORED']) {
            $monitored = true;
        } else {
            $monitored = false;
        }

        $this->editEndpointLogic($endpoint, $name, $url, $interfaceName, $description, $email, $monitored);
    }

    /**
     * Edit the given endpoint using the given values provided from the API
     *
     * @param  \EndpointLocation $endpoint           endpoint to be updated
     * @param  string            $name               name of endpoint
     * @param  string            $url                url of endpoint
     * @param  string            $interfaceName      interface name of endpoint
     * @param  string            $description        description of endpoint
     * @param  string            $email              email of endpoint
     * @param  boolean           $monitored          whether endpoint is monitored
     * @param  string            $authIdentifier     Authentication string from API
     * @param  string            $authIdentifierType Type of Authentication string
     */
    public function editEndpointApi (\EndpointLocation $endpoint, $name, $url, $interfaceName, $description, $email, $monitored, $authIdentifier, $authIdentifierType) {
      //Check the portal is not in read only mode, throws exception if it is
      $this->checkGOCDBIsNotReadOnly();

      $this->checkAuthorisedAPIIdentifier($endpoint->getService()->getParentSite(), $authIdentifier, $authIdentifierType);

      $this->editEndpointLogic($endpoint, $name, $url, $interfaceName, $description, $email, $monitored);

    }

    /**
     * Function containing the logic to edit an endpoint with none of the authorisation or validation
     *
     * @param  EndpointLocation $endpoint      [description]
     * @param  string            $name               name of endpoint
     * @param  string            $url                url of endpoint
     * @param  string            $interfaceName      interface name of endpoint
     * @param  string            $description        description of endpoint
     * @param  string            $email              email of endpoint
     * @param  boolean           $monitored          whether endpoint is monitored
     * @throws \Exception
     */
    private function editEndpointLogic (\EndpointLocation $endpoint, $name, $url, $interfaceName, $description, $email, $monitored){
      $service = $endpoint->getService ();

      if (empty ( $name )) {
          throw new \Exception ( "An endpoint must have a name." );
      }

      //if no interface name is provided, default to service type
      if ($interfaceName == '') {
          $interfaceName = ( string ) $service->getServiceType ();
      }

      // check endpoint's name is unique under the service
      foreach ( $service->getEndpointLocations () as $endpointL ) {
          // exclude itself
          if ($endpoint != $endpointL && $endpointL->getName () == $name) {
              throw new \Exception ( "Please provide a unique name for this endpoint." );
          }
      }

      $this->em->getConnection ()->beginTransaction ();

      try {
          // Set the endpoints new member variables
          $endpoint->setName ( $name );
          $endpoint->setUrl ( $url );
          $endpoint->setInterfaceName ( $interfaceName );
          $endpoint->setDescription ( $description );
          $endpoint->setEmail($email);
          $endpoint->setMonitored($monitored);
          $this->em->merge ( $endpoint );
          $this->em->flush ();
          $this->em->getConnection ()->commit ();
      } catch ( \Exception $ex ) {
          $this->em->getConnection ()->rollback ();
          $this->em->close ();
          throw $ex;
      }
    }

    /**
     * Function to delete endpoint using web portal
     *
     * @param \EndpointLocation $endpoint
     * @param \User $user
     * @throws Exception
     */
    public function deleteEndpoint(\EndpointLocation $endpoint, \User $user) {

        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );

        $service = $endpoint->getService ();

        // check user has permission to edit endpoint's service
        $this->validateAddEditDeleteActions ( $user, $service );

        $this->deleteEndpointLogic($endpoint);
    }

    /**
     * Function called from API to delete an endpoint
     *
     * @param  EndpointLocation $endpoint endpoint to be deleted
     * @param  Service          $service  service of endpoint being deleted
     * @thows  Exception
     */
    public function deleteEndpointAPI (\EndpointLocation $endpoint, $authIdentifier, $authIdentifierType) {
      //Check the portal is not in read only mode, throws exception if it is
      $this->checkGOCDBIsNotReadOnly();

      //Check authorisation
      $this->checkAuthorisedAPIIdentifier($endpoint->getService()->getParentSite(), $authIdentifier, $authIdentifierType);

      //Make the change
      $this->deleteEndpointLogic($endpoint);
    }

    /**
     * Logic to delete an endpoint, abstracted array from authorisation and validation
     *
     * @param  EndpointLocation $endpoint endpoint to delete
     * @throws Exception
     */
    private function deleteEndpointLogic (\EndpointLocation $endpoint) {
      require_once __DIR__ . '/../DAOs/ServiceDAO.php';

      $this->em->getConnection ()->beginTransaction ();
      try {
          $serviceDAO = new \ServiceDAO ();
          $serviceDAO->setEntityManager ( $this->em );
          $serviceDAO->removeEndpoint ( $endpoint );

          $this->em->flush ();
          $this->em->getConnection ()->commit ();
      } catch ( \Exception $e ) {
          $this->em->getConnection ()->rollback ();
          $this->em->close ();
          throw $e;
      }
    }

    /**
     * Function to establish if an endpoint already has a value set
     *
     * @returns boolean
    */
    public function EndpointPropSet (\EndpointLocation $endpoint, $endpointPropName) {
      switch (strtolower($endpointPropName)) {
        case 'name':{
          $propValue = $endpoint->getName();
          break;
        }
        case 'url':{
          $propValue = $endpoint->getUrl();
          break;
        }
        case 'interfacename':{
          $propValue = $endpoint->getInterfaceName();
          break;
        }
        case 'description':{
          $propValue = $endpoint->getDescription();
          break;
        }
        case 'email':{
          $propValue = $endpoint->getEmail();
          break;
        }
        case 'monitored': {
          #booleans are always set
          return true;
        }
        default:{
          throw new \Exception("Internal error: endpoint property name ($endpointPropName) not ".
          "recognised. Please contact a GOCDB administrator and report this error.");
        }
      }

      return !empty($propValue);
    }

    /**
     * Returns true if an endpoint with a given name exists for a given service
     *
     * TODO: This could be made more efficient using a DQL select statement
     * @param  Service $service service being checked
     * @param  string  $name    endpoint name being checked
     * @return boolean
     */
    public function endpointWithNameExists (\Service $service, $name) {
      foreach ($service->getEndpointLocations() as $endpoint) {
        if ($name == $endpoint->getName()){
          return true;
        }
      }
      return false;
    }

    /**
     * Function to return an endpoint for a given service with a given name
     *
     * TODO: This could be made more efficient using a DQL select statement
     * @param  Service $service service endpoint being sought belongs to
     * @param  string  $name    name of endpoint sought
     * @return \Endpoint           endpoint
     * @throws \Exception
     */
    public function getEndpointByName (\Service $service, $name) {
      foreach ($service->getEndpointLocations() as $endpoint) {
        if ($name == $endpoint->getName()){
          return $endpoint;
        }
      }

      //If the endpoint wasn't found, throw exceptions
      throw new \Exception("Endpoint not found");

    }

    private function checkNumberOfScopes($scopeIds) {
        require_once __DIR__ . '/Config.php';
        $configService = new \org\gocdb\services\Config ();
        $minumNumberOfScopes = $configService->getMinimumScopesRequired ( 'service' );
        if (sizeof ( $scopeIds ) < $minumNumberOfScopes) {
            throw new \Exception ( "A service must have at least " . $minumNumberOfScopes . " optional scope(s)  assigned to it." );
        }
    }

    /**
     * For a given service, returns an array containing the names of all the
     * scopes that service has as keys and a boolean as value.
     * The bool is true
     * if the scope is hared with the parent site, false if not.
     *
     * @param \Service $service
     * @return associative array
     */
    public function getScopesWithParentScopeInfo(\Service $service) {
        $parentSite = $service->getParentSite ();
        $parentScopes = $parentSite->getScopes ();
        $childScopes = $service->getScopes ();

        $parentScopesNames = array ();
        foreach ( $parentScopes as $parentScope ) {
            $parentScopesNames [] = $parentScope->getName ();
        }

        $childScopesNames = array ();
        foreach ( $childScopes as $childScope ) {
            $childScopesNames [] = $childScope->getName ();
        }

        $sharedScopesNames = array_intersect ( $childScopesNames, $parentScopesNames );

        $scopeNamesNotShared = array_diff ( $childScopesNames, $parentScopesNames );

        $ScopeNamesAndParentShareInfo = array ();
        foreach ( $sharedScopesNames as $sharedScopesName ) {
            $ScopeNamesAndParentShareInfo [$sharedScopesName] = true;
        }
        foreach ( $scopeNamesNotShared as $scopeNameNotShared ) {
            $ScopeNamesAndParentShareInfo [$scopeNameNotShared] = false;
        }

        // can be replaced with ksort($ScopeNamesAndParentShareInfo, SORT_NATURAL); in php>=5.5
        uksort ( $ScopeNamesAndParentShareInfo, 'strcasecmp' );

        return $ScopeNamesAndParentShareInfo;
    }

    /**
     * Returns true for "Y" and false for everything else.
     *
     * @param  string $text string, usually "Y" or "N"
     * @return boolean
     */
    private function ptlTexToBool($text) {
      if ($text == "Y") {
          return true;
      } else {
          return false;
      }
    }
}
