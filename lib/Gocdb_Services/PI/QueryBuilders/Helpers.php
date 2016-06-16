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

/**
 *
 * @author James McCarthy
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

}
