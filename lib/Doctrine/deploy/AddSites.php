<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";

/* AddSites.php: Loads a list of sites from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the output from get_site PI query.
 */
$sitesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/Sites.xml";
$sites = simplexml_load_file($sitesFileName);

$xmlCertStatusChanges = simplexml_load_file( __DIR__ . "/" . $GLOBALS['dataDir'] . "/CertStatusChanges.xml");
$xmlCertStatusLinkDates = simplexml_load_file(__DIR__ . "/" . $GLOBALS['dataDir'] . "/CertStatusDate.xml");

$largestV4SitePk = 0;
foreach($sites as $xmlSite) {

    // Check whether this site has a larger v4 primary key
    // than any other recorded so far
    $v4pkGO = trim((string) $xmlSite->PRIMARY_KEY);
    // isolate just the number part (slice the 'GO' off the end)
    $v4pk = (int)substr($v4pkGO, 0, strlen($v4pkGO)-2);
    if($v4pk > $largestV4SitePk){
        $largestV4SitePk = $v4pk;
    }


    $doctrineSite = new Site();
    $doctrineSite->setPrimaryKey((string) $xmlSite->PRIMARY_KEY);
    $doctrineSite->setOfficialName((string) $xmlSite->OFFICIAL_NAME);
    $doctrineSite->setShortName((string)$xmlSite->SHORT_NAME);
    $doctrineSite->setDescription((string) $xmlSite->SITE_DESCRIPTION);
    $doctrineSite->setHomeUrl((string) $xmlSite->HOME_URL);
    $doctrineSite->setEmail((string) $xmlSite->CONTACT_EMAIL);
    $doctrineSite->setTelephone((string) $xmlSite->CONTACT_TEL);
    $doctrineSite->setGiisUrl((string) $xmlSite->GIIS_URL);
    if(strlen((string)$xmlSite->LATITUDE) > 0){
        $doctrineSite->setLatitude((float)$xmlSite->LATITUDE);
    }
    if(strlen((string)$xmlSite->LONGITUDE) > 0){
        $doctrineSite->setLongitude((float)$xmlSite->LONGITUDE);
    }
    $doctrineSite->setCsirtEmail((string) $xmlSite->CSIRT_EMAIL);
    $doctrineSite->setIpRange((string) $xmlSite->IP_RANGE);
    $doctrineSite->setDomain((string) $xmlSite->DOMAIN->DOMAIN_NAME);
    $doctrineSite->setLocation((string) $xmlSite->LOCATION);
    $doctrineSite->setCsirtTel((string) $xmlSite->CSIRTTEL);
    $doctrineSite->setEmergencyTel((string) $xmlSite->EMERGENCYTEL);
    $doctrineSite->setEmergencyEmail((string) $xmlSite->EMERGENCYEMAIL);
    $doctrineSite->setAlarmEmail((string) $xmlSite->ALARM_EMAIL);
    $doctrineSite->setHelpdeskEmail((string) $xmlSite->HELPDESKEMAIL);

    // get the parent NGI entity
    $dql = "SELECT n FROM NGI n WHERE n.name = ?1";
    $parentNgis = $entityManager->createQuery($dql)
                                 ->setParameter(1, (string) $xmlSite->ROC)
                                 ->getResult();
    // /* Error checking: ensure each SE's "parent ngi" refers to exactly
     // * one ngi */
    if(count($parentNgis) !== 1) {
        throw new Exception(count($parentNgis) . " NGIs found with name: " .
            $xmlSite->ROC);
    }
    foreach($parentNgis as $result) {
        $parentNgi = $result;
    }
    $doctrineSite->setNgiDoJoin($parentNgi);

    // get the target infrastructure
    $dql = "SELECT i FROM Infrastructure i WHERE i.name = :name";
    $infs = $entityManager->createQuery($dql)
                                 ->setParameter('name', (string) $xmlSite->PRODUCTION_INFRASTRUCTURE)
                                 ->getResult();
    // /* Error checking: ensure each SE's "PRODUCTION_INFRASTRUCTURE" refers to exactly
     // * one PRODUCTION_INFRASTRUCTURE */
    if(count($infs) !== 1) {
        throw new Exception(count($infs) . " Infrastructures found with name: " .
            $xmlSite->PRODUCTION_INFRASTRUCTURE);
    }
    foreach($infs as $inf) {
        $inf = $inf;
    }
    $doctrineSite->setInfrastructure($inf);

    // get the cert status
    $dql = "SELECT c FROM CertificationStatus c WHERE c.name = ?1";
    $certStatuses = $entityManager->createQuery($dql)
                                 ->setParameter(1, (string) $xmlSite->CERTIFICATION_STATUS)
                                 ->getResult();
    /* Error checking: ensure each Site's "cert status" refers to exactly
     * one cert status */
    if(count($certStatuses) !== 1) {
        throw new Exception(count($certStatuses) . " cert statuses found with name: " .
            $xmlSite->CERTIFICATION_STATUS);
    }
    foreach($certStatuses as $certStatus) {
        $certStatus = $certStatus;
    }
    $doctrineSite->setCertificationStatus($certStatus);

    $doctrineSite->addScope(getScope($entityManager, (string) $xmlSite->SCOPE));

    // get / set the country
    $dql = "SELECT c FROM Country c WHERE c.name = ?1";
    $countries = $entityManager->createQuery($dql)
                                 ->setParameter(1, (string) $xmlSite->COUNTRY)
                                 ->getResult();
    /* Error checking: ensure each country refers to exactly
     * one country */
    if(count($countries) !== 1) {
        throw new Exception(count($countries) . " country found with name: " .
            $xmlSite->COUNTRY);
    }
    foreach($countries as $country) {
        $country = $country;
    }
    $doctrineSite->setCountry($country);

    $doctrineSite->setTimezoneId('UTC');


    // get the Tier (optional value)
    $dql = "SELECT t FROM Tier t WHERE t.name = ?1";
    $tiers = $entityManager->createQuery($dql)
                                 ->setParameter(1, (string) $xmlSite->TIER)
                                 ->getResult();
    /* Error checking: ensure each tier refers to exactly
     * one TIER */
    if(count($tiers) == 1) {
        foreach($tiers as $tier) {
            $tier = $tier;
        }

        $doctrineSite->setTier($tier);
    }

    // get the SubGrid (optional value)
    $dql = "SELECT s FROM SubGrid s WHERE s.name = ?1";
    $subGrids = $entityManager->createQuery($dql)
        ->setParameter(1, (string) $xmlSite->SUBGRID)
        ->getResult();
    /* Error checking: ensure each subgrid refers to exactly
     * one subgrid */
    if(count($subGrids) == 1) {
        foreach($subGrids as $subGrid) {
            $subGrid = $subGrid;
        }

        $doctrineSite->setSubGrid($subGrid);
    }



    //set creation date
    $creationDate = new \DateTime("now", new DateTimeZone('UTC'));

    $doctrineSite->setCreationDate($creationDate);


    // The date of the CURRENT certStatus in v4 is recorded as
    // a link/linkType object using the dateOn property. For simplicity, we
    // store this date as an attribute on the Site.
    foreach($xmlCertStatusLinkDates as $xmlCertStatusLinkDate){
       $targetSiteName = (string) $xmlCertStatusLinkDate->name;
       // only interested in the current site
       if($targetSiteName == $doctrineSite->getShortName()){
          // '01-JUL-13 11.09.10.000000 AM' which has the php datetime
          // format of 'd-M-y H.i.s A' provided we trim off the '.000000' (millisecs)
          // Note, '.000000' is present in all the <cert_date> elements.
          $xmlLinkDateString = (string) $xmlCertStatusLinkDate->cert_date;
          $xmlLinkDateString = preg_replace('/\.000000/', "", $xmlLinkDateString);
          $linkDate =  \DateTime::createFromFormat('d-M-y H.i.s A', $xmlLinkDateString, new \DateTimeZone('UTC'));
          if(!$linkDate) {
              throw new Exception("Can't parse date/time  " . $xmlLinkDateString . " for site " .
                      $doctrineSite->getShortName() . ". Correct format: 27-JUL-11 02.02.03 PM" );
          }

          $doctrineSite->setCertificationStatusChangeDate($linkDate);

       }
    }


    // Add the Site's certification status history/log.
    // If the Site certStatus has never been updated from its initial state,
    // then no changes will have occurred and the log will be empty for that Site.
    //
    // Importantly, because the v4 certStatus change log was added AFTER some
    // sites were already added to GOCDB4, the LAST AddedDate does NOT
    // necessarily correspond with the date of the CURRENT certification status.
    // Rather, the date of the CURRENT certStatus in v4 is recorded as
    // a link/linkType object using the dateOn property.
    foreach($xmlCertStatusChanges as $xmlCertStatusChange){
       $targetSiteName = (string) $xmlCertStatusChange->SITE;
       // only interested in the current site
       if($targetSiteName == $doctrineSite->getShortName()){
           $doctrineCertStatusChangeLog = new \CertificationStatusLog();
           $doctrineCertStatusChangeLog->setAddedBy((string) $xmlCertStatusChange->CHANGED_BY);
           $doctrineCertStatusChangeLog->setOldStatus((string) $xmlCertStatusChange->OLD_STATUS);
           $doctrineCertStatusChangeLog->setNewStatus((string) $xmlCertStatusChange->NEW_STATUS);
           $doctrineCertStatusChangeLog->setReason((string) $xmlCertStatusChange->COMMENT);
           $insertDate = new DateTime("@" . (string) $xmlCertStatusChange->UNIX_TIME);
           $doctrineCertStatusChangeLog->setAddedDate($insertDate);
           $entityManager->persist($doctrineCertStatusChangeLog);
           $doctrineSite->addCertificationStatusLog($doctrineCertStatusChangeLog);
       }
    }

    $entityManager->persist($doctrineSite);

}

// echo "\nPersisting Sites";
// $i = 0;
// foreach($allSites as $site) {
//     $i++;
//     $entityManager->persist($site);
//     // Flush periodically to free memory.
//     if($i % 10000 == 0) {
//         echo ".";
//         $entityManager->flush();
//     }
// }
// echo "Done\n";

try {
    $entityManager->flush();
} catch (Exception $e) {
    echo $e->getMessage();
}
