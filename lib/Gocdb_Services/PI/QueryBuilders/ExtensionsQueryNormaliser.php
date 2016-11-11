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
 * Converts the given query string into a tokenised array.
 * Used for parsing the 'extensions' URL parameter in the API.
 *
 * @author David Meredith
 */
class ExtensionsQueryNormaliser {


    /**
     * Convert the query string into a tokenised array with a known structure.
     * <p>
     * The format of the query string is one or more <code>OPERATOR(xxxx)</code>
     * groups, where the OPERATOR is optional and the xxxx string is
     * enclosed in parenthesis.
     * <p>
     * The format of the query string must be as follows:
     * <ul>
     *   <li>The OPERATOR is optional, but if specified must be one of
     *      <code>AND</code>, <code>OR</code> or <code>NOT</code>.</li>
     *   <li>The xxxx value must be enclosed with a leading and trailing parenthesis.</li>
     *   <li>Parenthesis occuring within the xxxx value must be escaped with a single backslash.</li>
     *   <li>Any other char including whitespace is allowed within the xxxx value
     *      including slash-escaped chars.</li>
     *   <li>Whitespace is allowed before/after the OPERATOR.</li>
     * </ul>
     * The format of the returned array is a two dimensional array. Each nested
     * array has two elements, the first being an operator string, the second
     * being the raw xxxx value with the leading/trailing parenthesis removed.
     * If the query string does not define a leading operator, <code>AND</code> is assumed.
     * For subsequent groups, if no OPERATOR is defined, then the last
     * encountered operator is used to populate the nested array element.
     * <p>
     * Note, calling clients will probably want to run each returned xxxx value
     * through <code>stripslashes($string)</code> to remove the character escaping
     * (e.g. of slash-escaped nested parethesis).
     * <p>
     * Given the expression <code>(VO=Alice)(VO=Atlas)NOT(VO=LHCB)(VO=AAA)OR(VO=\(BBB\))</code>
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
     *                [1] => VO=\(BBB\)
     *            )
     *    )
     * </code>
     *
     * @see IExtensionsParamParser::parseQuery($query)
     * @param string $extensionsExpression query string as described above
     * @return array Formatted array as described above
     * @throws \InvalidArgumentException if a problem occurs or the expression
     *   is invalid and can't be parsed
     */
    public function convert($extensionsExpression) {
        $this->validateExpression($extensionsExpression);
        return $this->extractPatternGroups($extensionsExpression);
    }

    /**
     * Validate the given query expression, throw if invalid.
     * @param string $string
     * @throws \InvalidArgumentException
     */
    private function validateExpression($string){
        if($string == null || trim($string) == ''){
            // throw early
            throw new \InvalidArgumentException('Query expression is null or empty');
        }

        $optionalPredicateAndWhitespaceGroup = '(?:(?:AND|OR|NOT)[\s]*)?' ; // occurs 0 or 1
        $parethesisGroup = '\((?:\\\\.|[^()\\\\])+\)';  // occurs 1

        $patternGreedyMatchAllLine =
                '/^'.    // anchor left
                  '(?:'.   // start main group but don't capture group
                      '[\s]*'.  // optional whitespace
                      $optionalPredicateAndWhitespaceGroup. // occus 0 or 1
                      $parethesisGroup.  // occurs 1
                      '[\s]*'.  // optional whitespace
                  ')+'.    // close main group, at least one required
                '$/s';   // anchor right, keep newlines

        // returns 1 if match, 0 if not match, FALSE if error
        $matchAllString = preg_match($patternGreedyMatchAllLine, $string);
        if($matchAllString === FALSE){
            throw new \InvalidArgumentException("An error occurred parsing query");
        }
        if($matchAllString != 1){
           throw new \InvalidArgumentException("Invalid query expression");
        }
    }

    /**
     * Parse the string and return the formatted array.
     * @param string $string
     * @return array
     * @throws \InvalidArgumentException
     */
    private function extractPatternGroups($string){
        // Regarding $optionalPredicateAndWhitespace :
        // Its really important to capture the optional predicate and trailing whitespace
        // in one optional group because this affects how the pattern is repeatedly
        // matched - the pattern does NOT include leading whitespace!
        // Therefore, a match will only start with EITHER a leading '(' or the
        // first char from one of the OR'd predicates: 'A' 'O' 'N'
        $optionalPredicateAndWhitespace = '(?:(?:AND|OR|NOT)[\s]*)?'; // occur 0 or 1
        $parethesisGroup = '\((?:\\\\.|[^()\\\\])+\)';   // occur 1

        $patternMatchRepeat = '/' . $optionalPredicateAndWhitespace . $parethesisGroup . '/s';

        // http://stackoverflow.com/questions/10208694/regex-to-parse-string-with-escaped-characters

        /* $optionalPredicateAndWhitespace
         * /                   # start regex
         *   (?:               #    |start a new group but don't catpure group
         *      (?:            #         |start a new group but don't capture group
         *         AND|OR|NOT  #         |  allow AND or OR or NOT
         *      )              #         |close group
         *      [\s]*          #         allow multiple whitespace
         *   )?                #    |close group, can occur zero or once (an optional group)
         */
        /* $parethesisGroup
         * \(                  #     find first opening of '('
         *      (?:            #     |      |start a new group but don't capture group
         *          \\\\.      #     |      |  allow any escaped char inc escaped parethesis \(  and escaped backslash \\
         *            |        #     |      |     or
         *          [^()\\\\]  #     |      |  allow any char except parenthesis or backslash in group
         *      )+             #     |      |close group, must be one or more chars in group
         * \)                  #     find balanced closing ')'
         * /s                  # end regex, /s is dotall mode - a dot metacharacter
         *                     # in the pattern matches all characters, including newlines.
         *                     # Without it, newlines are excluded
         */
        // Note we don't capture any groups, therefore the regex captures the
        // whole (repeating) pattern that starts with an optional AND|OR|NOT and ends with a closing )
        // Note, to escape a char we need to use 4 backslahses: http://php.net/manual/en/regexp.reference.escape.php

        // preg_match_all
        // - Returns the number of full pattern matches (which might be zero),
        // or FALSE if an error occurred.
        // - Default PREG_PATTERN_ORDER orders results so that $groups[0] is an
        // array of full pattern matches, $groups[1] is an array of strings
        // matched by the first parenthesized subgroup, and so on. Since we
        // are not capturing any sub-groups, only $groups[0] has matched values.
        $patternMatchCount = preg_match_all($patternMatchRepeat, $string, $groups);
        if($patternMatchCount === FALSE){
            throw new \InvalidArgumentException("This is not a valid extensions expression. Please see the wiki for information on valid expressions.
                    \nhttps://wiki.egi.eu/wiki/GOCDB/Release4/Development/ExtensibilityMechanism#PI_Examples\n\n");
        }

        // Returns all the values from the array and indexes the array numerically
        $matches = array_values($groups[0]);
        // Sample array, note that not all groups have a leading operator yet
        /*
         $matches[0] = "AND ( color: \) #888; )"
         $matches[1] = "OR ( body \( color: \( #333; \) )"
         $matches[2] = "NOT ( body \( color=\= \( #333; \) )"
         $matches[3] = "( help\ me \(forest\) help me )"
         $matches[4] = "( color: blue;\(\)


          )"
         $matches[5] = "( color: blue;\(\) \ )"
         $matches[6] = "( \ )"
         $matches[7] = "( \ )"
         $matches[8] = "OR( tab   tab )"
         $matches[9] = "(\\)"
         $matches[10] = "(\\)"
         $matches[11] = "NOT(\\////)"
        */

        // Foreach group, prepend an operator (if non is specified, use last
        // encountered operator) and trim leading/trailing parethesis chars
        $lastEncounteredPredicate = 'AND';  //default if not specified
        $normalizedKV = array();
        foreach($matches as $match){
            $firstChar = substr($match, 0, 1);
            //echo $firstChar; // one of the following: ( A O N
            if($firstChar == '('){
                $predicate = $lastEncounteredPredicate;
            } else if(strtolower($firstChar) == 'a'){
                $predicate = 'AND';
            } else if(strtolower($firstChar) == 'o'){
                $predicate = 'OR';
            } else if(strtolower($firstChar) == 'n'){
                $predicate = 'NOT';
            } else {
                throw new \InvalidArgumentException('Invalid expression, could not extract predicate');
            }
            $lastEncounteredPredicate = $predicate;
            //echo " ".$predicate."\n";

            // Find the position of the first occurrence of '(' (zero offset)
            $openingParenthesisPos = strpos($match, '(');
            if($openingParenthesisPos === FALSE){
                throw new \InvalidArgumentException('Invalid expression, could not extract starting parenthesis');
            }
            // extract just the '(xxxxxx)' part of the expression
            $keyValPair = substr($match, $openingParenthesisPos, strlen($match) );
            // trim the leading and trailing parenthesis
            $keyValPairNoParenthesis = substr($keyValPair, 1, strlen($keyValPair)-2);
            //echo $predicate.' '.$keyValPairNoParenthesis."\n";

            // Returns a string with backslashes stripped off. (\' becomes ' and so on.)
            // Double backslashes (\\) are made into a single backslash (\).
            // todo
            //$keyValPairNoParenthesis = stripslashes($keyValPairNoParenthesis);

            // if($this->splitParethesisGroupOn != null)
            // explode/split the k=v pair on the first occurrence of the '=' char


            // Validate the values of the key and value

            // build expected/normalized array
            $pred_kv = array();
            $pred_kv[] = $predicate;
            $pred_kv[] = $keyValPairNoParenthesis;
            $normalizedKV[] = $pred_kv;
        }
        // $normalizedKV sample. Note that the internal partheneis are escaped,
        // probably need to iterate the values and replace all '\(' '\)' with
        // '(' and ')'
        /*
         Array
            (
                [0] => Array
                    (
                        [0] => AND
                        [1] =>  color: \) #888;
                    )

                [1] => Array
                    (
                        [0] => OR
                        [1] =>  body \( color: \( #333; \)
                    )

                [2] => Array
                    (
                        [0] => NOT
                        [1] =>  body \( color=\= \( #333; \)
                    )

                [3] => Array
                    (
                        [0] => NOT
                        [1] =>  help\ me \(forest\) help me
                    )

                [4] => Array
                    (
                        [0] => NOT
                        [1] =>  color: blue;\(\)



                    )

                [5] => Array
                    (
                        [0] => NOT
                        [1] =>  color: blue;\(\) \
                    )

                [6] => Array
                    (
                        [0] => NOT
                        [1] =>  \
                    )

                [7] => Array
                    (
                        [0] => NOT
                        [1] =>  \
                    )

                [8] => Array
                    (
                        [0] => OR
                        [1] =>  tab tab
                    )

                [9] => Array
                    (
                        [0] => OR
                        [1] => \\
                    )

                [10] => Array
                    (
                        [0] => OR
                        [1] => \\
                    )

                [11] => Array
                    (
                        [0] => NOT
                        [1] => \\////
                    )

            )
         */
        return $normalizedKV;
    }

}
