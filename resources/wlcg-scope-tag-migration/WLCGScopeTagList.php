<?php

/*
 * A simple script to store variables useful for the WLCG Scope tag migration.
 */

$wlcgScopesList = ["alice", "atlas", "belle", "cms", "dune", "lhcb"];
$tierScopesList = ["tier0", "tier1", "tier2", "tier3"];

// Retrieve all scope objects from the DB using a Doctrine Query
// Language (DQL) query and store as an dict, with the name as the key, for
// later reference.
$scopeDql = "SELECT s FROM Scope s";
$allScopeList = $entityManager->createQuery($scopeDql)->getResult();
$allScopeDict = [];
foreach ($allScopeList as $scope) {
    $allScopeDict[$scope->getName()] = $scope;
}
