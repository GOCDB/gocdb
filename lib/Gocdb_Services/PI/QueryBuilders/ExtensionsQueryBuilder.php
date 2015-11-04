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
//require_once __DIR__ . '/ExtensionsParser.php';
require_once __DIR__ . '/ExtensionsParser2.php';
use Doctrine\ORM\EntityManager, Doctrine\ORM\QueryBuilder;

/** 
 * Appends sub-queries to the WHERE clause of a DQL query builder 
 * by parsing the value of the 'extensions' URL query parameter. 
 * <p>
 * For example, a {@link \Site} owns {@link \SiteProperty} proprties while a 
 * {@link \Service} owns {@link \ServiceProperty} properties - this allows 
 * clients to use the 'extensions' URL parameter to define a key=value 
 * type expression to filter the selected entities according to their properties. 
 * <p> 
 * The class takes a Doctrine {@link QueryBuilder} object which represents 
 * the current query that will be appended. This query may contain other 
 * bind parameters. This class also takes a raw 'extensions' query string that 
 * specifies the query expression {@see IExtensionsParser::parseQuery($extensionsQuery)}. 
 * <p>  
 * It parses the query using an {@link IExtensionsParser} and creates 
 * subqueries to limit the results. These subqueries are then appended to the original.
 * <p>
 * This query and its bind variables can then be fetched and used with getQB() and getBinds().
 * Important: This does not return the query or bind variables, they must be fetched. 
 *
 * @author James McCarthy
 * @author David Meredith (modifications)  
 */
class ExtensionsQueryBuilder{

    private $parsedExt = null;

    /* @var $qb \Doctrine\ORM\QueryBuilder */
    private $qb = null;

    /* @var $parmeterBindCounter int */
    private $parameterBindCounter = null;

    /* @var $em \Doctrine\ORM\EntityManager */
    private $em;
    private $type;
    private $propertyType;
    private $ltype;
    private $valuesToBind = array();

    /**
     * An ever increasing integer used to create unique table aliases used 
     * in the DQL query, eg: WHERE 'sp2.keyName' (uID is 2). 
     * Without this, repeated clauses may end up using the same identifier and 
     * break the query.   
     * @var integer 
     */
    private $tableAliasBindCounter = 0;

    private function setParsedExtensions($pExt) {
	$this->parsedExt = $pExt;
    }

    private function getParsedExtensions() {
	return $this->parsedExt;
    }

    private function setQB($qb) {
	$this->qb = $qb;
    }

    /**
     * Get the updated query builder with the newly added WHERE clauses.    
     * @return \Doctrine\ORM\QueryBuilder 
     */
    public function getQB() {
	return $this->qb;
    }

    /**
     * Get the current bind-parameter counter (an ever increasing integer). 
     * Incrementing this value creates unique positional bind parameters in the QueryBuilder.   
     * @return int 
     */
    public function getParameterBindCounter() {
	return $this->parameterBindCounter;
    }

    private function setParameterBindCounter($bc) {
	$this->parameterBindCounter = $bc;
    }

    /**
     * 2D array of 'parameterBindCounter-to-bindValue' mappings for the 
     * new WHERE clauses that are appended to the QueryBuilder.  
     * <p>
     * Each outer element stores a child array that has two elements; 
     * 1st element stores the parameterBindCounter (int) used for the positional bind param. 
     * 2nd element stores the bindValue (string).
     * 
     * @return array counter-to-value mapping array or empty array    
     */
    public function getValuesToBind() {
	return $this->valuesToBind;
    }

    private function setValues($valuesToBind) {
	$this->valuesToBind = $valuesToBind;
    }

    /**
     * Get current table-alias bind counter (an ever increasing integer). 
     * Used to create unique table alias names in the appended QueryBuilder 
     * (unique aliases are created by appending an int incremented from this value to the alias) 
     * @return int 
     */
    public function getTableAliasBindCounter() {
	return $this->tableAliasBindCounter;
    }

    private function setTableAliasBindCounter($uID) {
	$this->tableAliasBindCounter = $uID;
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
    private function setPropType($entityType) {
	switch ($entityType) {
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
     * The $extensionsQuery parameter represents an 'extensions' query. 
     * See {@see IExtensionsParser::parseQuery($extensionsQuery)} for details.  
     *  
     * @param string $extensionsQuery Value of the 'extensions' URL query parameter.   
     * @param \Doctrine\ORM\QueryBuilder $qb QueryBuilder that will be appended 
     *    with new where clause restrictions. 
     * @param \Doctrine\ORM\EntityManager $em
     * @param int $parameterBindCounter Current bind-parameter count 
     *        (this value is incremented to create new unique positional bind parameters)
     * @param string $entityType The name of the entity that owns the extensions, 
     *        one of 'Site' 'Service' or 'ServiceGroup' 
     * @param int $tableAliasBindCounter Current table-alias bind count, used to create unique table alias names 
     *        (aliases will be created by appending an int incremented from this value to the alias)
     * @throws \InvalidArgumentException if query can't be processed. 
     */
    public function __construct($extensionsQuery, \Doctrine\ORM\QueryBuilder $qb, 
	    \Doctrine\ORM\EntityManager $em, $parameterBindCounter, $entityType, 
	    $tableAliasBindCounter = 0) {
	//die($rawExt); 
	$this->setTableAliasBindCounter($tableAliasBindCounter);
	$this->em = $em;
	$this->parseExtensions($extensionsQuery);
	$this->setParameterBindCounter($parameterBindCounter);
	$this->setQB($qb);
	$this->setPropType($entityType);
	foreach ($this->getParsedExtensions() as $query) {
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
    private function parseExtensions($rawExt) {
//		$extensionsParser =  new ExtensionsParser();
//		$parsedLDAP = $extensionsParser->parseQuery($rawExt);
//		$this->setParsedExtensions($parsedLDAP);

	$extensionsParser = new ExtensionsParser2();
	$normalisedQuery = $extensionsParser->parseQuery($rawExt);
	$this->setParsedExtensions($normalisedQuery);
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
	$bc = $this->getParameterBindCounter();
	$uID = $this->getTableAliasBindCounter(); //Used to keep each subqueries table aliases unique
	
	//Create query builder and start of the subquery
	/* @var $sQ \Doctrine\ORM\QueryBuilder */
	$sQ = $this->em->createQueryBuilder();
	$sQ->select('s' . $uID . '.id')
		->from($entityT, 's' . $uID)
		->join('s' . $uID . '.' . $propT, 'sp' . $uID);

	// Split each given keyname and keyvalue pair on the first '=' char
	// If limit arg is set and positive, the returned array will contain a 
	// maximum of limit elements with the last element containing the rest of string. 
	// namevalue[0] = keyName
	// namevalue[1] = keyValue 
	$namevalue = explode('=', $query[1], 2);

	if (trim($namevalue[1]) == null) { //if no value or no value after trim do a wildcard search
	    $namevalue[1] = '%%'; //Set value as database wildcard symbol	                        
	}
	/*
	 * Create the where clause of the subquery, e.g: 
	 * WHERE sp0.keyName = ?0 AND sp0.keyValue = ?1 
	 * ...and append, e.g: 
	 * SELECT s0.id FROM Site s0 INNER JOIN s0.siteProperties sp0 WHERE sp0.keyName = ?0 AND sp0.keyValue = ?1 
	 */
	// This could be simplified further - no need to andX the property 
        // value if the query doesn't need to match the property value..
        $sQ->where($sQ->expr()->andX(
		$sQ->expr()->eq('sp' . $uID . '.keyName', '?' . ++$bc), 
		$namevalue[1] == "%%" ? 
		$sQ->expr()->like('sp' . $uID . '.keyValue', '?' . ++$bc) : 
	        $sQ->expr()->eq('sp' . $uID . '.keyValue', '?' . ++$bc)
	));

	// Bind keyName
	$valuesToBind[] = array(($bc - 1), $namevalue[0]);   
	// Bind keyValue
	$valuesToBind[] = array(($bc), $namevalue[1]);   
	
	//Update core variables
        $this->setTableAliasBindCounter(++$uID);
	$this->setParameterBindCounter($bc);
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
        /* @var $qb \Doctrine\ORM\QueryBuilder */
	$qb = $this->getQB(); 
       
        // QueryBuilder:  
        // orWhere() forms a logical DISCJUNCTION with all 
        // previously specfied restrictions while andWhere() forms a logical 
        // CONJUNCTION with any previously specified restrictions. 
        // 
        // Therefore, the order of AND/OR in URL query string 
        // is significant (we iterate subqueries from left to right, each time 
        // adding the new conjunction/disjunction to the previous restrictions. 
        
        // To support nesting parethesis around different subqueries 
        // to create complex where clauses, e.g.  
        // 'ConditionA OR (ConditionB AND ConditionC)'  versus 
        // '(ConditionA OR ConditionB) AND ConditionC'
        // will require something like a 'whereParamWrap()' 
        // method as described in the following post: 
        // http://criticalpursuits.com/solving-the-doctrine-parenthesis-problem/
        // Of course, this will also require a change in the syntax of the 
        // 'expressions' query string to support nesting parethesis.  
                
	switch ($operator) {
	    case 'OR': //OR
		$qb->orWhere($qb->expr()->in($this->ltype, $sQ->getDQL()));
		break;
	    case 'AND': //AND        	           
		$qb->andWhere($qb->expr()->in($this->ltype, $sQ->getDQL()));
		break;
	    case 'NOT': //NOT
		$qb->andWhere($qb->expr()->notIn($this->ltype, $sQ->getDQL()));
		break;
	}

	//finally replace original query with updated query ready for fetching by the calling class
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
