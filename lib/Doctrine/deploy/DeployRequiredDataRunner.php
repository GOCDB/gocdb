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
    die("Please specify your data directory (requiredData) \n");
}

print_r("Deploying Required Lookup Data\n");

require __DIR__."/AddInfrastructures.php";
echo "Added Infrastructures OK\n";

require __DIR__."/AddCountries.php";
echo "Added Countries OK\n";

require __DIR__."/AddRoleTypes.php";
echo "Added Roles OK\n";

require __DIR__."/AddCertificationStatuses.php";
echo "Added Certification Statuses OK\n";

require __DIR__."/AddServiceTypes.php";
echo "Added Service Types OK\n";
