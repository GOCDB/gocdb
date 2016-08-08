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
 * PI Method that takes query parameters and returns the list of downtimes recently declared with
 * Return an XML document that encodes the downtimes selected from the DB. Supports optional cursor paging. 
 * Optionally provide an associative array of query parameters with values used to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * <pre>
 * 'interval', 'scope', 'scope_match', 'id', 'next_cursor', 'prev_cursor' 
 * (where scope refers to Service scope)
 * </pre>
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author James McCarthy
 */
class GetDowntimeToBroadcast implements IPIQuery, IPIQueryPageable, IPIQueryRenderable {

    protected $query;
    protected $validParams;
    protected $em;
    private $selectedRenderingStyle = 'GOCDB_XML';
    private $helpers;
    private $downtimes;
    private $renderMultipleEndpoints;
    private $portalContextUrl;
    private $urlAuthority;

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

    /** Constructor takes entity manager which is then used by the
     *  query builder
     *
     * @param EntityManager $em
     * @param string $portalContextUrl String for the URL portal context (e.g. 'scheme://host:port/portal') 
     *   - used as a prefix to build absolute PORTAL URLs that are rendered in the query output.
     *   Should not end with '/'. 
     * @param string $urlAuthority String for the URL authority (e.g. 'scheme://host:port') 
     *   - used as a prefix to build absolute API URLs that are rendered in the query output 
     *  (e.g. for HATEOAS links/paging). Should not end with '/'.  
     */
    public function __construct($em, $portalContextUrl = 'https://goc.egi.eu/portal', $urlAuthority = ''){
        $this->em = $em;
        $this->helpers=new Helpers();
        $this->renderMultipleEndpoints = true;
        $this->portalContextUrl = $portalContextUrl;
        $this->urlAuthority = $urlAuthority;
    }

    /**
     * Validates parameters against array of pre-defined valid terms
     * for this PI type
     *
     * @param array $parameters
     */
    public function validateParameters($parameters) {

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array (
                'interval',
                'scope',
                'scope_match',
                'id', 
                'next_cursor', 
                'prev_cursor'
        );

        $this->helpers->validateParams ( $supportedQueryParams, $parameters );
        $this->validParams = $parameters;

    }

    /** Creates the query by building on a queryBuilder object as
     *  required by the supplied parameters
     */
    public function createQuery() {
        $parameters = $this->validParams;
        $binds= array();
        $bc=-1;


        define('DATE_FORMAT', 'Y-m-d H:i');

        //Set the interval
        if(isset($parameters['interval'])) {
            if(is_numeric($parameters['interval'])) {
                $interval = $parameters['interval'];
            } else {
                echo '<error>interval is not a number</error>';
                die();
            }
        } else {
            // Default: downtimes declared in the last day
            $interval = '1';
        }

        $nowMinusIntervalDays = new \DateTime();
        $nowMinusIntervalDays->sub(new \DateInterval('P'.$interval.'D'));
        
        $cursorParams = $this->helpers->getValidCursorPagingParamsHelper($parameters); 
        $this->prev_cursor = $cursorParams['prev_cursor']; 
        $this->next_cursor = $cursorParams['next_cursor']; 
        $this->isPaging = $cursorParams['isPaging']; 
        
        // if we are enforcing paging, force isPaging to true
        if($this->defaultPaging){
            $this->isPaging = true;
        }

        $qb = $this->em->createQueryBuilder();

        $qb	->select('d', 'els', 'se', 's', 'st'/*, 'elp'*/)
            ->from('Downtime', 'd')
            ->join('d.services', 'se')
            ->leftJoin('d.endpointLocations', 'els')
                 //->leftjoin('els.endpointProperties', 'elp') // to add if rendering endpoint in full (and in select clause)
            ->join('se.serviceType', 'st')
            ->join('se.parentSite', 's')
            ->leftJoin('se.scopes', 'sc')
            ->join('s.ngi', 'n')
            ->join('s.country', 'c')
            ->andWhere($qb->expr()->gt('d.insertDate', '?'.++$bc))
            //->orderBy('d.startDate', 'DESC')
          ;

        //Bind interval days
        $binds[] = array($bc,  $nowMinusIntervalDays);
        
        
        // Order by ASC (oldest first: 1, 2, 3, 4)
        $this->direction = 'ASC';
        
        // Cursor where clause:
        // Select rows *FROM* the current cursor position
        // by selecting rows either ABOVE or BELOW the current cursor position
        if($this->isPaging){
            if($this->next_cursor !== null){
                $qb->andWhere('d.id  > ?'.++$bc);
                $binds[] = array($bc, $this->next_cursor);
                $this->direction = 'ASC';
                $this->prev_cursor = null;
            }
            else if($this->prev_cursor !== null){
                $qb->andWhere('d.id  < ?'.++$bc);
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
        
        $qb->orderBy('d.id', $this->direction);
        
        

        if(isset($parameters['id'])){
           $qb->andWhere($qb->expr()->eq('d.id', '?'.++$bc));
           $binds[] = array($bc, $parameters['id']);
        }

        /*Pass parameters to the ParameterBuilder and allow it to add relevant where clauses
        * based on set parameters.
        */
        $parameterBuilder = new ParameterBuilder($parameters, $qb, $this->em, $bc);
        //Get the result of the scope builder
        $qb = $parameterBuilder->getQB();
        $bc = $parameterBuilder->getBindCount();
        //Get the binds and store them in the local bind array - only runs if the returned value is an array
        foreach((array)$parameterBuilder->getBinds() as $bind){
            $binds[] = $bind;
        }

        //Run ScopeQueryBuilder regardless of if scope is set.
        $scopeQueryBuilder = new ScopeQueryBuilder(
                (isset($parameters['scope'])) ? $parameters['scope'] : null,
                (isset($parameters['scope_match'])) ? $parameters['scope_match'] : null,
                $qb,
                $this->em,
                $bc,
                'Service',
                'se'
        );



        //Get the result of the scope builder
        $qb = $scopeQueryBuilder->getQB();
        $bc = $scopeQueryBuilder->getBindCount();

        //Get the binds and store them in the local bind array only if any binds are fetched from scopeQueryBuilder
        foreach((array)$scopeQueryBuilder->getBinds() as $bind){
            $binds[] = $bind;
        }

        //Bind all variables
        $qb = $this->helpers->bindValuesToQuery($binds, $qb);



        $query = $qb->getQuery();

        $this->query = $query;
        return $this->query;
    }


    /**
     * Executes the query that has been built and stores the returned data
     * so it can later be used to create XML, Glue2 XML or JSON.
     */
    public function executeQuery() {
        $cursorPageResults = $this->helpers->cursorPagingExecutorHelper(
                $this->isPaging, $this->query, $this->next_cursor, $this->prev_cursor, $this->direction);
        $this->downtimes = $cursorPageResults['resultSet'];
        $this->resultSetSize = $cursorPageResults['resultSetSize'];
        $this->firstCursorId = $cursorPageResults['firstCursorId'];
        $this->lastCursorId = $cursorPageResults['lastCursorId'];
        return $this->downtimes;
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
            return $this->getXml();
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


    /** Returns proprietary GocDB rendering of the downtime data
     *  in an XML String
     * @return String
     */
    private function getXML(){
        $helpers = $this->helpers;
        $query = $this->query;

        $xml = new \SimpleXMLElement ( "<results />" );

        // Calculate and add paging info
        if ($this->isPaging) {
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasCursorPagingLinksToMetaElem($metaXml, $this->firstCursorId, $this->lastCursorId, $this->urlAuthority);
            $metaXml->addChild("count", $this->resultSetSize);
            $metaXml->addChild("max_page_size", $this->maxResults);
        }

        $downtimes = $this->downtimes;

        foreach($downtimes as $downtime) {
            // duplicate the downtime for each affected service
            foreach($downtime->getServices() as $se){
                $xmlDowntime = $xml->addChild('DOWNTIME');
                $xmlDowntime->addAttribute("ID", $downtime->getId());
                // Note, we are preserving the v4 primary keys here.
                $xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getPrimaryKey());

                $xmlDowntime->addAttribute("CLASSIFICATION", $downtime->getClassification());
                $xmlDowntime->addChild("PRIMARY_KEY", $downtime->getPrimaryKey());

                $xmlDowntime->addChild("SITENAME", $se->getParentSite()->getShortName());
                $xmlDowntime->addChild("HOSTNAME", $se->getHostName());
                $xmlDowntime->addChild("SERVICE_TYPE", $se->getServiceType()->getName());
                $xmlDowntime->addChild("HOSTED_BY", $se->getParentSite()->getShortName());
                $portalUrl = htmlspecialchars($this->portalContextUrl.'/index.php?Page_Type=Downtime&id=' . $downtime->getId());
                $xmlDowntime->addChild('GOCDB_PORTAL_URL', $portalUrl);
                $xmlEndpoints = $xmlDowntime->addChild ( 'AFFECTED_ENDPOINTS' );
                if($this->renderMultipleEndpoints){
                    foreach($downtime->getEndpointLocations() as $endpoint){
                        $xmlEndpoint = $xmlEndpoints->addChild ( 'ENDPOINT' );
                        $xmlEndpoint->addChild ( 'ID', $endpoint->getId());
                        $xmlEndpoint->addChild ( 'NAME', $endpoint->getName());
                        // Extensions?
                        $xmlEndpoint->addChild ( 'URL', htmlspecialchars($endpoint->getUrl()));
                        $xmlEndpoint->addChild ( 'INTERFACENAME', $endpoint->getInterfaceName());
                    }
                }
                $xmlDowntime->addChild('SEVERITY', $downtime->getSeverity());
                $xmlDowntime->addChild('DESCRIPTION', xssafe($downtime->getDescription()));
                $xmlDowntime->addChild('INSERT_DATE', $downtime->getInsertDate()->getTimestamp());
                $xmlDowntime->addChild('START_DATE', $downtime->getStartDate()->getTimestamp());
                $xmlDowntime->addChild('END_DATE', $downtime->getEndDate()->getTimestamp());
                $xmlDowntime->addChild('REMINDER_START_DOWNTIME', $downtime->getAnnounceDate()->getTimestamp());
                // Intentionally left blank to duplicate GOCDBv4 PI behaviour
                $xmlDowntime->addChild('BROADCASTING_START_DOWNTIME', "");
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

    }


    /**
     * Choose to render the multiple endpoints of a service (or not)
     * @param boolean $renderMultipleEndpoints
     */
    public function setRenderMultipleEndpoints($renderMultipleEndpoints){
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