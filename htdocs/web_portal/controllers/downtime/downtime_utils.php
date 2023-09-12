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

/**
 * Sorts the impacted IDs into impacted services and impacted endpoints.
 *
 * @param array $impactedIDs An array of `impactedIDs` which user has selected.
 *
 * @return array `serviceWithEndpoints` An array with
 *                'SiteID->serviceID->EndpointID(s)' details.
 */
function endpointToServiceMapping($impactedIDs)
{
    $serviceWithEndpoints = [];

    /**
     * For each impacted ID, sort between endpoints and services
     * using the prepended letter.
     */
    foreach ($impactedIDs as $impactedID) {
        $indexPosition = 0;

        list($siteID, $serviceID, $idType) = explode(':', $impactedID);
        /**
         * `idType` => It will have either `s` followed by service ID or
         *             `e` followed by endpoint ID.
         */
        $trimmedID = str_replace(['s', 'e'], '', $idType);

        if (strpos($idType, 's') === $indexPosition) {
            continue;
        }

        // Using '+' to ensure we have an integer value after type coercion.
        $serviceWithEndpoints[$siteID][$serviceID]['endpointIDs'][] =
            +$trimmedID;
    }

    return $serviceWithEndpoints;
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
    if ($fromLocation == "edit") {
        unset($downtimeObj['DOWNTIME']['EXISTINGID']);
        unset($downtimeObj['isEdit']);
    }

    unset($downtimeObj['SELECTED_SINGLE_SITE']);

    return $downtimeObj;
}
