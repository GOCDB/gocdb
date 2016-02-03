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

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Return an XML document that encodes the downtimes.
 * Optionally provide an associative array of query parameters with values to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * <pre>
 * 'topentity', 'ongoing_only' , 'startdate', 'enddate', 'windowstart', 'windowend',
 * 'scope', 'scope_match', 'page', 'all_lastmonth', 'id' (where scope refers to Service scope)
 * </pre>
 *
 * @author James McCarthy
 * @author David Meredith
 */
class GetDowntime implements IPIQuery {

    protected $query;
    protected $validParams;
    protected $em;
    private $helpers;
    private $page;
    private $nested;
    private $downtimes;
    private $renderMultipleEndpoints;

    /** Constructor takes entity manager which is then used by the
     *  query builder
     *
     * @param EntityManager $em
     * @param Boolean $nested when true this method will return the
     * nested rendering of the downtime data
     */
    public function __construct($em, $nested = false) {
        $this->nested = $nested;
        $this->em = $em;
        $this->helpers = new Helpers();
        $this->renderMultipleEndpoints = true;
    }

    /**
     * Validates parameters against array of pre-defined valid terms
     * for this PI type
     *
     * @param array $parameters
     */
    public function validateParameters($parameters) {

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array(
            'topentity', 'ongoing_only', 'startdate', 'enddate', 'windowstart',
            'windowend', 'scope', 'scope_match', 'page', 'all_lastmonth',
            'site_extensions', 'service_extensions', 'id'
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


        define('DATE_FORMAT', 'Y-m-d H:i');

        $qb = $this->em->createQueryBuilder();

        $qb->select('DISTINCT d', 'els', 'se', 's', 'sc', 'st', 'seels'/* , 'elp' */)
            ->from('Downtime', 'd')
            ->leftJoin('d.endpointLocations', 'els')
            //->leftjoin('els.endpointProperties', 'elp') // to add if rendering endpoint in full (and in select clause)
            ->leftJoin('d.services', 'se')
            ->leftjoin('se.endpointLocations', 'seels')
            ->join('se.parentSite', 's')
            ->leftJoin('se.scopes', 'sc')
            ->join('s.ngi', 'n')
            ->join('s.country', 'c')
            ->join('se.serviceType', 'st')
            ->orderBy('d.startDate', 'DESC');


        /** Due to the unique parameters used by getdowntimes we don't use the ParameterBuilder
         *  here and instead do the parameter building here in this class */
        // Validate page parameter
        if (isset($parameters['page'])) {

            if (is_int(intval($parameters['page'])) && (int) $parameters['page'] > 0) {
                $this->page = (int) $parameters['page'];
            } else {
                echo "<error>Invalid 'page' parameter - must be a whole number greater than zero</error>";
                die();
            }
        }

        if (isset($parameters['topentity'])) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('se.hostName', '?' . ++$bc), $qb->expr()->like('s.shortName', '?' . $bc), $qb->expr()->like('n.name', '?' . $bc), $qb->expr()->like('c.name', '?' . $bc)
            ));
            $binds[] = array($bc, $parameters['topentity']);
        }

        if (isset($parameters['ongoing_only'])) {
            $onGoingOnly = $parameters['ongoing_only'];

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
                isset($parameters['windowend'])) {
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
        foreach ((array) $scopeQueryBuilder->getBinds() as $bind) {
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

        if (isset($parameters['page'])) {
            $maxResults = 1000;
            $page = $parameters['page'];
            if ($page == 1) {
                $offset = 0; // offset is zero-offset (starts from 0 not 1)
            } elseif ($page > 1) {
                $offset = (($page - 1) * $maxResults);
            } else {
                throw new \LogicException('Coding error - invalid page parameter, must be positive int.');
            }
            //See note 2 at bottom of class
            $query->setFirstResult($offset)->setMaxResults($maxResults);
        }

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
     */
    public function executeQuery() {
        //Not yet implemented
        $query = $this->query;

        if ($this->nested == true) {
            //get short formats
            if ($this->page != null) {
                $this->downtimes = new Paginator($query, $fetchJoinCollection = true);
            } else {
                $this->downtimes = $query->getArrayResult();
            }
        } else {
            if ($this->page != null) {
                $this->downtimes = new Paginator($query, $fetchJoinCollection = true);
            } else {
                $this->downtimes = $query->getArrayResult();
            }
        }
        return $this->downtimes;
    }

    /** Downtime data can be returned with a page parameter. When this parameter is set
     * we must use getResult type of fetch. When it is not set we can use getArrayResult.
     * This method will call the correct XML rendering based on whether a page parameter is
     * supplied and whether the nested downtime has been called or non nested.
     *
     * @return String
     */
    public function getXML() {
        $helpers = $this->helpers;
        $downtimes = $this->downtimes;

        if ($this->nested == true) {
            //get short formats
            if ($this->page != null) {
                $xml = $this->getXMLNestedPage($downtimes);
            } else {
                $xml = $this->getXMLNestedNoPage($downtimes);
            }
        } else {
            if ($this->page != null) {
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

    /** Not yet implemented, in future will return the downtime data in Glue2 XML string.
     * @return String
     */
    public function getGlue2XML() {
        throw new \LogicException("Not implemented yet");
    }

    /** Not yet implemented, in future will return the downtime
     *  data in JSON format
     * @throws \LogicException
     */
    public function getJSON() {
        throw new \LogicException("Not implemented yet");
    }

    /**
     * Get result method for Nested format downtime requests with page parameter
     * James Note: Working with MEPS
     * @param $downtimes
     * @return \SimpleXMLElement
     */
    private function getXMLNestedPage($downtimes) {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results />");

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
            $portalUrl = htmlspecialchars('#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtime->getId());
            $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

            $xmlImpactedSE = $xmlDowntime->addChild('SERVICES');
            foreach ($downtime->getServices() as $service) {
                $xmlServices = $xmlImpactedSE->addChild('SERVICE');
                $helpers->addIfNotEmpty($xmlServices, 'PRIMARY_KEY', $service->getId() . 'G0');
                $helpers->addIfNotEmpty($xmlServices, 'HOSTNAME', $service->getHostName());
                $helpers->addIfNotEmpty($xmlServices, 'SERVICE_TYPE', $service->getServiceType()->getName());
                // maybe rename ENDPOINT to SE_ENDPOINT (for rendering mep)
                $helpers->addIfNotEmpty($xmlServices, 'ENDPOINT', $service->getHostName() . $service->getServiceType()->getName());
                $helpers->addIfNotEmpty($xmlServices, 'HOSTED_BY', $service->getParentSite()->getShortName());
                if ($this->renderMultipleEndpoints) {
                    $xmlEndpoints = $xmlServices->addChild('AFFECTED_ENDPOINTS');
                    //Loop through the affected endpoints
                    foreach ($downtime->getEndpointLocations() as $endpoint) {
                        // Only show the endpoint if is from the current service
                        if ($endpoint->getService() == $service) {
                            $xmlEndpoint = $xmlEndpoints->addChild('ENDPOINT');
                            $xmlEndpoint->addChild('ID', $endpoint->getId());
                            $xmlEndpoint->addChild('NAME', $endpoint->getName());
                            // Extensions?
                            $xmlEndpoint->addChild('URL', htmlspecialchars($endpoint->getUrl()));
                            $xmlEndpoint->addChild('INTERFACENAME', $endpoint->getInterfaceName());
                        }
                    }
                }
            }
        }
        return $xml;
    }

    /**
     *
     * @param array $downtimes Array of downtime entities
     * @return \SimpleXMLElement
     */
    private function getXMLNotNestedPage($downtimes) {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results />");

        foreach ($downtimes as $downtime) {
            foreach ($downtime->getServices() as $service) {
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
                $portalUrl = htmlspecialchars('#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtime->getId());
                $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

                if ($this->renderMultipleEndpoints) {
                    $xmlEndpoints = $xmlDowntime->addChild('AFFECTED_ENDPOINTS');
                    //Loop through all the endpoints of a downtime but only render
                    //those from the current service
                    foreach ($downtime->getEndpointLocations() as $endpoint) {
                        if (in_array($endpoint, $service->getEndpointLocations()->toArray())) {
                            $xmlEndpoint = $xmlEndpoints->addChild('ENDPOINT');
                            $xmlEndpoint->addChild('ID', $endpoint->getId());
                            $xmlEndpoint->addChild('NAME', $endpoint->getName());
                            // Extensions?
                            $xmlEndpoint->addChild('URL', htmlspecialchars($endpoint->getUrl()));
                            $xmlEndpoint->addChild('INTERFACENAME', $endpoint->getInterfaceName());
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
     * @param array $downtimes A mixted array graph (a nested array)
     * @return \SimpleXMLElement
     */
    private function getXMLNestedNoPage($downtimes) {
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
            $helpers->addIfNotEmpty($xmlDowntime, 'DESCRIPTION', xssafe(( $downtimeArray ['description'])));
            $helpers->addIfNotEmpty($xmlDowntime, 'INSERT_DATE', strtotime($downtimeArray ['insertDate']->format('Y-m-d H:i:s')));
            $helpers->addIfNotEmpty($xmlDowntime, 'START_DATE', strtotime($downtimeArray ['startDate']->format('Y-m-d H:i:s')));
            $helpers->addIfNotEmpty($xmlDowntime, 'END_DATE', strtotime($downtimeArray ['endDate']->format('Y-m-d H:i:s')));
            $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_START_DATE', $downtimeArray ['startDate']->format('Y-m-d H:i'));
            $helpers->addIfNotEmpty($xmlDowntime, 'FORMATED_END_DATE', $downtimeArray ['endDate']->format('Y-m-d H:i'));
            $portalUrl = htmlspecialchars('#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtimeArray ['id']);
            $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

            //Iterate through the downtime's affected services
            $xmlImpactedSE = $xmlDowntime->addChild('SERVICES');
            foreach ($downtimeArray ['services'] as $service) {
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
                    foreach ($service['endpointLocations'] as $serviceEndpoint) {
                        $currentServiceEndpointIDs[] = $serviceEndpoint['id'];
                    }
                    $xmlEndpoints = $xmlService->addChild('AFFECTED_ENDPOINTS');
                    foreach ($downtimeArray['endpointLocations'] as $dtEndpoint) {
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
     * @param array $downtimes A mixted array graph (a nested array)
     * @return \SimpleXMLElement
     */
    private function getXMLNotNestedNoPage($downtimes) {
        $helpers = $this->helpers;
        $xml = new \SimpleXMLElement("<results/>");
        foreach ($downtimes as $downtimeArray) {
            foreach ($downtimeArray ['services'] as $service) {
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
                $portalUrl = htmlspecialchars('#GOCDB_BASE_PORTAL_URL#/index.php?Page_Type=Downtime&id=' . $downtimeArray ['id']);
                $helpers->addIfNotEmpty($xmlDowntime, 'GOCDB_PORTAL_URL', $portalUrl);

                // debugging
                //$xmlEndpoints = $xmlDowntime->addChild ( 'printServiceArray', htmlspecialchars(print_r($service, true)));
                //$xmlEndpoints = $xmlDowntime->addChild ( 'printServicesEndpointsArray', htmlspecialchars(print_r($service['endpointLocations'], true)));

                if ($this->renderMultipleEndpoints) {
                    // Slice the service's endpointLocations array and store just each endpointLocation's Id
                    $currentServiceEndpointIDs = array();
                    foreach ($service['endpointLocations'] as $serviceEndpoint) {
                        $currentServiceEndpointIDs[] = $serviceEndpoint['id'];
                    }
                    // debugging
                    //$xmlEndpoints = $xmlDowntime->addChild ( 'printServicesEndpointsIdsArray', htmlspecialchars(print_r($currentServiceEndpointIDs, true)));
                    //$arrayData = print_r($downtimeArray['endpointLocations'], true);
                    //$xmlEndpoints = $xmlDowntime->addChild ( 'downtimesAffectedEndpoints', htmlspecialchars($arrayData));
                    $xmlEndpoints = $xmlDowntime->addChild('AFFECTED_ENDPOINTS');
                    foreach ($downtimeArray['endpointLocations'] as $dtEndpoint) {
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
    public function setRenderMultipleEndpoints($renderMultipleEndpoints) {
        $this->renderMultipleEndpoints = $renderMultipleEndpoints;
    }

}
