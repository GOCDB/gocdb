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


/**
 * PI Method that takes query parameters and returns the list of downtimes recently declared with
 * Return an XML document that encodes the downtimes selected from the DB.
 * Optionally provide an associative array of query parameters with values used to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc.
 * Parmeter array keys include:
 * <pre>
 * 'interval', 'scope', 'scope_match' (where scope refers to Service scope)
 * </pre>
 *
 * @author James McCarthy
 * @author David Meredith
 */
class GetDowntimeToBroadcast implements IPIQuery{

    protected $query;
    protected $validParams;
    protected $em;
    private $helpers;
    private $downtimes;
    private $renderMultipleEndpoints;
    private $baseUrl;

    /** Constructor takes entity manager which is then used by the
     *  query builder
     *
     * @param EntityManager $em
     * @param string $baseUrl The base url string to prefix to urls generated in the query output.
     */
    public function __construct($em, $baseUrl = 'https://goc.egi.eu/portal'){
        $this->em = $em;
        $this->helpers=new Helpers();
        $this->renderMultipleEndpoints = true;
        $this->baseUrl = $baseUrl;
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
                'id'
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
            ->orderBy('d.startDate', 'DESC');
            //->orderBy('se.id', 'DESC');

        //Bind interval days
        $binds[] = array($bc,  $nowMinusIntervalDays);

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
    public function executeQuery(){
        $this->downtimes = $this->query->execute();
        return $this->downtimes;
    }




    /** Returns proprietary GocDB rendering of the downtime data
     *  in an XML String
     * @return String
     */
    public function getXML(){
        $helpers = $this->helpers;
        $query = $this->query;

        $xml = new \SimpleXMLElement ( "<results />" );

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
                $portalUrl = htmlspecialchars($this->baseUrl.'/index.php?Page_Type=Downtime&id=' . $downtime->getId());
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

    /** Not yet implemented, in future will return the downtime data in Glue2 XML string.
     * @return String
     */
    public function getGlue2XML(){
        throw new LogicException("Not implemented yet");
    }

    /** Not yet implemented, in future will return the downtime
     *  data in JSON format
     * @throws LogicException
     */
    public function getJSON(){
        throw new LogicException("Not implemented yet");
    }

    /**
     * Choose to render the multiple endpoints of a service (or not)
     * @param boolean $renderMultipleEndpoints
     */
    public function setRenderMultipleEndpoints($renderMultipleEndpoints){
        $this->renderMultipleEndpoints = $renderMultipleEndpoints;
    }



}