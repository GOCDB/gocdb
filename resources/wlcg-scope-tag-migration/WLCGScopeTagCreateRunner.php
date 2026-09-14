<?php

/*
 * Script to create new scope tags for the WLCG new heirachical structure.
 *
 * Desired Behaviour:
 * Create every combination of VO.tierN for the supplied WLCG VO and tierN
 * scope tags.
 *
 * This script is intended to be one time use, i.e. once it has run to
 * completion - I don't anticpate needing the script again.
 * However, effort has been taken to ensure the script can be safely
 * run muliptle times - i.e. should a update randomly fail, the whole
 * script can be re-run.
 *
 * Usage: php resources/wlcg-scope-tag-migration/WLCGScopeTagCreateRunner.php
 */

require_once dirname(__FILE__) . "/../../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/Factory.php';

// This will supply the WLCG VO and tierN scope tags.
require_once dirname(__FILE__) . "/WLCGScopeTagList.php";

$sectionBreak = "=========================================================\n";
$em = $entityManager;
$serv = \Factory::getScopeService();

echo "Starting creation of Scopes: " . date('D, d M Y H:i:s') . "\n";

foreach ($wlcgScopesList as $wlcgScope) {
    foreach ($tierScopesList as $tierScope) {
        $newScopeName = $wlcgScope . "." . $tierScope;

        echo "Trying to create " . $newScopeName . "\n";

        if (in_array($newScopeName, array_keys($allScopeDict))) {
            echo "Skipping " . $newScopeName . "\n";
            echo "It already exists\n";
            echo $sectionBreak;
            continue;
        }

        $newScope = new Scope();
        $newScope->setName($newScopeName);
        $newScope->setDescription("Tag for WLCG " . $tierScope . " " .
            "resources that support the " . $wlcgScope . " VO ");
        $newScope->setReserved(true);

        $em->getConnection()->beginTransaction();
        try {
            $em->persist($newScope);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $em->close();
            throw $e;
        }

        echo "Done" . "\n";
        echo $sectionBreak;
    }
}

$em->flush();
echo "Completed ok: " . date('D, d M Y H:i:s') . "\n";
