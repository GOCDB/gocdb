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
 * Return an XML document that encodes Site CertificationStatusLog entities.
 * Optionally provide an associative array of query parameters with values 
 * used to restrict the results. Only known parameters are honoured while 
 * unknown produce and error doc. Parmeter array keys include:
 * <pre>
 * 'site', 'startdate', 'enddate' 
 * </pre>
 * 
 * @author James McCarthy
 * @author David Meredith
 */
class GetCertStatusChanges implements IPIQuery{
	
	protected $query;
	protected $validParams;
	protected $em;
	private $helpers;
	private $allLogs;
	
	/** Constructor takes entity manager that will be used by the
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
				'site',
				'startdate',
				'enddate' 
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
		$qb	->select('log','s')
    		->from('CertificationStatusLog', 'log')		
    		->Join('log.parentSite', 's')
    		->Join('s.certificationStatus', 'cs')    		
    		->leftJoin('s.scopes', 'sc')    	
    		->Join('s.infrastructure', 'i')
		    ->orderBy('log.id', 'ASC');
		

		if (isset ( $parameters ['enddate'] )) {
		    $endDate = new \DateTime ( $parameters ['enddate'] );
		} else {
		    $endDate = null;
		}
		$qb->andWhere($qb->expr()->orX($qb->expr()->isNull('?'.++$bc), $qb->expr()->gt('log.addedDate', '?'.$bc)));
		$binds[] = array($bc, $endDate);
				
		if (isset ( $parameters ['startdate'] )) {
		    $startDate = new \DateTime ( $parameters ['startdate'] );
		} else {
		    $startDate = null;
		}
		$qb->andWhere($qb->expr()->orX($qb->expr()->isNull('?'.++$bc), $qb->expr()->lt('log.addedDate', '?'.$bc)));
		$binds[] = array($bc, $startDate);
		
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
	    //echo $dql;   
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
	    $this->allLogs = $this->query->execute();
	    return $this->allLogs;
	}
	
	/** 
	 * Returns proprietary GocDB rendering of the certification status change data 
	 * in an XML String
	 *  
	 * @return String
	 */
	public function getXML(){
		$helpers = $this->helpers;
		
		$allLogs = $this->allLogs;
		
		// Ensure all dates are in UTC
		date_default_timezone_set ( "UTC" );
		
		$xml = new \SimpleXMLElement ( "<results />" );
		foreach ( $allLogs as $log ) {
			$xmlLog = $xml->addChild ( 'result' );
			if ($log->getAddedDate () != null) {
				// e.g. <TIME>02-JUL-2013 12.51.58</TIME>
				$xmlLog->addChild ( 'TIME', $log->getAddedDate ()->format ( 'd-M-Y H.i.s' ) );
				// e.g. <UNIX_TIME>1372769518</UNIX_TIME>
				$xmlLog->addChild ( 'UNIX_TIME', $log->getAddedDate ()->format ( 'U' ) );
			}
			$xmlLog->addChild ( 'SITE', $log->getParentSite ()->getShortName () );
			$xmlLog->addChild ( 'OLD_STATUS', $log->getOldStatus () );
			$xmlLog->addChild ( 'NEW_STATUS', $log->getNewStatus () );
			$xmlLog->addChild ( 'CHANGED_BY', $log->getAddedBy () );
			$xmlLog->addChild ( 'COMMENT', xssafe($log->getReason()));
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
	
	/** Returns the certification status change data in Glue2 XML string.
	 * 
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