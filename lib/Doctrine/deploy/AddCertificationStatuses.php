<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";
/* AddCertificationStatuses.php: Loads a list of cert statuses from
 * an XML file and inserts them into the doctrine prototype.
 * XML format is the xml input format of the cert status seed data.
 */
$certStatsFileName = __DIR__ . "/" . $GLOBALS['dataDir'] .
    "/CertificationStatuses.xml";
$certStats = simplexml_load_file($certStatsFileName);

foreach ($certStats as $certStat) {
    $doctrineCertStat = new CertificationStatus();
    $name = "";
    foreach ($certStat as $key => $value) {
        if ($key == "name") {
            $name = (string) $value;
        }
    }

    $doctrineCertStat->setName($name);
    $entityManager->persist($doctrineCertStat);
}

$entityManager->flush();
