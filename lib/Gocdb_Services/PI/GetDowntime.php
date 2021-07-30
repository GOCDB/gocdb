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


use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Return an XML document that encodes the downtimes with optional cursor-based paging.
 * Optionally provide an associative array of query parameters with values to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * 'topentity', 'ongoing_only' , 'startdate', 'enddate', 'windowstart', 'windowend',
 * 'scope', 'scope_match', 'page', 'all_lastmonth', 'id', 'next_cursor', 'prev_cursor'
 * (where scope refers to Service scope)
 *
 * Note: the following parameters are also available (added for the downtime calendar), and are not yet documented for PI use.
 * (they will work fine though)
 *
 * 'sitelist', 'servicelist', 'ngilist', 'severity', 'classification', 'production' (service production status),
 * 'monitored' (service monitored?), 'certification_status' (site cert status), 'service_type_list'
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author James McCarthy
 * @author Tom Byrne
 */
class GetDowntime implements IPIQuery, IPIQueryPageable, IPIQueryRenderable
{

    protected $query;
    protected $validParams;
    protected $em;
    private $selectedRenderingStyle = 'GOCDB_XML_DUPLICATE_BY_SE';
    private $portalContextUrl;
    private $helpers;
    private $nested;
    private $downtimes;
    private $renderMultipleEndpoints;

    //private $page;
    private $maxResults = 500; //1000;
    private $defaultPaging = false;
    private $isPaging = FALSE;

    // following members are needed for paging
    private $next_cursor=null;     // Stores the 'next_cursor' URL parameter
    private $prev_cursor=null;     // Stores the 'prev_cursor' URL parameter
    private $direction;       // ASC or DESC depending on if this query pages forward or back
    private $resultSetSize=0; // used to build the <count> HATEOAS link
    private $lastCursorId=null;  // Used to build the <next> page HATEOAS link
    private $firstCursorId=null; // Used to build the <prev> page HATEOAS link
    private $urlAuthority;

    // Controls DT result set ordering
    // DESC = youngest first (5,4,3,2,1)
    // ASC = oldest first (1,2,3,4,1)
    // DESC means newer DTs will be prepended to the start of the first page of results.
    // This means that clients will potentially miss any newly added DTs that were added
    // after the time of the first query invocation. It may be better to switch
    // to default ASC so newly added DTs get appended to the last page?
    private $orderByAscDesc = 'ASC'; // DESC or ASC


    /**
     * Constructor takes entity manager which is then used by the query builder
     *
     * @param EntityManager $em
     * @param Boolean $nested When true the affected service endpoints are nested within each downtime element,
     *   when false the dowmtime element is repeated for each affected service endpoint (legacy)
     * @param string $portalContextUrl String for the URL portal context (e.g. 'scheme://host:port/portal')
     *   - used as a prefix to build absolute PORTAL URLs that are rendered in the query output.
     *   Should not end with '/'.
     * @param string $urlAuthority String for the URL authority (e.g. 'scheme://host:port')
     *   - used as a prefix to build absolute API URLs that are rendered in the query output
     *  (e.g. for HATEOAS links/paging). Should not end with '/'.
     */
    public function __construct($em, $nested = false, $portalContextUrl = 'https://goc.egi.eu/portal', $urlAuthority='')
    {
        $this->nested = $nested;
        if($this->nested){
            $this->selectedRenderingStyle = 'GOCDB_XML_NESTED_SE';
        } else {
            $this->selectedRenderingStyle = 'GOCDB_XML_DUPLICATE_BY_SE';
        }
        $this->em = $em;
        $this->helpers = new Helpers();
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
    public function validateParameters($parameters)
    {

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array(
            'topentity', 'ongoing_only', 'startdate', 'enddate', 'windowstart',
            'windowend', 'scope', 'scope_match', 'all_lastmonth',
            'site_extensions', 'service_extensions', 'id', 'sitelist',
            'servicelist', 'ngilist', 'severity', 'classification', 'production',
            'monitored', 'certification_status', 'service_type_list',
            'next_cursor',
            'prev_cursor'
        );

        $this->helpers->validateParams($supportedQueryParams, $parameters);
        $this->validParams = $parameters;
    }

    /** Creates the query by building on a queryBuilder object as
     *  required by the supplied parameters
     */
    public function createQuery()
    {
        $parameters = $this->validParams;
        $binds = array();
        $bc = -1;
        define('DATE_FORMAT', 'Y-m-d H:i');

        $cursorParams = $this->helpers->getValidCursorPagingParamsHelper($parameters);
        $this->prev_cursor = $cursorParams['prev_cursor'];
        $this->next_cursor = $cursorParams['next_cursor'];
        $this->isPaging = $cursorParams['isPaging'];

        // if we are enforcing paging, force isPaging to true
        if($this->defaultPaging){
            $this->isPaging = true;
        }


        $qb = $this->em->createQueryBuilder();

        $qb->select('DISTINCT d', 'els', 'se', 's', 'sc', /*'i',*/ 'cs', 'st', 'seels'/* , 'elp' */)
            ->from('Downtime', 'd')
            ->leftJoin('d.endpointLocations', 'els')
            //->leftjoin('els.endpointProperties', 'elp') // to add if rendering endpoint in full (and in select clause)
            ->leftJoin('d.services', 'se')
            ->leftjoin('se.endpointLocations', 'seels')
            ->join('se.parentSite', 's')
            ->leftJoin('se.scopes', 'sc')
            ->leftJoin('s.certificationStatus', 'cs')
            //->leftJoin('s.infrastructure', 'i') Not needed?
            ->join('s.ngi', 'n')
            ->join('s.country', 'c')
            ->join('se.serviceType', 'st')
            ; //try just joins!


        $this->direction = $this->orderByAscDesc;

        // Cursor where clause:
        // Select rows *FROM* the current cursor position
        // by selecting rows either ABOVE or BELOW the current cursor position
        // (this depends on the orderByAscDesc setting)
        if($this->isPaging){
            if($this->next_cursor !== null){
                // MOVING DOWN/FWD:
                if($this->orderByAscDesc == 'DESC'){
                    $qb->andWhere('d.id  < ?'.++$bc);   // (orderByAscDesc == DESC requires '<')

                } else {
                    $qb->andWhere('d.id  > ?'.++$bc);
                }
                $binds[] = array($bc, $this->next_cursor);
                $this->prev_cursor = null;
            }
            else if($this->prev_cursor !== null){
                // MOVING UP/BACK:
                // We need to order the results in order to move AWAY *FROM*
                // the current cursor position (e.g. 50).
                // We later revese the ordering in the rendering/output to list results
                // as specified by $this->orderByAscDesc.
                if($this->orderByAscDesc == 'DESC'){
                    $qb->andWhere('d.id  > ?'.++$bc);   // (orderByAscDesc == DESC requires '>' )
                    $this->direction = 'ASC';
                    // moving backward: 'select ... where id > prev_cursor(50) orderBy ASC' =>
                    // 51
                    // 52
                    // 53
                } else {
                    $qb->andWhere('d.id  < ?'.++$bc);
                    $this->direction = 'DESC';
                    // moving backward: 'select ... where id < prev_cursor(50) orderBy DESC' =>
                    // 49
                    // 48
                    // 47
                }
                $binds[] = array($bc, $this->prev_cursor);
                $this->next_cursor = null;
            } else {
                $this->next_cursor = null;
                $this->prev_cursor = null;
            }
            // sets the position of the first result to retrieve (the "offset" - 0 by default)
            //$qb->setFirstResult(0);
            // Sets the maximum number of results to retrieve (the "limit")
            $qb->setMaxResults($this->maxResults);
        }

        $qb->orderBy('d.id', $this->direction);



        //These following parameters are for the downtime calendar and are not documented for use in PI.
        if (isset($parameters ['certification_status'])) {
            $qb->andWhere($qb->expr()->like('cs.name', '?' . ++$bc));
            $binds[] = array($bc, $parameters['certification_status']);
        }

        if (isset($parameters['severity'])) {
            $qb->andWhere($qb->expr()->eq('d.severity', '?' . ++$bc));
            $binds[] = array($bc, $parameters['severity']);
        }

        if (isset($parameters['classification'])) {
            $qb->andWhere($qb->expr()->eq('d.classification', '?' . ++$bc));
            $binds[] = array($bc, $parameters['classification']);
        }

        if (isset($parameters['production'])) {
            $qb->andWhere($qb->expr()->eq('se.production', '?' . ++$bc));
            $binds[] = array($bc, $parameters['production']);
        }

        if (isset($parameters ['monitored'])) {
            $qb->andWhere($qb->expr()->eq('se.monitored', '?' . ++$bc));
            $binds[] = array($bc, $parameters['monitored']);
        }

        if (isset($parameters ['service_type_list'])) {
            $serviceTypeArray = explode(",", $parameters['service_type_list']);

            $orX = $qb->expr()->orX();

            foreach ($serviceTypeArray as $serviceType) {
                ++$bc;
                $orX->add($qb->expr()->eq('st.name', '?' . $bc));
                $binds[] = array($bc, $serviceType);
            }
            $qb->andWhere($orX);

        }

        if (isset($parameters['sitelist'])) {
            $siteArray = explode(",", $parameters['sitelist']);

            $orX = $qb->expr()->orX();

            foreach ($siteArray as $site) {
                ++$bc;
                $orX->add($qb->expr()->eq('s.shortName', '?' . $bc));
                $binds[] = array($bc, $site);
            }
            $qb->andWhere($orX);

        }

        //not used by downtime calendar, but could be useful?
//        if (isset($parameters['servicelist'])) {
//            $serviceArray = explode(",", $parameters['servicelist']);
//
//            $orX = $qb->expr()->orX();
//
//            foreach ($serviceArray as $service) {
//                ++$bc;
//                $orX->add($qb->expr()->like('se.hostName', '?' . $bc));
//                $binds[] = array($bc, $service);
//            }
//            $qb->andWhere($orX);
//        }

        if (isset($parameters['ngilist'])) {
            $ngiArray = explode(",", $parameters['ngilist']);

            $orX = $qb->expr()->orX();

            foreach ($ngiArray as $ngi) {
                ++$bc;
                $orX->add($qb->expr()->eq('n.name', '?' . $bc));
                $binds[] = array($bc, $ngi);
            }
            $qb->andWhere($orX);
        }

        //end of parameters for the downtime calendar

        if (isset($parameters['topentity'])) {
            ++$bc;
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('se.hostName', '?' . $bc), $qb->expr()->like('s.shortName', '?' . $bc), $qb->expr()->like('n.name', '?' . $bc), $qb->expr()->like('c.name', '?' . $bc)
            ));
            $binds[] = array($bc, $parameters['topentity']);
        }

        if (isset($parameters['ongoing_only'])) {
            $onGoingOnly = $parameters['ongoing_only'];

            // where d.startDate < now and d.endDate > now
            $qb->andWhere($qb->expr()->andX(
                $qb->expr()->lt('d.startDate', '?' . ++$bc), $qb->expr()->gt('d.endDate', '?' . $bc)
            ));
            $binds[] = array($bc, new \DateTime());


            if ($onGoingOnly == 'yes') {
                if (isset($parameters['enddate']) || isset($parameters['startdate'])) {
                    echo "<error>Invalid parameter combination - do not specify startdate or enddate with ongoing_only</error>";
                    die();
                }
            } else if ($onGoingOnly == 'no') {
                // else do nothing
            } else {
                echo "<error>Invalid ongoing_only value - must be 'yes' or 'no'</error>";
                die();
            }
        }


        if (isset($parameters['startdate'])) {
            $qb->andWhere($qb->expr()->gt('d.startDate', '?' . ++$bc));
            $binds[] = array($bc, new \DateTime($parameters['startdate']));
        }

        if (isset($parameters['enddate'])) {
            $qb->andWhere($qb->expr()->lt('d.endDate', '?' . ++$bc));
            $binds[] = array($bc, new \DateTime($parameters['enddate']));
        }

        if (isset($parameters['windowstart'])) {
            $qb->andWhere($qb->expr()->gt('d.endDate', '?' . ++$bc));
            $binds[] = array($bc, new \DateTime($parameters['windowstart']));
        }

        if (isset($parameters['windowend'])) {
            //Add on dat to windowend so that this reflects V4 method
            $windowEnd = new \DateTime($parameters['windowend']);
            $windowEnd->add(new \DateInterval('P1D'));

            $qb->andWhere($qb->expr()->lt('d.startDate', '?' . ++$bc));
            $binds[] = array($bc, $windowEnd);
        }

        if (isset($parameters['id'])) {
            $qb->andWhere($qb->expr()->eq('d.id', '?' . ++$bc));
            $binds[] = array($bc, $parameters['id']);
        }


        // Special parameter added for ATP who require all downtimes starting
        // from one month ago (including current and future DTs) to be generated
        // in one page result. We don't want to allow the disabling of paging (using $page=-1)
        // for other getDowntime() queries as this circumvents paging as a saftey parameter.
        // It is ok if we are only returning DTs for the last month as the number
        // of DTs will probably be less than the page limit anyway.
        if (isset($parameters['all_lastmonth'])) {
            if (isset($parameters['page']) ||
                isset($parameters['ongoing_only']) ||
                isset($parameters['startdate']) ||
                isset($parameters['enddate']) ||
                isset($parameters['windowstart']) ||
                isset($parameters['windowend'])
            ) {
                echo "<error>Invalid parameters - only scope, scope_match,
                   topentity params allowed when specifying all_lastmonth</error>";
                die();
            }
            // current date with 1 month and 1 day subtracted.
            $startDate = new \DateTime();
            $startDate->sub(new \DateInterval('P1M'));
            $startDate->sub(new \DateInterval('P1D'));

            $qb->andWhere($qb->expr()->gt('d.startDate', '?' . ++$bc));
            $binds[] = array($bc, $startDate);
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
        foreach ((array)$scopeQueryBuilder->getBinds() as $bind) {
            $binds[] = $bind;
        }
        $uID = 0; //If uID is not set a single service_extensions query won't set the uID as it will be null
        /* Pass the site extensions to the extensions class.
         * It will return a query with a clause based on the provided LDAP style query
         */
        if (isset($parameters ['site_extensions'])) {
            $ExtensionsQueryBuilder = new ExtensionsQueryBuilder($parameters ['site_extensions'], $qb, $this->em, $bc, 'Site');
            //Get the modified query
            $qb = $ExtensionsQueryBuilder->getQB();
            $bc = $ExtensionsQueryBuilder->getParameterBindCounter();
            $uID = $ExtensionsQueryBuilder->getTableAliasBindCounter();
            //Get the binds and store them in the local bind array
            foreach ($ExtensionsQueryBuilder->getValuesToBind() as $value) {
                $binds[] = $value;
            }
        }

        /* Pass the service extensions to the extensions class.
         * It will return a query with a clause based on the provided LDAP style query
         */
        if (isset($parameters ['service_extensions'])) {
            $ExtensionsQueryBuilder = new ExtensionsQueryBuilder($parameters ['service_extensions'], $qb, $this->em, $bc, 'Service', $uID);
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

        $query = $qb->getQuery();

        $this->query = $query;
        return $this->query;

        //Testing Get the dql query from the Query Builder object
        /*
          echo "\n\n\n\n";
          $query = $this->query;
          $dql = $qb->getDql(); //for testing
          $parameters=$query->getParameters();
          print_r($parameters);
          echo $dql;
          echo "\n\n\n\n";
          echo $query->getSql();
         */
    }

    /**
     * Executes the query that has been built and stores the returned data
     * so it can later be used to create XML, Glue2 XML or JSON.
     * <p>
     * WARNING - The returned array will be populated using Doctrine's HYDRATE_OBJECT if the query
     * performed (cursor) paging and HYDRATE_ARRAY if not. You MUST be
     * aware of this when processing the returned array.
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-result-formats
     *
     * @return array An array populated using either HYDRATE_OBJECT or HYDRATE_ARRAY
     */
    public function executeQuery() {
        // if paging, then either the user has specified a 'cursor' url param,
        // or defaultPaging is true and this has been set to 0
        if ($this->isPaging) {
            $paginator = new Paginator($this->query, $fetchJoinCollection = true);
            $this->downtimes= array();
            foreach ($paginator as $downtime) {
                $this->downtimes[] = $downtime;
            }

            if($this->orderByAscDesc == 'DESC'){
                if($this->direction == 'ASC'){
                    $this->downtimes = array_reverse($this->downtimes);
                }
            } else {
                if($this->direction == 'DESC'){
                    $this->downtimes = array_reverse($this->downtimes);
                }
            }

            // The paginator returns an array of Downtime objects (HYDRATE_OBJECT), see:
            //   http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-result-formats
            // Therefore, the xml rendering methods that DO page (getXMLNestedPage() + getXMLNotNestedPage())
            // require an object hydrated array, i.e.
//             Array (
//                     [0] => Downtime Object (
//                             [id:protected] => 2284
//                             [description:protected] => WAN Router Firmware Upgrade
//                             [severity:protected] => WARNING
//                     )
//                     [1] => Downtime Object (
//                             [id:protected] => 1234
//                             [description:protected] => blah
//                             [severity:protected] => WARNING
//                     )


        } else {
            // The Downtime calendar AND the xml rendering methods that DONT page (getXMLNestedNoPage() + getXMLNotNestedNoPage())
            // require an array hydrated array (HYDRATE_ARRAY). If I were to use:
            // $this->downtimes= $this->query->execute(); // Alias for execute(null, HYDRATE_OBJECT) to return array of Downtime objects
            // i get (expectedly) the following errors:
            //
            // Invoked from the Downtime calendar:
            // PHP Fatal error:  Cannot use object of type Downtime as array
            // in C:\Users\djm76\Documents\programming-vcs\php\gocdb\gocdb\htdocs\web_portal\controllers\downtime\downtimes_calendar.php on line 114
            //
            // Invoked from a PI query:
            // PHP Fatal error: Cannot use object of type Downtime as array
            // in C:\Users\djm76\Documents\programming-vcs\php\gocdb\gocdb\lib\Gocdb_Services\PI\GetDowntime.php on line 847
            //
            // Therefore we need to ensure HYDRATE_ARRAY with:
            $this->downtimes= $this->query->getArrayResult(); // Alias for execute(null, HYDRATE_ARRAY).
//          $this->downtimes =  Array(
//                     [0] => Array (
//                             [id] => 2284
//                             [description] => WAN Router Firmware Upgrade
//                             [severity] => WARNING
//                             ...
//                     )
//                     [1] => Array (
//                             [id] => 1234
//                             [description] => blah blah blah
//                             [severity] => WARNING
//                             ...
//                     )
//             )
        }

        $this->resultSetSize = count($this->downtimes);

        // Set the first/last Cursor Ids from the FIRST/TOP and LAST/BOTTOM records listed in the result set
        // (needed for building cursor-pagination links).
        if($this->isPaging){
            if($this->resultSetSize > 0){
                $this->lastCursorId = $this->downtimes[$this->resultSetSize - 1]->getId();
                $this->firstCursorId = $this->downtimes[0]->getId();

            } else if ($this->resultSetSize == 0 && $this->next_cursor !==null && $this->next_cursor >= 0){
                // The next_cursor has overshot the last available record,
                // so use the current next_cursor in order to build the 'cursor_prev' link.
                // If the user has been using the next/prev links only and not manually
                // entering the cursor URL param values, then the first occurence of
                // this 'if' condition should mean that the current 'next_cursor' value
                // is the ID of the last record from the previous page (e.g. 20).
                // We +1 to include the last record (e.g. 20) in the previous page i.e. 'where id < 21'.
                if($this->orderByAscDesc == 'DESC'){
                    $this->firstCursorId = $this->next_cursor - 1;     // subtract 1 for DESC
                } else {
                    $this->firstCursorId = $this->next_cursor + 1;     // plus one for ASC
                }
                $this->lastCursorId = null; // if we have overshot, there is no last/next cursor Id
            }
            else if ($this->resultSetSize == 0 && $this->prev_cursor !==null && $this->prev_cursor >= 0){
                // The prev_cursor has undershot the first available record,
                // so use 'prev_cursor - 1' in order to build the 'cursor_next' link.
                if($this->orderByAscDesc == 'DESC'){
                    $this->lastCursorId = $this->prev_cursor + 1;      // add 1 for DESC
                } else {
                    $this->lastCursorId = $this->prev_cursor - 1;      // subtract one for DESC
                }
                $this->firstCursorId = null; // if we have undershot, there is no first/prev cursor Id
            }

            if($this->lastCursorId !==null && $this->lastCursorId < 0) $this->lastCursorId = 0;
            if($this->firstCursorId !==null && $this->firstCursorId < 0) $this->firstCursorId = 0;


        }


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
     * @throws \InvalidArgumentException If the requested rendering style is not 'GOCDB_XML_DUPLICATE_BY_SE' or 'GOCDB_XML_NESTED_SE'
     */
    public function setSelectedRendering($renderingStyle){
        if($renderingStyle = 'GOCDB_XML_DUPLICATE_BY_SE') {
            $this->nested = false;
        } else if($renderingStyle = 'GOCDB_XML_NESTED_SE'){
            $this->nested = true;
        } else {
            throw new \InvalidArgumentException('Requested rendering is not supported');
        }

        $this->selectedRenderingStyle = $renderingStyle;
    }

    /**
     * @return string Query output as a string according to the current rendering style.
     */
    public function getRenderingOutput(){
        if($this->selectedRenderingStyle == 'GOCDB_XML_DUPLICATE_BY_SE' || $this->selectedRenderingStyle == 'GOCDB_XML_NESTED_SE'){
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
        $array[] = ('GOCDB_XML_DUPLICATE_BY_SE');
        $array[] = ('GOCDB_XML_NESTED_SE');
        return $array;
    }


    /** Downtime data can be returned with a page parameter. When this parameter is set
     * we must use getResult type of fetch. When it is not set we can use getArrayResult.
     * This method will call the correct XML rendering based on whether a page parameter is
     * supplied and whether the nested downtime has been called or non nested.
     *
     * @return String
     */
    private function getXML()
    {
        $helpers = $this->helpers;
        $downtimes = $this->downtimes;

        if ($this->nested == true) {
            //get short formats
            if ($this->isPaging) {
                $xml = $this->getXMLNestedPage($downtimes);
            } else {
                $xml = $this->getXMLNestedNoPage($downtimes);
            }
        } else {
            if ($this->isPaging) {
                $xml = $this->getXMLNotNestedPage($downtimes);
            } else {
                $xml = $this->getXMLNotNestedNoPage($downtimes);
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
     * @param array $downtimes An array of Downtime objects - requires Doctrine HYDRATE_OBJECT
     * @return \SimpleXMLElement
     */
    private function getXMLNestedPage($downtimes)
    {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results />");


        // Calculate and add paging info
        if ($this->isPaging) {
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasCursorPagingLinksToMetaElem($metaXml, $this->firstCursorId, $this->lastCursorId, $this->urlAuthority);
            $metaXml->addChild("count", $this->resultSetSize);
            $metaXml->addChild("max_page_size", $this->maxResults);
        }


        foreach ($downtimes as $downtime) {
            $xmlDowntime = $xml->addChild('DOWNTIME');
            // ID is the internal object id/sequence
            $xmlDowntime->addAttribute("ID", $downtime->getId());
            // Note, we are preserving the v4 primary keys here.
            $xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getPrimaryKey());
            $xmlDowntime->addAttribute("CLASSIFICATION", $downtime->getClassification());

            $helpers->addIfNotEmpty($xmlDowntime, 'SEVERITY', $downtime->getSeverity());
            $helpers->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', xssafe($downtime->getDescription()));
            $helpers->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', $downtime->getInsertDate()->getTimestamp());
            $helpers->addIfNotEmpty($xmlDowntime, 'START_DATE', $downtime->getStartDate()->getTimestamp());
            $helpers->addIfNotEmpty($xmlDowntime, 'END_DATE', $downtime->getEndDate()->getTimestamp());
            $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtime->getStartDate()->format(DATE_FORMAT));
            $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtime->getEndDate()->format(DATE_FORMAT));
            $portalUrl = htmlspecialchars($this->portalContextUrl.'/index.php?Page_Type=Downtime&id=' . $downtime->getId());
            $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

            $xmlImpactedSE = $xmlDowntime->addChild('SERVICES');

            // Sort services
            $orderedServices = $this->helpers->orderArrById($downtime->getServices());

            foreach ($orderedServices as $service) {
                $xmlServices = $xmlImpactedSE->addChild('SERVICE');
                $helpers->addIfNotEmpty($xmlServices, 'PRIMARY_KEY', $service->getId() . 'G0');
                $helpers->addIfNotEmpty($xmlServices, 'HOSTNAME', $service->getHostName());
                $helpers->addIfNotEmpty($xmlServices, 'SERVICE_TYPE', $service->getServiceType()->getName());
                // maybe rename ENDPOINT to SE_ENDPOINT (for rendering mep)
                $helpers->addIfNotEmpty($xmlServices, 'ENDPOINT', $service->getHostName() . $service->getServiceType()->getName());
                $helpers->addIfNotEmpty($xmlServices, 'HOSTED_BY', $service->getParentSite()->getShortName());
                if ($this->renderMultipleEndpoints) {
                    $xmlEndpoints = $xmlServices->addChild('AFFECTED_ENDPOINTS');

                    // Sort endpoints
                    $orderedEndpoints = $this->helpers->orderArrById($downtime->getEndpointLocations());

                    foreach ($orderedEndpoints as $endpoint) {
                        // Only show the endpoint if is from the current service
                        if ($endpoint->getService() == $service) {
                            $xmlEndpoint = $xmlEndpoints->addChild('ENDPOINT');
                            $xmlEndpoint->addChild('ID', $endpoint->getId());
                            $xmlEndpoint->addChild('NAME', $endpoint->getName());
                            // Extensions?
                            $xmlEndpoint->addChild('URL', htmlspecialchars($endpoint->getUrl()));
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
                }
            }
        }
        return $xml;
    }

    /**
     * @param array $downtimes An array of Downtime objects - requires Doctrine HYDRATE_OBJECT
     * @return \SimpleXMLElement
     */
    private function getXMLNotNestedPage($downtimes)
    {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results />");

        // Calculate and add paging info
        if ($this->isPaging) {
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasCursorPagingLinksToMetaElem($metaXml, $this->firstCursorId, $this->lastCursorId, $this->urlAuthority);
            $metaXml->addChild("count", $this->resultSetSize);
            $metaXml->addChild("max_page_size", $this->maxResults);
        }

        foreach ($downtimes as $downtime) {

            // Sort services
            $orderedServices = $this->helpers->orderArrById($downtime->getServices());

            foreach ($orderedServices as $service) {
                $xmlDowntime = $xml->addChild('DOWNTIME');
                // ID is the internal object id/sequence
                $xmlDowntime->addAttribute("ID", $downtime->getId());
                $xmlDowntime->addAttribute("PRIMARY_KEY", $downtime->getPrimaryKey());
                $xmlDowntime->addAttribute("CLASSIFICATION", $downtime->getClassification());
                $helpers->addIfNotEmpty($xmlDowntime, 'PRIMARY_KEY', $downtime->getPrimaryKey());
                $helpers->addIfNotEmpty($xmlDowntime, 'HOSTNAME', $service->getHostName());
                $helpers->addIfNotEmpty($xmlDowntime, 'SERVICE_TYPE', $service->getServiceType()->getName());
                // maybe rename ENDPOINT to SE_ENDPOINT (for rendering mep)
                $helpers->addIfNotEmpty($xmlDowntime, 'ENDPOINT', $service->getHostName() . $service->getServiceType()->getName());
                $helpers->addIfNotEmpty($xmlDowntime, 'HOSTED_BY', $service->getParentSite()->getShortName());
                $portalUrl = htmlspecialchars($this->portalContextUrl.'/index.php?Page_Type=Downtime&id=' . $downtime->getId());
                $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

                if ($this->renderMultipleEndpoints) {
                    $xmlEndpoints = $xmlDowntime->addChild('AFFECTED_ENDPOINTS');
                    //Loop through all the endpoints of a downtime but only render
                    //those from the current service

                    // Sort endpoints
                    $orderedEndpoints = $this->helpers->orderArrById($downtime->getEndpointLocations());

                    foreach ($orderedEndpoints as $endpoint) {
                        if (in_array($endpoint, $service->getEndpointLocations()->toArray())) {
                            $xmlEndpoint = $xmlEndpoints->addChild('ENDPOINT');
                            $xmlEndpoint->addChild('ID', $endpoint->getId());
                            $xmlEndpoint->addChild('NAME', $endpoint->getName());
                            // Extensions?
                            $xmlEndpoint->addChild('URL', htmlspecialchars($endpoint->getUrl()));
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
                }
                $helpers->addIfNotEmpty($xmlDowntime, 'SEVERITY', $downtime->getSeverity());
                $helpers->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', xssafe($downtime->getDescription()));
                $helpers->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', $downtime->getInsertDate()->getTimestamp());
                $helpers->addIfNotEmpty($xmlDowntime, 'START_DATE', $downtime->getStartDate()->getTimestamp());
                $helpers->addIfNotEmpty($xmlDowntime, 'END_DATE', $downtime->getEndDate()->getTimestamp());
                $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtime->getStartDate()->format(DATE_FORMAT));
                $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtime->getEndDate()->format(DATE_FORMAT));
            }
        }
        return $xml;
    }

    /**
     * For every downtime, render a <pre>DOWNTIME</pre> element displaying the
     * downtime information. Nest as children the affected services and their
     * endpoints. This is the preferred rendering over getXMLNotNestedNoPage().
     *
     * @param array $downtimes An array graph (a nested array) - requires Doctrine HYDRATE_ARRAY
     * @return \SimpleXMLElement
     */
    private function getXMLNestedNoPage($downtimes)
    {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results/>");
        foreach ($downtimes as $downtimeArray) {
            //Use this to see the strucutre of the array:
            //print_r($downtimeArray);
            // <DOWNTIME> element and attributes start
            $xmlDowntime = $xml->addChild('DOWNTIME');
            $xmlDowntime->addAttribute("ID", $downtimeArray ['id']);
            $xmlDowntime->addAttribute("PRIMARY_KEY", $downtimeArray ['primaryKey']);
            $xmlDowntime->addAttribute("CLASSIFICATION", $downtimeArray ['classification']);
            // <DOWNTIME> element and attributes end

            $helpers->addIfNotEmpty($xmlDowntime, 'SEVERITY', $downtimeArray ['severity']);
            $helpers->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', xssafe(($downtimeArray ['description'])));
            $helpers->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', strtotime($downtimeArray ['insertDate']->format('Y-m-d H:i:s')));
            $helpers->addIfNotEmpty($xmlDowntime, 'START_DATE', strtotime($downtimeArray ['startDate']->format('Y-m-d H:i:s')));
            $helpers->addIfNotEmpty($xmlDowntime, 'END_DATE', strtotime($downtimeArray ['endDate']->format('Y-m-d H:i:s')));
            $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtimeArray ['startDate']->format('Y-m-d H:i'));
            $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtimeArray ['endDate']->format('Y-m-d H:i'));
            $portalUrl = htmlspecialchars($this->portalContextUrl.'/index.php?Page_Type=Downtime&id=' . $downtimeArray ['id']);
            $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

            //Iterate through the downtime's affected services
            $xmlImpactedSE = $xmlDowntime->addChild('SERVICES');

            // Sort services - must be correct type for sorting
            $orderedServices = $this->helpers->orderArrById(new ArrayCollection($downtimeArray['services']));

            foreach ($orderedServices as $service) {
                $xmlService = $xmlImpactedSE->addChild('SERVICE');
                $helpers->addIfNotEmpty($xmlService, 'PRIMARY_KEY', $service['id'] . 'G0');
                $helpers->addIfNotEmpty($xmlService, 'HOSTNAME', htmlspecialchars($service ['hostName']));
                $helpers->addIfNotEmpty($xmlService, 'SERVICE_TYPE', $service ['serviceType'] ['name']);
                // maybe rename ENDPOINT to SE_ENDPOINT (for rendering mep)
                $helpers->addIfNotEmpty($xmlService, 'ENDPOINT', $service['hostName'] . $service['serviceType'] ['name']);
                $helpers->addIfNotEmpty($xmlService, 'HOSTED_BY', $service ['parentSite'] ['shortName']);


                if ($this->renderMultipleEndpoints) {
                    // Slice the service's endpointLocations array and store just each endpointLocation's Id
                    $currentServiceEndpointIDs = array();

                    // These endpoints are unsorted, but there is no benefit adding sorting here, as they are only used
                    // to populate $currentServiceEndpointIDs, which itself is only passed to in_array()
                    foreach ($service['endpointLocations'] as $serviceEndpoint) {
                        $currentServiceEndpointIDs[] = $serviceEndpoint['id'];
                    }
                    $xmlEndpoints = $xmlService->addChild('AFFECTED_ENDPOINTS');

                    // Sort service endpoints
                    $orderedDtEndpoints = $this->helpers->orderArrById(new ArrayCollection($downtimeArray['endpointLocations']));

                    foreach ($orderedDtEndpoints as $dtEndpoint) {
                        // Does this downtimeEndpoint ALSO belong to the current current service?
                        // (we only want to render the current service's endpoints
                        // that are affected by the downtime, NOT all of the
                        // endpoints that are linked to the downtime !)
                        if (in_array($dtEndpoint['id'], $currentServiceEndpointIDs)) {
                            $xmlEndpoint = $xmlEndpoints->addChild('ENDPOINT');
                            $xmlEndpoint->addChild('ID', $dtEndpoint['id']);
                            $xmlEndpoint->addChild('NAME', $dtEndpoint['name']);
                            // Extensions?
                            $xmlEndpoint->addChild('URL', htmlspecialchars($dtEndpoint['url']));
                            $xmlEndpoint->addChild('INTERFACENAME', $dtEndpoint['interfaceName']);
                            if ($dtEndpoint['monitored']) {
                                $mon = "Y";
                            } else {
                                $mon = "N";
                            }
                            $xmlEndpoint->addChild('ENDPOINT_MONITORED', $mon);
                            //$xmlEndpoint->addChild('CONTACT_EMAIL', $dtEndpoint['email']);
                        }
                    }
                }
            }
        }
        return $xml;
    }

    /**
     * For every service that is affected by a downtime, render a new
     * <pre>DOWNTIME</pre> element. If a single downtime affects muliple
     * services, then a new DOWNTIME element is rendered for each service.
     * In doing this, the downtime info is repeated. This doc style is required
     * for legacy reasons.
     *
     * @param array $downtimes An array graph (a nested array) - requires Doctrine HYDRATE_ARRAY
     * @return \SimpleXMLElement
     */
    private function getXMLNotNestedNoPage($downtimes)
    {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results/>");
        foreach ($downtimes as $downtimeArray) {

            // Sort services - must be correct type for sorting
            $orderedServices = $this->helpers->orderArrById(new ArrayCollection($downtimeArray['services']));

            foreach ($orderedServices as $service) {
                // <DOWNTIME> element and attributes start
                $xmlDowntime = $xml->addChild('DOWNTIME');
                $xmlDowntime->addAttribute("ID", $downtimeArray ['id']);
                $xmlDowntime->addAttribute("PRIMARY_KEY", $downtimeArray ['primaryKey']);
                $xmlDowntime->addAttribute("CLASSIFICATION", $downtimeArray ['classification']);
                // <DOWNTIME> element and attributes end
                // Nested elements
                $helpers->addIfNotEmpty($xmlDowntime, 'PRIMARY_KEY', $downtimeArray ['primaryKey']);
                $helpers->addIfNotEmpty($xmlDowntime, 'HOSTNAME', htmlspecialchars($service ['hostName']));
                $helpers->addIfNotEmpty($xmlDowntime, 'SERVICE_TYPE', $service ['serviceType']['name']);
                // maybe rename ENDPOINT to SE_ENDPOINT (for rendering mep)
                $helpers->addIfNotEmpty($xmlDowntime, 'ENDPOINT', $service ['hostName'] . $service ['serviceType']['name']);
                $helpers->addIfNotEmpty($xmlDowntime, 'HOSTED_BY', $service ['parentSite'] ['shortName']);
                $portalUrl = htmlspecialchars($this->portalContextUrl.'/index.php?Page_Type=Downtime&id=' . $downtimeArray ['id']);
                $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

                // debugging
                //$xmlEndpoints = $xmlDowntime->addChild ( 'printServiceArray', htmlspecialchars(print_r($service, true)));
                //$xmlEndpoints = $xmlDowntime->addChild ( 'printServicesEndpointsArray', htmlspecialchars(print_r($service['endpointLocations'], true)));

                if ($this->renderMultipleEndpoints) {
                    // Slice the service's endpointLocations array and store just each endpointLocation's Id
                    $currentServiceEndpointIDs = array();

                    // These endpoints are unsorted, but there is no benefit adding sorting here, as they are only used
                    // to populate $currentServiceEndpointIDs, which itself is only passed to in_array()
                    foreach ($service['endpointLocations'] as $serviceEndpoint) {
                        $currentServiceEndpointIDs[] = $serviceEndpoint['id'];
                    }
                    // debugging
                    //$xmlEndpoints = $xmlDowntime->addChild ( 'printServicesEndpointsIdsArray', htmlspecialchars(print_r($currentServiceEndpointIDs, true)));
                    //$arrayData = print_r($downtimeArray['endpointLocations'], true);
                    //$xmlEndpoints = $xmlDowntime->addChild ( 'downtimesAffectedEndpoints', htmlspecialchars($arrayData));
                    $xmlEndpoints = $xmlDowntime->addChild('AFFECTED_ENDPOINTS');

                    // Sort downtime endpoints - must be correct type for sorting
                    $orderedDtEndpoints = $this->helpers->orderArrById(new ArrayCollection($downtimeArray['endpointLocations']));

                    foreach ($orderedDtEndpoints as $dtEndpoint) {
                        // Does this downtimeEndpoint ALSO belong to the current current service?
                        // (we only want to render the current service's endpoints
                        // that are affected by the downtime, NOT all of the
                        // endpoints that are linked to the downtime !)
                        if (in_array($dtEndpoint['id'], $currentServiceEndpointIDs)) {
                            $xmlEndpoint = $xmlEndpoints->addChild('ENDPOINT');
                            $xmlEndpoint->addChild('ID', $dtEndpoint['id']);
                            $xmlEndpoint->addChild('NAME', $dtEndpoint['name']);
                            // Extensions ?
                            $xmlEndpoint->addChild('URL', htmlspecialchars($dtEndpoint['url']));
                            $xmlEndpoint->addChild('INTERFACENAME', $dtEndpoint['interfaceName']);
                            if ($dtEndpoint['monitored']) {
                                $mon = "Y";
                            } else {
                                $mon = "N";
                            }
                            $xmlEndpoint->addChild('ENDPOINT_MONITORED', $mon);
                            //$xmlEndpoint->addChild('CONTACT_EMAIL', $dtEndpoint['email']);
                        }
                    }
                }
                $helpers->addIfNotEmpty($xmlDowntime, 'SEVERITY', $downtimeArray ['severity']);
                $helpers->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', xssafe($downtimeArray ['description']));
                $helpers->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', strtotime($downtimeArray ['insertDate']->format('Y-m-d H:i:s')));
                $helpers->addIfNotEmpty($xmlDowntime, 'START_DATE', strtotime($downtimeArray ['startDate']->format('Y-m-d H:i:s')));
                $helpers->addIfNotEmpty($xmlDowntime, 'END_DATE', strtotime($downtimeArray ['endDate']->format('Y-m-d H:i:s')));
                $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtimeArray ['startDate']->format('Y-m-d H:i'));
                $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtimeArray ['endDate']->format('Y-m-d H:i'));
            }
        }
        return $xml;
    }

    /**
     * Choose to render the multiple endpoints of a service (or not)
     * @param boolean $renderMultipleEndpoints
     */
    public function setRenderMultipleEndpoints($renderMultipleEndpoints)
    {
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
