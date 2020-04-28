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

// Set the timezone to UTC for rendering all times/dates in PI.
// The date-times stored in the DB are in UTC, however, we still need to
// set the TZ to utc when re-readig those date-times for subsequent
// getTimestamp() calls; without setting the TZ to UTC, the calculated timestamp
// value will be according to the server's default timezone (e.g. GMT).
date_default_timezone_set("UTC");

#TODO php errors return a  200 code! see: http://stackoverflow.com/questions/2331582/catch-php-fatal-error & https://bugs.php.net/bug.php?id=50921

/**
 * Class to process write API requests.
 *
 * Once an instance of the class is intiated, the function processRequest() should
 * be called. This will need the request method (e.g. POST), teh request URL, the
 * request contents (if provided, in a JSON format, if not then pass null), and an
 * instance of the site service. An array containing a http response code and an
 * object to be returned to the user (which may be null).
 *
 * @author George Ryall <github.com/GRyall>
 */
class PIWriteRequest {
    #Note: $supportedAPIVersions are defined in lower case
    private $supportedAPIVersions= array("v5");
    private $supportedRequestMethods= array("POST","PUT","DELETE");

    private $baseUrl;
    private $docsURL=null;
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

    #Default response is 500, as this will be overwritten in every other case
    private $httpResponseCode=500;

    #The following services are only required for some methods and so have their own setters
    private $serviceService = null;

    #An array that will ultimately be returned to the user
    private $returnObject=null;

    #An array of generic exception messages used by multiple functions
    private $genericExceptionMessages = array();


    /**
     * construct function for PIWriteREquest class.
     *
     * Uses the config service to set some member variables ($this->baseUrl and
     * $this->docsURL)
     */
    public function __construct() {
        # returns the base portal URL as defined in conf file
        $configServ = new config();
        $this->baseUrl = $configServ->getServerBaseUrl() . "/gocdbpi";
        $this->docsURL = $configServ->getWriteApiDocsUrl();

        #Define some generic exception messages (remaining ones will be generated once entity type and value are known)
        $this->genericExceptionMessages["URLFormat"] =
          "API requests should take the form $this->baseUrl" .
          "/APIVERSION/ENTITYTYPE/ENTITYID/ENTITYPROPERTY/[ENTITYPROPERTYKEY]. " .
          "For more details see: $this->docsURL";
    }

    /**
     * Function called externally in order to process a PI request.
     *
     * Calls a series of privatre functions that process the inputs, then updates
     * the required entities, before finally returning an array containing a http
     * response code and (sometimes) an object for rendering to the user.
     *
     * Catches exceptions and returns them as an object for rendering to the user
     * in an appropriate format.
     *
     * @param  string $method request method (e.g. GET or POST)
     * @param  string $requestUrl url used to access API, only the last section
     * @param  string|null $requestContents contents of the request (JSON String or null)
     * @param  Site $siteService Site Service
     * @param array ('userIdentifier'=><Identifier of user>,'userIdentifierType'=><Type of identifier e.g. X509>)
     * @return array ('httpResponseCode'=><code>,'returnObject'=><object to return to user>)
     */
    public function processRequest($method, $requestUrl, $requestContents, Site $siteService, $authArray) {
        try {
            $this->processURL($method, $requestUrl);
            $this->generateExceptionMessages();
            $this->getRequestContent($requestContents);
            $this->validateEntityTypePropertyAndPropValue();
            $this->checkIfGOCDBIsReadOnlyAndRequestisNotGET();
            $this->getAndSetAuthInfo($authArray);
            $this->updateEntity($siteService);

        } catch (\Exception $e) {
            #For 500 errors, make it explicit it's an internal error
            if ($this->httpResponseCode==500) {
                $message = "Internal error. Please contact the GOCDB administrators. Message: " . $e->getMessage();
            } else {
                $message = $e->getMessage();
            }

            #Return the error as the return object
            $errorArray['Error']= array('Code' => $this->httpResponseCode, 'Message' => utf8_encode($message), 'API-Documentation'=>$this->docsURL);
            $this->returnObject = $errorArray;
        }

        return array(
            'httpResponseCode' => $this->httpResponseCode,
            'returnObject' => $this->returnObject
        );
    }

    /**
     * Processes the url and method of the request.
     *
     * Takes the URL and Method of a request and process them into the member
     * variables $this->httpResponseCode, $this->entityID, $this->entityType,
     * $this->entityProperty, $this->entityPropertyKey, and $this->requestMethod
     * It depends on the member variables $this->supportedAPIVersions and $this->supportedRequestMethods.
     * @param  string $method HTTP method used to access API, e.g. POST
     * @param  string $requestUrl url used to access API, only the last section
     * @throws \Exception
     */
    private function processURL($method, $requestUrl) {
        #Process URL into array of useful values
        $requestArray = $this->processURLtoArray($requestUrl);

        #Process url contents
        #API Version
        $this->processAPIVersion($requestArray[0]);

        #entityType
        $this->entityType = strtolower($requestArray[1]);

        #entityID
        $this->processEntityID ($requestArray[2]);

        #entityProperty
        $this->entityProperty = strtolower($requestArray[3]);

        #entityPropertyKey - note that this is optional
        if(isset($requestArray[4])) {
            $this->entityPropertyKey = $requestArray[4];
        }

        #RequestMethod
        $this->processRequestMethod($method);
    }

  /**
   * Process the contents of the request provided by the client.
   *
   * Process the contents of the JSON provided by the client (and checks that
   * the client has provided some if it should have). Sets the member vaariables
   * $this->entityPropertyValue or $this->entityPropertyKVArray. Relies on the
   * member variables $this->entityProperty, $this->entityPropertyKey.
   *
   * @param  string $requestContents json string provided by client
   * @throws \Exception
   */
  private function getRequestContent($requestContents) {
    $genericError = "For more information on correctly formatting your request, see $this->docsURL";

    #Convert the request to JSON - note depth of 2, as current, and expected,
    #use cases don't contain any nested properties
    $requestArray = json_decode($requestContents,true,2);

    #json_decode returns null for invalid JSON and $requestContents will be empty if there was no request body
    if(is_null($requestArray) && !empty($requestContents)) {
      $this->exceptionWithResponseCode(400,
        "The JSON message is not correctly formatted. " . $genericError
      );
    }

    #If the request contents is empty then there is nothing for us to get
    if(empty($requestContents)) {
      return;
    }

    /* The default case is that a single value (or nothing at all) is specified.
     * But, we first deal with cases where we expect multiple key/value pairs.
     * Single values are stored in $this->entityPropertyValue whilst miultiple
     * values are stored in $this->entityPropertyKVArray.
     */
    switch ($this->entityProperty) {
      case 'extensionproperties':

        //If a property key has been specified, then we don't expect a K/V list
        //and want to make use of the default case
        if(is_null($this->entityPropertyKey)) {
          $this->entityPropertyKVArray=$requestArray;
          break;
        }

      default:

        //If there is not a value in the array at this point throw an exception
        if (!isset($requestArray['value'])) {
          $this->exceptionWithResponseCode(400,
            "A value for \"$this->entityProperty\" should be provided. " .
            "This should be provided in a JSON string {\"value\":\"<value for " .
            "\"$this->entityProperty\">\"}, with no other pairs present." . $genericError
          );
        }

        //If there are additional entiries in our array to the value, throw exception
        if(count($requestArray)>1) {
          $this->exceptionWithResponseCode(400,
            "Only one value for \"$this->entityProperty\" should be provided. " .
            "This should be provided in a JSON string {\"value\":\"<value for " .
            "\"$this->entityProperty\">\"}, with no other pairs present." .
            " If you believe \"$this->entityProperty\" should take multiple key/value ".
            "pairs, check your spelling of \"$this->entityProperty\"" . $genericError
          );
        }

        $this->entityPropertyValue=$requestArray['value'];

        break;
     }
  }

    /**
    * Carries out the business logic around using the validate service to check the
    * entity Property and property value are valid.
    *
    * Relies on the member variables $this->entityProperty, $this->entityType,
    * $this->entityPropertyKey, $this->entityPropertyValue, $this->entityPropertyKVArray,
    * $this->requestMethod.
    *
    * Changes the member variables .
    *
    * @throws \Exception
    */
    private function validateEntityTypePropertyAndPropValue() {
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
                $this->exceptionWithResponseCode(400,
                  "The API currently only supports specifying a key in the url for extension properties. For help see: $this->docsURL"
                );
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
                $this->exceptionWithResponseCode(400,
                  "The API currently only supports specifying an array of values for extension properties. For help see: $this->docsURL"
                );
            }
        } elseif (is_null($this->entityPropertyValue) && is_null($this->entityPropertyKVArray)) {
            #Only delete methods support not providing values of any kind
            if ($this->requestMethod == 'DELETE') {
                #only currently supported for extension properties
                if ($this->entityProperty != 'extensionproperties') {
                    $this->exceptionWithResponseCode(400,
                      "The API currently only supports deleting without specifying values for extension properties. For help see: $this->docsURL"
                    );
                }
            } else {
                $this->exceptionWithResponseCode(400,
                  "For methods other than 'DELETE' a value or set of values must be provided. For help see: $this->docsURL"
                );
            }

        } else {
            $this->exceptionWithResponseCode(500,
              "The validation process has failed due to an error in the internal logic. Please contact the administrator."
            );
        }
    }

    /**
    * Uses the validate service to check the object type, property, and property
    * value using the validate service.
    * @param  string $objectType      the type of the object being validated
    * @param  string $objectProperty  The property being validated
    * @param  string $propertyValue   the value being validated
    * @param  validate service $validateService instance of the validate service
    * @throws \Exception
    */
    private function validateWithService($objectType,$objectProperty,$propertyValue,$validateService) {
        $genericError = ". For more information see $this->docsURL.";

        try {
            #validate throws erros if the entity or entity property can't be found and
            #returns either true or false depending on if the value is valid.
            $valid = $validateService->validate(strtolower($objectType),strtoupper($objectProperty),$propertyValue);
        } catch (\Exception $e) {
            $this->exceptionWithResponseCode(400,"Validation Error. " . $e->getMessage() . $genericError);
        }

        if(!$valid) {
          $this->exceptionWithResponseCode(400,"The value ($propertyValue) you have specified is not valid$genericError");
        }
    }

    /**
     * Function to make the change requested my the API in the DB
     *
     * This function uses the range of member variables set by previous functions (
     * in some instances including additional services) and the site site service
     * in order to make the change that was actually requested.
     * @param  Site   $siteService instance of the site service
     * @throws \Exception
     */
    private function updateEntity(Site $siteService) {
      //We don't currently allow any access to unauthenticated users, so for now
      //we can simply refuse unauthenticated users. In the future we could look
      //to authenticate everything except 'GET' requests and then process 'GETs'
      //on a case by case basis.
      $this->checkUserAuthenticated();


        switch($this->entityType) {
          case 'site': {
            $this->updateSite($siteService);
            break;
          }
          case 'service': {
            $this->updateService($siteService);
            break;
          }
          case 'endpoint': {
            $this->updateEndpoint($siteService);
            break;
          }
          default: {
            $this->exceptionWithResponseCode(501,
              "Updating " . $this->entityType. "s is not currently supported " .
              "through the API, for details of the currently supported methods " .
              "see: $this->docsURL"
            );
          }
        }

        #TODO: return the entity that's been changed or created as $this->returnObject,
        # for now just return the no content http code (delete operations should
        #  return nothing and a 204, others should return the changed object and a 200)
        $this->httpResponseCode = 204;
    }

    /**
    * Takes the URL and turns it into a useful array
    *
    * Also checks some things and tidy's it up
    * @param string $url A section of the url the user accessed the wirte API
    * @throws \exception
    * @return array $requestArray an array of useful things extracted from the url
    */
    private function processURLtoArray($url) {
      #If the request isn't set then no url parameters have been used
      if (is_null($url)) {
        $this->exceptionWithResponseCode(400,$this->genericExceptionMessages["URLFormat"]);
      }

      #Split the request into seperate parts, with the slash as a seperator
      #Note that apache will collapse multiple /'s into a single /)
      $requestArray = explode("/",$url);

      #We should probably ignore trailing slashes, which will generate an empty array element
      #Using strlen so that '0' is not removed
      $requestArray = array_filter($requestArray, 'strlen');

      #The request url should have either 4 or 5 elements
      if(!in_array(count($requestArray),array(4,5))){
        $this->exceptionWithResponseCode(400,
          "Request URL has the wrong number of elements. " . $this->genericExceptionMessages["URLFormat"]
        );
      }

      return $requestArray;
    }

    /**
    * Processes the API Version
    *
    * Takes the API version provided by the user, updates the relevant class
    * property and checks it against supported values
    * @param $versionText the API version from the url the user used to access the API
    * @throws \Exception
    */
    private function processAPIVersion($versionText) {
      $this->apiVersion = strtolower($versionText);

     #Check the API version requested is supported by this version of GOCDB
     if(!in_array($this->apiVersion,$this->supportedAPIVersions)) {
      $this->exceptionWithResponseCode(400,
        "Unsupported API version: \"$this->apiVersion\". " . $this->genericExceptionMessages["URLFormat"]
         );
     }
    }

    /**
     * Processes the entity ID
     *
     * Takes the entity id, checks it is an integer and sets the relevant class
     * property.
     * @param  $entityIDText ID of entity from the url used to access the api
     * @throws \Exception
     */
    private function processEntityID($entityIDText) {
      #also check that an integer has been specified
      if(is_numeric($entityIDText)&&(intval($entityIDText)==floatval($entityIDText))) {
          $this->entityID = intval($entityIDText);
      } else {
        $this->exceptionWithResponseCode(400,
          "Entity ID's should be integers. \"$entityIDText\" is not an integer. "
          . $this->genericExceptionMessages["URLFormat"]
        );
      }
    }

    /**
     * Throws an exception with the given message after setting the response code
     * It will be caught later and given to the user in a readable format
     * TODO:There are better ways of doing this, involving extnding the exception class
     * @param  int    $code    HTTP Response code
     * @param  string $message exception message
     * @throws \Exception
     */
    private function exceptionWithResponseCode($code, $message) {
      $this->httpResponseCode = $code;
      throw new \Exception($message);
    }

    /**
     * Processes the method specified when accessing the APIVERSION
     *
     * Checks method against supported list (giving a specific error for GET) and
     * sets the appropriate class property
     * @param  string $method method specified by user when accessing the api
     */
    private function processRequestMethod($method) {
      if(in_array(strtoupper($method),$this->supportedRequestMethods)) {
          $this->requestMethod=strtoupper($method);
      } elseif (strtoupper($method)=="GET") {
          $this->exceptionWithResponseCode(405,
            "\"GET\" is not currently a supported request method. " .
            "Try the other API: https://wiki.egi.eu/wiki/GOCDB/PI/Technical_Documentation"
          );
      }else {
        $this->exceptionWithResponseCode(405,
          "\"" . $method . "\" is not currently a supported request method. For more details see: $this->docsURL"
        );
      }
    }


    /**
     * Function to allow a serviceService to be set.
     *
     * The service service is only required for calls that change services or
     * endpoints, so we allow it to not be set and provide a setter.
     * @param ServiceService $serviceService Instance of service service
     */
    public function setServiceService (ServiceService $serviceService) {
        $this->serviceService = $serviceService;
    }

    /**
     * Function to check the service service is set and throw error if not.
     *
     * The service service is only required for calls that change services or
     * endpoints, so we allow it to not be set and provide a setter.
     * @throws \Exception
     */
    private function checkServiceServiceSet () {
        if(is_null($this->serviceService)) {
          $this->exceptionWithResponseCode(500,
            "Internal error: The service service has not been set. Please contact a GOCDB administrator and report this error."
          );
        }
    }

    /**
    * Returns true if portal is read only portal is read only
    *
    * @return boolean
    */
    private function portalIsReadOnly() {
        $configServ = new \org\gocdb\services\Config();
        return $configServ->IsPortalReadOnly();
    }

    /**
    * Throws error if portal/GOCDB is in read only mode
    *
    * @throws \Exception
    */
    private function checkIfGOCDBIsReadOnlyAndRequestisNotGET(){
      if ($this->requestMethod != 'GET' && $this->portalIsReadOnly()){
        $this->exceptionWithResponseCode(503,"GOCDB is currently in read only mode");
      }
    }


    /**
     * Sets the relevant class property
     */
    private function getAndSetAuthInfo($authArray) {
      #Authentication
      #$this->userIdentifier will be empty if the unser doesn't provide a credential
      #If in the future we implement API keys, then I suggest we only look for
      #the DN if the API key isn't presented.
      #Failure to authenticate is handled elsewhere
      if (array_key_exists('userIdentifier', $authArray)) {
        $this->userIdentifier = $authArray['userIdentifier'];
      } else {
        $this->exceptionWithResponseCode(500,
          "Internal error: no identifier found. Please contact the GOCDB administrators"
        );
      }
      if (array_key_exists('userIdentifierType', $authArray)) {
        $this->userIdentifierType = $authArray['userIdentifierType'];
      } else {
        $this->exceptionWithResponseCode(500,
          "Internal error: no identifier type found. Please contact the GOCDB administrators"
        );
      }
    }

    /**
     * Checks the user is authenticated and throws exception if not
     */
    private function checkUserAuthenticated () {
      if (empty($this->userIdentifier)) {
        #yes 403 - 401 is not appropriate for X509 authentication
        $this->exceptionWithResponseCode(403,
          "You need to be authenticated to access this resource. " .
          "Please provide a valid IGTF X509 Certificate"
        );
      }
    }

    private function checkAuthorisation (Site $siteService, \Site $site, $identifier, $indentifierType) {
      try {
        $siteService->checkAuthorisedAPIIdentifier($site, $identifier, $indentifierType);
      } catch (\Exception $e) {
        #yes 403 - 401 is not appropriate for X509 authentication
        $this->httpResponseCode = 403;
        throw $e;
      }

    }

    /**
     * Updates the properties of the site specified in the request
     *
     * @param  Site   $siteService
     * @throws \Exception
     */
    private function updateSite (Site $siteService) {
      #Identify the site
      try {
          $site = $siteService->getSite($this->entityID);
      } catch(\Exception $e){
        $this->exceptionWithResponseCode(404,"A site with the specified id could not be found");
      }

      #Authorisation
      $this->checkAuthorisation ($siteService, $site, $this->userIdentifier, $this->userIdentifierType);

      switch($this->entityProperty) {
          case 'extensionproperties':{
            $this->updateSiteExtensionProperties ($siteService, $site);
            break;
          }
          default: {
            $this->exceptionWithResponseCode(501,$this->genericExceptionMessages["entityTypePropertyCombo"]);
          }
      }
    }

    /**
     * Update the extension properties of a site
     * @param  Site   $siteService
     * @param   $site        The site being updated
     * @throws \Exception
     */
    private function updateSiteExtensionProperties (Site $siteService, $site) {
      #TWO CASES: one there is a single value, the other mutliple - create an array for the single, use array for multiple.
      if (is_null($this->entityPropertyKey)) {
          $extensionPropKVArray = $this->entityPropertyKVArray;
      } else {
          $extensionPropKVArray = array($this->entityPropertyKey => $this->entityPropertyValue);
      }

      #Based on Request types run either an add with or without overwrite or a remove.
      switch ($this->requestMethod) {
        case 'POST':{
          $this->updateSiteExtensionPropertiesPost($siteService, $site, $extensionPropKVArray);
          break;
        }
        case 'PUT':{
          $this->updateSiteExtensionPropertiesPut($siteService, $site, $extensionPropKVArray);
          break;
        }
        case 'DELETE':{
          $this->updateSiteExtensionPropertiesDelete($siteService, $site, $extensionPropKVArray);
          break;
        }
        default: {
          $this->exceptionWithResponseCode(405,$this->genericExceptionMessages["entityTypePropertyMethod"]);
          break;
        }
      }
    }

    /**
     * Updates extension properties for site, following POST request
     *
     * @param  Site   $siteService [description]
     * @param array   $extensionPropKVArray
     * @param  $site site being updated
     * @throws \Exception
     */
    private function updateSiteExtensionPropertiesPost (Site $siteService, $site, $extensionPropKVArray) {
      #Post requests will fail if the property with that value already exists or the property limit has been reached
      #TODO:This will return the wrong error code if something else goes wrong
      try {
          $siteService->addPropertiesAPI($site, $extensionPropKVArray, true, $this->userIdentifierType, $this->userIdentifier);
      } catch(\Exception $e) {
          $this->httpResponseCode=409;
          throw $e;
      }
    }

    /**
     * Updates extension properties for site, following PUT request
     *
     * @param  Site   $siteService [description]
     * @param array   $extensionPropKVArray
     * @param  $site site being updated
     * @throws \Exception
     */
    private function updateSiteExtensionPropertiesPut (Site $siteService, $site, $extensionPropKVArray) {
      #Put requests will fail if the property limit has been reached
      #TODO:This will return the wrong error code if something else goes wrong
      try {
          $siteService->addPropertiesAPI($site, $extensionPropKVArray, false, $this->userIdentifierType, $this->userIdentifier);
      } catch(\Exception $e) {
          $this->exceptionWithResponseCode(409, $e->getMessage());
      }
    }

    /**
     * Updates extension properties for site, following DELETE request
     *
     * @param  Site   $siteService [description]
     * @param array   $extensionPropKVArray
     * @param  $site site being updated
     * @throws \Exception
     */
    private function updateSiteExtensionPropertiesDelete (Site $siteService, $site, $extensionPropKVArray) {
      #Convert the array of K/V pairs into an array of properties. If the array is empty, then we want to delte all the properties
      if (empty($extensionPropKVArray)) {
          $extensionPropArray = $site->getSiteProperties()->toArray();
      } else {
          $extensionPropArray = array();
          foreach ($extensionPropKVArray as $key => $value) {

              $extensionProp = $siteService->getPropertyByKeyAndParent($key, $site);
              if (is_null($extensionProp)) {
                $this->exceptionWithResponseCode(404,
                  "A property with key \"$key\" could not be found for "
                  . $site->getName() . ". No properties have been deleted. "
                );
              }

              #If a value has been provided for the property, it needs to matched
              if (!empty($value) && ($extensionProp->getKeyValue() != $value)) {
                $this->exceptionWithResponseCode(409,
                "The value provided for the property with key \"$key\" does " .
                "not match the existing one. No Properties have been deleted."
                );
              }

              $extensionPropArray[] = $extensionProp;
          }
      }

      $siteService->deleteSitePropertiesAPI($site, $extensionPropArray, $this->userIdentifierType, $this->userIdentifier);
    }

  /**
   * Updates the specified service
   * @param  Site   $siteService [description]
   * @throws \Exception
   */
  private function updateService(Site $siteService){
    #This case requires the serviceService
    $this->checkServiceServiceSet();

    #Identify the service
    try {
      $service = $this->serviceService->getService($this->entityID);
    } catch (\Exception $e) {
      $this->exceptionWithResponseCode(404,"A service with the specified id could not be found");
    }

    #Authorisation
    $this->checkAuthorisation ($siteService, $service->getParentSite(), $this->userIdentifier, $this->userIdentifierType);

    switch($this->entityProperty) {
      case 'extensionproperties':{
        $this->updateServiceExtensionProperties($service);
        break;
      }
      default: {
        $this->exceptionWithResponseCode(501,$this->genericExceptionMessages["entityTypePropertyCombo"]);
      }
    }
  }

  /**
   * Updates the extension properties of the service specified
   *
   * @param  $service serivce being updated
   * @throws \Exception
   */
  private function updateServiceExtensionProperties($service) {
    #TWO CASES: one there is a single value, the other mutliple - create an array for the single, use array for multiple.
    if (is_null($this->entityPropertyKey)) {
        $extensionPropKVArray = $this->entityPropertyKVArray;
    } else {
        $extensionPropKVArray = array($this->entityPropertyKey => $this->entityPropertyValue);
    }

    #Based on Request types run either an add with or without overwritebv or a remove.
    switch ($this->requestMethod) {
      case 'POST':{
        $this->updateServiceExtensionPropertiesPost($service, $extensionPropKVArray);
        break;
      }
      case 'PUT':{
        $this->updateServiceExtensionPropertiesPut($service, $extensionPropKVArray);
        break;
      }
      case 'DELETE':{
          $this->updateServiceExtensionPropertiesDelete($service, $extensionPropKVArray);
          break;
      }
      default: {
        $this->exceptionWithResponseCode(405, $this->genericExceptionMessages["entityTypePropertyMethod"]);
        break;
      }
    }
  }

  /**
   * Update extension properties for the service requested following a POST
   * request
   * @param  $service service being updated
   * @param  array $extensionPropKVArray
   * @throws \Exception
   */
  private function updateServiceExtensionPropertiesPost ($service, $extensionPropKVArray) {
    #Post requests will fail if the property with that value already exists or the property limit has been reached
    #TODO:This will return the wrong error code if something else goes wrong
    try {
      $this->serviceService->addServicePropertiesAPI($service, $extensionPropKVArray, true, $this->userIdentifierType, $this->userIdentifier);
    } catch(\Exception $e) {
      $this->exceptionWithResponseCode(409, $e->getMessage());
    }
  }

  /**
   * Update extension properties for the service requested following a PUT
   * request
   * @param  $service service being updated
   * @param  array $extensionPropKVArray
   * @throws \Exception
   */
  private function updateServiceExtensionPropertiesPut ($service, $extensionPropKVArray) {
    #Put requests will fail if the property limit has been reached
    #TODO:This will return the wrong error code if something else goes wrong
    try {
      $this->serviceService->addServicePropertiesAPI($service, $extensionPropKVArray, false, $this->userIdentifierType, $this->userIdentifier);
    } catch(\Exception $e) {
      $this->exceptionWithResponseCode(409, $e->getMessage());
    }
  }

  /**
   * Update extension properties for the service requested following a DELETE
   * request
   * @param  $service service being updated
   * @param  array $extensionPropKVArray
   * @throws \Exception
   */
  private function updateServiceExtensionPropertiesDelete ($service, $extensionPropKVArray) {
    #Convert the array of K/V pairs into an array of properties. If the array is empty, then we want to delte all the properties
    if (empty($extensionPropKVArray)) {
      $extensionPropArray = $service->getServiceProperties()->toArray();
    } else {
      $extensionPropArray = array();
      foreach ($extensionPropKVArray as $key => $value) {
        $extensionProp = $this->serviceService->getServicePropertyByKeyAndParent($key, $service);
        if (is_null($extensionProp)) {
          $this->exceptionWithResponseCode(404,
            "A property with key \"$key\" could not be found for "
            . $service->getHostName() . ". No properties have been deleted. "
          );
        }

        #If a value has been provided for the property, it needs to matched
        if (!empty($value) && ($extensionProp->getKeyValue() != $value)) {
          $this->exceptionWithResponseCode(409,
            "The value provided for the property with key \"$key\" does " .
            "not match the existing one. No Properties have been deleted."
          );
        }

        $extensionPropArray[] = $extensionProp;
      }
    }

    $this->serviceService->deleteServicePropertiesAPI($service, $extensionPropArray, $this->userIdentifierType, $this->userIdentifier);
  }

  /**
   * Update an Endpoint
   *
   * @param  Site   $siteService
   * @throws \Exception
   */
  private function updateEndpoint (Site $siteService) {
    #This case requires the serviceService
    $this->checkServiceServiceSet();

    #Identify the SE
    try {
        $endpoint = $this->serviceService->getEndpoint($this->entityID);
    } catch (\Exception $e) {
      $this->exceptionWithResponseCode(404, "An endpoint with the specified ID could not be found");
    }

    #Authorisation
    $this->checkAuthorisation ($siteService, $endpoint->getService()->getParentSite(), $this->userIdentifier, $this->userIdentifierType);

    #Make the requested change
    switch($this->entityProperty) {
        case 'extensionproperties':{
          $this->updateEndpointExtensionProperties($endpoint);
          break;
        }
        default: {
          $this->exceptionWithResponseCode(501,$this->genericExceptionMessages["entityTypePropertyCombo"]);
        }
    }
  }

  /**
   * Update the Extension properties of an endpoint
   * @param   $endpoint The endpoint to be updated
   * @throws \Exception
   */
  private function updateEndpointExtensionProperties ($endpoint) {
    #TWO CASES: one there is a single value, the other mutliple - create an array for the single, use array for multiple.
    if (is_null($this->entityPropertyKey)) {
        $extensionPropKVArray = $this->entityPropertyKVArray;
    } else {
        $extensionPropKVArray = array($this->entityPropertyKey => $this->entityPropertyValue);
    }

    #Based on Request types run either an add with or without overwrite or a remove.
    switch ($this->requestMethod) {
      case 'POST':{
        $this->updateEndpointExtensionPropertiesPost($endpoint, $extensionPropKVArray);
        break;
      }
      case 'PUT':{
        $this->updateEndpointExtensionPropertiesPut($endpoint, $extensionPropKVArray);
        break;
      }
      case 'DELETE':{
        $this->updateEndpointExtensionPropertiesDelete($endpoint, $extensionPropKVArray);
        break;
      }
      default: {
        $this->exceptionWithResponseCode(405,$this->genericExceptionMessages["entityTypePropertyMethod"]);
        break;
      }
    }
  }

  /**
   * Update the Extension properties of an endpoint requested using Post
   *
   * @param   $endpoint The endpoint to be updated
   * @param  array  $extensionPropKVArray
   * @throws \Exception
   */
  private function updateEndpointExtensionPropertiesPost ($endpoint, $extensionPropKVArray) {
    #Post requests will fail if the property with that value already exists or the property limit has been reached
    #TODO:This will return the wrong error code if something else goes wrong
    try {
      $this->serviceService->addEndpointPropertiesAPI($endpoint, $extensionPropKVArray, true, $this->userIdentifierType, $this->userIdentifier);
    } catch(\Exception $e) {
      $this->exceptionWithResponseCode(409, $e->getMessage());
    }
  }

  /**
   * Update the Extension properties of an endpoint requested using Put
   *
   * @param   $endpoint The endpoint to be updated
   * @param  array  $extensionPropKVArray
   * @throws \Exception
   */
  private function updateEndpointExtensionPropertiesPut ($endpoint, $extensionPropKVArray) {
    #TODO:This will return the wrong error code if something else goes wrong
    try {
      $this->serviceService->addEndpointPropertiesAPI($endpoint, $extensionPropKVArray, false, $this->userIdentifierType, $this->userIdentifier);
    } catch(\Exception $e) {
      $this->exceptionWithResponseCode(409, $e->getMessage());
    }
  }

  /**
   * Update the Extension properties of an endpoint requested using Delete
   *
   * @param   $endpoint The endpoint to be updated
   * @param  array  $extensionPropKVArray
   * @throws \Exception
   */
  private function updateEndpointExtensionPropertiesDelete ($endpoint, $extensionPropKVArray) {
    #Convert the array of K/V pairs into an array of properties. If the array is empty, then we want to delte all the properties
    if (empty($extensionPropKVArray)) {
      $extensionPropArray = $endpoint->getEndpointProperties()->toArray();
    } else {
      $extensionPropArray = array();
      foreach ($extensionPropKVArray as $key => $value) {
        $extensionProp = $this->serviceService->getEndpointPropertyByKeyAndParent($key, $endpoint);
        if (is_null($extensionProp)) {
          $this->exceptionWithResponseCode(404,
            "A property with key \"$key\" could not be found for "
            . $endpoint->getName() . ". No properties have been deleted. "
          );
        }

        #If a value has been provided for the property, it needs to matched
        if (!empty($value) && ($extensionProp->getKeyValue() != $value)) {
          $this->exceptionWithResponseCode(409,
            "The value provided for the property with key \"$key\" does " .
            "not match the existing one. No Properties have been deleted."
          );
        }

        $extensionPropArray[] = $extensionProp;
      }
    }

    $this->serviceService->deleteendpointPropertiesAPI($endpoint, $extensionPropArray, $this->userIdentifierType, $this->userIdentifier);
  }

  /**
   * Generates exception messages which depend on the entutity type and entity
   * value being known (and so which can't be created in _Construct)
   */
  private function generateExceptionMessages () {
    $this->genericExceptionMessages["entityTypePropertyCombo"] =
      "Updating a " . $this->entityType. "'s $this->entityProperty is " .
      "not currently supported through the API, for details of " .
      "the currently supported methods see: $this->docsURL";
    $this->genericExceptionMessages["entityTypePropertyMethod"] =
      "\"" . $this->requestMethod . "\" is not currently a supported " .
      "request method for altering" . $this->entityType. "'s " .
      $this->entityProperty . " . For more details see: $this->docsURL";
  }

}
