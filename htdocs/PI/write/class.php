<?php
/*______________________________________________________
 *======================================================
 * File: index.php
 * Author: George Ryall
 * Description: Entry point for the write programmatic interface
 *
 * License information
 *
 * Copyright 2016 STFC
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
namespace org\gocdb\services;

require_once __DIR__ . '/../../../lib/Gocdb_Services/Config.php';
require_once __DIR__ . '/../../../lib/Gocdb_Services/Validate.php';
require_once __DIR__ . '/../../../lib/Doctrine/bootstrap.php';
require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';


// Set the timezone to UTC for rendering all times/dates in PI.
// The date-times stored in the DB are in UTC, however, we still need to
// set the TZ to utc when re-readig those date-times for subsequent
// getTimestamp() calls; without setting the TZ to UTC, the calculated timestamp
// value will be according to the server's default timezone (e.g. GMT).
date_default_timezone_set("UTC");

/*
* http_response_code() is implemented in php from php 5.4.0 onwards, when we
* upgrade, this block (and the associated file) can be deleted (having checked that  http_response_code
* implments all the status codes that we may wish to use).
* See http://stackoverflow.com/questions/3258634/php-how-to-send-http-response-code
* and http://php.net/http_response_code#107261
*/
if (!function_exists('http_response_code')) {
    require_once __DIR__ . '/responseCode.php';
}

#TODO php errors return a  200 code! see: http://stackoverflow.com/questions/2331582/catch-php-fatal-error & https://bugs.php.net/bug.php?id=50921

class PIWriteRequest {
    private $requestURL=null;
    private $baseUrl;
    #TODO add correct url for documentation (also write it!)
    #TODO: move the docs url to the local_info.xml
    private $docsURL="https://wiki.egi.eu/wiki/GOCDB/PI/Technical_Documentation";
    private $apiVersion=null;
    private $entityType=null;
    private $entityID=null;
    private $entityProperty=null;
    private $entityPropertyKey=null;
    private $requestMethod=null;
    private $userIdentifier=null;
    private $userIdentifierType=null;

    #Either a value of a single property will be specified, or a series of key/values
    private $entityPropertyValue=null;
    private $entityPropertyKVArray=null;

    private $httpResponseCode=500;
    #Note: $supportedAPIVersions are defined in lower case
    private $supportedAPIVersions= array("v5");
    private $supportedRequestMethods= array("POST","PUT","DELETE");

    public function __construct() {
        # returns the base portal URL as defined in conf file
        $configServ = new config();
        $this->baseUrl = $configServ->getServerBaseUrl() . "/writeDev"; #TODO: This will need to be the correct url once the location of the write API is settled
    }

    /**
    * Process the API request
    *@throws \Exception
    */
    function processChange() {
        try {
            $this->getAndProcessURL();
            $this->getRequestContent();
            $this->validateEntityTypePropertyAndPropValue();
            $this->updateEntity();

        } catch (\Exception $e) {

            #Set the HTTP response code
            http_response_code($this->httpResponseCode);

            #Set the content type
            header("Content-Type:application/json");

            #echo the error as JSON
            $errorArray['Error']= array('Code' => $this->httpResponseCode, 'Message' => utf8_encode($e->getMessage()), 'API-Documentation'=>$this->docsURL);
            echo json_encode($errorArray);

            die();
        }
    }

    /**
    *Takes the URL of the API request and processes it into variables
    *@throws \Exception
    */
    function getAndProcessURL() {
        $genericURLFormatErrorMessage = "API requests should take the form $this->baseUrl" .
            "/APIVERSION/ENTITYTYPE/ENTITYID/ENTITYPROPERTY/[ENTITYPROPERTYKEY]. " .
            "For more details see: $this->docsURL";

        #If the request isn't set then no url parameters have been used
        if (isset($_REQUEST['request'])) {
            #Note that apache will collapse multiple /'s into a single /
            $this->requestURL = $_REQUEST['request'];
        }
        else {
            $this->httpResponseCode=400;
            throw new \Exception($genericURLFormatErrorMessage);
        }

        #Split the request into seperate parts, with the slash as a seperator
        $requestArray = explode("/",$this->requestURL);

        #We should probably ignore trailing slashes, which will generate an empty array element
        #Using strlen so that '0' is not removed
        $requestArray = array_filter($requestArray, 'strlen');

        #The request url should have either 4 or 5 elements
        if(!in_array(count($requestArray),array(4,5))){
            $this->httpResponseCode=400;
            throw new \Exception(
                "Request url has the wrong number of elements. " . $genericURLFormatErrorMessage
            );
        }

        #Process request contents
        #API Version
         $this->apiVersion = strtolower($requestArray[0]);

        #check api version is supported
        if(!in_array($this->apiVersion,$this->supportedAPIVersions)) {
            $this->httpResponseCode=400;
            throw new \Exception(
                "Unsupported API version: \"$requestArray[0]\". "
                . $genericURLFormatErrorMessage
            );
        }

        #entityType
        $this->entityType = strtolower($requestArray[1]);

        #entityID - Also check that an integer has been specified
        if(is_numeric($requestArray[2])&&(intval($requestArray[2])==floatval($requestArray[2]))) {
            $this->entityID = intval($requestArray[2]);
        } else {
            $this->httpResponseCode=400;
            throw new \Exception(
                "Entity ID's should be integers. \"$requestArray[2]\" is not an integer. "
                . $genericURLFormatErrorMessage
            );
        }

        #entityProperty
        $this->entityProperty = strtolower($requestArray[3]);

        #entityPropertyKey - note that this is optional
        if(isset($requestArray[4])) {
            $this->entityPropertyKey = $requestArray[4];
        }

        #RequestMethod
        if(in_array(strtoupper($_SERVER['REQUEST_METHOD']),$this->supportedRequestMethods)) {
            $this->requestMethod=strtoupper($_SERVER['REQUEST_METHOD']);
        } elseif (strtoupper($_SERVER['REQUEST_METHOD'])=="GET") {
            $this->httpResponseCode=405;
            throw new \Exception(
                "\"GET\" is not currently a supported request method. " .
                "Try the other API: https://wiki.egi.eu/wiki/GOCDB/PI/Technical_Documentation"
            );
        }else {
            $this->httpResponseCode=405;
            throw new \Exception(
                "\"" . $_SERVER['REQUEST_METHOD'] .
                "\" is not currently a supported request method. For more details see: $this->docsURL"
            );
        }
    }


    function getRequestContent() {
        $genericError = "For more information on correctly formatting your request, see $this->docsURL";

        #Get the contents of the Request
        #see http://php.net/manual/en/wrappers.php.php
        $requestContents = file_get_contents('php://input');

        #Convert the request to JSON - note depth of 2, as current, and expected,
        #use cases don't contain any nested properties
        $requestArray = json_decode($requestContents,true,2);

        #json_decode returns null for invalid JSON and $requestContents will be empty if there was no request body
        if(!is_null($requestArray)) {
            /*The general case expects a single value to be given. In the specific case
            * of updating extension properties where no specific property is specified
            * in the url (and so now in $this->entityPropertyKey) we expect multiple
            * key/value pairs. We use $this->entityPropertyValue and $this->entityPropertyKVArray
            * frespectivly for these cases.
            */
            if($this->entityProperty<>'extensionproperties'||!is_null($this->entityPropertyKey)) {
                if (isset($requestArray['value']) && count($requestArray)==1) {
                    $this->entityPropertyValue=$requestArray['value'];
                } elseif(!isset($requestArray['value'])) {
                    $this->httpResponseCode=400;
                    throw new \Exception("A value for \"$this->entityProperty\" should be provided. " . $genericError );
                } else {
                    $this->httpResponseCode=400;
                    throw new \Exception("Request message cotnained more than one object. " . $genericError );
                }
            } else {
                if(!empty($requestArray)) {
                    $this->entityPropertyKVArray=$requestArray;
                } else {
                    $this->httpResponseCode=400;
                    throw new \Exception("Please specify the properties you wish to change. " . $genericError );
                }

            }
        } elseif (!empty($requestContents)) {
            $this->httpResponseCode=400;
            throw new \Exception( "The JSON message is not correctly formatted. " . $genericError );
        }
    }

    /**
    * Carries out the business logic around using the validate service to check the
    * entity Property and property value are valid.
    */
    function validateEntityTypePropertyAndPropValue() {
        $validateServ = new validate();

        #Because of how extension properties appear in the schema (as a seperate entity), we need to change the entity name
        if ($this->entityProperty == 'extensionproperties') {
            $objectType = $this->entityType . 'property';
        } else {
            $objectType = $this->entityType;
        }

        #Now we itterate through all the possible cases.
        #The first is that a entity key has been provided, currently only extension properties support this
        #The second is that there is no key, and a single value has been provided
        #The third is that a series of key/value pairs have been provided
        #The fourth is a delete where no array or value has been specified, this is only currently supported for extension properties
        #The final statement deals with the case where a key has been specified as well as multiple values or where both a single value and an array are set. Neither should happen.
        if (!is_null($this->entityPropertyKey) && !is_null($this->entityPropertyValue) && is_null($this->entityPropertyKVArray)) {
            #only currently supported for extension properties
            if ($this->entityProperty == 'extensionproperties') {
                $this->validateWithService($objectType,'name',$this->entityPropertyKey,$validateServ);
                $this->validateWithService($objectType,'value',$this->entityPropertyValue,$validateServ);
            } else {
                $this->httpResponseCode=400;
                throw new \Exception("The API currently only supports specifying a key in the url for extension properties. For help see: $this->docsURL");
            }
        } elseif (is_null($this->entityPropertyKey) && !is_null($this->entityPropertyValue) && is_null($this->entityPropertyKVArray)) {
            $this->validateWithService($objectType,$this->entityProperty,$this->entityPropertyValue,$validateServ);
        } elseif (is_null($this->entityPropertyKey) && is_null($this->entityPropertyValue) && !is_null($this->entityPropertyKVArray)) {
            #only currently supported for extension properties
            if ($this->entityProperty == 'extensionproperties') {
                foreach ($this->entityPropertyKVArray as $key => $value) {
                    $this->validateWithService($objectType,'name',$key,$validateServ);
                    #Values can be null in the case of DELETEs
                    if (!(empty($value) && $this->requestMethod == 'DELETE')) {
                        $this->validateWithService($objectType,'value',$value,$validateServ);
                    }
                }
                unset($value);
            } else {
                $this->httpResponseCode=400;
                throw new \Exception("The API currently only supports specifying an array of values for extension properties. For help see: $this->docsURL");
            }
        } elseif (is_null($this->entityPropertyValue) && is_null($this->entityPropertyKVArray)) {
            #Only delete methods support not providing values of any kind
            if ($this->requestMethod == 'DELETE') {
                #only currently supported for extension properties
                if ($this->entityProperty != 'extensionproperties') {
                    $this->httpResponseCode=400;
                    throw new \Exception("The API currently only supports deleting without specifying values for extension properties. For help see: $this->docsURL");
                }
            } else {
                $this->httpResponseCode=400;
                throw new \Exception("For methods other than 'DELETE' a value or set of values must be provided. For help see: $this->docsURL");
            }

        } else {
            $this->httpResponseCode=500;
            throw new \Exception("The validation process has failed due to an error in the internal logic. Please contact the administrator.");
        }
    }

    /**
    * Uses the validate service to check the object type, property, and property value
    * using the validate service.
    */
    function validateWithService($objectType,$objectProperty,$propertyValue,$validateService) {
        $genericError = ". For more information see $this->docsURL.";

        try {
            #validate throws erros if the entity or entity property can't be found and
            #returns either true or false depending on if the value is valid.
            $valid = $validateService->validate(strtolower($objectType),strtoupper($objectProperty),$propertyValue);
        } catch (\Exception $e) {
            $this->httpResponseCode=400;
            throw new \Exception("Validation Error. " . $e->getMessage() . $genericError);
        }

        if(!$valid) {
            $this->httpResponseCode=400;
            throw new \exception("The value ($propertyValue) you have specified is not valid$genericError");
        }
    }

    function updateEntity() {

        #Authentication
        #$this->userIdentifier will be empty if the unser doesn't provide a credential
        #If in the future we implement API keys, then I suggest we only look for
        #the DN if the API key isn't presented.
        $this->userIdentifier = Get_User_Principle_PI();
        $this->userIdentifierType = 'X509';

        /*
        * We don't currently allow any access to unauthenticated users, so for now
        * we can simply refuse unauthenticated users. In the future we could look
        * to authenticate everything except 'GET' requests and then process 'GETs'
        * on a case by case basis.
        */
        if (empty($this->userIdentifier)) {
            $this->httpResponseCode = 403; #yes 403 - 401 is not appropriate for X509 authentication
            throw new \Exception(
                "You need to be authenticated to access this resource. " .
                "Please provide a valid IGTF X509 Certificate");
        }

        #TODO: put these error message texts into a seperate function
        $entityTypePropertyComboNotSupportedText =
            "Updating a " . $this->entityType. "'s $this->entityProperty is " .
            "not currently supported through the API, for details of " .
            "the currently supported methods see: $this->docsURL";

        $entityTypePropertyMethodNotSupportedText =
            "\"" . $_SERVER['REQUEST_METHOD'] . "\" is not currently a supported " .
            "request method for altering" . $this->entityType. "'s " .
            $this->entityProperty . " . For more details see: $this->docsURL";

        #We will need a site service in every case - in order to carry out authoristion
        $siteServ = \Factory::getSiteService();

        switch($this->entityType) {
            case 'site': {

                #Identify the site
                try {
                    $site = $siteServ->getSite($this->entityID);
                } catch(\Exception $e){
                    $this->httpResponseCode=404;
                    throw new \Exception("A site with the specified id could not be found");
                }

                #Authorisation
                try {
                    $siteServ->checkAuthroisedAPIIDentifier($site, $this->userIdentifier, $this->userIdentifierType);
                } catch(\Exception $e){
                    $this->httpResponseCode=403;
                    throw $e;
                }

                switch($this->entityProperty) {
                    case 'extensionproperties':{
                        #TWO CASES: one there is a single value, the other mutliple - create an array for the single, use array for multiple.
                        if (is_null($this->entityPropertyKey)) {
                            $extensionPropKVArray = $this->entityPropertyKVArray;
                        } else {
                            $extensionPropKVArray = array($this->entityPropertyKey => $this->entityPropertyValue);
                        }

                        #Based on Request types run either an add with or without overwritebv or a remove.
                        switch ($this->requestMethod) {
                            case 'POST':{
                                #Post requests will fail if the property with that value already exists
                                #TODO:This will return the wrong error code if somethign else goes wrong
                                try {
                                    $siteServ->addPropertiesAPI($site, $extensionPropKVArray, true, $this->userIdentifierType, $this->userIdentifier);
                                    break;
                                } catch(\Exception $e) {
                                    $this->httpResponseCode=409;
                                    throw $e;
                                }
                            }
                            case 'PUT':{
                                $siteServ->addPropertiesAPI($site, $extensionPropKVArray, false, $this->userIdentifierType, $this->userIdentifier);
                                break;
                            }
                            case 'DELETE':{
                                #Convert the array of K/V pairs into an array of properties. If the array is empty, then we want to delte all the properties
                                if (empty($extensionPropKVArray)) {
                                    $extensionPropArray = $site->getSiteProperties()->toArray();
                                } else {
                                    $extensionPropArray = array();
                                    foreach ($extensionPropKVArray as $key => $value) {

                                        $extensionProp = $siteServ->getPropertyByKeyAndParent($key, $site);
                                        if (is_null($extensionProp)) {
                                            $this->httpResponseCode=404;
                                            throw new \Exception(
                                                "A property with key \"$key\" could not be found for "
                                                . $site->getName() . ". No properties have been deleted. "
                                            );
                                        }

                                        #If a value has been provided for the property, it needs to matched
                                        if (!empty($value) && ($extensionProp->getKeyValue() != $value)) {
                                            $this->httpResponseCode=409;
                                            throw new \Exception(
                                                "The value provided for the property with key \"$key\" does " .
                                                "not match the existing one. No Properties have been deleted."
                                            );
                                        }

                                        $extensionPropArray[] = $extensionProp;
                                    }
                                }

                                $siteServ->deleteSitePropertiesAPI($site, $extensionPropArray, $this->userIdentifierType, $this->userIdentifier);

                                break;
                            }
                            default: {
                                $this->httpResponseCode=405;
                                throw new \Exception($entityTypePropertyMethodNotSupportedText);
                                break;
                            }
                        }
                        break;
                    }
                    default: {
                        $this->httpResponseCode=501;
                        throw new \exception($entityTypePropertyComboNotSupportedText);
                    }
                }
                break;
            }
            case 'service': {
                require_once __DIR__ . '/../../../lib/Gocdb_Services/ServiceService.php';
                $serviceServ = \Factory::getServiceService();

                #Identify the service
                try {
                    $service = $serviceServ->getService($this->entityID);
                } catch (\Exception $e) {
                    $this->httpResponseCode=404;
                    throw new \Exception("A service with the specified id could not be found");
                }

                #Authorisation
                try {
                    $siteServ->checkAuthroisedAPIIDentifier($service->getParentSite(), $this->userIdentifier, $this->userIdentifierType);
                } catch(\Exception $e){
                    $this->httpResponseCode=403;
                    throw $e;
                }

                switch($this->entityProperty) {
                    case 'extensionproperties':{
                        #TWO CASES: one there is a single value, the other mutliple - create an array for the single, use array for multiple.
                        if (is_null($this->entityPropertyKey)) {
                            $extensionPropKVArray = $this->entityPropertyKVArray;
                        } else {
                            $extensionPropKVArray = array($this->entityPropertyKey => $this->entityPropertyValue);
                        }

                        #Based on Request types run either an add with or without overwritebv or a remove.
                        switch ($this->requestMethod) {
                            case 'POST':{
                                #Post requests will fail if the property with that value already exists
                                #TODO:This will return the wrong error code if somethign else goes wrong
                                try {
                                    $serviceServ->addServicePropertiesAPI($service, $extensionPropKVArray, true, $this->userIdentifierType, $this->userIdentifier);
                                    break;
                                } catch(\Exception $e) {
                                    $this->httpResponseCode=409;
                                    throw $e;
                                }
                            }
                            case 'PUT':{
                                $serviceServ->addServicePropertiesAPI($service, $extensionPropKVArray, false, $this->userIdentifierType, $this->userIdentifier);
                                break;
                            }
                            case 'DELETE':{
                                #Convert the array of K/V pairs into an array of properties. If the array is empty, then we want to delte all the properties
                                if (empty($extensionPropKVArray)) {
                                    $extensionPropArray = $service->getServiceProperties()->toArray();
                                } else {
                                    $extensionPropArray = array();
                                    foreach ($extensionPropKVArray as $key => $value) {

                                        $extensionProp = $serviceServ->getServicePropertyByKeyAndParent($key, $service);
                                        if (is_null($extensionProp)) {
                                            $this->httpResponseCode=404;
                                            throw new \Exception(
                                                "A property with key \"$key\" could not be found for "
                                                . $service->getHostName() . ". No properties have been deleted. "
                                            );
                                        }

                                        #If a value has been provided for the property, it needs to matched
                                        if (!empty($value) && ($extensionProp->getKeyValue() != $value)) {
                                            $this->httpResponseCode=409;
                                            throw new \Exception(
                                                "The value provided for the property with key \"$key\" does " .
                                                "not match the existing one. No Properties have been deleted."
                                            );
                                        }

                                        $extensionPropArray[] = $extensionProp;
                                    }
                                }

                                $serviceServ->deleteServicePropertiesAPI($service, $extensionPropArray, $this->userIdentifierType, $this->userIdentifier);

                                break;
                            }
                            default: {
                                $this->httpResponseCode=405;
                                throw new \Exception($entityTypePropertyMethodNotSupportedText);
                                break;
                            }
                        }
                        break;
                    }
                    default: {
                        $this->httpResponseCode=501;
                        throw new \exception($entityTypePropertyComboNotSupportedText);
                    }
                }
                break;
            }
            case 'endpoint': {
                require_once __DIR__ . '/../../../lib/Gocdb_Services/ServiceService.php';
                $serviceServ = \Factory::getServiceService();

                #Identify the SE
                try {
                    $endpoint = $serviceServ->getEndpoint($this->entityID);
                } catch (\Exception $e) {
                    $this->httpResponseCode=404;
                    throw new \Exception("A endpoint with the specified id could not be found");
                }

                #Authorisation
                try {
                    $siteServ->checkAuthroisedAPIIDentifier($endpoint->getService()->getParentSite(), $this->userIdentifier, $this->userIdentifierType);
                } catch(\Exception $e){
                    $this->httpResponseCode=403;
                    throw $e;
                }

                switch($this->entityProperty == 'extensionproperties') {
                    case 'extensionproperties':{
                        #TWO CASES: one there is a single value, the other mutliple - create an array for the single, use array for multiple.
                        if (is_null($this->entityPropertyKey)) {
                            $extensionPropKVArray = $this->entityPropertyKVArray;
                        } else {
                            $extensionPropKVArray = array($this->entityPropertyKey => $this->entityPropertyValue);
                        }

                        #Based on Request types run either an add with or without overwritebv or a remove.
                        switch ($this->requestMethod) {
                            case 'POST':{
                                #Post requests will fail if the property with that value already exists
                                #TODO:This will return the wrong error code if somethign else goes wrong
                                try {
                                    $serviceServ->addEndpointPropertiesAPI($endpoint, $extensionPropKVArray, true, $this->userIdentifierType, $this->userIdentifier);
                                    break;
                                } catch(\Exception $e) {
                                    $this->httpResponseCode=409;
                                    throw $e;
                                }
                            }
                            case 'PUT':{
                                $serviceServ->addEndpointPropertiesAPI($endpoint, $extensionPropKVArray, false, $this->userIdentifierType, $this->userIdentifier);
                                break;
                            }
                            case 'DELETE':{
                                #Convert the array of K/V pairs into an array of properties. If the array is empty, then we want to delte all the properties
                                if (empty($extensionPropKVArray)) {
                                    $extensionPropArray = $endpoint->getEndpointProperties()->toArray();
                                } else {
                                    $extensionPropArray = array();
                                    foreach ($extensionPropKVArray as $key => $value) {

                                        $extensionProp = $serviceServ->getEndpointPropertyByKeyAndParent($key, $endpoint);
                                        if (is_null($extensionProp)) {
                                            $this->httpResponseCode=404;
                                            throw new \Exception(
                                                "A property with key \"$key\" could not be found for "
                                                . $endpoint->getName() . ". No properties have been deleted. "
                                            );
                                        }

                                        #If a value has been provided for the property, it needs to matched
                                        if (!empty($value) && ($extensionProp->getKeyValue() != $value)) {
                                            $this->httpResponseCode=409;
                                            throw new \Exception(
                                                "The value provided for the property with key \"$key\" does " .
                                                "not match the existing one. No Properties have been deleted."
                                            );
                                        }

                                        $extensionPropArray[] = $extensionProp;
                                    }
                                }

                                $serviceServ->deleteendpointPropertiesAPI($endpoint, $extensionPropArray, $this->userIdentifierType, $this->userIdentifier);

                                break;
                            }
                            default: {
                                $this->httpResponseCode=405;
                                throw new \Exception($entityTypePropertyMethodNotSupportedText);
                                break;
                            }
                        }
                        break;
                    }
                    default: {
                        $this->httpResponseCode=501;
                        throw new \exception($entityTypePropertyComboNotSupportedText);
                    }
                }
                break;
            }
            default: {
                $this->httpResponseCode=501;
                throw new \exception(
                    "Updating " . $this->entityType. "s is not currently supported " .
                    "through the API, for details of the currently supported methods " .
                    "see: $this->docsURL"
                );
            }
        }

        #TODO: add this into the logic above and feed it the entities and return the entities
        $this->returnResult();
    }

    function returnResult() {
        #TODO: return the entity that's been changed or created, for now just
        #return the no content http code (delete operations should return nothing and a 204)
        #$this->httpResponseCode = 204;
        $this->httpResponseCode=200;

        #Set the HTTP response code
        http_response_code($this->httpResponseCode);

        #Set the Content-type in the header (204 response should have no content)
        if($this->httpResponseCode == 204) {
            #This removes the content-type from the header
            header("Content-Type:");
        } else {
            header("Content-Type:application/json");
        }
    }
}
