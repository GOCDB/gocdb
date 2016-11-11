<?php
function view_sgroup_downtimes() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    $id = $_REQUEST['id'];

    if(!is_numeric($id)) {
        $error = "Invalid Service Group ID";
        show_view('error.php', $error);
        return;
    }

    $sGroup = \Factory::getServiceGroupService()->getServiceGroup($id);
    $downtimes = \Factory::getServiceGroupService()->getDowntimes($id, null);

    $params['downtimes'] = $downtimes;
    $params['sGroup'] = $sGroup;

    $title = "Downtimes for " . $sGroup->getName();
    show_view('service_group/view_sgroup_downtimes.php', $params, $title);
    return;
}