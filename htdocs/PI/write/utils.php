<?php
/*______________________________________________________
 *======================================================
 * File: utils.php
 * Author: George Ryall
 * Description: utils for the write programmatic interface
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

/**
 * following the processign of a write-api request, this function returns any
 * object provided in a JSON format. It also sets the http resposne code given
 * to it and adds to the http header.
 * @param  integer $httpResponseCode http response code produced by write-api
 * @param  array $object The object to be converted to JSON and returned to the user
 */
function returnJsonWriteAPIResult ($httpResponseCode, $object) {
    #Set the HTTP response code
    http_response_code($httpResponseCode);

    #Set the Content-type in the header (204 response should have no content)
    if($httpResponseCode == 204) {
        #This removes the content-type from the header
        header("Content-Type:");
    } else {
        header("Content-Type:application/json");
    }

    if (!is_null($object)) {
        echo json_encode($object);
    }
}

/**
 * Get the authentication information for the user making an API requests
 *
 * @return array type of user identifier and identifier string
 */
function getAuthenticationInfo () {
  require_once __DIR__ . '/../../web_portal/components/Get_User_Principle.php';
  #Check if associated cert/token is set to define identifier type
  if(isset($_SERVER['SSL_CLIENT_CERT'])){$identifierType = 'X.509';}
  if(isset($_SERVER['OIDC_access_token'])){$identifierType = 'OIDC Subject';}

  #This will return null if no cert is presented
  $identifier = Get_User_Principle_PI();

  return array('userIdentifier'=>$identifier,'userIdentifierType'=>$identifierType);
}
