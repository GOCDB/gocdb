<?php
$services = $params['services'];
$dt = $params['downtime'];
$configService = \Factory::getConfigService();

// Get the affected services and store the IDs in an array.
$affectedServiceIDs = array();

foreach ($dt->getServices() as $affectedService) {
    $affectedServiceIDs[] = $affectedService->getId();
}

// Get the affected endpoints and store the IDs in an array.
$affectedEndpointIDs = array();

foreach ($dt->getEndpointLocations() as $affectedEndpoints) {
    $affectedEndpointIDs[] = $affectedEndpoints->getId();
}

?>


<!-- Dynamically create a select list from a sites services
 AND SELECT ONLY THOSE SERVICES AND ENDPOINTS THAT ARE AFFECTED AS DEFINED IN THE DOWNTIME -->
<label> Select Affected Services</label>
<select name="IMPACTED_IDS[]" id="Select_Services" size="10"
        class="form-control" onclick=""
        style="width:99%; margin-left:1%"
        onChange="selectServicesEndpoint()" multiple>
<?php
foreach ($services as $siteID => $siteServiceList) {
    foreach ($siteServiceList as $service) {
        $count = 0;

        /**
         * Set the HTML 'SELECTED' attribute on the <option>
         * only if this service was affected.
         */
        if (in_array($service->getId(), $affectedServiceIDs)) {
            $selected = 'SELECTED';
        } else {
            $selected = '';
        }

        echo "<option value=\"" . $siteID . ":" . $service->getId()
            . ":s" . $service->getId() . "\" id=\"" . $service->getId()
            . "\" " . $selected . ">";
        xecho('(' . $service->getServiceType()->getName() . ') ');
        xecho($service->getHostName());
        echo("</option>");

        foreach ($service->getEndpointLocations() as $endpoint) {
            if (in_array($endpoint->getId(), $affectedEndpointIDs)) {
                $selected = 'SELECTED';
            } else {
                $selected = '';
            }
            if ($endpoint->getName() == '') {
                $name = xssafe('myEndpoint');
            } else {
                $name = xssafe($endpoint->getName());
            }

            /* Option styling doesn't work well cross browser,
               so just use 4 spaces to indent the branch.*/
            echo "<option id=\"" . $service->getId() . "\" value=\""
                . $siteID . ":" .  $service->getId() . ":e"
                . $endpoint->getId() . "\" " . $selected
                . ">&nbsp&nbsp&nbsp&nbsp-" . $name . "</option>";
            $count++;
        }
    }
}
?>
</select>
