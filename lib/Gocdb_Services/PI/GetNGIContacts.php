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
 * Return an XML document that encodes the NGIs selected from the DB.
 * Optionally provide an associative array of query parameters with values to restrict the results.
 * Only known parameters are honoured while unknown params produce an error doc. 
 * Parmeter array keys include:
 * <pre>
 * 'roc', 'roletype'  
 * </pre>
 * 
 * @author James McCarthy
 * @author David Meredith 
 */
class GetNGIContacts implements IPIQuery {
    
    protected $query;
    protected $validParams;
    protected $em;
    private $helpers;
    private $roleT;
    private $ngis;
    private $baseUrl; 
    
    /** Constructor takes entity manager to be passed to method using the
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
                'roc',
                'roletype',
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
        $qb	->select('n')
        ->from('NGI', 'n')
        ->orderBy('n.id', 'ASC');
    
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
    $this->ngis = $this->query->execute();
    return $this->ngis;
    }
    
    
    /** Returns proprietary GocDB rendering of the NGI contact data 
     *  in an XML String
     * @return String
     */
    public function getXML(){
        $helpers = $this->helpers;

        $ngis = $this->ngis;

        $xml = new \SimpleXMLElement ( "<results />" );
        
       foreach($ngis as $ngi) {
            $xmlNgi = $xml->addChild('ROC');
            $xmlNgi->addAttribute('ROC_NAME', $ngi->getName());
            $xmlNgi->addChild('ROCNAME', $ngi->getName());
            $xmlNgi->addChild('MAIL_CONTACT', $ngi->getEmail());
            $portalUrl = $this->baseUrl.'/index.php?Page_Type=NGI&id=' . $ngi->getId ();
            $portalUrl = htmlspecialchars ( $portalUrl );
            $helpers->addIfNotEmpty ( $xmlNgi, 'GOCDB_PORTAL_URL', $portalUrl );
            foreach($ngi->getRoles() as $role) {
                if ($role->getStatus() == "STATUS_GRANTED") {   //Only show users who are granted the role, not pending				
                    $rtype = $role->getRoleType()->getName(); 
                    if($this->roleT == '%%' || $rtype == $this->roleT) {
                        $user = $role->getUser();
                        $xmlContact = $xmlNgi->addChild('CONTACT');
                        $xmlContact->addAttribute('USER_ID', $user->getId() . "G0");
                        $xmlContact->addAttribute('PRIMARY_KEY', $user->getId() . "G0");
                        $xmlContact->addChild('FORENAME', $user->getForename());
                        $xmlContact->addChild('SURNAME', $user->getSurname());
                        $xmlContact->addChild('TITLE', $user->getTitle());
                        $xmlContact->addChild('EMAIL', $user->getEmail());
                        $xmlContact->addChild('TEL', $user->getTelephone());
                        $xmlContact->addChild('CERTDN', $user->getCertificateDn());
                        
                        $roleName = $role->getRoleType()->getName();  
                        $xmlContact->addChild('ROLE_NAME', $roleName);
                    }
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
    
    /** Returns the NGI contact data in Glue2 XML string.
     * 
     * @return String
     */
    public function getGlue2XML(){
        throw new LogicException("Not implemented yet");	     
    }
    
    /** Not yet implemented, in future will return the NGI contact 
     *  data in JSON format
     * @throws LogicException
     */
    public function getJSON(){
        $query = $this->query;		
        throw new LogicException("Not implemented yet");
    }
}