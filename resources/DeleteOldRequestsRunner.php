<?php

require_once dirname(__FILE__) . "/../lib/Doctrine/bootstrap.php";
require dirname(__FILE__) . '/../lib/Doctrine/bootstrap_doctrine.php';
require_once dirname(__FILE__) . '/../lib/Gocdb_Services/Factory.php';

// Use UTC (set elsewhere by index.php)
date_default_timezone_set("UTC");

echo "Querying identity linking and account recovery requests\n\n";

$em = $entityManager;
$dql = "SELECT l FROM LinkIdentityRequest l";
$requests = $entityManager->createQuery($dql)->getResult();

// Remove requests older than one day
$nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
$oneDay = \DateInterval::createFromDateString('1 days');
$yesterdayUtc = $nowUtc->sub($oneDay);

echo "Starting scan of request creation dates at: " . $nowUtc->format('D, d M Y H:i:s') . "\n\n";

foreach ($requests as $request) {

    $creationDate = $request->getCreationDate()->setTimezone(new \DateTimeZone('UTC'));

    if ($yesterdayUtc > $creationDate) {
        echo "Deleting request ID " . $request->getId() . " (creation date: " . $creationDate->format('D, d M Y H:i:s') . ")\n\n";
        $em->remove($request);
    }
}

$em->flush();

$nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
echo "Completed ok: " . $nowUtc->format('D, d M Y H:i:s');
