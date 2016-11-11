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

    /* Validates $Field_Value using validation settings from the GOCDB
     *
    * Checks the particular object (specified by $Object_Name) for a field
    * ($Field_Name) and gets the validation requirements for this field type.
    * Returns a boolean value as to whether $Field_Value is valid for the field
    * type specified in $Field_Name.
    */
    public function validate($Object_Name, $Field_Name, $Field_Value)
    {
        //Check the length of the field value, if too long, throws exception
        $this->checkFieldLength($Object_Name, $Field_Name, $Field_Value);

        $RegEx = $this->Get_Field_value($Object_Name, $Field_Name, 'regex');
        // If there are no checks to perform then $Field_Value must be valid
        if(count($RegEx) == 0)
            return true;

        if(!preg_match($RegEx, $Field_Value)) {
            return false;
        }

        return true;
    }

    /**
     * Checks the length of inputs against the length specified in the schema.
     * @throws \Exception
     */
    private function checkFieldLength($objectName, $fieldName, $fieldValue){
        $length = $this->Get_Field_value($objectName, $fieldName, 'length');

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
    private function Get_Field_value($Object_Name, $Field_Name, $valueType)
    {
        $Schema_XML = simplexml_load_file(__DIR__ . $this::SCHEMA_XML);
        $Entity = $this->Find_Entity($Object_Name, $Schema_XML);
        $Field = $this->Find_Field($Field_Name, $Entity);
        return $Field->$valueType;
    }

    /* Search for the XML for $Object_Name within Schema XML
     * Throw an exeption if this isn't found. */
    private function Find_Entity($Object_Name, $Schema_XML)
    {
        foreach($Schema_XML->entity as $entity)
        {
            if ((string) $entity->name == $Object_Name) {
                return $entity;
            }
        }
        throw new \Exception("Object type: $Object_Name not found in schema XML");
    }


    private function Find_Field($Field_Name, $Entity)
    {
        foreach($Entity->field as $Field)
        {
            if(strtoupper((string) $Field->fname) == strtoupper($Field_Name)) {
                return $Field;
            }
        }

        throw new \Exception("Field Name: $Field_Name not found in schema XML");
    }
}