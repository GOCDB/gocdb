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

/**
 * Used for parsing the value of 'extensions' URL parameter in the API. 
 * 
 * @author David Meredith
 */
interface IExtensionsParser {

    /**
     * Parse the given extensions query string and return a tokenised array. 
     * <p>
     * The format of the string is one or more <code>AND_OR_NOT_OPERATOR(key=value)</code> groups. 
     * <p>
     * The format of the query string must be as follows:  
     * <ul>
     *   <li>For each group the AND_OR_NOT_OPERATOR is optional, but if specified must be one of 
     *      <code>AND</code>, <code>OR</code> or <code>NOT</code>.</li>
     *   <li>The key=value must be enclosed with a leading and trailing parenthesis.</li>
     *   <li>The key=value string must contain one or more equals '=' chars 
     *   (the string is split on the first equals char moving from left to right)</li>
     *   <li>The rules for allowed chars in the 'key' and 'value' parts are  
     *   defined by the implementation.</li>
     * </ul>
     * <p>
     * The format of the returned array is a two dimensional array. Each nested 
     * array has two elements, the first being an operator string, the second 
     * being the formatted key=value string.  If the query 
     * string does not define a leading operator, <code>AND</code> is assumed. 
     * For subsequent groups, if no OPERATOR is defined, then the last 
     * encountered operator is used to populate the nested array element.  
     * <p>
     * Each key=value string value is formatted as follows: 
     * <ul>
     *   <li>The leading/trailing parenthesis are stripped off.</li>
     *   <li>Backslashes are stripped out: (\' becomes ' and so on) while 
     *   double backslashes (\\) are made into a single backslash (\) 
     *   (<code>stripslashes($string)</code> is appled to each value) </li>
     * </ul>
     * 
     * <p> 
     * Given the expression <code>(VO=Alice)(VO=Atlas)NOT(VO=LHCB)(VO=AAA)OR(VO=BBB)</code> 
     * the following array is returned:  
     * <code>
     *    Array
     *    (
     *        [0] => Array
     *            (
     *                [0] => AND
     *                [1] => VO=Alice
     *            )
     *        [1] => Array
     *            (
     *                [0] => AND
     *                [1] => VO=Atlas
     *            )
     *        [2] => Array
     *            (
     *                [0] => NOT
     *                [1] => VO=LHCB
     *            )
     *        [3] => Array
     *            (
     *                [0] => NOT
     *                [1] => VO=AAA
     *            )
     *        [4] => Array
     *            (
     *                [0] => OR
     *                [1] => VO=BBB
     *            )
     *    )
     * </code> 
     * 
     * @param string $extensionsQuery
     * @return array Array formatted as described above 
     * @throws \InvalidArgumentException if query is invalid/can't be processed 
     */
    public function parseQuery($extensionsQuery); 
}
