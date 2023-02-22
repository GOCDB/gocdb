<?php

/*
 *Declares varibales to be used in Help_And_Contact.php
 */

require_once __DIR__.'/../../../lib/Gocdb_Services/Factory.php';

$configService = \Factory::getConfigService();

$communityDocs = $configService->getCommunityDocs();
$helpdeskLink = $configService->getHelpdeskLink();
$requestTracker = $configService->getRequestTracker();
