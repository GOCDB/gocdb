<?php

# This is designed to sanity check the role request email functionality.
# It is not a unit test, it is designed to be run against the sample data.
# Usage: php tests/manualTests/TestRoleRequestEmail.php
require_once 'lib/Gocdb_Services/NotificationService.php';

$requesting_user_id = 5; // This corresponds to Richard Winters.
$requesting_user = \Factory::getUserService()->getUser($requesting_user_id);

echo "=====================================================================\n";
# Test 1, requesting a role over a site with existing role approvers

$site_id = 5; // This corresponds to UNIBE-LHEP
# Get Site object from above ID.
$site = \Factory::getSiteService()->getSite($site_id);

# Have Richard appear to request the "Site Operations Manager" role over
# UNIBE-LHEP. This request should go to Donna, who also has the
# "Site Operations Manager" role.
$role_requested = new \Role(
      new \RoleType("Site Operations Manager"),
      $requesting_user, $site, RoleStatus::PENDING);

$notificationService = \Factory::getNotificationService();
$notificationService->roleRequest($role_requested, $requesting_user, $site);

echo "=====================================================================\n";
# Test 2, requesting a role over a site with no existing role approvers.
$site_id = 6; // This corresponds to T3_CH_PSI
# Get Site object from above ID.
$site = \Factory::getSiteService()->getSite($site_id);

# Have Richard appear to request the "Site Operations Manager" role over
# T3_CH_PSI. As this Site has no one with a role over it the request should
# email Neville (NGI Operations Manager of NGI_CH) but not Donna (who cannot
# approve the role as "Regional Staff (ROD)")
$role_requested = new \Role(
      new \RoleType("Site Operations Manager"),
      $requesting_user, $site, RoleStatus::PENDING);

$notificationService = \Factory::getNotificationService();
$notificationService->roleRequest($role_requested, $requesting_user, $site);

echo "=====================================================================\n";
# Test 3, requesting a role over a NGI with existing role approvers.
$ngi_id = 2; // This corresponds to NGI_CH
# Get Site object from above ID.
$ngi = \Factory::getNgiService()->getNgi($ngi_id);

# Have Richard appear to request the "NGI Operations Manager" role over
# NGI_CH. This request should go to Neville, who also has the
# "NGI Operations Manager" role but not Donna (who cannot approve the role as
# "Regional Staff (ROD)")
$role_requested = new \Role(
      new \RoleType("NGI Operations Manager"),
      $requesting_user, $ngi, RoleStatus::PENDING);

$notificationService = \Factory::getNotificationService();
$notificationService->roleRequest($role_requested, $requesting_user, $ngi);

echo "=====================================================================\n";
# Test 4, requesting a role over a site with no existing role approvers.
$ngi_id = 3; // This corresponds to NGI_FIFE
# Get Site object from above ID.
$ngi = \Factory::getNgiService()->getNgi($ngi_id);

# Have Richard appear to request the "NGI Operations Manager" role over
# NGI_CH. This request should go to Griffin and Amy, who have Project roles.
$role_requested = new \Role(
      new \RoleType("NGI Operations Manager"),
      $requesting_user, $ngi, RoleStatus::PENDING);

$notificationService = \Factory::getNotificationService();
$notificationService->roleRequest($role_requested, $requesting_user, $ngi);

?>
