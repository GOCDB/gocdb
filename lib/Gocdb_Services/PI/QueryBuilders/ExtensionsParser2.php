<?php

/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace org\gocdb\services;
require_once __DIR__ . '/IExtensionsParser.php';
require_once __DIR__ . '/ExtensionsQueryNormaliser.php';
require_once __DIR__ . '/KeyValueValidator.php'; 

/**
 * @author David Meredith
 */
class ExtensionsParser2 implements IExtensionsParser{
   
    /**
     * {@inheritDoc}
     * <p>
     * The rules for allowed chars in the 'key' and 'value' parts depend on the 
     * implementation. This implementation defines the following rules:  
     * <ul>
     *    <li>The key conforms to the regex: <code>/^([a-zA-Z0-9\s@_\-\[\]\+\.]{1,255})$/</code>
     *   (1 to 255 alpha numeric chars and selected chars)</li>
     *   <li>The value conforms to the regex (any char 0 to 255 times including 
     *     newline/whitespace): <code>/^([\s\S]{0,255})$/</code></li>
     *   <li>Parenthesis occuring within each 'value' must be escaped with a backslash.</li> 
     *   <li>Whitespace is allowed before and after the AND_OR_NOT_OPERATOR.</li> 
     * </ul>
     */
    public function parseQuery($extensionsQuery) {
        $queryNormaliser = new \org\gocdb\services\ExtensionsQueryNormaliser(); 
        $keyValueValidator = new \org\gocdb\services\KeyValueValidator(); 

        $normalisedQuery = $queryNormaliser->convert($extensionsQuery); 
        // validate the k=v pairs 
        $errors = array(); 
        $normalisedAndSlashStripped = array(); 
        foreach ($normalisedQuery as $singleOperatorAndExpression) {
            // don't strictly need to validate the operator, its already done in the 
            // extensions query normalizer 
            $operator = $singleOperatorAndExpression[0];
            if($operator != 'AND' && $operator != 'NOT' && $operator != 'OR'){
                throw new \InvalidArgumentException(
                        'Illegal operator in expression, should be one of AND OR NOT'); 
            }
            $errors = $keyValueValidator->validate($singleOperatorAndExpression[1], $errors);
            
            $normalisedAndSlashStrippedChildArray = array(); 
            $normalisedAndSlashStrippedChildArray[0] = $singleOperatorAndExpression[0]; 
            $normalisedAndSlashStrippedChildArray[1] = stripslashes($singleOperatorAndExpression[1]); 
            $normalisedAndSlashStripped[] = $normalisedAndSlashStrippedChildArray; 
        }
        if(count($errors) > 0){
            throw new \InvalidArgumentException($errors[0]);  
        }
        
        //$this->checkLimit($normalisedQuery); 
        //return $normalisedQuery;
        return $normalisedAndSlashStripped; 
    }

    /**
     * Check that the amount of queries the user has entered is not greater
     * than the limit set in local_info.xml
     * @param Array $parsedQueries
     * @return boolean
     */
//	private function checkLimit($parsedQueries){
//	    $configService = \Factory::getConfigService();	     
//	    $limit = $configService->getExtensionsLimit();
//    	    if(count($parsedQueries) < $limit){
//    	        return true;
//    	    }else{
//    	        throw new \InvalidArgumentException("You have exceeded the max amount of quieries allowed. Max allowed is: ".$limit);
//    	    }	    
//	}

}
