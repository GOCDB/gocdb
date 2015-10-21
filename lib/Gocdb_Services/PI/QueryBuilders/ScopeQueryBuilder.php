<?php
namespace org\gocdb\services;
/*
 * Copyright 2011 STFC Licensed under the Apache License, Version 2.0 (the "License"); 
 * you may not use this file except in compliance with the License. 
 * You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 
 * Unless required by applicable law or agreed to in writing, software distributed under 
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing permissions
 * and limitations under the License.
*/
use Doctrine\ORM\EntityManager;

/** 
 * This class is used to apply additional filtering to a query according to the
 * scope tags that are joined to the target entity. 
 * <p> 
 * The class takes a Doctrine {@link QueryBuilder} object which represents 
 * the current query that will be appended/updated. This query may contain other 
 * bind parameters. This class also takes scope type, scope match, bind count and entity type
 * and uses these to build a sub query that will filter the results based on the correct entities scope
 * <p>  
 * This subquery is then appended to the original.
 * This query and its bind variables can then be fetched and used with getQB() and getBinds().
 * Important: This does not return the query or bind variables, they must be fetched. 
 *
 * @author James McCarthy
 * @author David Meredith 
 */
class ScopeQueryBuilder{

    private $binds = null;
    private $qb = null;
    private $scopeMatch = null;
    private $bc;
    private $tableType;
    private $em;

    private function setQB($qb) {
	$this->qb = $qb;
    }

    private function setScopeMatch($scopeMatch) {
	$this->scopeMatch = $scopeMatch;
    }

    public function getQB() {
	return $this->qb;
    }

    /**
     * 2D array of postitional bind parameter to bindValue mappings for the 
     * new WHERE clauses that are appended to the QueryBuilder.  
     * <p>
     * Each outer element stores a child array that has two elements; 
     * 1st element stores the parameterBindCounter (int) used for the positional bind param. 
     * 2nd element stores the bindValue (mixed).
     * 
     * @return array counter-to-value mapping array or empty array    
     */
    public function getBinds() {
	return $this->binds;
    }

    private function setBindCount($bc) {
	$this->bc = $bc;
    }

    public function getBindCount() {
	return $this->bc;
    }

    private function setTableType($tableType, $tId) {
	$this->tableType = $tableType;
	$this->tId = $tId;
    }

    private function getTableType() {
	return $this->tableType;
    }

    /**
     * The constructor takes the scope value(s) to search. It takes the currently built query which
     * it will then append the sub query to. It takes the bind count which is used as a unique identifier
     * to create bind variables. It takes the table type that the scope is from, eg Service or Site and the 
     * identifier used in the main query for this table. Eg Site would be S and Service would be se.   
     * 
     * @param string $scopeParameter either null, a single scope tag value e.g. 'EGI' or comma sep list 'EGI,EUDAT,SCOPEX'
     * @param string $scopeMatch either null, 'any' or 'all' 
     * @param \Doctrine\ORM\QueryBuilder $qb QueryBuilder instance to update
     * @param \Doctrine\ORM\EntityManager $em EntityManager to use for query 
     * @param int $bc Current bind count, used to create unique bind param names 
     *        (bind params will be created by appending an int incremented from this value)
     * @param string $tableType The name of the entity that owns the scopes, 
     *        e.g. 'Site' or 'Service'
     * @param string $tId The alias used in the query for this table, ie 's' or 'se'
     */
    public function __construct($scopeParameter, $scopeMatch, \Doctrine\ORM\QueryBuilder 
                                $qb, \Doctrine\ORM\EntityManager $em, $bc, $tableType, $tId){				
	$this->em = $em;
	$this->setQB($qb);
	$this->setBindCount($bc);
	$this->setScopeMatch($scopeMatch);
	$this->setTableType($tableType, $tId);

	if ($scopeParameter == NULL && !isset($scopeParameter)) {
	    $this->createDefaultSubQuery();
	} else {
	    $this->createSubQuery($scopeParameter, $scopeMatch);
	}
    }
	
    /** 
     *  The TTS alias used in these statements refers to Table To Select,
     *  a generic term to which means less inserting of unique names into the 
     *  query when selecting from sites, ngis, services etc. 
     */
	
    /**
     * Generates the sub query from the scope that will be appended
     * to the existing query
     * 
     * @param String $scopeParameters either null, a single scope tag value e.g. 
     *    'EGI' or comma sep list 'EGI,EUDAT,SCOPEX'
     * @param String $scopeMatch either null, 'any' or 'all' 
     */
    private function createSubQuery($scopeParameters, $scopeMatch) {
	// If user has specified "&scope=" as a parameter then don't add any 
	// scope clause to the query - do nothing with scopes including no default 
	if ($scopeParameters != NULL) {
	    $tId = $this->tId;
	    $bc = $this->getBindCount();
	    $tableType = $this->tableType;
	    $qb = $this->getQB();

	    //If scopes does not contain a comma then it is not a list so do a standard query		
	    if (!strpos($scopeParameters, ',')) {
		$qb2 = $this->em->createQueryBuilder();
		$qb2->select('tts')
			->from($tableType, 'tts')
			->innerJoin('tts.scopes', 'sc2')
			->where($qb->expr()->eq('sc2.name', '?' . ++$bc));

		//Join sub-clause onto main query
		$qb->andWhere($qb->expr()->in($tId, $qb2->getDQL()));
		//Store bind name and variable for later binding
		$this->binds[] = array($bc, $scopeParameters);
	    } else {
		//$scopeParameters Contains multiple scope tags, so construct a 'WHERE IN' query
		// trim whitespace and trailing comma (if present) 
		$scopeParameters = trim($scopeParameters); 
		$scopeParameters = rtrim($scopeParameters, ','); 
		
		//If no scope match was provided get the default
		if ($scopeMatch == null) {
		    $configService = \Factory::getConfigService();
		    $scopeMatch = $configService->getDefaultScopeMatch();
		}

		//If scope match was 'all' then construct query using HAVING and 
		//GROUP BY clauses to ensure all scopes are matched 
		if ($scopeMatch == 'all') {
		    $qb2 = $this->em->createQueryBuilder();
		    $qb2->select('sc2.id')
			    ->from('Scope', 'sc2')
			    ->where($qb2->expr()->in('sc2.name', '?' . ++$bc));
		    
		    // Store bind name and value for later binding, e.g. 
		    // $this->binds[] = array(15, array('EGI', 'WLCG' ,'Local'))
		    $scopesArray = explode(',', $scopeParameters);
		    $this->binds[] = array($bc, $scopesArray);
		    
		    /* ------------IMPORTANT-------------------------------------------------
		     * We need this extra parent clause when performing a GROUP BY and HAVING.
		     * Without this doctrine will get confused when creating the SQL it sends
		     * to the database. At first look this extra clause will seem un-needed
		     * but this is not the case.
		     */
		    $qb1 = $this->em->createQueryBuilder();
		    $qb1->select('tts.id')
			    ->from($tableType, 'tts')
			    ->innerJoin('tts.scopes', 'sc1')
			    ->where($qb1->expr()->in('sc1.id', $qb2->getDQL()));

		    $qb1->groupBy('tts.id')
			    ->andHaving($qb1->expr()->eq($qb1->expr()->count('tts.id'), '?' . ++$bc));
		    
		    // Count split terms and store, e.g. array(15, 3), is needed
		    // for HAVING clause above 
		    $this->binds[] = array($bc, count($scopesArray)); 
		    
		    //Join sub-clause onto main query
		    $qb->andWhere($qb->expr()->in($tId, $qb1->getDQL()));
		    
		} else {
		    // scope_match == 'any' so there is no need for an extra 
		    // parent query as we aren't using HAVING and GROUP BY.

		    $qb2 = $this->em->createQueryBuilder();
		    $qb2->select('tts')
			    ->from($tableType, 'tts')
			    ->innerJoin('tts.scopes', 'sc2')
			    ->where($qb->expr()->in('sc2.name', '?' . ++$bc));
		    
		    // bind the array of scope values 
		    $this->binds[] = array($bc, explode(',', $scopeParameters));

		    //Join sub-clause onto main query
		    $qb->andWhere($qb->expr()->in($tId, $qb2->getDQL()));
		}
	    }
	    //finally replace original query with updated joined query
	    $this->setBindCount($bc);
	    $this->setQB($qb);
	}
    }


    /**
     * When no scope is specified the default is set via defaultScope 
     * which in turn is read from a local_info.xml file in config folder.
     */
    private function createDefaultSubQuery() {
	// If no default scope is provided in local_info then don't do
	// anything to the query and leave it without a where clause for scope
	if ($this->defaultScope() != null) {
	    $tId = $this->tId;
	    $tableType = $this->getTableType();
	    $qb = $this->getQB();
	    $bc = $this->getBindCount();
	    $qb2 = $this->em->createQueryBuilder();
	    $qb2->select('tts')
		    ->from($tableType, 'tts')
		    ->innerJoin('tts.scopes', 'sc2')
		    ->where($qb->expr()->like('sc2.name', '?' . ++$bc));

	    //Add subquery to main query
	    $qb->andWhere($qb->expr()->in($tId, $qb2->getDQL()));

	    //Store bind name and variable for later binding
	    $this->binds[] = array($bc, $this->defaultScope());
	    //Replace original query with updated query
	    $this->setBindCount($bc);
	    $this->setQB($qb);
	}
    }

    /**
     * Gets the name of the default scope from the config service 
     * and returns it as a string value. 
     * @return $scopes
     */
    private function defaultScope() {
	$configService = \Factory::getConfigService();
	$scopes = $configService->getDefaultScopeName();

	if ($scopes == null || trim($scopes) == "") {
	    return null;
	} else {
	    return $scopes;
	}
    }

    /**
     * This is a backup function incase the other function utilizing group by and having 
     * breaks. This can be switched over to with a function name change and will use 
     * repeated AND/OR queries to filter by scope
     * 
     * @param String $scopeParameters
     * @param String $scopeMatch
     */
    private function BACKUP_createSubQuery($scopeParameters, $scopeMatch) {
	/* If user has specified "&scope=" as a parameter then don't add any
	 * scope clause to the query - do nothing with scopes including no default */
	if ($scopeParameters != NULL) {
	    $tId = $this->tId;
	    $bc = $this->getBindCount();
	    $tableType = $this->getTableType();
	    $qb = $this->getQB();
	    $valuesToBind;

	    //If scopes does not contain a comma then not a list do a standard query
	    if (!strpos($scopeParameters, ',')) {
		$qb2 = $this->em->createQueryBuilder();
		$qb2->select('tts')
			->from($tableType, 'tts')
			->innerJoin('tts.scopes', 'sc2')
			->where($qb->expr()->eq('sc2.name', '?' . ++$bc));

		//Join sub-clause onto main query
		$qb->andWhere($qb->expr()->in($tId, $qb2->getDQL()));
		//Store bind name and variable for later binding
		$this->binds[] = array($bc, $scopeParameters);
	    } else {
		$splitScopes = explode(',', $scopeParameters);
		$uID = 0;
		//For each supplied scope create a new AND or OR clause
		foreach ($splitScopes as $scope) {
		    $qb1 = $this->em->createQueryBuilder();
		    $qb1->select('tts' . $uID)
			    ->from($tableType, 'tts' . $uID)
			    ->innerJoin('tts' . $uID . '.scopes', 'sc' . $uID)
			    ->where($qb->expr()->eq('sc' . $uID++ . '.name', '?' . ++$bc));

		    //Join sub-clause onto main query
		    if ($scopeMatch == 'all') {
			$qb->andWhere($qb->expr()->in($tId, $qb1->getDQL()));
		    } else {
			$qb->orWhere($qb->expr()->in($tId, $qb1->getDQL()));
		    }
		    //Store bind name and variable for later binding
		    $this->binds[] = array($bc, $scope);
		}
	    }
	}
    }

}



/*  Example Queries (+ indicate the appended sub-query added by this class) 
  
    When a single scope or no scope is provided and a default scope is provide in local_info.xml:
   
    SELECT s,
           sc,
           sp
    FROM Site s
    LEFT JOIN s.siteProperties sp
    LEFT JOIN s.scopes sc
    LEFT JOIN s.ngi n
    LEFT JOIN s.country c
    LEFT JOIN s.certificationStatus cs
    LEFT JOIN s.infrastructure i
 +   WHERE s IN                     ('s' is passed to constructor as $tableType)
 +       (SELECT tts
 +        FROM Site tts             ('Site' is passed to constructor as $tId) 
 +        INNER JOIN tts.scopes sc2
 +        WHERE sc2.name LIKE 'EGI') ('EGI' is passed to constr as $scopeParameter)
    ORDER BY s.shortName ASC
  
  
   When the user supplies multiple scopes  and scope match = all or default scope match = all
   eg scope=EGI,Prace,Local&scope_match=all:
  
     SELECT s,
           sc,
           sp
    FROM Site s
    LEFT JOIN s.siteProperties sp
    LEFT JOIN s.scopes sc
    LEFT JOIN s.ngi n
    LEFT JOIN s.country c
    LEFT JOIN s.certificationStatus cs
    LEFT JOIN s.infrastructure i
 +    WHERE s IN                     ('s' is passed to constructor as $tableType)
 +        (SELECT tts.id
 +         FROM Site tts             ('Site' is passed to constructor as $tId)
 +         INNER JOIN tts.scopes sc1
 +         WHERE sc1.id IN
 +             (SELECT sc2.id
 +              FROM SCOPE sc2
 +              WHERE sc2.name IN('EGI','Prace','Local'))
 +         GROUP BY tts.id HAVING COUNT(tts.id) = 3)
    ORDER BY s.shortName ASC
  
   When the user supplies multiple scopes  and scope match = any or default scope match = any
   eg scope=EGI,Prace,Local&scope_match=any:
  
     SELECT s,
       sc,
       sp
    FROM Site s
    LEFT JOIN s.siteProperties sp
    LEFT JOIN s.scopes sc
    LEFT JOIN s.ngi n
    LEFT JOIN s.country c
    LEFT JOIN s.certificationStatus cs
    LEFT JOIN s.infrastructure i
    WHERE s.shortName LIKE ?0
 +    AND s IN                     ('s' is passed to constructor as $tableType)
 +      (SELECT tts
 +       FROM Site tts             ('Site' is passed to constructor as $tId)
 +       INNER JOIN tts.scopes sc2
 +       WHERE sc2.name IN('EGI','Prace','Local'))
    ORDER BY s.shortName ASC
  
   When the user specifies 'scope=' which should represent no scope specified 
   so no sub query for scope at all:

   SELECT s,
           sc,
           sp
    FROM Site s
    LEFT JOIN s.siteProperties sp
    LEFT JOIN s.scopes sc
    LEFT JOIN s.ngi n
    LEFT JOIN s.country c
    LEFT JOIN s.certificationStatus cs
    LEFT JOIN s.infrastructure i
    ORDER BY s.shortName ASC
 */

/** BACKUP SQL
 * &scope=EGI,Local&scope_match=any:
   SELECT s,
       sc,
       sp
    FROM Site s
    LEFT JOIN s.siteProperties sp
    LEFT JOIN s.scopes sc
    LEFT JOIN s.ngi n
    LEFT JOIN s.country c
    LEFT JOIN s.certificationStatus cs
    LEFT JOIN s.infrastructure i
    WHERE s.shortName LIKE ?0
      OR s IN
        (SELECT tts0
         FROM Site tts0
         INNER JOIN tts0.scopes sc0
         WHERE sc0.name = ?1)
      OR s IN
        (SELECT tts1
         FROM Site tts1
         INNER JOIN tts1.scopes sc1
         WHERE sc1.name = ?2)
    ORDER BY s.shortName ASC
    
*
*
* &scope=EGI,Local&scope_match=all:
    SELECT s,
       sc,
       sp
    FROM Site s
    LEFT JOIN s.siteProperties sp
    LEFT JOIN s.scopes sc
    LEFT JOIN s.ngi n
    LEFT JOIN s.country c
    LEFT JOIN s.certificationStatus cs
    LEFT JOIN s.infrastructure i
    WHERE s.shortName LIKE ?0
      AND s IN
        (SELECT tts0
         FROM Site tts0
         INNER JOIN tts0.scopes sc0
         WHERE sc0.name = ?1)
      AND s IN
        (SELECT tts1
         FROM Site tts1
         INNER JOIN tts1.scopes sc1
         WHERE sc1.name = ?2)
    ORDER BY s.shortName ASC
 */