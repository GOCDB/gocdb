<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/* AddNGIs.php: Loads a list of timezones from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the PROM GOCDB PI get_timezones_doctrine query.
 */
$timezonesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/Timezones.xml";
$timezones = simplexml_load_file($timezonesFileName);

foreach($timezones as $timezone) {
	$doctrineTimezone = new Timezone();
	$name = (string) $timezone;
	$doctrineTimezone->setName($name);
	$entityManager->persist($doctrineTimezone);
}

$entityManager->flush();