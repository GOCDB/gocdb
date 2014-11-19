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
 * Return an XML document that encodes the project contacts selected from the DB.
 * Supported params: 
 * <pre>'project'</pre> 
 *  
 * @author James McCarthy
 * @author David Meredith 
 */
class GetProjectContacts implements IPIQuery{
	
	protected $query;
	protected $validParams;
	protected $em;
	private $helpers;
	private $projects;
	
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
				'project'
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
		$qb = $this->em->createQueryBuilder();
		$qb->select('p')
		   ->from('project', 'p');
		
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
		//Get the dql query from the Query Builder object
		$query = $qb->getQuery();
		
		$this->query = $query;	
	}	
	
	/**
	 * Executes the query that has been built and stores the returned data
	 * so it can later be used to create XML, Glue2 XML or JSON.
	 */
	public function executeQuery(){
	    $this->projects = $this->query->execute();
	    return $this->projects;
	}
	
	/** Returns proprietary GocDB rendering of the sites data 
	 *  in an XML String
	 * @return String
	 */
	public function getXML(){
		$helpers = $this->helpers;
		$query = $this->query;
		
		$projects = $this->projects;

		$xml = new \SimpleXMLElement ( "<results />" );

	    foreach($projects as $project){			
			$xmlProjUser = $xml->addChild('Project');
			$xmlProjUser->addAttribute('NAME', $project->getName());
			
			foreach($project->getRoles() as $role){
			    if($role->getStatus() == \RoleStatus::GRANTED &&
			    $role->getRoleType()->getName() != \RoleTypeName::CIC_STAFF){
			
						//$rtype = $role->getRoleType()->getName();
						$user = $role->getUser();
						$xmlContact = $xmlProjUser->addChild('CONTACT');
						$xmlContact->addAttribute('USER_ID', $user->getId() . "G0");
						$xmlContact->addAttribute('PRIMARY_KEY', $user->getId() . "G0");
						$xmlContact->addChild('FORENAME', $user->getForename());
						$xmlContact->addChild('SURNAME', $user->getSurname());
						$xmlContact->addChild('TITLE', $user->getTitle());
						$xmlContact->addChild('EMAIL', $user->getEmail());
						$xmlContact->addChild('TEL', $user->getTelephone());
						$xmlContact->addChild ( 'WORKING_HOURS_START', $user->getWorkingHoursStart () );
						$xmlContact->addChild ( 'WORKING_HOURS_END', $user->getWorkingHoursEnd () );
						$xmlContact->addChild('CERTDN', $user->getCertificateDn());
						 
						$roleName = $role->getRoleType()->getName();
						$xmlContact->addChild('ROLE_NAME', $roleName);
					
			    }
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
	
	/** Returns the project contact data in Glue2 XML string.
	 * 
	 * @return String
	 */
	public function getGlue2XML(){	
		throw new LogicException("Not implemented yet");
	}
	
	/** Not yet implemented, in future will return the project contact  
	 *  data in JSON format
	 * @throws LogicException
	 */
	public function getJSON(){		
		throw new LogicException("Not implemented yet");
	}
}