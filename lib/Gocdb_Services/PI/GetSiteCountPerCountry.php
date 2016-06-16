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
require_once __DIR__ . '/../OwnedEntity.php';



/**
 * Returns and XML document showing which sites within the EGI scope are production and certified grouped by country.
 *
 * @author James McCarthy
 * @author David Meredith
 */
class GetSiteCountPerCountry implements IPIQuery{

    protected $query;
    protected $validParams;
    protected $em;
    private $helpers;
    private $countries;
    private $sites;

    /** Constructor takes entity manager which is then used by the
     *  query builder
     *
     * @param EntityManager $em
     */
    public function __construct($em){
        $this->em = $em;
        $this->helpers=new Helpers();
    }

    /** Validates parameters against array of pre-defined valid terms
     *  for this PI type
     * @param array $parameters
     */
    public function validateParameters($parameters){

        // Define supported parameters and validate given params (die if an unsupported param is given)
        $supportedQueryParams = array (
                'production_status', 'certification_status', 'scope'
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

        //Main query
        $qb	->select('COUNT(c.name) as cCount', 'c.name')
            ->from('Site', 's')
            ->leftJoin('s.scopes', 'sc')
            ->join('s.ngi', 'n')
            ->join('s.country', 'c')
            ->join('s.certificationStatus', 'cs')
            ->join('s.infrastructure', 'i')
            ->groupBy('c.name')
            ->orderBy('c.name');


        //If a scope was specified attach the sub query to query by EGI scope
        if(isset($parameters['scope'])) {

            /**
             * We are using a simplified scope query here that only supports a single scope
             * instead of using the scope-query builder. When using the scope query builder (SQB)
             * the sub query created by the SQB will count sites with more than one scope and this
             * can cause an error in the results. To combat this you can put a distinct within the
             * select clause: "COUNT (DISTINCT c.name)" and this will fix that issue. However we
             * still are not using the SQB as this supports comma seperated lists and scope matching
             * which is not supported by the NGI scope sub query. In conclusion this PI query supports
             * only a single scope.
             */

            $sQ = $this->em->createQueryBuilder();
            $sQ ->select('n2')
                ->from('NGI', 'n2')
                ->leftJoin('n2.scopes', 'sqsc2')
                ->where($sQ->expr()->eq('sqsc2.name', '?'.++$bc));


            $qb ->andWhere($qb->expr()->eq('sc.name', '?'.$bc))
                ->andWhere($qb->expr()->in('n', $sQ->getDQL()));


            $binds[] = array($bc, $parameters['scope']);
        }


        /*Pass parameters to the ParameterBuilder and allow it to add relevant where clauses
         * based on set parameters. */
        $parameterBuilder = new ParameterBuilder($parameters, $qb, $this->em, $bc);
        //Get the result of the scope builder
        $qb = $parameterBuilder->getQB();
        $bc = $parameterBuilder->getBindCount();
        //Get the binds and store them in the local bind array - only runs if the returned value is an array
        foreach((array)$parameterBuilder->getBinds() as $bind){
            $binds[] = $bind;
        }

        //Bind all variables
        $qb = $this->helpers->bindValuesToQuery($binds, $qb);


        //echo $qb->getDql(); //for testing




        $query[0] = $qb->getQuery();

        $qb = $this->em->createQueryBuilder();

        $qb	->select('c.name')
            ->from('Country', 'c')
            ->orderBy('c.name');

        $query[1] = $qb->getQuery();
        $this->query = $query;
        return $this->query;
    }

    /**
     * To display countries with 0 count we execute two queries here but only sites
     * which holds the data of all countries with a count > 0.
     * Executes the query that has been built and stores the returned data
     * so it can later be used to create XML, Glue2 XML or JSON.
     */
    public function executeQuery(){
        //Execute the two queries
        $this->sites = $this->query[0]->execute();
        $this->countries = $this->query[1]->execute();
        return $this->sites;
    }


    /** Returns proprietary GocDB rendering of data
     *  in an XML String
     * @return String
     */
    public function getXML(){
        $helpers = $this->helpers;

        //Get the two result sets
        $sites = $this->sites;
        $countries = $this->countries;

        //Create an array with the names as the key
        foreach($countries as $country){
            $output[$country['name']] = 0;
        }

        //For each site with a count store the count
        foreach ( $sites as $site ) {
            $output[$site['name']] = $site['cCount'];
        }

        //Render the XML
        $xml = new \SimpleXMLElement ('<results />');
        foreach ($output as $country => $count) {
            $xmlSite = $xml->addChild ('SITE');
            $helpers->addIfNotEmpty ($xmlSite, 'COUNTRY', $country);
            $xmlSite->addChild('COUNT', $count);
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

    /** Returns the user data in Glue2 XML string.
     *
     * @return String
     */
    public function getGlue2XML(){
        throw new LogicException("Not implemented yet");
    }

    /** Not yet implemented, in future will return the user
     *  data in JSON format
     * @throws LogicException
     */
    public function getJSON(){
        throw new LogicException("Not implemented yet");
    }
}
