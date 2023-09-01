<?php

/*_____________________________________________________________________________
 *=============================================================================
 * File: downtime_utils.php
 * Description: Helper functions which can be re-used while adding
 *              or editing a downtime.
 *
 * License information
 *
 * Copyright 2023 STFC
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
        /**
         * `$siteNumber` => It's about Site ID
         * `$parentService` => It's about service ID endpoint belongs too
         * `idType` => It's about to differentiate
         *             the endpoint vs service selection.
         */
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
 *
 * @return array An array containing `$siteDetails` and `servWithEndpoints`.
 */
function addParentServiceForEndpoints(
    $servWithEndpoints,
    $siteDetails
) {
    foreach ($servWithEndpoints as $siteID => $siteData) {
        $siteDetails[$siteID]['services'] = [];

        $newSite = \Factory::getSiteService()->getSite($siteID);
        $siteDetails[$siteID]['siteName'] = $newSite->getShortName();

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
