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

#services for request
$siteServ = \Factory::getSiteService();
$serviceServ = \Factory::getServiceService();

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

$piReq = new PIWriteRequest();
$piReq->setServiceService($serviceServ);
$piReq->processChange($requestMethod, $baseUrl, $requestContents, $siteServ);
