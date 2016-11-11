<?php
// bootstrap_test.php
$entitiesPath = dirname(__FILE__)."/../../lib/Doctrine/entities";

require_once $entitiesPath."/OwnedEntity.php";
require_once $entitiesPath."/Site.php";
require_once $entitiesPath."/SiteProperty.php";
require_once $entitiesPath."/Service.php";
require_once $entitiesPath."/ServiceProperty.php";
require_once $entitiesPath."/NGI.php";
require_once $entitiesPath."/Infrastructure.php";
require_once $entitiesPath."/CertificationStatus.php";
require_once $entitiesPath."/CertificationStatusLog.php";
require_once $entitiesPath."/Scope.php";
require_once $entitiesPath."/Country.php";
require_once $entitiesPath."/Timezone.php";
require_once $entitiesPath."/Tier.php";
require_once $entitiesPath."/SubGrid.php";
require_once $entitiesPath."/ServiceType.php";
require_once $entitiesPath."/EndpointLocation.php";
require_once $entitiesPath."/User.php";
require_once $entitiesPath."/RoleType.php";
require_once $entitiesPath."/Role.php";
require_once $entitiesPath."/Downtime.php";
require_once $entitiesPath."/Project.php";
require_once $entitiesPath."/ServiceGroup.php";
require_once $entitiesPath."/ServiceGroupProperty.php";
require_once $entitiesPath."/RetrieveAccountRequest.php";
require_once $entitiesPath."/PrimaryKey.php";
require_once $entitiesPath."/ArchivedNGI.php";
require_once $entitiesPath."/ArchivedService.php";
require_once $entitiesPath."/ArchivedServiceGroup.php";
require_once $entitiesPath."/ArchivedSite.php";
require_once $entitiesPath."/EndpointProperty.php";
require_once $entitiesPath."/RoleActionRecord.php";
require_once $entitiesPath."/APIAuthentication.php";

//if (!class_exists("Doctrine\Common\Version", false)) {
//    require_once dirname(__FILE__)."/bootstrap_doctrine.php";
//}
require __DIR__."/bootstrap_doctrine.php";
