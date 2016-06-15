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
 * Return an XML document that encodes the services.
 * Optionally provide an associative array of query parameters with values to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * <pre>
 * 'hostname', 'sitename', 'roc', 'country', 'service_type', 'monitored',
 * 'scope', 'scope_match', 'properties', page (where scope refers to Service scope)
 * </pre>
 *
 * @author James McCarthy
 * @author David Meredith
 * @author Tom Byrne
 */
class GetService implements IPIQuery, IPIQueryPageable {

    protected $query;
    protected $validParams;
    protected $em;
    protected $queryBuilder;
    private $helpers;
    private $serviceEndpoints;
    private $renderMultipleEndpoints;
    private $baseUrl;

    private $page;  // specifies the requested page number - must be null if not paging
    private $maxResults = 500; //1000;
    private $seCountTotal;
    private $queryBuilder2;
    private $query2;
    private $defaultPaging = false;

    /**
     * Constructor takes entity manager which is then used by the query builder.
     *
     * @param EntityManager $em
     * @param string $baseUrl The base url string to prefix to urls generated in the query output.
     */
    public function __construct($em, $baseUrl = 'https://goc.egi.eu/portal') {
        $this->em = $em;
        $this->helpers = new Helpers();
        $this->renderMultipleEndpoints = true;
        $this->baseUrl = $baseUrl;
    }

    /** Validates parameters against array of pre-defined valid terms
     *  for this PI type
     * @param array $parameters
     */
    public function validateParameters($parameters) {

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array(
            'hostname',
            'sitename',
            'roc',
            'country',
            'service_type',
            'monitored',
            'scope',
            'scope_match',
            'extensions',
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
        $qb->select('DISTINCT se', 'sp', 's', 'sc', 'el', 'c', 'n', 'st', 'elp', 'sescopes')
                ->from('Service', 'se')
                ->leftjoin('se.parentSite', 's')
                ->leftjoin('s.certificationStatus', 'cs')
                ->leftJoin('se.scopes', 'sescopes')
                ->leftJoin('s.scopes', 'sc')
                ->leftJoin('se.serviceProperties', 'sp')
                ->leftjoin('se.endpointLocations', 'el')
                ->leftjoin('el.endpointProperties', 'elp')
                ->leftjoin('s.country', 'c')
                ->leftjoin('s.ngi', 'n')
                ->leftjoin('se.serviceType', 'st')
                ->andWhere($qb->expr()->neq('cs.name', '?' . ++$bc))
                ->orderBy('se.id', 'ASC');

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

        //Add closed parameter to binds
        $binds[] = array($bc, 'Closed');

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



        //Run ScopeQueryBuilder regardless of if scope is set.
        $scopeQueryBuilder = new ScopeQueryBuilder(
                (isset($parameters['scope'])) ? $parameters['scope'] : null,
                (isset($parameters['scope_match'])) ? $parameters['scope_match'] : null,
                $qb, $this->em, $bc, 'Service', 'se'
        );

        //Get the result of the scope builder
        $qb = $scopeQueryBuilder->getQB();
        $bc = $scopeQueryBuilder->getBindCount();

        //Get the binds and store them in the local bind array only if any binds are fetched from scopeQueryBuilder
        foreach ((array) $scopeQueryBuilder->getBinds() as $bind) {
            $binds[] = $bind;
        }

        /* Pass the properties to the properties class.
         * It will return a query with a clause based on the provided LDAP
         */
        if (isset($parameters ['extensions'])) {
            $ExtensionsQueryBuilder = new ExtensionsQueryBuilder($parameters ['extensions'], $qb, $this->em, $bc, 'Service');
            //Get the modified query
            $qb = $ExtensionsQueryBuilder->getQB();
            $bc = $ExtensionsQueryBuilder->getParameterBindCounter();
            //Get the binds and store them in the local bind array
            foreach ($ExtensionsQueryBuilder->getValuesToBind() as $value) {
                $binds[] = $value;
            }
        }

        //Bind all variables
        $qb = $this->helpers->bindValuesToQuery($binds, $qb);

        /*
          $dql = $qb->getDql(); //for testing
          $query = $qb->getQuery();
          echo "\n\n\n\n";
          $parameters=$query->getParameters();
          print_r($parameters);
          echo $dql;
          echo "\n\n\n\n";
         */

        //Get the dql query from the Query Builder object
        $query = $qb->getQuery();

        if($this->page != null){

            // In order to properly support paging, we need to count the
            // total number of results that can be returned:

            //start by cloning the query
            $this->queryBuilder2 = clone $qb;
            //alter the clone so it only returns the count of SE objects
            $this->queryBuilder2->select('count(DISTINCT se)');
            $this->query2 = $this->queryBuilder2->getQuery();
            //then we don't use setFirst/MaxResult on this query
            //so all SE's will be returned and counted, but without all the additional info

            // offset is zero offset (starts from zero)
            $offset = (($this->page - 1) * $this->maxResults);
            // sets the position of the first result to retrieve (the "offset")
            $query->setFirstResult($offset);
            // Sets the maximum number of results to retrieve (the "limit")
            $query->setMaxResults($this->maxResults);

        }

        $this->queryBuilder = $qb;
        $this->query = $query;
        return $this->query;
    }

    public function getQueryBuilder(){
        return $this->queryBuilder;
    }

    /**
     * Executes the query that has been built and stores the returned data
     * so it can later be used to create XML, Glue2 XML or JSON.
     */
    public function executeQuery() {
        //$this->serviceEndpoints = $this->query->execute();
        //return $this->serviceEndpoints;

        if ($this->page != null) {
            $this->serviceEndpoints = new Paginator($this->query, $fetchJoinCollection = true);
            //put the total number of SE's into $this->seCountTotal
            $this->seCountTotal = $this->query2->getSingleScalarResult();

        } else {
            $this->serviceEndpoints = $this->query->execute();
        }

        return $this->serviceEndpoints;
    }

    /** Returns proprietary GocDB rendering of the service endpoint data
     *  in an XML String
     * @return String
     */
    public function getXML() {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results />");

        //calculate and add paging info attributes
        if ($this->page != null) {
            $last = ceil($this->seCountTotal / $this->maxResults);
            $next = $this->page + 1;

            $xml->addAttribute("page", $this->page);
            if ($next <= $last) {
                $xml->addAttribute("next", $next);
            }
            $xml->addAttribute("last", $last);
        }


        $serviceEndpoints = $this->serviceEndpoints;

        foreach ($serviceEndpoints as $se) {
            // maybe rename SERVICE_ENDPOINT to SERVICE
            $xmlSe = $xml->addChild('SERVICE_ENDPOINT');
            $xmlSe->addAttribute("PRIMARY_KEY", $se->getId() . "G0");
            $helpers->addIfNotEmpty($xmlSe, 'PRIMARY_KEY', $se->getId() . "G0");
            $helpers->addIfNotEmpty($xmlSe, 'HOSTNAME', $se->getHostName());
            $portalUrl = htmlspecialchars($this->baseUrl.'/index.php?Page_Type=Service&id=' . $se->getId());
            $helpers->addIfNotEmpty($xmlSe, 'GOCDB_PORTAL_URL', $portalUrl);
            $helpers->addIfNotEmpty($xmlSe, 'HOSTDN', $se->getDn());
            $helpers->addIfNotEmpty($xmlSe, 'HOST_OS', $se->getOperatingSystem());
            $helpers->addIfNotEmpty($xmlSe, 'HOST_ARCH', $se->getArchitecture());

            if ($se->getBeta()) {
                $beta = "Y";
            } else {
                $beta = "N";
            }
            $xmlSe->addChild('BETA', $beta);

            $helpers->addIfNotEmpty($xmlSe, 'SERVICE_TYPE', $se->getServiceType()->getName());
            $helpers->addIfNotEmpty($xmlSe, 'HOST_IP', $se->getIpAddress());
            $helpers->addIfNotEmpty($xmlSe, 'HOST_IPV6', $se->getIpV6Address());
            $xmlSe->addChild("CORE", "");

            if ($se->getProduction()) {
                $prod = "Y";
            } else {
                $prod = "N";
            }
            $xmlSe->addChild('IN_PRODUCTION', $prod);

            if ($se->getMonitored()) {
                $mon = "Y";
            } else {
                $mon = "N";
            }
            $xmlSe->addChild('NODE_MONITORED', $mon);
            $site = $se->getParentSite();
            $helpers->addIfNotEmpty($xmlSe, "SITENAME", $site->getShortName());
            $helpers->addIfNotEmpty($xmlSe, "COUNTRY_NAME", $site->getCountry()->getName());
            $helpers->addIfNotEmpty($xmlSe, "COUNTRY_CODE", $site->getCountry()->getCode());
            $helpers->addIfNotEmpty($xmlSe, "ROC_NAME", $site->getNGI()->getName());
            $xmlSe->addChild("URL", xssafe($se->getUrl()));

            if ($this->renderMultipleEndpoints) {
                $xmlEndpoints = $xmlSe->addChild('ENDPOINTS');
                foreach ($se->getEndpointLocations() as $endpoint) {
                    $xmlEndpoint = $xmlEndpoints->addChild('ENDPOINT');
                    $xmlEndpoint->addChild('ID', $endpoint->getId());
                    $xmlEndpoint->addChild('NAME', xssafe($endpoint->getName()));
                    // Endpoint Extensions
                    $xmlExtensions = $xmlEndpoint->addChild('EXTENSIONS');
                    foreach ($endpoint->getEndpointProperties() as $prop) {
                        $xmlProperty = $xmlExtensions->addChild('EXTENSION');
                        $xmlProperty->addChild('LOCAL_ID', $prop->getId());
                        $xmlProperty->addChild('KEY', $prop->getKeyName());
                        $xmlProperty->addChild('VALUE', $prop->getKeyValue());
                    }
                    $xmlEndpoint->addChild('URL', xssafe($endpoint->getUrl()));
                    $xmlEndpoint->addChild('INTERFACENAME', $endpoint->getInterfaceName());
                }
            }

            // scopes
            $xmlScopes = $xmlSe->addChild('SCOPES');
            foreach($se->getScopes() as $scope){
               $xmlScopes->addChild('SCOPE', xssafe($scope->getName()));
            }

            // Service Extensions
            $xmlExtensions = $xmlSe->addChild('EXTENSIONS');
            foreach ($se->getServiceProperties() as $prop) {
                $xmlProperty = $xmlExtensions->addChild('EXTENSION');
                $xmlProperty->addChild('LOCAL_ID', $prop->getId());
                $xmlProperty->addChild('KEY', xssafe($prop->getKeyName()));
                $xmlProperty->addChild('VALUE', xssafe($prop->getKeyValue()));
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

    /** Returns the service endpoint data in Glue2 XML string.
     *
     * @return String
     */
    public function getGlue2XML() {
        throw new LogicException("Not implemented yet");
    }

    /** Not yet implemented, in future will return the service endpoint
     *  data in JSON format
     * @throws LogicException
     */
    public function getJSON() {
        throw new LogicException("Not implemented yet");
    }

    /**
     * Choose to render the multiple endpoints of a service (or not)
     * @param boolean $renderMultipleEndpoints
     */
    public function setRenderMultipleEndpoints($renderMultipleEndpoints) {
        $this->renderMultipleEndpoints = $renderMultipleEndpoints;
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
