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
 * Return an XML document that encodes the service groups selected from the DB.
 * Optionally provide an associative array of query parameters with values used to restrict the results.
 * Only known parameters are honoured while unknown params produce error doc.
 * Parmeter array keys include:
 * <pre>
 * 	'service_group_name',
 *	'scope',
 *	'scope_match'
 * </pre>
 *
 * @author James McCarthy
 * @author David Meredith
 */
class GetServiceGroupRole implements IPIQuery{

    protected $query;
    protected $validParams;
    protected $em;
    private $helpers;
    private $sgs;
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
        $this->baseUrl = $baseUrl;
    }

    /** Validates parameters against array of pre-defined valid terms
     *  for this PI type
     * @param array $parameters
     */
    public function validateParameters($parameters){

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array (
                'service_group_name',
                'scope',
                'scope_match'
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

        $qb = $this->em->createQueryBuilder();

        //Initialize base query
        $qb	->select('sg', 'r', 'u', 'rt')
        ->from('ServiceGroup', 'sg')
        ->leftJoin('sg.roles', 'r')
        ->leftJoin('r.user', 'u')
        ->leftJoin('r.roleType', 'rt')
        ->orderBy('sg.id', 'ASC');


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
                'ServiceGroup',
                'sg'
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
    public function executeQuery(){
        $this->sgs = $this->query->execute();
        return $this->sgs;
    }


    /** Returns proprietary GocDB rendering of the service group data
     *  in an XML String
     * @return String
     */
    public function getXML(){
        $helpers = $this->helpers;

        $xml = new \SimpleXMLElement ( "<results />" );

        $sgs = $this->sgs;

        $xml = new \SimpleXMLElement ( "<results />" );
        foreach ( $sgs as $sg ) {
            $xmlSg = $xml->addChild ( 'SERVICE_GROUP' );
            $xmlSg->addAttribute ( "PRIMARY_KEY", $sg->getId () . "G0" );
            $xmlSg->addChild ( 'NAME', $sg->getName () );
            $xmlSg->addChild ( 'DESCRIPTION', htmlspecialchars ( $sg->getDescription () ) );
            $mon = ($sg->getMonitored ()) ? 'Y' : 'N';
            $xmlSg->addChild ( 'MONITORED', $mon );
            $xmlSg->addChild ( 'CONTACT_EMAIL', $sg->getEmail () );
            $url = $this->baseUrl.'/index.php?Page_Type=Service_Group&id=' . $sg->getId ();
            $url = htmlspecialchars ( $url );
            $xmlSg->addChild ( 'GOCDB_PORTAL_URL', $url );
            foreach ( $sg->getRoles () as $role ) {
                $user = $role->getUser ();
                $xmlUser = $xmlSg->addChild ( 'USER' );
                $xmlUser->addChild ( 'FORENAME', $user->getForename () );
                $xmlUser->addChild ( 'SURNAME', $user->getSurname () );
                $xmlUser->addChild ( 'CERTDN', $user->getCertificateDn () );
                $url = $this->baseUrl.'/index.php?Page_Type=User&id=' . $user->getId ();
                $url = htmlspecialchars ( $url );
                $xmlUser->addChild ( 'GOCDB_PORTAL_URL', $url );
                $xmlUser->addChild ( 'ROLE', $role->getRoleType ()->getName () );
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

    /** Returns the service group data in Glue2 XML string.
     *
     * @return String
     */
    public function getGlue2XML(){
        $query = $this->query;
        throw new LogicException("Not implemented yet");
    }

    /** Not yet implemented, in future will return the service group
     *  data in JSON format
     * @throws LogicException
     */
    public function getJSON(){
        $query = $this->query;
        throw new LogicException("Not implemented yet");
    }
}