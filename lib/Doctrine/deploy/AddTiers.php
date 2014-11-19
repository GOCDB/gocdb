<?php
require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";
/* AddTiers.php: Manually inserts a list of tiers into
 * the doctrine prototype.
 */
$tierArray = array ("0", "1", "2");

foreach($tierArray as $tierName) {
    $tier = new Tier();
    $tier->setName($tierName);
    $entityManager->persist($tier);
}

$entityManager->flush();