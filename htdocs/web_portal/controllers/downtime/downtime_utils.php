<?php

/*_____________________________________________________________________________
 *=============================================================================
 * File: downtime_utils.php
 * Author: GOCDB DEV TEAM, STFC.
 * Description: Helper functions which can be re-used while adding
 *              or editing a downtime.
 *
 * License information
 *
 * Copyright 2013 STFC
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
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';

use DateTime;
use DateTimeZone;

/**
 * Sorts the impacted IDs into impacted services and impacted endpoints.
 *
 * @param array $impactedIDs An array of `impactedIDs` which user has selected.
 *
 * @return array An array containing
 *               `$siteLevelDetails` and `serviceWithEndpoints`.
 */
function endpointToServiceMapping($impactedIDs)
{
    $siteLevelDetails = [];
    $serviceWithEndpoints = [];

    /**
     * For each impacted ID,
     * sort between endpoints and services using the prepended letter.
     */
    foreach ($impactedIDs as $id) {
        list($siteNumber, $parentService, $idType) = explode(':', $id);

        $type = strpos($idType, 's') !== false ? 'services' : 'endpoints';
        $id = str_replace(['s', 'e'], '', $idType);

        $siteLevelDetails[$siteNumber][$type][] = $id;
        $serviceWithEndpoints[$siteNumber][$parentService][$type][] = $id;
    }

    return [$siteLevelDetails, $serviceWithEndpoints];
}

/**
 * If a user has selected endpoints but not the parent service here
 * we will add the service to maintain the link between a downtime
 * having both the service and the endpoint.
 *
 * @param array $servWithEndpoints   Used for displaying affected service
 *                                   with endpoint(s).
 * @param array $siteDetails         Each site ID will have a `services` that
 *                                   stores all affected service ID(s) and an
 *                                   `endpoints` that stores all affected
 *                                   endpoint ID(s).
 * @param bool $hasMultipleTimezones If the user selects multiple sites in
 *                                   the  web portal along with the option
 *                                   "site timezone" it will be true;
 *                                   otherwise, it will be false.
 * @param mixed $downtimeDetails     Downtime information.
 *
 * @return array An array containing `$siteDetails` and `servWithEndpoints`.
 */
function addParentServiceForEndpoints(
    $servWithEndpoints,
    $siteDetails,
    $hasMultipleTimezones,
    $downtimeDetails
) {
    foreach ($servWithEndpoints as $siteID => $siteData) {
        $siteDetails[$siteID]['services'] = [];

        $newSite = \Factory::getSiteService()->getSite($siteID);
        $siteDetails[$siteID]['siteName'] = $newSite->getShortName();

        if ($hasMultipleTimezones) {
            list(
                $siteDetails[$siteID]['START_TIMESTAMP'],
                $siteDetails[$siteID]['END_TIMESTAMP']
            ) = setLocalTimeForSites($downtimeDetails, $siteID);
        }

        foreach (array_keys($siteData) as $serviceID) {
            $servWithEndpoints[$siteID][$serviceID]['services'] = [];
            $servWithEndpoints[$siteID][$serviceID]['services'][] = $serviceID;
            // Ensuring that service IDs are unique for the selected sites.
            $siteDetails[$siteID]['services'][] = $serviceID;
        }
    }

    return [$siteDetails, $servWithEndpoints];
}

/**
 * Converts UTC start and end timestamps to the local timezone
 * of a specific site based on that site's timezone.
 *
 * @param mixed $downtimeDetails Downtime information.
 * @param integer $siteID        Site ID
 */
function setLocalTimeForSites($downtimeDetails, $siteID)
{
    $site = \Factory::getSiteService()->getSite($siteID);

    $siteTimezone = $site->getTimeZoneId();

    $startTimeAsString = $downtimeDetails['START_TIMESTAMP'];
    $utcEndTime = $downtimeDetails['END_TIMESTAMP'];

    $utcStartDateTime = DateTime::createFromFormat(
        'd/m/Y H:i',
        $startTimeAsString,
        new DateTimeZone('UTC')
    );
    $utcEndDateTime = DateTime::createFromFormat(
        'd/m/Y H:i',
        $utcEndTime,
        new DateTimeZone('UTC')
    );

    $targetSiteTimezone = new DateTimeZone($siteTimezone);
    $utcOffset = $targetSiteTimezone->getOffset($utcStartDateTime);

    // Calculate the equivalent time in the target timezone.
    // Ref: https://www.php.net/manual/en/datetime.modify.php
    $siteStartDateTime = $utcStartDateTime->modify("-$utcOffset seconds");
    $siteEndDateTime = $utcEndDateTime->modify("-$utcOffset seconds");

    return [
        $siteStartDateTime->format('d/m/Y H:i'),
        $siteEndDateTime->format('d/m/Y H:i')
    ];
}

/**
 * Unset a given variable, helper method to destroy the specified variables.
 *
 * @param mixed $downtimeObj   Object to destroy specified variables.
 * @param string $fromLocation Location from where the
 *                             function is being called.
 */
function unsetVariables($downtimeObj, $fromLocation)
{
    if ($fromLocation == "add") {
        unset($downtimeObj['SERVICE_WITH_ENDPOINTS']);
        unset($downtimeObj['SINGLE_SITE']);
    } else {
        unset($downtimeObj['DOWNTIME']['EXISTINGID']);
        unset($downtimeObj['isEdit']);
        unset($downtimeObj['SERVICE_WITH_ENDPOINTS']);
        unset($downtimeObj['SINGLE_SITE']);
    }

    return $downtimeObj;
}
