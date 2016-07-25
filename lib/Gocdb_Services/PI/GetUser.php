<?php

namespace org\gocdb\services;

/*
 * Copyright © 2011 STFC Licensed under the Apache License,
 * Version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable
 * law or agreed to in writing, software distributed under the License is
 * distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */
require_once __DIR__ . '/QueryBuilders/ExtensionsQueryBuilder.php';
require_once __DIR__ . '/QueryBuilders/ExtensionsParser.php';
require_once __DIR__ . '/QueryBuilders/ScopeQueryBuilder.php';
require_once __DIR__ . '/QueryBuilders/ParameterBuilder.php';
require_once __DIR__ . '/QueryBuilders/Helpers.php';
require_once __DIR__ . '/IPIQuery.php';
require_once __DIR__ . '/../OwnedEntity.php';
require_once __DIR__ . '/IPIQueryPageable.php';

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Return an XML document that encodes the users.
 * Optionally provide an associative array of query parameters with values to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * <pre>
 * 'dn', 'dnlike', 'forename', 'surname', 'roletype', 'page'
 * </pre>
 * Implemented with Doctrine.
 *
 * @author James McCarthy
 * @author David Meredith
 */
class GetUser implements IPIQuery, IPIQueryPageable {

    protected $query;
    protected $validParams;
    protected $em;
    private $helpers;
    private $users;
    private $roleAuthorisationService;
    private $baseUrl;
    
    private $page;  // specifies the requested page number - must be null if not paging
    private $maxResults = 500; //default, set via setPageSize(int);
    private $defaultPaging = false;  // default, set via setDefaultPaging(t/f);
    private $queryBuilder2;
    private $query2;
    private $userCountTotal;
    private $urlAuthority;

    /** Constructor takes entity manager which is then used by the
     *  query builder
     *
     * @param EntityManager $em
     * @param $roleAuthorisationService org\gocdb\services\RoleActionAuthorisationService
     * @param string $baseUrl The base url string to prefix to urls generated in the query output.
     * @param string $urlAuthority String for the URL authority (e.g. 'scheme://host:port') 
     *   - used as a prefix to build absolute API URLs that are rendered in the query output 
     *  (e.g. for HATEOAS links/paging). Should not end with '/'. 
     */
    public function __construct($em, $roleAuthorisationService, $baseUrl = 'https://goc.egi.eu/portal', $urlAuthority='') {
        $this->em = $em;
        $this->helpers = new Helpers();
        $this->roleAuthorisationService = $roleAuthorisationService;
        $this->baseUrl = $baseUrl;
        $this->urlAuthority = $urlAuthority;
    }

    /** Validates parameters against array of pre-defined valid terms
     *  for this PI type
     * @param array $parameters
     */
    public function validateParameters($parameters) {

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array(
            'dn',
            'dnlike',
            'forename',
            'surname',
            'roletype', 
            'page'
        );

        $this->helpers->validateParams($supportedQueryParams, $parameters);
        $this->validParams = $parameters;
    }

    /** Creates the query by building on a queryBuilder object as
     *  required by the supplied parameters
     */
    public function createQuery() {
        $parameters = $this->validParams;
        $binds = array();
        $bc = -1;

        $qb = $this->em->createQueryBuilder();

        //Initialize base query
        $qb->select('u', 'r')
                ->from('User', 'u')
                ->leftJoin('u.roles', 'r')
                ->orderBy('u.id', 'ASC');
        
        // Validate page parameter
        if (isset($parameters['page'])) {
            if( ((string)(int)$parameters['page'] == $parameters['page']) && (int)$parameters['page'] > 0) {
                $this->page = (int) $parameters['page'];
            } else {
                echo "<error>Invalid 'page' parameter - must be a whole number greater than zero</error>";
                die();
            }
        } else {
            if($this->defaultPaging){
                $this->page = 1;
            }
        }

        if (isset($parameters ['roletype']) && isset($parameters ['roletypeAND'])) {
            echo '<error>Only use either roletype or roletypeAND not both</error>';
            die();
        }

        /* If the user has specified a role type generate a new subquery
         * and join this to the main query with "where r.roleType in"
         */
        if (isset($parameters ['roletype'])) {

            $qb1 = $this->em->createQueryBuilder();
            $qb1->select('rt.id')
                    ->from('roleType', 'rt')
                    ->where($qb1->expr()->in('rt.name', '?' . ++$bc));

            //Add to main query
            $qb->andWhere($qb->expr()->in('r.roleType', $qb1->getDQL()));
            //If user provided comma seprated values explode it and bind the resulting array
            if (strpos($parameters['roletype'], ',')) {
                $exValues = explode(',', $parameters['roletype']);
                $qb->setParameter($bc, $exValues);
            } else {
                $qb->setParameter($bc, $parameters['roletype']);
            }
        }

        /* Pass parameters to the ParameterBuilder and allow it to add relevant where clauses
         * based on set parameters.
         */
        $parameterBuilder = new ParameterBuilder($parameters, $qb, $this->em, $bc);
        //Get the result of the scope builder
        $qb = $parameterBuilder->getQB();
        $bc = $parameterBuilder->getBindCount();
        //Get the binds and store them in the local bind array - only runs if the returned value is an array
        foreach ((array) $parameterBuilder->getBinds() as $bind) {
            $binds[] = $bind;
        }



        //Bind all variables
        $qb = $this->helpers->bindValuesToQuery($binds, $qb);
        //Get the dql query from the Query Builder object
        $query = $qb->getQuery();
        
        
        if($this->page != null){
        
            // In order to properly support paging, we need to count the
            // total number of results that can be returned:
        
            //start by cloning the query
            $this->queryBuilder2 = clone $qb;
            //alter the clone so it only returns the count of objects
            $this->queryBuilder2->select('count(DISTINCT u)');
            $this->query2 = $this->queryBuilder2->getQuery();
            //then we don't use setFirst/MaxResult on this query
            //so all sites will be returned and counted, but without all the additional info
        
            // offset is zero offset (starts from zero)
            $offset = (($this->page - 1) * $this->maxResults);
            // sets the position of the first result to retrieve (the "offset")
            $query->setFirstResult($offset);
            // Sets the maximum number of results to retrieve (the "limit")
            $query->setMaxResults($this->maxResults);
        
        }

        $this->query = $query;
        return $this->query;
    }

    /**
     * Executes the query that has been built and stores the returned data
     * so it can later be used to create XML, Glue2 XML or JSON.
     */
    public function executeQuery() {
        //$this->users = $this->query->execute();
        //return $this->users;
        
        // if page is not null, then either the user has specified a 'page' url param,
        // or defaultPaging is true and this has been set to 1
        if ($this->page != null) {
            $this->users = new Paginator($this->query, $fetchJoinCollection = true);
            $this->userCountTotal = $this->query2->getSingleScalarResult();
        
        } else {
            $this->users = $this->query->execute();
        }
        
        return $this->users;
    }

    /** Returns proprietary GocDB rendering of the user data
     *  in an XML String
     * @return String
     */
    public function getXML() {
        $helpers = $this->helpers;
        $users = $this->users;
        $xml = new \SimpleXMLElement("<results />");
        
        // Calculate and add paging info
        // if page is not null, then either the user has specified a 'page' url param,
        // or defaultPaging is true and this has been set to 1
        if ($this->page != null) {
            $last = ceil($this->userCountTotal / $this->maxResults); // can be zero
            $next = $this->page + 1;
            if($last == 0){
                $last = 1;
            }
             
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasPagingLinksToMetaElem($metaXml, $next, $last, $this->urlAuthority);
        }
        
        foreach ($users as $user) {
            $xmlUser = $xml->addChild('EGEE_USER');
            $xmlUser->addAttribute("ID", $user->getId() . "G0");
            $xmlUser->addAttribute("PRIMARY_KEY", $user->getId() . "G0");
            $xmlUser->addChild('FORENAME', $user->getForename());
            $xmlUser->addChild('SURNAME', $user->getSurname());
            $xmlUser->addChild('TITLE', $user->getTitle());
            /*
             * Description is always blank in the PROM get_user output so
             * we'll keep it blank in the Doctrine output for compatibility
             */
            $xmlUser->addChild('DESCRIPTION', "");
            $portalUrl = $this->baseUrl.'/index.php?Page_Type=User&id=' . $user->getId();
            $portalUrl = htmlspecialchars($portalUrl);
            $xmlUser->addChild('GOCDB_PORTAL_URL', $portalUrl);
            $xmlUser->addChild('EMAIL', $user->getEmail());
            $xmlUser->addChild('TEL', $user->getTelephone());
            $xmlUser->addChild('WORKING_HOURS_START', $user->getWorkingHoursStart());
            $xmlUser->addChild('WORKING_HOURS_END', $user->getWorkingHoursEnd());
            $xmlUser->addChild('CERTDN', $user->getCertificateDn());

            $ssousername = $user->getUsername1();
            if ($ssousername != null) {
                $xmlUser->addChild('SSOUSERNAME', $ssousername);
            } else {
                $xmlUser->addChild('SSOUSERNAME');
            }

            /*
             * APPROVED and ACTIVE are always blank in the GOCDBv4 get_user
             * output so we'll keep it blank in the GOCDBv5 output for compatibility
             */
            $xmlUser->addChild('APPROVED', null);
            $xmlUser->addChild('ACTIVE', null);
            $homeSite = "";
            if ($user->getHomeSite() != null) {
                $homeSite = $user->getHomeSite()->getShortName();
            }
            $xmlUser->addChild('HOMESITE', $homeSite);
            /*
             * Add a USER_ROLE element to the XML for each role this user holds.
             */
            foreach ($user->getRoles() as $role) {
                if ($role->getStatus() == "STATUS_GRANTED") {
                    $xmlRole = $xmlUser->addChild('USER_ROLE');
                    $xmlRole->addChild('USER_ROLE', $role->getRoleType()->getName());

                    /*
                     * Find out what the owned entity is to get its name and type
                     */
                    $ownedEntity = $role->getOwnedEntity();
                    // We should use the below method from the ownedEntityService
                    // to get the type value, but we may need to display 'group' to be
                    // backward compatible as below. Also added servicegroup to below else if.
                    // $type = $ownedEntityService->getOwnedEntityDerivedClassName($ownedEntity);
                    $name = $ownedEntity->getName();
                    $type = '';
                    $entityPk = '';
                    if ($ownedEntity instanceof \Site) {
                        $type = "site";
                        $entityPk = $ownedEntity->getPrimaryKey();
                    } else if ($ownedEntity instanceof \NGI) {
                        $type = "ngi"; //"ngi"; // this should be ngi not group
                        $entityPk = $ownedEntity->getId();
                    } else if ($ownedEntity instanceof \Project) {
                        $type = "project"; //"project"; // this should be project not group
                        $entityPk = $ownedEntity->getId();
                    } else if ($ownedEntity instanceof \ServiceGroup) {
                        $type = 'servicegroup';
                        $entityPk = $ownedEntity->getId() . 'G0';
                    } // note, no subgrids but we are removing subgrids.

                    $xmlRole->addChild('ON_ENTITY', $name);
                    $xmlRole->addChild('ENTITY_TYPE', $type);
                    //$xmlRole->addChild ( 'ID', $ownedEntity->getId() );
                    if ($entityPk != '') {
                        $xmlRole->addChild('PRIMARY_KEY', $entityPk);
                    }

                    // Show which projects recognise the role
                    $xmlProjects = $xmlRole->addChild('RECOGNISED_IN_PROJECTS');
                    $parentProjectsForRole = $this->roleAuthorisationService
                            ->getReachableProjectsFromOwnedEntity($role->getOwnedEntity());
                    foreach($parentProjectsForRole as $_proj){
                       $xmlProj = $xmlProjects->addChild('PROJECT', $_proj->getName());
                       $xmlProj->addAttribute('ID', $_proj->getId());
                    }


                }
            }
        }

        $dom_sxe = dom_import_simplexml ( $xml );
        $dom = new \DOMDocument ( '1.0' );
        $dom->encoding = 'UTF-8';
        $dom_sxe = $dom->importNode ( $dom_sxe, true );
        $dom_sxe = $dom->appendChild ( $dom_sxe );
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML ();
        return $xmlString;
        //return $xml->asXML(); // loses formatting
    }

    /** Returns the user data in Glue2 XML string.
     *
     * @return String
     */
    public function getGlue2XML() {
        throw new LogicException("Not implemented yet");
    }

    /** Not yet implemented, in future will return the user
     *  data in JSON format
     * @throws LogicException
     */
    public function getJSON() {
        throw new LogicException("Not implemented yet");
    }

    private function cleanDN($dn) {
        return trim(str_replace(' ', '%20', $dn));
    }
    
    /**
     * This query does not page by default.
     * If set to true, the query will return the first page of results even if the
     * the <pre>page</page> URL param is not provided.
     *
     * @return bool
     */
    public function getDefaultPaging(){
        return $this->defaultPaging;
    }
    
    /**
     * @param boolean $pageTrueOrFalse Set if this query pages by default
     */
    public function setDefaultPaging($pageTrueOrFalse){
        if(!is_bool($pageTrueOrFalse)){
            throw new \InvalidArgumentException('Invalid pageTrueOrFalse, requried bool');
        }
        $this->defaultPaging = $pageTrueOrFalse;
    }
    
    /**
     * Set the default page size (100 by default if not set)
     * @return int The page size (number of results per page)
     */
    public function getPageSize(){
        return $this->maxResults;
    }
    
    /**
     * Set the size of a single page.
     * @param int $pageSize
     */
    public function setPageSize($pageSize){
        if(!is_int($pageSize)){
            throw new \InvalidArgumentException('Invalid pageSize, required int');
        }
        $this->maxResults = $pageSize;
    }

}
