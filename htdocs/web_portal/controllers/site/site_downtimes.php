<?php
function site_downtimes() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

    $serv = \Factory::getSiteService();
    $site = $serv->getSite($_REQUEST['id']);
    $downtimes = $serv->getDowntimes($_REQUEST['id'], null);

    $params['site'] = $site;
    $params['downtimes'] = $downtimes;

	$title = "$site downtimes";
    show_view('site/site_downtimes.php', $params, $title);
    return;
}