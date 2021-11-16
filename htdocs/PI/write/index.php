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

require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/PIWriteRequest.php';
require_once __DIR__ . '/utils.php';

#services for request
$siteServ = \Factory::getSiteService();
$serviceServ = \Factory::getServiceService();

// Initialise the configuration service with the host url of the incoming request.
// Allows the overriding of configuration values. Do not use 'new' to create a new 
// instance after this.

\Factory::getConfigService()->setLocalInfoOverride($_SERVER['SERVER_NAME']);

#Request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

#Base URL
#If the request isn't set then no url parameters have been used. passing null,
#rather than throwing an exception here, will give a properly formatted error
if (isset($_REQUEST['request'])) {
    #Note that apache will collapse multiple /'s into a single /
    $baseUrl = $_REQUEST['request'];
}
else {
    $baseUrl = null;
}

#Get the contents of the Request
#see http://php.net/manual/en/wrappers.php.php
$requestContents = file_get_contents('php://input');

#Get authentication details
$authArray = getAuthenticationInfo();

#Run the request
$piReq = new PIWriteRequest();
$piReq->setServiceService($serviceServ);
$returnArray = $piReq->processRequest($requestMethod, $baseUrl, $requestContents, $siteServ, $authArray);

#Return the object to the user
returnJsonWriteAPIResult($returnArray['httpResponseCode'],$returnArray['returnObject']);
