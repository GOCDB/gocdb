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
 * Return an XML document that encodes Site Certification status dates.
 * Optionally provide an associative array of query parameters with values
 * used to restrict the results. Only known parameters are honoured while
 * unknown produce and error doc. Parmeter array keys include:
 * <pre>
 * 'roc', 'certification_status'
 * </pre>
 * 
 * @author James McCarthy
 * @author David Meredith
 */
class GetCertStatusDate implements IPIQuery{
	
	protected $query;
	protected $validParams;
	protected $em;
	private $helpers;
	private $allSites;
	
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
				'certification_status',
				'roc' 
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
		$qb	->select('s')
    		->from('Site', 's')		
    		->join('s.certificationStatus', 'cs')
    		->join('s.ngi', 'n')
    		->leftJoin('s.scopes', 'sc')
    		->join('s.infrastructure', 'i')
    		->andWhere($qb->expr()->like('i.name', '?'.++$bc))
		    ->orderBy('s.id', 'ASC');
		
    		
    		$binds[] = array($bc, 'Production');
    		
    		
    		
	
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
			
		//Bind all variables
		$qb = $this->helpers->bindValuesToQuery($binds, $qb);
	
		$dql = $qb->getDql(); //for testing
	
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
	    $this->allSites = $this->query->execute();
	    return $this->allSites;	     
	}
	
	/** 
	 * Returns proprietary GocDB rendering of the downtime data 
	 *  in an XML String
	 *  
	 * @return String
	 */
	public function getXML(){
		$helpers = $this->helpers;
		
		$allSites = $this->allSites;
		
		// Ensure all dates are in UTC
		date_default_timezone_set ( "UTC" );
		
		$xml = new \SimpleXMLElement ( "<results />" );
		foreach ( $allSites as $site ) {

    			$xmlSite = $xml->addChild ( 'site' );
    			$xmlSite->addChild ( 'name', $site->getShortName () );
    			$xmlSite->addChild ( 'cert_status', $site->getCertificationStatus ()->getName () );
    			//Some V4 data was imported without dates. This stops those sites being displayed
    			if ($site->getCertificationStatusChangeDate () != null) {            		
    			// e.g. <cert_date>29-JAN-13 05.13.08 PM</cert_date>
    			$xmlSite->addChild ( 'cert_date', $site->getCertificationStatusChangeDate ()->format ( 'd-M-y H.i.s A' ) );
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
	
	/** Returns the site data in Glue2 XML string. 
	 * @return String
	 */
	public function getGlue2XML(){	
		throw new LogicException("Not implemented yet");
	}
	
	/** Not yet implemented, in future will return the sites 
	 *  data in JSON format
	 * @throws LogicException
	 */
	public function getJSON(){		
		throw new LogicException("Not implemented yet");
	}
}