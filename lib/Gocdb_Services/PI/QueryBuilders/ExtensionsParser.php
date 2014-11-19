<?php

namespace org\gocdb\services;
/*
 * Copyright © 2011 STFC Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
*/
/**
 * Class to parse and validate LDAP style queries 
 * Valid queries are: keyname=keyvalue
 * 					 AND(keyname=keyvalue)(keyname=keyvalue)
 * 					 OR(keyname=keyvalue)(keyname=keyvalue)
 *                   NOT(keyname=keyvalue)
 *                   wildcards:(keyname=*)
 *          
 * A limit on the number of key value pairs is defined in local_info.xml and enforced in this class
 * 
 * @author James McCarthy
 */
class ExtensionsParser{

    /**
     * This function can take an LDAP style query from the URL
	 * and parses it to check its a valid format before splitting
	 * the parts of the query into an array and returning.
     * 
     * @param String $rawQuery
     * @return array $normalizedQuery
     */
	public function parseQuery($rawQuery) {

	    $anchLeftToCapture='/';
	    $anchRightToCapture='/i';
	    $anchLeftFull='/';
	    $anchRightFull='$/';
	    // key is quite restrictive, only alpha-numerics and some chars considered useful for keys
	    $keyregex="[a-zA-Z0-9\s@_\-\[\]\+\.]{1,255}";
	    // val is any char except parenthesis () and the following to protect against sql injection  "';`
	    $valregex="[^'\";\(\)`]{0,255}";  //0 to allow for no input which will repsent wildcard
	    // A single key=value pair
	    $keyVal = "\(".$keyregex."=".$valregex."\)";
	    // must specify at least 1 kv pair
	    $regexKeyVal = "(".$keyVal.")+";
	    $regexOperator = "(AND|OR|NOT)?";
	     
	    // This regex can be used to extract the captures
	    $regexCapture = $anchLeftToCapture."(".$regexOperator.$regexKeyVal.")".$anchRightToCapture;
	    // This regex can be used to test that the whole string in full passes (using full left and right anchors)
	    $regexFull = $anchLeftFull."(".$regexOperator.$regexKeyVal.")".$anchRightFull;    
	    
	    if(!preg_match($regexFull, $rawQuery)){
	        throw new \InvalidArgumentException("This is not a valid extensions expression. Please see the wiki for information on valid expressions.
	                \nhttps://wiki.egi.eu/wiki/GOCDB/Release4/Development/ExtensibilityMechanism#PI_Examples\n\n");
	    }
	    
        /**
         * Query is now validated but we now use regexCapture to extract the parts of the query
         */
	    
	    /* We now use regex capture to extract and clean the queries */
	    preg_match_all($regexCapture, $rawQuery, $matches);
	    //Remove surplus array elements
	    unset($matches[1]);
	    unset($matches[3]);
	    $matches = array_values($matches); //reindex
	    	
	    //If no operator was supplied set it AND as a default
	    for($i=0; $i<count($matches[1]); $i++){
	        if($matches[1][$i] == ''){
	            $matches[1][$i] = 'AND';
	        }
	    }
	    	
	    //Remove operator from start of the query strings
	    for($i=0; $i<count($matches[0]); $i++){
	        $c=0;
	        while($matches[0][$i][$c] != '('){
	            $c++;
	        }
	        $matches[0][$i]=substr($matches[0][$i], $c);
	    }
	    	
	    /** Given this query: 
	     * AND(VO=Alice)(VO=Atlas)NOT(VO=LHCB)
         * Matches array should now have this structure:
         
        Array
        (
            [0] => Array
                (
                    [0] => (VO=Alice)(VO=Atlas)
                    [1] => (VO=LHCB)
                )
        
            [1] => Array
                (
                    [0] => AND
                    [1] => NOT
                )
        
        )
	    */
        
	    	    
	    /** This loop will normalize the query so each pair of brackets is paired with an operator **/
	    for($i=0; $i<count($matches[0]); $i++){
	        $operator = $matches[1][$i]; //store the operator
	        $queries = explode(')(', trim($matches[0][$i], '()')); //split the queries if multiple quries have been provided
	        foreach($queries as $q){
	            $normalized[] = array($operator, $q);	//store each operator and query in 2d array
	        }
	    }
	    
	    	     
	    
	    /** After normalization the query AND(VO=Alice)(VO=Atlas)NOT(VO=LHCB) will be in this format:
        Array
        (
            [0] => Array
                (
                    [0] => AND
                    [1] => VO=Alice
                )
        
            [1] => Array
                (
                    [0] => AND
                    [1] => VO=Atlas
                )
        
            [2] => Array
                (
                    [0] => NOT
                    [1] => VO=LHCB
                )
        
        )
	     */

	    if($this->checkLimit($normalized)){
	        return $normalized;
	    }   
	}
	
	/**
	 * Check that the amount of queries the user has entered is not greater
	 * than the limit set in local_info.xml
	 * @param Array $parsedQueries
	 * @return boolean
	 */
	private function checkLimit($parsedQueries){
	    $configService = \Factory::getConfigService();	     
	    $limit = $configService->getExtensionsLimit();
    	    if(count($parsedQueries) < $limit){
    	        return true;
    	    }else{
    	        throw new \InvalidArgumentException("You have exceeded the max amount of quieries allowed. Max allowed is: ".$limit);
    	    }	    
	}

}