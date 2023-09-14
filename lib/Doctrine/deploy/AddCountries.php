<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";

/* AddCountries.php: Loads a list of countries from an XML file and inserts them into
 * the doctrine prototype.
 */

$countriesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/Countries.xml";
$countries = simplexml_load_file($countriesFileName);

foreach ($countries as $country) {
    $doctrineCountry = new Country();
    $code = "";
    $name = "";
    foreach ($country as $key => $value) {
        if ($key == "name") {
            $code = (string) $value;
        }

        if ($key == "description") {
            $name = (string) $value;
        }
    }

    $doctrineCountry->setName($name);
    $doctrineCountry->setCode($code);
    $entityManager->persist($doctrineCountry);
}

$entityManager->flush();
