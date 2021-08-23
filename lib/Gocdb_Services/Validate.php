<?php
namespace org\gocdb\services;
/*______________________________________________________
 *======================================================
* File: Validation.php
* Author: John Casson
* Description: Validates objects using the gocdb_schema file.
*
* License information
*
* Copyright  2009 STFC
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
* http://www.apache.org/licenses/LICENSE-2.0
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
/*====================================================== */

class Validate {
    const SCHEMA_XML = '/../../config/gocdb_schema.xml';

    function __construct() {

        # Load schema file

        $this->schemaXml = simplexml_load_file(__DIR__ . $this::SCHEMA_XML);
    }

    /* Validates $fieldValue using validation settings from the GOCDB
     *
    * Checks the particular object (specified by $objectName) for a field
    * ($fieldName) and gets the validation requirements for this field type.
    * Returns a boolean value as to whether $fieldValue is valid for the field
    * type specified in $fieldName.
    */
    public function validate($objectName, $fieldName, $fieldValue)
    {
        //Check the length of the field value, if too long, throws exception
        $this->checkFieldLength($objectName, $fieldName, $fieldValue);

        //Check the Type of the field value, where this is defined
        $type = $this->getFieldValue($objectName, $fieldName, 'ftype');
        if (count($type) != 0) {
          if (strtolower($type) == "string") {
            if (!is_string($fieldValue)) {
                throw new \Exception($fieldName . " must be a string.");
            }
          } elseif (strtolower($type) == "boolean") {
            if (!is_bool($fieldValue)) {
                throw new \Exception($fieldName . " must be a boolean.");
            }
          } else {
            throw new \Exception("Internal error: validation of data of type $type is not supported by the validation service");
          }
        }

        $regEx = $this->getFieldValue($objectName, $fieldName, 'regex');
        // If there are no checks to perform then $fieldValue must be valid
        if(count($regEx) == 0)
            return true;

        if(!preg_match($regEx, $fieldValue)) {
            return false;
        }

        return true;
    }

    /**
     * Checks the length of inputs against the length specified in the schema.
     * @throws \Exception
     */
    private function checkFieldLength($objectName, $fieldName, $fieldValue){
        $length = $this->getFieldValue($objectName, $fieldName, 'length');

        if(!count($length) == 0){ //only check length if the schema has a length specified in it
            if(strlen($fieldValue)>$length or $length == 0){
                throw new \Exception($fieldName . " may not be more than " . $length . " chracters in length");
            }
        }
    }


    /* Returns an associative array of check names and their parameters.
     * Format: $Checks[Check_Name] = $Check_Parameter
    * i.e. $Checks[Regular_Expression] = "[Y|N]"
    */
    private function getFieldValue($objectName, $fieldName, $valueType)
    {
        $entity = $this->findEntity($objectName, $this->schemaXml);
        $field = $this->findField($fieldName, $entity);
        return $field->$valueType;
    }

    /* Search for the XML for $objectName within Schema XML
     * Throw an exeption if this isn't found. */
    private function findEntity($objectName, $schemaXml)
    {
        foreach($schemaXml->entity as $entity)
        {
            if ((string) $entity->name == $objectName) {
                return $entity;
            }
        }
        throw new \Exception("Object type: $objectName not found in schema XML");
    }


    private function findField($fieldName, $entity)
    {
        foreach($entity->field as $field)
        {
            if(strtoupper((string) $field->fname) == strtoupper($fieldName)) {
                return $field;
            }
        }

        throw new \Exception("Field Name: $fieldName not found in schema XML");
    }
}
