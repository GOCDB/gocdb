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

require_once __DIR__ . '/../../validation/IValidator.php';

/**
 * Validator for a 'Key=Value' pair expressiosn (assumes no enclosing chars).
 * Used to validate a k=v pairs in the 'extensions' URL parameter of the API.
 *
 * @author David Meredith
 */
class KeyValueValidator implements \IValidator {

    /**
     * @see \IValidator::supports($object)
     * @param string $object
     * @return boolean
     */
    public function supports($object) {
        if (is_string($object)) {
            return true;
        }
        return false;
    }

    /**
     * Validate the given 'key=value' pair and add new elements to the
     * given errors array if there are any validation errors.
     * <p>
     * A k=v pair is valid if it follows the following rules:
     * <ul>
     *   <li>String contains one or more equals '=' chars
     *   (the key=val string is split on the first equals char moving left to right)</li>
     *   <li>The key=val pair has no enclosing chars, e.g. no leading/trailing parethesis</>
     *   <li>The key conforms to the regex: <code>/^([a-zA-Z0-9\s@_\-\[\]\+\.]{1,255})$/</code>
     *   (1 to 255 alpha numeric chars and selected chars)</li>
     *   <li>The value conforms to the regex (any char 0 to 255 times including
     *     newline/whitespace): <code>/^([\s\S]{0,255})$/</code></li>
     * </ul>
     *
     * @see \IValidator::validate($object, $errors)
     * @param string $keyValuePair
     * @param array $errors
     * @return array Will be same size as given array if no errors
     * @throws \LogicException on coding errors
     */
    public function validate($keyValuePair, $errors) {
         //<li>The value conforms to the regex: <code>/^([^'\";\(\)`]{0,255})$/</code>
         //(any char 0 to 255 times except <code>'";()`</code></li>

        if (!is_array($errors)) {
            throw new \LogicException("Invalid errors array");
        }
        if (!$this->supports($keyValuePair)) {
            throw new \LogicException("Invalid key value pair");
        }

        // key is quite restrictive, only alpha-numerics and some chars considered useful for keys and whitespace
        $keyregex = "/^([a-zA-Z0-9\s@_\-\[\]\+\.]{1,255})$/";
        // to be restrictive on what vals allowed, use:
        //$valregex = "/^([^'\";\(\)`]{0,255})$/";  //0 to allow for no input which will repsent wildcard
        // any char 0 to 255 times
        $valregex = "/^([\s\S]{0,255})$/";  //0 to allow for no input which will repsent wildcard

        $keyValuePair = trim($keyValuePair);
        if($keyValuePair == ''){
            $errors[] = 'Invalid key=value pair, no content';
            return $errors;
        }

        // check expression contains an '=' char to perform split on
        if (strpos($keyValuePair, '=') === FALSE) {
            $errors[] = 'Invalid key=value pair, missing = char';
            return $errors;
        }

        // split k=v expression on the first occurence of '=' (to allow other '=' chars in value)
        $namevalueArray = explode('=', $keyValuePair, 2);
        $key = $namevalueArray[0];  // could trim key so ' VOa' becomes 'VOa'
        $val = $namevalueArray[1];  // don't trim val, whitespace is valid part of value
        //print_r('['.$key.'] ['.$val."]\n");
        // validate key
        if (preg_match($keyregex, $key) != 1) {
            $errors[] = ('Invalid key=value pair, invalid key');
            return $errors;
        }
        // validate value
        if (preg_match($valregex, $val) != 1) {
            $errors[] = ('Invalid key=value pair, invalid value');
            return $errors;
        }
        return $errors; // will be empty/no elements added if validation passed
    }

}
