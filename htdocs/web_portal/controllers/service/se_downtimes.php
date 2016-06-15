<?php
function se_downtimes() {    
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    $serviceService = \Factory::getServiceService();
    $se = $serviceService->getService($_REQUEST['id']);
    $downtimes = $serviceService->getDowntimes($_REQUEST['id'], null);

    $params['se'] = $se;
    $params['downtimes'] = $downtimes;

    $title = "Downtimes for " . $se->getHostName();
    show_view('service/se_downtimes.php', $params, $title);
    return;
}