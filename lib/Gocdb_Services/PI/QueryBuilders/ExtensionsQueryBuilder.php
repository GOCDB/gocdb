<?php

namespace org\gocdb\services;
/*
 * Copyright © 2011 STFC Licensed under the Apache License, 
 * Version 2.0 (the "License"); you may not use this file except in compliance 
 * with the License. You may obtain a copy of the License at 
 * http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable law 
 * or agreed to in writing, software distributed under the License is distributed 
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either 
 * express or implied. See the License for the specific language governing 
 * permissions and limitations under the License.
*/
require_once __DIR__ . '/ExtensionsParser.php';
use Doctrine\ORM\EntityManager, Doctrine\ORM\QueryBuilder;

/** 
 * This class is used to apply additional filtering to a query according to the
 * extension properties an entity owns. For example {@link \Site} owns  
 * {@link \SiteProperty} while {@link \Service} owns {@link \ServiceProperty}. 
 * The class can therefore be used to narrow the returned entities based on these 
 * key/value pair extension properties.  
 * <p> 
 * The class takes a Doctrine {@link QueryBuilder} object which represents 
 * the current query that will be appended/updated. This query may contain other 
 * bind parameters. This class also takes a raw LDAP style query string that 
 * specifies the required key/value pair props the extension properties must support. 
 * <p>  
 * It parses the query using {@link ExtensionsParser} and creates a sub-query to limit 
 * the results. This subquery is then appended to the original.
 * This query and its bind variables can then be fetched and used with getQB() and getBinds().
 * Important: This does not return the query or bind variables, they must be fetched. 
 *
 * @author James McCarthy
 * @author David Meredith 
 */
class ExtensionsQueryBuilder{

	private $parsedExt = null;	
	private $qb = null;
	private $bc = null;
	private $em;
	private $type;
	private $propertyType;
	private $ltype;	
	private $valuesToBind;
	/** This is used as the number to value to bind to each individual 
	 * sub statement, eg: WHERE 'sp2.keyName'. Without this repeated clauses
	 * may end up using the same identifier and break the query. This variable
	 * is used to keep a global incrementing variable which will ensure each sub
	 * queries identifiers are unique.  
	 * @var Unique ID
	 */
	private $uID=0;
	
	private function setParsedExtensions($pExt){
		$this->parsedExt = $pExt;
	}
	
	private function getParsedExtensions(){
	    return $this->parsedExt;
	}
	
	private function setQB($qb){
		$this->qb = $qb;
	}
	
	public function getQB(){
		return $this->qb;
	}
	
	public function getBindCount(){
	    return $this->bc;
	}
	
	private function setBindCount($bc){
		$this->bc= $bc;
	}

	public function getValuesToBind(){
	    return $this->valuesToBind;
	}
	
	private function setValues($valuesToBind){
	    $this->valuesToBind = $valuesToBind;
	}
	
	public function getUniqueID(){
	    return $this->uID;
	}
	
	private function setUniqueID($uID){
	    $this->uID = $uID;
	}
	
	
	/** 
	 * Sets the two table names needed to get properties from
	 * can easily be extended for any other entities.
	 * Type is the main entity whos properties we are searching for, eg; 'Site'
	 * PropertyType is the corrosponding table where the properties are stored eg; 'siteProperties'
	 * Ltype is the single letter designator used in the main select statment  eg; 's'
	 * which needs to be consistent through the statement
	 * 
	 * @param String $entityType
	 */
    private function setPropType($entityType){
        switch($entityType){
        	case 'Site':
        	    $this->type = 'Site';
        	    $this->propertyType = 'siteProperties';
        	    $this->ltype = 's';
        	    break;
        	case 'Service':
        	    $this->type = 'Service';
        	    $this->propertyType = 'serviceProperties';
        	    $this->ltype = 'se';
        	    break;
    	    case 'ServiceGroup':
    	        $this->type = 'ServiceGroup';
    	        $this->propertyType = 'serviceGroupProperties';
    	        $this->ltype = 'sg';
    	        break;        	            	    
        }
    }
	
	/** 
	 * Construct a new instance, initialize variables, then stores the 
     * new query ready for fetching by calling code. 
     * <p>
     * The rawExt parameter represents an LDAP style query.  Sample queries shown below: 
     * <ul>
     *   <li>Simple: <code>keyname=keyvalue</code></li>
     *   <li>And together keys and values (all extension properties must 
     *   exist with the specified values): 
     *   <code>AND(keyname1=keyvalueX)(keyname1=keyvalueY)(keyname3=keyvalueZ)</code>
     *   </li>
     *   <li>Single key can have value1 OR value2: 
     *   <code>OR(keyname=keyvalueX)(keyname=keyvalueY)</code>
     *   </li>
     *   <li>Wildcard, keyname can have any value: <code>keyname=*</code></li>
     * </ul>
     *  
     * @param string $rawExt See above 
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \Doctrine\ORM\EntityManager $em
     * @param int $bc Current bind count, used to create unique bind param names 
     *        (bind params will be created by appending an int incremented from this value)
     * @param string $entityType The name of the entity that owns the extensions, 
     *        e.g. 'Site' or 'Service'
     */
    public function __construct($rawExt, \Doctrine\ORM\QueryBuilder 
                                $qb, \Doctrine\ORM\EntityManager $em, $bc, $entityType, $uID=0){

        $this->setUniqueID($uID);                                    
        $this->em=$em;
		$this->parseExtensions($rawExt);
		$this->setBindCount($bc); 
		$this->setQB($qb);
		$this->setPropType($entityType);
		foreach($this->getParsedExtensions() as $query){
		  $this->createSubQuery($this->type, $this->propertyType, $query);
		}						
	}
	
	/** 
	 * This method takes the raw extensions from the extensions parameter
	 * and uses the ExtensionsParser() class to convert this into a normalized
	 * array of queries and their opereators.
	 * 
	 * @param unknown $rawExt
	 */ 
	private function parseExtensions($rawExt){
		$ExtensionsParser =  new ExtensionsParser();
		$parsedLDAP = $ExtensionsParser->parseQuery($rawExt);
		$this->setParsedExtensions($parsedLDAP);
	}
    
           
    /**
     * Build a subquery that will be added as a new restriction to the  
     * main class-query in the WHERE clause.   
     * The $query array has the form: 
     * <tt>
     * Array ( 
     * [0] => OR 
     * [1] => VO2=baz
     * )
     * <tt>
     * 
     * @param string $entityT The name of the entity that owns the extensions, 
     *        e.g. 'Site' or 'Service'
     * @param type $propT The name of the extension properties in the main query
     *       e.g. 'SiteProperties' or 'ServiceProperties'  
     * @param array $query A two element array, 
     *   [0]=>predicateString, eg 'AND', 'OR' or 'NOT', [1]=>expressionString, eg 'key=value'
     */
	private function createSubQuery($entityT, $propT, $query){
	    //Get core variables    	    
	    $valuesToBind = $this->getValuesToBind();
	    $bc = $this->getBindCount();
	    $uID = $this->getUniqueID(); //Use the unique ID to keep each subqueries identifiers unique

	    
	    //Create query builder and start of the subquery
	    $sQ = $this->em->createQueryBuilder();
	    $sQ ->select('s'.$uID.'.id')
    	    ->from($entityT, 's'.$uID)
	        ->join('s'.$uID.'.'.$propT, 'sp'.$uID);
        
	        // Split each given keyname and keyvalue pair on = char
	        $namevalue = explode ( '=', $query[1]);
            
            if(trim($namevalue[1]) == null){ //if no value or no value after trim do a wildcard search
                $namevalue[1]='%%'; //Set value as database wildcard symbol	                        
            }
            /*
             * Create the where clause of the subquery, e.g: 
             * WHERE sp0.keyName = ?0 AND sp0.keyValue = ?1 
             * ...and append, e.g: 
             * SELECT s0.id FROM Site s0 INNER JOIN s0.siteProperties sp0 WHERE sp0.keyName = ?0 AND sp0.keyValue = ?1 
             */
            // This could be simplified further - no need to andX the property 
            // value if the query doesn't need to match the property value..
            $sQ ->where($sQ->expr()->andX(
            	            $sQ->expr()->eq('sp'.$uID.'.keyName', '?'.++$bc),
                            $namevalue[1] == "%%" 
            	            ? $sQ->expr()->like('sp'.$uID.'.keyValue', '?'.++$bc)
                            : $sQ->expr()->eq('sp'.$uID.'.keyValue', '?'.++$bc) 
                            ));
            
            $valuesToBind[] = array(($bc-1), $namevalue[0]);   //Bind for keyName
            $valuesToBind[] = array(($bc), $namevalue[1]);   //Bind for keyValue

            //Update core variables
            $this->setUniqueID(++$uID);
	        $this->setBindCount($bc);
	        $this->setValues($valuesToBind);
	    //Add this sub query to the main query
	    $this->addSubQueryToMainQuery($sQ, $query[0]);
	}

	
	/**
	 * Add the given subquery as a new clause in the main query's  
     * WHERE clause using and an AND or OR operator. 
     * <p>
     * Important: The method *APPENDS* the suquery as a new restriction in the 
     * WHERE clause, forming a logical AND/OR conjunction with any 
     * **PREVIOUSLY** specified restrictions in the WHERE clause.
	 * 
	 * @param QueryBuilder $sQ
     * @param String $operator AND, OR or NOT 
	 */
	private function addSubQueryToMainQuery($sQ, $operator){
	    //Get the query that was passed at initialization
	    $qb = $this->getQB();
       
        // QueryBuilder orWhere() + addWhere() *APPENDS* the specified restrictions to the 
        // query results, forming a logical AND/OR conjunction with any **PREVIOUSLY** 
        // specified restrictions! Therefore, the order of AND/OR in URL query string 
        // is significant (we iterate subqueries from left to right, each time 
        // adding the new conjunction to the previous restrictions. 
                
	    switch ($operator) {
        	case 'OR': //OR
        	    $qb ->orWhere($qb->expr()->in($this->ltype, $sQ->getDQL()));
        	    break;
        	case 'AND': //AND        	           
        	    $qb ->andWhere($qb->expr()->in($this->ltype, $sQ->getDQL()));	        	    
        	    break;	   
    	    case 'NOT': //NOT
    	        $qb ->andWhere($qb->expr()->notIn($this->ltype, $sQ->getDQL()));        	        	
    	        break;	        	    
	    }	       	

	    /*finally replace original query with updated query ready for fetching
	     *by the calling class*/
	    $this->setQB($qb);
	}		
}  


/**
 * Example Queries:
 * 
 *  ?method=get_site&extensions=AND(VO=Alice)&scope=
 *   
 *  SELECT s,
 *          sc,
 *          sp
 *   FROM Site s
 *   LEFT JOIN s.siteProperties sp
 *   LEFT JOIN s.scopes sc
 *   LEFT JOIN s.ngi n
 *   LEFT JOIN s.country c
 *   LEFT JOIN s.certificationStatus cs
 *   LEFT JOIN s.infrastructure i
 *   WHERE s IN
 *       (SELECT s0.id
 *        FROM Site s0
 *        INNER JOIN s0.siteProperties sp0
 *        WHERE sp0.keyName = ?0
 *          AND sp0.keyValue = ?1)
 *   ORDER BY s.shortName ASC
 *  
 *  
 *  ?method=get_site&extensions=OR(VO=Alice)(VO=LHCB)&scope=
 *  
 *  SELECT s,
 *          sc,
 *          sp
 *   FROM Site s
 *   LEFT JOIN s.siteProperties sp
 *   LEFT JOIN s.scopes sc
 *   LEFT JOIN s.ngi n
 *   LEFT JOIN s.country c
 *   LEFT JOIN s.certificationStatus cs
 *   LEFT JOIN s.infrastructure i
 *   WHERE (s IN
 *            (SELECT s0.id
 *             FROM Site s0
 *             INNER JOIN s0.siteProperties sp0
 *             WHERE sp0.keyName = ?0
 *               AND sp0.keyValue = ?1))
 *     OR (s IN
 *           (SELECT s1.id
 *            FROM Site s1
 *            INNER JOIN s1.siteProperties sp1
 *            WHERE sp1.keyName = ?2
 *              AND sp1.keyValue = ?3))
 *   ORDER BY s.shortName ASC
 *
 *   
 *  ?method=get_site&extensions=AND(VO=Alice)OR(VO=Atlas)NOT(VO=LHCB)&scope=
 *  
 *  SELECT s,
 *          sc,
 *          sp
 *   FROM Site s
 *   LEFT JOIN s.siteProperties sp
 *   LEFT JOIN s.scopes sc
 *   LEFT JOIN s.ngi n
 *   LEFT JOIN s.country c
 *   LEFT JOIN s.certificationStatus cs
 *   LEFT JOIN s.infrastructure i
 *   WHERE ((s IN
 *             (SELECT s0.id
 *              FROM Site s0
 *              INNER JOIN s0.siteProperties sp0
 *              WHERE sp0.keyName = ?0
 *                AND sp0.keyValue = ?1))
 *          OR (s IN
 *                (SELECT s1.id
 *                 FROM Site s1
 *                 INNER JOIN s1.siteProperties sp1
 *                 WHERE sp1.keyName = ?2
 *                   AND sp1.keyValue = ?3)))
 *     AND (s NOT IN
 *            (SELECT s2.id
 *             FROM Site s2
 *             INNER JOIN s2.siteProperties sp2
 *             WHERE sp2.keyName = ?4
 *               AND sp2.keyValue = ?5))
 *   ORDER BY s.shortName ASC
 *   
 */   
