<?php
namespace org\gocdb\services;

/*
 * Copyright © 2011 STFC Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */


/**
 *
 * @author James McCarthy
 */
class Helpers{
	
	/**
	 * Takes a built query with bind variables and an array of variables
	 * with bind identities and binds all values.
	 *
	 * @param 2D Array $bindValues
	 * @param QueryBuilder $query        	
	 * @return BoundQueryBuilder
	 */
	public function bindValuesToQuery($bindValues, $query) {
	    
		foreach( $bindValues as $bind ) {
			$values = $bind;
			// Check value is string not time/date object
			if (is_string ( $values [1] )) {
				
				// Check for multiple comma seperated values
				if (strpos ( $values [1], ',' )) {
					$exValues = explode ( ',', $values [1] );
					$query->setParameter ( $values [0], $exValues );
				} else {
					// echo "Binding at: ".$values[0]. " With: ".$values[1]. "\n\n";
					$query->setParameter ( $values [0], $values [1] );
				}
			//If value was object bind it as is	
			} else {
				$query->setParameter ( $values [0], $values [1] );
			}
		}
		return $query;
	}
	
	/**
	 * Ensure that testParams only contain array keys that are supported as listed in $supportedParams.
	 * If an unsupported parameter is detected, then die with a message.
	 *
	 * @param type $supportedParams
	 *        	A single dimensional array of supported/expected parameter names
	 * @param type $testParams
	 *        	An associatative array of key => value pairs (parameter key, value)
	 * @throws InvalidArgument\Exception if either of the given args are not arrays.
	 */
	public function validateParams($supportedParams, $testParams) {
		if (! is_array ( $supportedParams ) || ! is_array ( $testParams )) {
			throw new \InvalidArgumentException (); // InvalidArgument\Exception;
		}
	
		// Check the parmiter keys are supoported
		$testParamKeys = array_keys ( $testParams );
		foreach ( $testParamKeys as $key ) {
			// if givenkey is not defined in supportedkeys it is unsupported
			if (! in_array ( $key, $supportedParams )) {
				echo '<error>Unsupported parameter: ' . $key . '</error>';
				die ();
			}
		}
	
		// Check that the paramater does not contain invalid chracters
		$testParamValues = array_values ( $testParams );
		foreach ( $testParamValues as $value ) {
			if (! preg_match ( "/^[^\"'`]*$/", $value )) {
				echo '<error>Unsuported chracter in value: ' . $value . '</error>';
				die ();
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
        if($value != null && $value != "") {  
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
    	if($value != "") {
    		$extension = $xmlParent->addChild("Extension");
    		$extension->addChild("LocalID", $tagName);
    		$extension->addChild("Key", $tagName);
    		$extension->addChild("Value", $value);
    	}
    }
	
}