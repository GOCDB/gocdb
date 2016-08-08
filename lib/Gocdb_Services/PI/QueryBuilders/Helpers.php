<?php
namespace org\gocdb\services;

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

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Contains helper functions for API queries. 
 * Many of these functions simply act to collect common code that is repeated in 
 * different PI queries and so these functions are not intended for use outside 
 * the PI package. 
 * 
 * @author James McCarthy
 * @author David Meredith
 */
class Helpers {

    /**
     * Takes a pre-built Doctrine query containing positional bind parameter
     * placeholders e.g. ':?15', and a 2D array that contains values for binding each parameter,
     * then binds all the positional bind parameters in the query.
     * <p>
     * In the bindValues array, each nested element is a child array where:
     * <ol>
     *   <li>1st element is an int used to select the positional param.</li>
     *   <li>2nd element is the value to bind, has a value of mixed. </li>
     * </ol>
     * <code>
     * array(
     *    array(10, 'some value'),
     *    array(11, 'another value'),
     *    array(12, 'a, third, value')
     * );
     * </code>
     *
     * @param array $bindIdValues 2D array, see description above
     * @param \Doctrine\ORM\QueryBuilder $query
     * @return \Doctrine\ORM\QueryBuilder Updated with bind params bound
     */
    public function bindValuesToQuery($bindIdValues, $query) {

    foreach ($bindIdValues as $bindIdValue) {
        $query->setParameter($bindIdValue[0], $bindIdValue[1]);

        // Check value is string not time/date object
        /*if (is_string($bindIdValue[1])) {

        // Presence of a 3rd element in the bind array means the bind
        // value has to be exploded using the string stored in the 3rd
        // element, e.g. for multiple scopes this is a comma: 'EGI,wlcg,scopex'
        //if (count($bindIdValue) == 3 && strpos($bindIdValue[1], ',')) {
        //if (count($bindIdValue) == 3) {
        //    $explodedValuesArray = explode($bindIdValue[2], $bindIdValue[1]);
        //    $query->setParameter($bindIdValue[0], $explodedValuesArray);
        //} else {
            // No 3rd element in bind array means bind the raw value
            // echo "Binding at: ".$values[0]. " With: ".$values[1]. "\n\n";
            $query->setParameter($bindIdValue[0], $bindIdValue[1]);
        //}
        //If value was object bind it as is
        } else {
        $query->setParameter($bindIdValue[0], $bindIdValue[1]);
        }*/
    }
    return $query;
    }

    /**
     * Ensure that $testParams only contain array keys that are supported as
     * listed in $supportedParams, and that parameter values don't contain any
     * of the following chars: "'`
     * If an unsupported parameter is detected, then die with a message.
     *
     * @param array $supportedParams
     *        	A single dimensional array of supported/expected parameter names
     * @param array $testParams
     *        	An associatative array of key => value pairs (parameter_key => value)
     * @throws \InvalidArgumentException if either of the given args are not arrays.
     */
    public function validateParams($supportedParams, $testParams) {
        if (!is_array($supportedParams) || !is_array($testParams)) {
            throw new \InvalidArgumentException('Invalid parameters passed to PI query');
        }

        // Check the parameter keys are supoported
        $testParamKeys = array_keys($testParams);
        foreach ($testParamKeys as $key) {
            // if givenkey is not defined in supportedkeys it is unsupported
            if (!in_array($key, $supportedParams)) {
            echo '<error>Unsupported parameter: ' . $key . '</error>';
            die();
            }
        }

        // Check that the paramater does not contain invalid chracters
        $testParamValues = array_values($testParams);
        foreach ($testParamValues as $value) {
            // whole string anchored left and right, allow any char except "'`
            if (!preg_match("/^[^\"'`]*$/", $value)) {
            echo '<error>Unsuported chracter in value: ' . $value . '</error>';
            die();
            }
        }
    }
    
    /**
     * Validates and searches the given parameter array for valid 'next_cursor' and 'prev_cursor' keys.  
     * Returns an associative array with the following structure: 
     * <code>
     *  $array = array(
     *    'isPaging' => boolean true|false, 
     *    'next_cursor' => null or int, 
     *    'prev_cursor' => null or int
     *  )
     * </code>
     * 
     * @param array $parameters Associative array 
     * @return array Associative array
     */
    public function getValidCursorPagingParamsHelper($parameters){
        $pagingDetailsArray = array();
        $pagingDetailsArray['isPaging'] = false;
        $pagingDetailsArray['next_cursor'] = null;
        $pagingDetailsArray['prev_cursor'] = null;
        
        // Cant specify both next_cursor and prev_cursor
        if (isset($parameters['next_cursor']) && isset($parameters['prev_cursor'])) {
            die("<error>It is invalid to specify both 'next_cursor' and 'prev_cursor' parameters in the same query</error>");
        }
        
        // Validate next_cursor parameter
        if (isset($parameters['next_cursor'])) {
            if( ((string)(int)$parameters['next_cursor'] == $parameters['next_cursor']) && (int)$parameters['next_cursor'] >= 0) {
                $pagingDetailsArray['next_cursor'] = (int) $parameters['next_cursor'];
                $pagingDetailsArray['isPaging'] = true;
            } else {
                die("<error>Invalid 'next_cursor' parameter - must be a whole number greater than or equal to zero</error>");
            }
        }
        // Validate prev_cursor parameter
        if (isset($parameters['prev_cursor'])) {
            if( ((string)(int)$parameters['prev_cursor'] == $parameters['prev_cursor']) && (int)$parameters['prev_cursor'] >= 0) {
                $pagingDetailsArray['prev_cursor'] = (int) $parameters['prev_cursor'];
                $pagingDetailsArray['isPaging'] = true;
            } else {
                die("<error>Invalid 'prev_cursor' parameter - must be a whole number greater than or equal to zero</error>");
            }
        }
       
        return $pagingDetailsArray; 
    }
    
    /**
     * Executes the given query using a Doctrine Paginator or as a straight query
     * and populates the returned array with expected objects.  
     * The query applies Doctrine object hydration (HYDRATE_OBJECT).  
     * Returns an associative array with the following structure: 
     * 
     * <code>
     * $array = array(
     *   'resultSet' => array of Doctrine objects/entities 
     *   'resultSetSize' => int 
     *   'lastCursorId' => null or int 
     *   'firstCursorId'=> null or int
     * )
     * </code>
     * 
     * @param bool $isPaging  
     * @param \Doctrine\ORM\Query $query
     * @param mixed $next_cursor null or int
     * @param mixed $prev_cursor null or int
     * @param string $direction 'ASC' or 'DESC' 
     */
    public function cursorPagingExecutorHelper($isPaging, $query, $next_cursor, $prev_cursor, $direction){
        $resultSet = array(); 
        $lastCursorId = null; 
        $firstCursorId = null; 
        
        // if paging, then either the user has specified a 'cursor' url param,
        // or defaultPaging is true and this has been set to 0
        if ($isPaging) {
            $paginator = new Paginator($query, $fetchJoinCollection = true); // object hydration
            foreach ($paginator as $obj) {
                $resultSet[] = $obj;
            }
    
            if($direction == 'DESC'){
                $resultSet = array_reverse($resultSet);
            }   
    
        } else {
            $resultSet = $query->execute();  // object hydration
        }
         
        $resultSetSize = count($resultSet);
    
        // Set the first/last Cursor Ids from the FIRST/TOP and LAST/BOTTOM records listed in the result set
        // (needed for building cursor-pagination links).
        //if($isPaging){
        if($resultSetSize > 0){
            $lastCursorId = $resultSet[$resultSetSize - 1]->getId();
            $firstCursorId = $resultSet[0]->getId();
    
        } else if ($resultSetSize == 0 && $next_cursor !==null && $next_cursor >= 0){
            // The next_cursor has overshot the last available record,
            // so use the current next_cursor in order to build the 'cursor_prev' link.
            // If the user has been using the next/prev links only and not manually
            // entering the cursor URL param values, then the first occurence of
            // this 'if' condition should mean that the current 'next_cursor' value
            // is the ID of the last record from the previous page (e.g. 20).
            // We +1 to include the last record (e.g. 20) in the previous page i.e. 'where id < 21'.
            $firstCursorId = $next_cursor + 1;
            $lastCursorId = null; // if we have overshot, there is no last/next cursor Id
        }
        else if ($resultSetSize == 0 && $prev_cursor !==null && $prev_cursor >= 0){
            // The prev_cursor has undershot the first available record,
            // so use 'prev_cursor - 1' in order to build the 'cursor_next' link.
            $lastCursorId = $prev_cursor - 1;
            $firstCursorId = null; // if we have undershot, there is no first/prev cursor Id

        }
        
        if($lastCursorId !== null && $lastCursorId < 0) $lastCursorId = 0; 
        if($firstCursorId !== null && $firstCursorId < 0) $firstCursorId = 0;
        
        
        //}
        
    
        $returnArray = array();
        $returnArray['resultSet'] = $resultSet; 
        $returnArray['resultSetSize'] = $resultSetSize; 
        $returnArray['lastCursorId'] = $lastCursorId; 
        $returnArray['firstCursorId'] = $firstCursorId; 
        return $returnArray;   
        
    }
    
    

    /**
     * Adds a new tag $tagName to $xml if $value isn't ""
     *
     * @param $xml SimpleXMLElement
     * @param $tagName String
     *        	Name of the tag
     * @param $value String
     *        	Tag value
     * @return string XML result string
     * @throws \Exception
     */
    public function addIfNotEmpty($xml, $tagName, $value) {
        if ($value != null && $value != "") {
            $xml->addChild($tagName, $value);
        }
    }

    /**
     * Additional method to allow easy creation of extensions within entities
     * Adds a new tag $tagName to $xml if $value isn't ""
     * @param $xmlParent SimpleXMLElement of parent
     * @param $tagName String Name of the tag
     * @param $value String Tag value
     * @return string XML result string
     */
    public function addExtIfNotEmpty($xmlParent, $tagName, $value) {
        if ($value != "") {
            $extension = $xmlParent->addChild("Extension");
            $extension->addChild("LocalID", $tagName);
            $extension->addChild("Key", $tagName);
            $extension->addChild("Value", $value);
        }
    }

    /**
     * Deprecated - do not use. 
     * Adds 'link' child elements to the given parent xml element to build links for OFFSET paging. 
     * <p>
     * <ul>
     *   <li>The parent xml element should normally be the 'meta' element following HATEOAS.</li>  
     *   <li>The 'next' link is only added if $next <= $last.</li>
     *   <li>The 'prev' link is only added if there is a page previous to the current page.</li>  
     *   <li>The 'href' hyperlink value defines a link which is constructed from the current URI.</li> 
     *   <li>The 'page' url parameter is added to the value of the href attributes.</li>   
     * </ul>
     * For example, the following links would be added as child elements to the given metaXml: 
     * <pre>
     *    link rel="self" href="/gocdbpi/public/?method=get_service" 
     *    link rel="next" href="/gocdbpi/public/?method=get_service&amp;page=2" 
     *    link rel="prev" href="/gocdbpi/public/?method=get_service&amp;page=1" 
     *    link rel="first" href="/gocdbpi/public/?method=get_service&amp;page=1"
     *    link rel="last" href="/gocdbpi/public/?method=get_service&amp;page=42"
     * </pre>
     * @see http://restcookbook.com/Resources/pagination/ 
     * @deprecated This method assumes offset based paging which should not be used. Use cursor based
     * paging with 'addHateoasCursorPagingLinksToMetaElem()' instead.
     * 
     * @param \SimpleXMLElement $metaXml Parent xml tag, normally the 'meta' tag from HATEOAS
     * @param int $next 
     * @param int $last
     * @param string $urlAuthority Is prefixed to each 'href' attribute value in order  
     *   to specify an optional 'scheme://host:port' for absolute URL values (href values are relative 
     *   URLs by default, i.e. starting with '/'). 
     */
    public function addHateoasPagingLinksToMetaElem($metaXml, $next, $last, $urlAuthority=''){
        // HATEOAS meta element as per: http://restcookbook.com/Resources/pagination/
        $urlParts = parse_url($_SERVER['REQUEST_URI']);

        $urlBase = $urlAuthority.$urlParts['path'].'?';
        $urlParamStr = $urlParts['query']; // get only the url query parameter string
        parse_str($urlParamStr, $urlQueryParamsArray); // parse urlParamString into array

        // add self link (don't modify the query)
        $selfLink = $metaXml->addChild("link");
        $selfLink->addAttribute("rel", "self");
        $selfLink->addAttribute("href", $urlBase.$urlParts['query']);

        // add next link
        if ($next <= $last) {
            $urlQueryParamsArray['page'] = $next; // reset or add the 'page' parameter
            $nextQueryUrl = http_build_query($urlQueryParamsArray);
            //$escapedNextQueryUrl = htmlspecialchars( $nextQueryUrl, ENT_QUOTES, 'UTF-8' );
            $nextLink = $metaXml->addChild("link");
            $nextLink->addAttribute("rel", "next");
            $nextLink->addAttribute("href", $urlBase.$nextQueryUrl);
        }

        // add prev  link
        if ($next - 2 > 0) {
            $urlQueryParamsArray['page'] = $next-2;
            $prevQueryUrl = http_build_query($urlQueryParamsArray);
            //$escapedNextQueryUrl = htmlspecialchars( $nextQueryUrl, ENT_QUOTES, 'UTF-8' );
            $prevLink = $metaXml->addChild("link");
            $prevLink->addAttribute("rel", "prev");
            $prevLink->addAttribute("href", $urlBase.$prevQueryUrl);
        }

        // add first link
        $urlQueryParamsArray['page'] = 1;
        $firstQueryUrl = http_build_query($urlQueryParamsArray);
        $firstLink = $metaXml->addChild("link");
        $firstLink->addAttribute("rel", "first");
        $firstLink->addAttribute("href", $urlBase.$firstQueryUrl);

        // add last link
        $urlQueryParamsArray['page'] = $last;
        $lastQueryUrl = http_build_query($urlQueryParamsArray);
        $lastLink = $metaXml->addChild("link");
        $lastLink->addAttribute("rel", "last");
        $lastLink->addAttribute("href", $urlBase.$lastQueryUrl);
    }
    
    
    /**
     * Add 'link'child elements to the given parent xml element for CURSOR based paging. 
     * 
     * <ul>
     *   <li>The parent xml element should normally be the 'meta' element following HATEOAS.</li>  
     *   <li>The 'next' link is only added if $prev_cursor is not null.</li>
     *   <li>The 'prev' link is only added if $next_cursor is not null.</li>  
     *   <li>The 'href' hyperlink value defines a link which is constructed from the current URI.</li>    
     * </ul
     * 
     * @param  \SimpleXMLElement $metaXml Parent xml tag, normally the 'meta' tag from HATEOAS
     * @param mixed $prev_cursor null or int
     * @param mixed $next_cursor null or int
     * @param string $urlAuthority Is prefixed to each 'href' attribute value in order  
     *   to specify an optional 'scheme://host:port' for absolute URL values (href values are relative 
     *   URLs by default, i.e. starting with '/'). 
     */
    public function addHateoasCursorPagingLinksToMetaElem($metaXml, $prev_cursor, $next_cursor, $urlAuthority=''){
        // HATEOAS meta element as per: http://restcookbook.com/Resources/pagination/
        $urlParts = parse_url($_SERVER['REQUEST_URI']);
    
        $urlBase = $urlAuthority.$urlParts['path'].'?';
        $urlParamStr = $urlParts['query']; // get only the url query parameter string
        parse_str($urlParamStr, $urlQueryParamsArray); // parse urlParamString into array
    
        // add self link (don't modify the query)
        $selfLink = $metaXml->addChild("link");
        $selfLink->addAttribute("rel", "self");
        $selfLink->addAttribute("href", $urlBase.$urlParts['query']);
    
        // add next link 
        if ($next_cursor !== null ){ //&& $next_cursor > 0) {
            unset($urlQueryParamsArray['prev_cursor'] );
            $urlQueryParamsArray['next_cursor'] = $next_cursor; // reset or add the 'next_cursor' parameter
            $nextQueryUrl = http_build_query($urlQueryParamsArray);
            //$escapedNextQueryUrl = htmlspecialchars( $nextQueryUrl, ENT_QUOTES, 'UTF-8' );
            $nextLink = $metaXml->addChild("link");
            $nextLink->addAttribute("rel", "next");
            $nextLink->addAttribute("href", $urlBase.$nextQueryUrl);
        }
    
        // add prev  link
        if ($prev_cursor !== null ){ //&& $prev_cursor >= 1) {
            unset($urlQueryParamsArray['next_cursor'] ); 
            $urlQueryParamsArray['prev_cursor'] = $prev_cursor;
            $prevQueryUrl = http_build_query($urlQueryParamsArray);
            //$escapedNextQueryUrl = htmlspecialchars( $nextQueryUrl, ENT_QUOTES, 'UTF-8' );
            $prevLink = $metaXml->addChild("link");
            $prevLink->addAttribute("rel", "prev");
            $prevLink->addAttribute("href", $urlBase.$prevQueryUrl);
        }
    
        // add start link
        unset($urlQueryParamsArray['prev_cursor'] );
        unset($urlQueryParamsArray['next_cursor'] );
        $urlQueryParamsArray['next_cursor'] = 0;
        $firstQueryUrl = http_build_query($urlQueryParamsArray);
        $firstLink = $metaXml->addChild("link");
        $firstLink->addAttribute("rel", "start");
        $firstLink->addAttribute("href", $urlBase.$firstQueryUrl);
    
        // add last link
//         $urlQueryParamsArray['page'] = $last;
//         $lastQueryUrl = http_build_query($urlQueryParamsArray);
//         $lastLink = $metaXml->addChild("link");
//         $lastLink->addAttribute("rel", "last");
//         $lastLink->addAttribute("href", $urlBase.$lastQueryUrl);
    }
    

}
