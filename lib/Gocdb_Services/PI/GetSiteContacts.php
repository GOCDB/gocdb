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
 * Return an XML document that encodes the site contacts with optional cursor paging.
 * Optionally provide an associative array of query parameters with values
 * used to restrict the results. Only known parameters are honoured while
 * unknown params produce an error doc.
 * <pre>
 * 'sitename', 'roc', 'country', 'roletype', 'scope', 'scope_match', 'next_cursor', 'prev_cursor'
 * (where scope refers to Site scope)
 * </pre>
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author James McCarthy
 */
class GetSiteContacts implements IPIQuery, IPIQueryPageable, IPIQueryRenderable {
    protected $query;
    protected $validParams;
    protected $em;
    private $selectedRenderingStyle = 'GOCDB_XML';
    private $helpers;
    private $roleT;
    private $sites;

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

    /**
     * Constructor takes entity manager which is then used by the query builder
     *
     * @param EntityManager $em
     * @param string $urlAuthority String for the URL authority (e.g. 'scheme://host:port')
     *   - used as a prefix to build absolute API URLs that are rendered in the query output
     *  (e.g. for HATEOAS links/paging). Should not end with '/'.
     */
    public function __construct($em,  $urlAuthority = ''){
        $this->em = $em;
        $this->helpers=new Helpers();
        $this->urlAuthority = $urlAuthority;
    }

    /** Validates parameters against array of pre-defined valid terms
     *  for this PI type
     * @param array $parameters
     */
    public function validateParameters($parameters){

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array (
                'sitename',
                'roc',
                'country',
                'roletype',
                'scope',
                'scope_match',
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
        $qb	->select('s', 'n', 'r', 'u', 'rt')
        ->from('Site', 's')
        ->leftJoin('s.ngi', 'n')
        ->leftJoin('s.country', 'c')
        ->leftJoin('s.roles', 'r')
        ->leftJoin('r.user', 'u')
        ->leftJoin('r.roleType', 'rt')
        //->orderBy('s.shortName', 'ASC');
        //->orderBy('s.id', 'ASC') // oldest first
        ;

        // Order by ASC (oldest first: 1, 2, 3, 4)
        $this->direction = 'ASC';

        // Cursor where clause:
        // Select rows *FROM* the current cursor position
        // by selecting rows either ABOVE or BELOW the current cursor position
        if($this->isPaging){
            if($this->next_cursor !== null){
                $qb->andWhere('s.id  > ?'.++$bc);
                $binds[] = array($bc, $this->next_cursor);
                $this->direction = 'ASC';
                $this->prev_cursor = null;
            }
            else if($this->prev_cursor !== null){
                $qb->andWhere('s.id  < ?'.++$bc);
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

        $qb->orderBy('s.id', $this->direction);

        /**This is used to filter the reults at the point
         * of building the XML to only show the correct roletypes.
        * Future work could see this build into the query.
        */
        if(isset($parameters['roletype'])) {
            $this->roleT = $parameters['roletype'];
        } else {
            $this->roleT = '%%';
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
                'Site',
                's'
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

        //Get the dql query from the Query Builder object
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
        $this->sites = $cursorPageResults['resultSet'];
        $this->resultSetSize = $cursorPageResults['resultSetSize'];
        $this->firstCursorId = $cursorPageResults['firstCursorId'];
        $this->lastCursorId = $cursorPageResults['lastCursorId'];
        return $this->sites;
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

    /** Returns proprietary GocDB rendering of the site contacts data
     *  in an XML String
     * @return String
     */
    private function getXML(){
        $helpers = $this->helpers;
        $serv = \Factory::getUserService();

        $sites = $this->sites;
        $xml = new \SimpleXMLElement("<results />");

        // Calculate and add paging info
        if ($this->isPaging) {
            $metaXml = $xml->addChild("meta");
            $helpers->addHateoasCursorPagingLinksToMetaElem($metaXml, $this->firstCursorId, $this->lastCursorId, $this->urlAuthority);
            $metaXml->addChild("count", $this->resultSetSize);
            $metaXml->addChild("max_page_size", $this->maxResults);
        }


        foreach ( $sites as $site ) {
            $xmlSite = $xml->addChild ( 'SITE' );
            $xmlSite->addAttribute ( 'ID', $site->getId () . "G0" );
            $xmlSite->addAttribute ( 'PRIMARY_KEY', $site->getPrimaryKey () );
            $xmlSite->addAttribute ( 'NAME', $site->getShortName () );

            $xmlSite->addChild ( 'PRIMARY_KEY', $site->getPrimaryKey () );
            $xmlSite->addChild ( 'SHORT_NAME', $site->getShortName () );
            foreach ( $site->getRoles () as $role ) {
                if ($role->getStatus () == "STATUS_GRANTED") { // Only show users who are granted the role, not pending

                    $rtype = $role->getRoleType ()->getName ();
                    if ($this->roleT == '%%' || $rtype == $this->roleT) {
                        $user = $role->getUser ();
                        $xmlContact = $xmlSite->addChild ( 'CONTACT' );
                        $xmlContact->addAttribute ( 'USER_ID', $user->getId () . "G0" );
                        $xmlContact->addAttribute ( 'PRIMARY_KEY', $user->getId () . "G0" );
                        $xmlContact->addChild ( 'FORENAME', $user->getForename () );
                        $xmlContact->addChild ( 'SURNAME', $user->getSurname () );
                        $xmlContact->addChild ( 'TITLE', $user->getTitle () );
                        $xmlContact->addChild ( 'EMAIL', $user->getEmail () );
                        $xmlContact->addChild ( 'TEL', $user->getTelephone () );

                        if (\Factory::getConfigService()->getAPIAllAuthRealms()) {
                            $xmlContact->addChild ( 'CERTDN', $serv->getIdStringByAuthType ( $user, 'X.509' ) );
                            $xmlContact->addChild ( 'EGICHECKIN', $serv->getIdStringByAuthType ( $user, 'EGI Proxy IdP' ) );
                            $xmlContact->addChild ( 'IRISIAM', $serv->getIdStringByAuthType ( $user, 'IRIS IAM - OIDC' ) );
                            $xmlContact->addChild('EOSCAAI', $serv->getIdStringByAuthType($user, 'EOSC Proxy IdP'));
                        } else {
                            $xmlContact->addChild ( 'CERTDN', $serv->getDefaultIdString ( $user ) );
                        }

                        $xmlContact->addChild ( 'ROLE_NAME', $role->getRoleType ()->getName () );
                    }
                }
            }
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
