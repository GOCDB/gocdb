<?php

/*
 * Script to query and update each Site and Service with WLCG scope tags to
 * migrate them to a new heirachical structure.
 *
 * Desired Behaviour:
 * Entities (Sites and Services) get every possible combination of their
 * existing WLCG VO and tierN scope tags in the new heirachical structure.
 * - a entity with alice and tier1 should get alice.tier1
 * - a entity with alice, atlas and tier2 should get alice.tier2 and
 *   atlas.tier2
 * - a entity with atlas and tier2 should get atlas.tier2
 * - a entity with alice, tier1 and tier2 should get alice.tier1 and
 *   alice.tier2
 * - a entity with alice, atlas, tier1 and tier2 should get
 *   - alice.tier1
 *   - alice.tier2
 *   - atlas.tier1
 *   - atlas.tier2
 *
 * This script is intended to be one time use, i.e. once it has run to
 * completion - I don't anticpate needing the script again.
 * However, effort has been taken to ensure the script can be safely
 * run muliptle times - i.e. should a update randomly fail, the whole
 * script can be re-run.
 *
 * Usage: php resources/wlcg-scope-tag-migration/WLCGScopeTagAddRunner.php
 */

require_once dirname(__FILE__) . "/../../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/Factory.php';

// This will supply the WLCG VO and tierN scope tags.
require_once dirname(__FILE__) . "/WLCGScopeTagList.php";


$sectionBreak = "=========================================================\n";
$em = $entityManager;

echo "Querying for all sites and services\n";
// Fetch all Sites using a Doctrine Query Language (DQL) query.
$siteDql = "SELECT s FROM Site s";
$allSitesList = $entityManager->createQuery($siteDql)->getResult();

// Fetch all Services using a Doctrine Query Language (DQL) query.
$serviceDql = "SELECT s FROM Service s";
$allServicesList = $entityManager->createQuery($serviceDql)->getResult();

// Merge into a single list to loop through.
$allEntitiesList = array_merge($allSitesList, $allServicesList);

echo "Starting update of Entities: " . date('D, d M Y H:i:s') . "\n";
echo $sectionBreak;

foreach ($allEntitiesList as $entity) {
    // Handle different ways to refer (in a human readable sense) to
    // different entities.
    if (get_class($entity) === "Site") {
        $shortName = $entity->getShortName();
    } elseif (get_class($entity) === "Service") {
        $shortName = $entity->getServiceType() . ': ' . $entity->getHostName();
    } else {
        echo "unexpected entity class! " . get_class($entity) . "\n";
        die();
    }

    echo $shortName . ":\n";

    $entityScopes = $entity->getScopes()->toArray();

    // Enities can support multiple CERN VOs - at any and multiple tiers.
    // Check each combination in turn.
    foreach ($wlcgScopesList as $wlcgScope) {
        // If the WLCG scope in question this iteration is not applied to the
        // entity, no need to check tier scopes.
        if (!in_array($wlcgScope, $entityScopes)) {
            continue;
        }

        foreach ($tierScopesList as $tierScope) {
            if (!in_array($tierScope, $entityScopes)) {
                // If the tier scope in question this iteration is not applied
                // to the entity, no need to progress any further.
                continue;
            }

            $newScopeName = $wlcgScope . "." . $tierScope;

            // check if new scope has been already applied
            // (from a previous run).
            if (in_array($newScopeName, $entityScopes)) {
                echo "Skipping, enitity already has " . $newScopeName . "\n";
                continue;
            }

            // apply new scope tag in a transaction.
            $em->getConnection()->beginTransaction();
            try {
                // need the object not the name to add to an entity.
                $entity->addScope($allScopeDict[$newScopeName]);
                $em->merge($entity);
                $em->flush();
                $em->getConnection()->commit();
                echo "Added " . $newScopeName . "\n";
            } catch (\Exception $ex) {
                $em->getConnection()->rollback();
                $em->close();
                throw $ex;
            }
        }
    }
}

$em->flush();
echo "Completed ok: " . date('D, d M Y H:i:s') . "\n";
