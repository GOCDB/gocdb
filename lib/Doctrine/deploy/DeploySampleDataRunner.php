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


require_once __DIR__."/AddUtils.php";

if(isset($argv[1])) {
    $GLOBALS['dataDir'] = $argv[1];
} else {
    die("Please specify your data directory (sampleData) \n");
}

print_r("Deploying Sample Data from ".$GLOBALS['dataDir']."\n");

require __DIR__."/AddProjects.php";
echo "Added Projects OK\n";

require __DIR__."/AddScopes.php";
echo "Added Scopes OK\n";

require __DIR__."/AddNGIs.php";
echo "Added NGIs OK\n";

require __DIR__."/AddSites.php";
echo "Added Sites and JOINED to NGIs OK\n";

require __DIR__."/AddServiceEndpoints.php";
echo "Added Services, EndpointLocations and JOINED associations OK\n";

require __DIR__."/AddUsers.php";
echo "Added Users OK\n";

require __DIR__."/AddSiteRoles.php";
echo "Added Site level Roles OK\n";

require __DIR__."/AddGroupRoles.php";
echo "Added NGI level Roles OK\n";

require __DIR__."/AddEgiRoles.php";
echo "Added EGI level Roles OK\n";

require __DIR__."/AddServiceGroups.php";
echo "Added Service Groups OK\n";
