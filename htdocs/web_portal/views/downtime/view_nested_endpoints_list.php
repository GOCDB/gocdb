<?php
$services = $params['services'];
$specificServiceEndpoint = isset($params['se']) ? $params['se'] : '';
$configService = \Factory::getConfigService();
?>
<!-- Dynamically create a select list from a sites services -->
<label> Select Affected Services+Endpoints (Ctrl+click to select)</label>
<select name="IMPACTED_IDS[]" id="Select_Services" size="10" class="form-control" onclick="" style="width:99%; margin-left:1%" onChange="selectServicesEndpoint()" multiple>
<?php
foreach ($services as $service) {
    $count = 0;

    if ($specificServiceEndpoint) {
        if ($service->getId() == $specificServiceEndpoint) {
            $selected = 'SELECTED';
        } else {
            $selected = '';
        }
    } else {
        $selected = 'SELECTED';
    }

    echo "<option value=\"s" . $service->getId() . "\" id=\""
        . $service->getId() . "\" " . $selected . ">" . '('
        . xssafe($service->getServiceType()->getName()) . ') '
        . xssafe($service->getHostName()) . "</option>";

    foreach ($service->getEndpointLocations() as $endpoint) {
        if ($specificServiceEndpoint) {
            if ($service->getId() == $specificServiceEndpoint) {
                $selected = 'SELECTED';
            } else {
                $selected = '';
            }
        } else {
            $selected = 'SELECTED';
        }

        if ($endpoint->getName() == '') {
            $name = xssafe('myEndpoint');
        } else {
            $name = xssafe($endpoint->getName());
        }

        /**
         * Option styling doesn't work well cross browser,
         * so just use 4 spaces to indent the branch.
         */
        echo "<option id=\"" . $service->getId() . "\" value=\"e"
            . $endpoint->getId() . "\" " . $selected
            . ">&nbsp&nbsp&nbsp&nbsp-" . $name . "</option>";

        $count++;
    }
}
?>
</select>
