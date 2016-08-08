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
 * Return an XML document that encodes the Site entities with optional cursor paging.
 * Optionally provide an associative array of query parameters with values
 * used to restrict the results. Only known parameters are honoured while
 * unknown produce and error doc. Parmeter array keys include:
 * <pre>
 * 'sitename', 'roc', 'country', 'certification_status',
 * 'exclude_certification_status', 'production_status', 'scope', 'scope_match', 'extensions', 
 * 'next_cursor', 'prev_cursor' 
 * (where scope refers to Site scope)
 * </pre>
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author James McCarthy
 */
class GetSite implements IPIQuery, IPIQueryPageable, IPIQueryRenderable {

    protected $query;
    protected $validParams;
    protected $em;
    private $selectedRenderingStyle = 'GOCDB_XML';
    private $helpers;
    private $sites;
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

    /**
     * Constructor takes entity manager which is then used by the query builder
     * @param EntityManager $em
     * @param string $portalContextUrl String for the URL portal context (e.g. 'scheme://host:port/portal') 
     *   - used as a prefix to build absolute PORTAL URLs that are rendered in the query output.
     *   Should not end with '/'. 
     * @param string $urlAuthority String for the URL authority (e.g. 'scheme://host:port') 
     *   - used as a prefix to build absolute API URLs that are rendered in the query output 
     *  (e.g. for HATEOAS links/paging). Should not end with '/'.  
     */
    public function __construct($em, $portalContextUrl = 'https://goc.egi.eu/portal', $urlAuthority = '') {
        $this->em = $em;
        $this->helpers = new Helpers();
        $this->portalContextUrl = $portalContextUrl;
        $this->urlAuthority = $urlAuthority; 
    }

    /**
     * Validates parameters against array of pre-defined valid terms for this PI type
     * @param array $parameters
     */
    public function validateParameters($parameters) {
        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array(
            'sitename',
            'roc',
            'country',
            'certification_status',
            'exclude_certification_status',
            'production_status',
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

        $qb->select('DISTINCT s', 'sc', 'sp', 'i', 'cs', 'c', 'n', 'sgrid', 'ti') //, 'tz')
                ->from('Site', 's')
                ->leftJoin('s.siteProperties', 'sp')
                ->leftJoin('s.scopes', 'sc')
                ->leftJoin('s.ngi', 'n')
                ->leftJoin('s.country', 'c')
                ->leftJoin('s.certificationStatus', 'cs')
                ->leftJoin('s.infrastructure', 'i')
                ->leftJoin('s.subGrid', 'sgrid')
                ->leftJoin('s.tier', 'ti')
                //->leftJoin('s.timezone', 'tz') // deprecated, dont use the tz entity
                //->orderBy('s.shortName', 'ASC');
                //->orderBy('s.id', 'ASC')  // oldest first 
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
                $qb, $this->em, $bc, 'Site', 's'
        );

        //Get the result of the scope builder
        $qb = $scopeQueryBuilder->getQB();
        $bc = $scopeQueryBuilder->getBindCount();

        //Get the binds and store them in the local bind array only if any binds are fetched from scopeQueryBuilder
        foreach ((array) $scopeQueryBuilder->getBinds() as $bind) {
            $binds[] = $bind;
        }


        if (isset($parameters ['extensions'])) {
            $ExtensionsQueryBuilder = new ExtensionsQueryBuilder(
                    $parameters ['extensions'], $qb, $this->em, $bc, 'Site');
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


        //Get the dql query from the Query Builder object
        //Testing
        /*
          $dql = $qb->getDql(); //for testing
          $query = $qb->getQuery();
          echo "\n\n\n\n";
          $parameters=$query->getParameters();
          print_r($parameters);
          echo $dql;
          echo "\n\n\n\n";
         */
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
        if($renderingStyle != 'GOCDB_XML' && $renderingStyle != 'GOCDB_XML_LIST' && $renderingStyle != 'GLUE2_XML'){
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
        } else if($this->selectedRenderingStyle == 'GOCDB_XML_LIST'){
            return $this->getXMLShort(); 
        } else if($this->selectedRenderingStyle == 'GLUE2_XML'){
            return $this->getGlue2XML(); 
        }
        else {
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
        $array[] = ('GOCDB_XML_LIST');
        $array[] = ('GLUE2_XML');
        return $array;
    }

    /** Returns proprietary GocDB rendering of the sites data
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

        $sites = $this->sites;

        foreach ($sites as $site) {
            $xmlSite = $xml->addChild('SITE');
            $xmlSite->addAttribute('ID', $site->getId());
            $xmlSite->addAttribute('PRIMARY_KEY', $site->getPrimaryKey());
            $xmlSite->addAttribute('NAME', $site->getShortName());
            $helpers->addIfNotEmpty($xmlSite, 'PRIMARY_KEY', $site->getPrimaryKey());
            $helpers->addIfNotEmpty($xmlSite, 'SHORT_NAME', $site->getShortName());
            $helpers->addIfNotEmpty($xmlSite, 'OFFICIAL_NAME', htmlspecialchars($site->getOfficialName()));
            $helpers->addIfNotEmpty($xmlSite, 'SITE_DESCRIPTION', htmlspecialchars($site->getDescription()));
            $portalUrl = $this->portalContextUrl.'/index.php?Page_Type=Site&id=' . $site->getId();
            $portalUrl = htmlspecialchars($portalUrl);
            $helpers->addIfNotEmpty($xmlSite, 'GOCDB_PORTAL_URL', $portalUrl);
            $helpers->addIfNotEmpty($xmlSite, 'HOME_URL', htmlspecialchars($site->getHomeUrl()));
            $helpers->addIfNotEmpty($xmlSite, 'CONTACT_EMAIL', $site->getEmail());
            $helpers->addIfNotEmpty($xmlSite, 'CONTACT_TEL', $site->getTelephone());
            $helpers->addIfNotEmpty($xmlSite, 'ALARM_EMAIL', $site->getAlarmEmail());
            $helpers->addIfNotEmpty($xmlSite, 'GIIS_URL', htmlspecialchars($site->getGiisUrl()));
            // Tier is an optional parameter
            if ($site->getTier() != null) {
                $helpers->addIfNotEmpty($xmlSite, 'TIER', $site->getTier()->getName());
            }
            $helpers->addIfNotEmpty($xmlSite, 'COUNTRY_CODE', $site->getCountry()->getCode());
            $helpers->addIfNotEmpty($xmlSite, 'COUNTRY', $site->getCountry()->getName());
            $helpers->addIfNotEmpty($xmlSite, 'ROC', $site->getNgi()->getName());
            // SubGrid is an optional parameter
            if ($site->getSubGrid() != null) {
                $helpers->addIfNotEmpty($xmlSite, 'SUBGRID', $site->getSubGrid()->getName());
            }
            $helpers->addIfNotEmpty($xmlSite, 'PRODUCTION_INFRASTRUCTURE', $site->getInfrastructure()->getName());
            $helpers->addIfNotEmpty($xmlSite, 'CERTIFICATION_STATUS', $site->getCertificationStatus()->getName());
            $helpers->addIfNotEmpty($xmlSite, 'TIMEZONE', $site->getTimezoneId());
            $helpers->addIfNotEmpty($xmlSite, 'LATITUDE', $site->getLatitude());
            $helpers->addIfNotEmpty($xmlSite, 'LONGITUDE', $site->getLongitude());
            $helpers->addIfNotEmpty($xmlSite, 'CSIRT_EMAIL', $site->getCsirtEmail());
            $domain = $xmlSite->addChild('DOMAIN');
            $helpers->addIfNotEmpty($domain, 'DOMAIN_NAME', $site->getDomain());
            $helpers->addIfNotEmpty($xmlSite, 'SITE_IP', $site->getIpRange());
            $helpers->addIfNotEmpty($xmlSite, 'SITE_IPV6', $site->getIpV6Range());

            // scopes
            $xmlScopes = $xmlSite->addChild('SCOPES');
            foreach($site->getScopes() as $scope){
               $xmlScope = $xmlScopes->addChild('SCOPE', xssafe($scope->getName()));
            }

            $xmlExtensions = $xmlSite->addChild('EXTENSIONS');
            foreach ($site->getSiteProperties() as $siteProp) {
                //if ($siteProp != "") {
                    $xmlSiteProperty = $xmlExtensions->addChild('EXTENSION');
                    $xmlSiteProperty->addChild('LOCAL_ID', $siteProp->getId());
                    $xmlSiteProperty->addChild('KEY', xssafe($siteProp->getKeyName()));
                    $xmlSiteProperty->addChild('VALUE', xssafe($siteProp->getKeyValue()));

                    // If we want support any char in a property, then we will probably
                    // need to support CDATA sections rather than escaping the
                    // value using xsafe. Below shows how this can be done.
                    // this don't work for obvious reasons:
                    //$xmlSiteProperty->addChild ( 'VALUE', '<![CDATA[<dave>d</dave>]]>' );
                    //$xmlSiteProperty->addChild ( 'VALUE', '<dave>d</dave>' );
                    // Both the samples below show how a CDATA section can be
                    // added to a SimpleXMLElement. The logic uses the DOM api
                    // because the SimpleXML api don't support adding CDATA.
                    // For performance reasons, it may be necessary to create the
                    // whole doc using DOM rather than SimpleXML which would save
                    // on expesnive conversion to/from the SimpleXML to/from DOM.
//                    $myextended = new SimpleXMLExtended('<mycdata/>');
//                    $myextended->title = NULL; // VERY IMPORTANT! We need a node where to append
//                    $myextended->title->addCData('<dave>d</dave>');
//                    $myextended->title->addAttribute('lang', 'en');
//                    $this->sxml_append($xmlSiteProperty, $myextended);
//                    $myextended = new SimpleXMLExtended('<mycdata/>');
//                    $myextended->addCData('<dave>https://dave.dl.ac.uk/query?a=b&c=d</dave>');
//                    $myextended->addAttribute('lang', 'en');
//                    $this->sxml_append($xmlSiteProperty, $myextended);
                //}
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
     * Append the $to element as a child to $from.
     * @param \SimpleXMLElement $to
     * @param \SimpleXMLElement $from
     */
    function sxml_append(\SimpleXMLElement $to, \SimpleXMLElement $from) {
        $toDom = dom_import_simplexml($to);
        $fromDom = dom_import_simplexml($from);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }

    /** Returns the site data in Glue2 XML string.
     *
     * @return String
     */
    private function getGlue2XML() {
        $helpers = $this->helpers;
        $query = $this->query;

        $sites = $query->getResult();

        $xml = new \SimpleXMLElement('<Entities />');
        foreach ($sites as $site) {
            $xmlSite = $xml->addChild('AdminDomain');
            $xmlSite->addAttribute('BaseType', 'Domain');
            $xmlSite->addChild('ID', $site->getId());
            $xmlSite->addChild('Name', $site->getShortName());
            $xmlSite->addChild('OtherInfo', $site->getPrimaryKey());

            $xmlSiteExtParent = $xmlSite->addChild("Extensions");

            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Short_Name', htmlspecialchars($site->getShortName()));
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Official_Name', htmlspecialchars($site->getOfficialName()));
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Site_Description', htmlspecialchars($site->getDescription()));

            $sID = $site->getId();
            if ($sID != "") {
                $portalUrl = htmlspecialchars($this->portalContextUrl.'/index.php?Page_Type=Site&id=' . $sID);
                $xmlSiteExt = $xmlSiteExtParent->addChild("Extension");
                $xmlSiteExt->addChild("LocalID", "GOCDB_Portal_URL");
                $xmlSiteExt->addChild("Key", "GOCDB_Portal_URL");
                $safeUrl = htmlspecialchars($portalUrl);
                $xmlSiteExt->addChild("Value", $safeUrl);
            }

            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Home_URL', htmlspecialchars($site->getHomeUrl()));
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Contact_Email', htmlspecialchars($site->getEmail()));
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Contact_Tel', $site->getTelephone());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Alarm_Email', $site->getAlarmEmail());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'GIIS_URL', htmlspecialchars($site->getGiisUrl()));


            if ($site->getTier() != null) {
                $xmlSiteExt = $xmlSiteExtParent->addChild("Extension");
                $xmlSiteExt->addChild("LocalID", "Tier");
                $xmlSiteExt->addChild("Key", "Tier");
                $xmlSiteExt->addChild("Value", $site->getTier()->getName());
            }

            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Country_Code', $site->getCountry()->getCode());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Country', $site->getCountry()->getName());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'ROC', $site->getNgi()->getName());

            // SubGrid is an optional parameter
            if ($site->getSubGrid() != null) {
                $subGrid = $site->getSubGrid()->getName();
                if ($subGrid != "") {
                    $xmlSiteExt = $xmlSiteExtParent->addChild("Extension");
                    $xmlSiteExt->addChild("LocalID", "Sub_Grid");
                    $xmlSiteExt->addChild("Key", "Sub_Grid");
                    $xmlSiteExt->addChild("Value", $subGrid);
                }
            }


            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Production_Infrastructure', $site->getInfrastructure()->getName());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Certification_Status', $site->getCertificationStatus()->getName());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Timezone', $site->getTimezoneId());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Longitude', $site->getLongitude());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Latitude', $site->getLatitude());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'CSIRT_Email', $site->getCsirtEmail());
            $helpers->addExtIfNotEmpty($xmlSiteExtParent, 'Domain_Name', $site->getDomain());

            $xmlNgiAsoc = $xmlSite->addChild("Associations");
            $services = $site->getServices();
            foreach ($services as $service) {
                $xmlNgiAsoc->addChild("ChildDomainID", $service->getID());
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



    /** Returns proprietary GocDB rendering of the sites data
     *  in an XML String in it's short format
     * @return String
     */
    private function getXMLShort() {
        $helpers = $this->helpers;
        $query = $this->query;

        $xml = new \SimpleXMLElement("<results />");

        $sites = $query->execute();

        foreach ($sites as $site) {
            $xmlSite = $xml->addChild('SITE');
            $xmlSite->addAttribute('ID', $site->getId() /* . "G0" */);
            $xmlSite->addAttribute('PRIMARY_KEY', $site->getPrimaryKey());
            $xmlSite->addAttribute('NAME', $site->getShortName());
            $xmlSite->addAttribute('COUNTRY', $site->getCountry()->getName());
            $xmlSite->addAttribute('COUNTRY_CODE', $site->getCountry()->getCode());
            $xmlSite->addAttribute('ROC', $site->getNgi()->getName());
            $subGrid = $site->getSubGrid();
            if ($subGrid != null) {
                $subGrid = $subGrid->getName();
            }
            $xmlSite->addAttribute('SUBGRID', $subGrid);
            $xmlSite->addAttribute('GIIS_URL', $site->getGiisUrl());
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

// http://coffeerings.posterous.com/php-simplexml-and-cdata
class SimpleXMLExtended extends \SimpleXMLElement {

    public function addCData($cdata_text) {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdata_text));
    }

}
