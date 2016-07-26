<?php

namespace org\gocdb\services;

/*
 * Copyright © 2011 STFC Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
require_once __DIR__ . '/QueryBuilders/ExtensionsQueryBuilder.php';
require_once __DIR__ . '/QueryBuilders/ExtensionsParser.php';
require_once __DIR__ . '/QueryBuilders/ScopeQueryBuilder.php';
require_once __DIR__ . '/QueryBuilders/ParameterBuilder.php';
require_once __DIR__ . '/QueryBuilders/Helpers.php';
require_once __DIR__ . '/IPIQuery.php';
require_once __DIR__ . '/IPIQueryPageable.php';

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Return an XML document that encodes the NGIs selected from the DB.
 * Optionally provide an associative array of query parameters with values to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * 'roc', 'scope', 'scope_match', 'page' (where scope refers to NGI scope)
 *
 * @author James McCarthy
 * @author David Meredith
 */
class GetNGI implements IPIQuery, IPIQueryPageable {

    protected $query;
    protected $validParams;
    protected $em;
    private $helpers;
    private $ngis;
    
    private $page;  // specifies the requested page number - must be null if not paging
    private $maxResults = 500; //default, set via setPageSize(int);
    private $defaultPaging = false;  // default, set via setDefaultPaging(t/f);
    private $queryBuilder2;
    private $query2;
    private $ngiCountTotal;
    private $urlAuthority;

    /** Constructor takes entity manager which is then used by the
     *  query builder
     *
     * @param EntityManager $em
     * @param string $urlAuthority String for the URL authority (e.g. 'scheme://host:port') 
     *   - used as a prefix to build absolute API URLs that are rendered in the query output 
     *  (e.g. for HATEOAS links/paging). Should not end with '/'. 
     */
    public function __construct($em,  $urlAuthority = '') {
        $this->em = $em;
        $this->helpers = new Helpers();
        $this->urlAuthority = $urlAuthority;
    }

    /** Validates parameters against array of pre-defined valid terms
     *  for this PI type
     * @param array $parameters
     */
    public function validateParameters($parameters) {

    // Define supported parameters and validate given params (die if an unsupported param is given)
    $supportedQueryParams = array(
        'roc',
        'scope',
        'scope_match', 
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
        $qb->select('n')
            ->from('NGI', 'n')
            ->leftJoin('n.scopes', 'sc')
            ->orderBy('n.id', 'ASC');

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

        //////Start Scope
        //Run ScopeQueryBuilder regardless of if scope is set.
        $scopeQueryBuilder = new ScopeQueryBuilder(
            (isset($parameters['scope'])) ? $parameters['scope'] : null,
            (isset($parameters['scope_match'])) ? $parameters['scope_match'] : null,
            $qb, $this->em, $bc, 'NGI', 'n'
        );

        //Get the result of the scope builder
        $qb = $scopeQueryBuilder->getQB();
        $bc = $scopeQueryBuilder->getBindCount();

        //Get the binds and store them in the local bind array only if any binds are fetched from scopeQueryBuilder
        foreach ((array) $scopeQueryBuilder->getBinds() as $bind) {
            $binds[] = $bind;
        }
        /////End Scope
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
            $this->queryBuilder2->select('count(DISTINCT n)');
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
        //$this->ngis = $this->query->execute();
        //return $this->ngis;
        
        // if page is not null, then either the user has specified a 'page' url param,
        // or defaultPaging is true and this has been set to 1
        if ($this->page != null) {
            $this->ngis = new Paginator($this->query, $fetchJoinCollection = true);
            $this->ngiCountTotal = $this->query2->getSingleScalarResult();
        
        } else {
            $this->ngis = $this->query->execute();
        }
        
        return $this->ngis;
    }

    /** Returns proprietary GocDB rendering of the NGI data
     *  in an XML String
     * @return String
     */
    public function getXML() {
        $helpers = $this->helpers;
        $ngis = $this->ngis;

        $xml = new \SimpleXMLElement("<results />");
        
        // Calculate and add paging info
        // if page is not null, then either the user has specified a 'page' url param,
        // or defaultPaging is true and this has been set to 1
        if ($this->page != null) {
            $last = ceil($this->ngiCountTotal / $this->maxResults); // can be zero
            $next = $this->page + 1;
            if($last == 0){
                $last = 1;
            }
        
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasPagingLinksToMetaElem($metaXml, $next, $last, $this->urlAuthority);
        }

        foreach ($ngis as $ngi) {
            $xmlNgi = $xml->addChild('NGI');
            $xmlNgi->addAttribute("NAME", $ngi->getName());
            $xmlNgi->addChild("PRIMARY_KEY", $ngi->getId());
            $xmlNgi->addChild("NAME", $ngi->getName());
            $xmlNgi->addChild("OBJECT_ID", $ngi->getId());
            $xmlNgi->addChild("DESCRIPTION", xssafe($ngi->getDescription()));
            $xmlNgi->addChild("EMAIL", $ngi->getEmail());
            $xmlNgi->addChild("GGUS_SU", $ngi->getGgus_Su());
            $xmlNgi->addChild("ROD_EMAIL", $ngi->getRodEmail());
            $xmlNgi->addChild("HELPDESK_EMAIL", $ngi->getHelpdeskEmail());
            $xmlNgi->addChild("SECURITY_EMAIL", $ngi->getSecurityEmail());
            $xmlNgi->addChild("SITE_COUNT", count($ngi->getSites()));
            // scopes
            $xmlScopes = $xmlNgi->addChild('SCOPES');
            foreach ($ngi->getScopes() as $scope) {
            $xmlScopes->addChild('SCOPE', xssafe($scope->getName()));
            }
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding = 'UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();
        return $xmlString;
    }

    /** Returns the NGI data in Glue2 XML string.
     *
     * @return String
     */
    public function getGlue2XML() {
        $helpers = $this->helpers;
        $query = $this->query;

        $ngis = $query->getResult();

        $xml = new \SimpleXMLElement("<Entities />");
        
        // Calculate and add paging info
        // if page is not null, then either the user has specified a 'page' url param,
        // or defaultPaging is true and this has been set to 1
        if ($this->page != null) {
            $last = ceil($this->ngiCountTotal / $this->maxResults); // can be zero
            $next = $this->page + 1;
            if($last == 0){
                $last = 1;
            }
        
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasPagingLinksToMetaElem($metaXml, $next, $last, $this->urlAuthority);
        }

        foreach ($ngis as $ngi) {
            $xmlNgi = $xml->addChild("AdminDomain");
            $xmlNgi->addAttribute("BaseType", "Domain");
            $xmlNgi->addChild("ID", $ngi->getId());
            $xmlNgi->addChild("Name", $ngi->getName());

            $xmlNgiExtParent = $xmlNgi->addChild("Extensions");

            $helpers->addExtIfNotEmpty($xmlNgiExtParent, 'Email', $ngi->getEmail());
            $helpers->addExtIfNotEmpty($xmlNgiExtParent, 'Object_ID', $ngi->getId());
            $helpers->addExtIfNotEmpty($xmlNgiExtParent, 'GGUS_SU', $ngi->getGgus_Su());
            $helpers->addExtIfNotEmpty($xmlNgiExtParent, 'Rod_Email', $ngi->getRodEmail());
            $helpers->addExtIfNotEmpty($xmlNgiExtParent, 'Helpdesk_Email', $ngi->getHelpdeskEmail());
            $helpers->addExtIfNotEmpty($xmlNgiExtParent, 'Security_Email', $ngi->getSecurityEmail());
            $helpers->addExtIfNotEmpty($xmlNgiExtParent, 'Site_Count', count($ngi->getSites()));

            $xmlNgi->addChild("Description", $ngi->getDescription());
            $xmlNgi->addChild("Distributed", "true");

            $xmlNgiAsoc = $xmlNgi->addChild("Associations");

            $sites = $ngi->getSites();
            foreach ($sites as $site) {
            $xmlNgiAsoc->addChild("ChildDomainID", $site->getPrimaryKey());
            }
        }

        $dom_sxe = dom_import_simplexml($xml);
        $dom = new \DOMDocument('1.0');
        $dom->encoding = 'UTF-8';
        $dom_sxe = $dom->importNode($dom_sxe, true);
        $dom_sxe = $dom->appendChild($dom_sxe);
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /** Not yet implemented, in future will return the NGI
     *  data in JSON format
     * @throws LogicException
     */
    public function getJSON() {
        $query = $this->query;
        throw new LogicException("Not implemented yet");
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
