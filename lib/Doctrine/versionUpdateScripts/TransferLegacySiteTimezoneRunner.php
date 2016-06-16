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

require_once dirname(__FILE__) . "/../bootstrap.php";
/**
 * Script used to copy over legacy/deprecated Timezone->name lookup value (v5.3)
 * to the Site->timezoneId value (v5.4).
 * The Timezone entity is a legacy lookup table and should not be used anymore,
 * it will be removed from the domain model in the future. Instead, the timezoneId
 * value is declared directly as a memeber of Site and will store a standard PHP timezone
 * identifier from the internal PHP timezone db.
 *
 * Note:
 * The script will only update the new Site->timezoneId value if it is null
 * or an empty string (users may have already manually set their site timezoneId).
 *
 * Not all legacy timezone lookup values can be automatically mapped to a
 * new Site->timezoneId value, e.g. those starting with 'Etc/'. This is because
 * there is no new equivalent. In these cases, the Site will be skipped and the
 * value will need to be manually updated ("can all NGIs please check and update their
 * Site timezone value").
 *
 * Usage:
 * ======
 *
 * 1) Update DB tables
 * ====================
 * Before running this script, you MUST update the DB schema to correspond to the
 * latest Gocdb entity model. This can be done using the following Doctrine
 * commands on the command line:
 *
 * // 1.1) Test you can run the schema-tool on the command line:
 *  $doctrine orm:schema-tool:update --help
 *
 * // 1.2) View what DB schema changes would occur without actually updating the DB:
 * $doctrine orm:schema-tool:update --dump-sql
 *     ...SQL DDL statemetns will be printed here...
 *
 * // 1.3) Update the DB schema using force (or copy the DDL as printed above and run manually):
 * $doctrine orm:schema-tool:update --force
 *     Updating database schema...
 *     Database schema updated successfully! "n" queries were executed
 *
 * 2) Run this script
 * ====================
 * // 2.1) Cd into '<GOCDB_SRC_HOME>/lib/Doctrine/versionUpdateScripts'
 *
 * // 2.2) Run this script on the command line using:
 * $php TransferLegacySiteTimezonesRunner.php --show      // shows what will be updated
 * $php TransferLegacySiteTimezonesRunner.php --force     // does the update
 */

$commandLineArgValid = false;
if (isset($argv[1])) {
    $forceOrShow = $argv[1];
    if ($forceOrShow == '--force' || $forceOrShow == '--show') {
        $commandLineArgValid = true;
    }
}

if (!$commandLineArgValid) {
    die("Usage: php <scriptName> --force or --show \n");
}


$timezones = array_values(DateTimeZone::listIdentifiers());
$dql = "SELECT s FROM Site s";
$sites = $entityManager->createQuery($dql)->getResult();
$skippdCount = 0;
$changedCount = 0;
$skippedDueToExistingValue = 0;
/* @var $site \Site */
foreach ($sites as $site) {
    $oldTzName = $site->getTimezone()->getName();
    if (!in_array($oldTzName, $timezones)) {
        // legacy timezone value is not supported, so skip
        //print_r("Skipping site: " . $site->getName() . ' ' . $site->getId() . ' ' . $oldTz . "\n");
        ++$skippdCount;
    } else {
        // copy the old timezone value into the new site->setTimzoneId() field
        // if there is no value already present.
        if ($site->getTimezoneId() == null || trim($site->getTimezoneId()) == '') {
            print_r("Changing SiteTimezoneId to: [".$oldTzName."]\n");
            if ($forceOrShow == '--force') {
                $site->setTimezoneId($oldTzName);
                $entityManager->persist($site);
            }
            ++$changedCount;
        } else {
            ++$skippedDueToExistingValue;
        }
    }
    //print_r('SiteTimezoneId: ['.$site->getTimezoneId()."] OldTz: [".$oldTz."]\n");
}
if ($forceOrShow == '--force') {
    $entityManager->flush();
}

print_r("Sites skipped: [" . $skippdCount . "]\n");
print_r("Sites withVal: [" . $skippedDueToExistingValue . "]\n");
print_r("Sites updated: [" . $changedCount . "]\n");
