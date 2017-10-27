<?php

namespace org\gocdb\services;

/*
 * Copyright © 2011 STFC Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at:
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 */
require_once __DIR__ . '/QueryBuilders/ExtensionsQueryBuilder.php';
require_once __DIR__ . '/QueryBuilders/ExtensionsParser.php';
require_once __DIR__ . '/QueryBuilders/ScopeQueryBuilder.php';
require_once __DIR__ . '/QueryBuilders/ParameterBuilder.php';
require_once __DIR__ . '/QueryBuilders/Helpers.php';
require_once __DIR__ . '/IPIQuery.php';
require_once __DIR__ . '/IPIQueryPageable.php';
require_once __DIR__ . '/IPIQueryRenderable.php';

//use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * Return an XML document that encodes the services with optional cursor-based paging.
 * Optionally provide an associative array of query parameters with values to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * 'hostname', 'sitename', 'roc', 'country', 'service_type', 'monitored',
 * 'scope', 'scope_match', 'properties', 'next_cursor', 'prev_cursor'
 * (where scope refers to Service scope)
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author James McCarthy
 * @author Tom Byrne
 */
class GetService implements IPIQuery, IPIQueryPageable, IPIQueryRenderable {

    protected $query;
    protected $validParams;
    protected $em;
    protected $queryBuilder;
    private $selectedRenderingStyle = 'GOCDB_XML';
    private $helpers;
    private $serviceEndpoints; // A Doctrine Paginator or ArrayCollection
    private $renderMultipleEndpoints;
    private $portalContextUrl;

    //private $page;  // specifies the requested page number - must be null if not paging
    //private $seCountTotal;
    //private $queryBuilder2;
    //private $query2;
    private $maxResults = 500; //default page size, set via setPageSize(int);
    private $defaultPaging = false;  // default, set via setDefaultPaging(t/f);
    private $isPaging = false;   // is true if default paging is t OR if a cursor URL param has been specified for paging.

    // following members are needed for paging
    private $next_cursor=null;     // Stores the 'next_cursor' URL parameter
    private $prev_cursor=null;     // Stores the 'prev_cursor' URL parameter
    private $direction;       // ASC or DESC depending on if this query pages forward or back
    private $resultSetSize=0; // used to build the <count> HATEOAS link
    private $lastCursorId=null;  // Used to build the <next> page HATEOAS link
    private $firstCursorId=null; // Used to build the <prev> page HATEOAS link
    private $urlAuthority;

    /**
     * Constructor takes entity manager which is then used by the query builder.
     *
     * @param EntityManager $em
     * @param string $portalContextUrl String for the URL portal context (e.g. 'scheme://host:port/portal')
     *   - used as a prefix to build absolute PORTAL URLs that are rendered in the query output.
     *   Should not end with '/'.
     * @param string $urlAuthority Authority part of URL (e.g. 'scheme://host:port')
     *   - used as a prefix to build absolute API URLs that are rendered in the query output
     *  (e.g. for HATEOAS links/paging). Should not end with '/'.
     */
    public function __construct($em, $portalContextUrl = 'https://goc.egi.eu/portal', $urlAuthority = '') {
        $this->em = $em;
        $this->helpers = new Helpers();
        $this->renderMultipleEndpoints = true;
        $this->portalContextUrl = $portalContextUrl;
        $this->urlAuthority = $urlAuthority;
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
            'next_cursor',
            'prev_cursor'
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

        $cursorParams = $this->helpers->getValidCursorPagingParamsHelper($parameters);
        $this->prev_cursor = $cursorParams['prev_cursor'];
        $this->next_cursor = $cursorParams['next_cursor'];
        $this->isPaging = $cursorParams['isPaging'];

        // if we are enforcing paging, force isPaging to true
        if($this->defaultPaging){
            $this->isPaging = true;
        }


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
                ->andWhere($qb->expr()->neq('cs.name', '?' . ++$bc)) // certstatus is not 'Closed'
                ;

        //Add 'Closed' certStatus parameter to binds
        $binds[] = array($bc, 'Closed');


        // Order by ASC (oldest first: 1, 2, 3, 4)
        $this->direction = 'ASC';

        // Cursor where clause:
        // Select rows *FROM* the current cursor position
        // by selecting rows either ABOVE or BELOW the current cursor position
        if($this->isPaging){
            if($this->next_cursor !== null){
                // MOVING DOWN/FORWARD:
                // 'select ... where id > next_cursor(50) order by ASC' =>
                // 51
                // 52
                // 53
                $qb->andWhere('se.id  > ?'.++$bc);
                $binds[] = array($bc, $this->next_cursor);
                $this->direction = 'ASC';
                $this->prev_cursor = null;
            }
            else if($this->prev_cursor !== null){
                // MOVING UP/BACKWARD:
                // 'select ... where id < prev_cursor(50) order by DESC' =>
                // 49
                // 48
                // 47
                // When rendering results, we need to revese the ordering
                // to be consistent with ASC.
                $qb->andWhere('se.id  < ?'.++$bc);
                $binds[] = array($bc, $this->prev_cursor);
                $this->direction = 'DESC';
                $this->next_cursor = null;
            } else {
                // no cursor specified
                $this->direction = 'ASC';
                $this->next_cursor = null;
                $this->prev_cursor = null;
            }
            // sets the position of the first result to retrieve (the "offset" - 0 by default)
            //$qb->setFirstResult(0);
            // Sets the maximum number of results to retrieve (the "limit")
            $qb->setMaxResults($this->maxResults);
        }

        $qb->orderBy('se.id', $this->direction);


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
        $cursorPageResults = $this->helpers->cursorPagingExecutorHelper(
                $this->isPaging, $this->query, $this->next_cursor, $this->prev_cursor, $this->direction);
        $this->serviceEndpoints = $cursorPageResults['resultSet'];
        $this->resultSetSize = $cursorPageResults['resultSetSize'];
        $this->firstCursorId = $cursorPageResults['firstCursorId'];
        $this->lastCursorId = $cursorPageResults['lastCursorId'];
        return $this->serviceEndpoints;
    }


    /**
     * Gets the current or default rendering output style.
     */
    public function getSelectedRendering(){
        return $this->$selectedRenderingStyle;
    }

    /**
     * Set the required rendering output style.
     * @param string $renderingStyle
     * @throws \InvalidArgumentException If the requested rendering style is not 'GOCDB_XML'
     */
    public function setSelectedRendering($renderingStyle){
        if($renderingStyle != 'GOCDB_XML'){
            throw new \InvalidArgumentException('Requested rendering is not supported');
        }
        $this->selectedRenderingStyle = $renderingStyle;
    }

    /**
     * @return string Query output as a string according to the current rendering style.
     */
    public function getRenderingOutput(){
        if($this->selectedRenderingStyle == 'GOCDB_XML'){
            return $this->getXML();
        }  else {
            throw new \LogicException('Invalid rendering style internal state');
        }
    }

    /**
     * Returns array with 'GOCDB_XML' values.
     * {@inheritDoc}
     * @see \org\gocdb\services\IPIQueryRenderable::getSupportedRenderings()
     */
    public function getSupportedRenderings(){
        $array = array();
        $array[] = ('GOCDB_XML');
        return $array;
    }


    /** Returns proprietary GocDB rendering of the service endpoint data
     *  in an XML String
     * @return String
     */
    private function getXML() {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results />");

        // Calculate and add paging info
        if ($this->isPaging) {
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasCursorPagingLinksToMetaElem($metaXml, $this->firstCursorId, $this->lastCursorId, $this->urlAuthority);
            $metaXml->addChild("count", $this->resultSetSize);
            $metaXml->addChild("max_page_size", $this->maxResults);
        }


        $serviceEndpoints = $this->serviceEndpoints;

        foreach ($serviceEndpoints as $se) {
            // maybe rename SERVICE_ENDPOINT to SERVICE
            $xmlSe = $xml->addChild('SERVICE_ENDPOINT');
            $xmlSe->addAttribute("PRIMARY_KEY", $se->getId() . "G0");
            $helpers->addIfNotEmpty($xmlSe, 'PRIMARY_KEY', $se->getId() . "G0");
            $helpers->addIfNotEmpty($xmlSe, 'HOSTNAME', $se->getHostName());
            $portalUrl = htmlspecialchars($this->portalContextUrl.'/index.php?Page_Type=Service&id=' . $se->getId());
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
            if ($se->getNotify()) {
                $notifyText = "Y";
            } else {
                $notifyText = "N";
            }
            $xmlSe->addChild('NOTIFICATIONS', $notifyText);
            $site = $se->getParentSite();
            $helpers->addIfNotEmpty($xmlSe, "SITENAME", $site->getShortName());
            $helpers->addIfNotEmpty($xmlSe, "COUNTRY_NAME", $site->getCountry()->getName());
            $helpers->addIfNotEmpty($xmlSe, "COUNTRY_CODE", $site->getCountry()->getCode());
            $helpers->addIfNotEmpty($xmlSe, "ROC_NAME", $site->getNGI()->getName());
    //$helpers->addIfNotEmpty($xmlSe, "siteCertStatus", $site->getCertificationStatus()->getName() );
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
                    if ($endpoint->getMonitored()) {
                        $mon = "Y";
                    } else {
                        $mon = "N";
                    }
                    $xmlEndpoint->addChild('ENDPOINT_MONITORED', $mon);
                    //$xmlEndpoint->addChild('CONTACT_EMAIL', $endpoint->getEmail());
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

    /**
     * See inteface doc.
     * {@inheritDoc}
     * @see \org\gocdb\services\IPIQueryPageable::getPostExecutionPageInfo()
     */
    public function getPostExecutionPageInfo(){
        $pageInfo = array();
        $pageInfo['prev_cursor'] = $this->firstCursorId;
        $pageInfo['next_cursor'] = $this->lastCursorId;
        $pageInfo['count'] = $this->resultSetSize;
        return $pageInfo;
    }


}
