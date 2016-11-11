<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

$usersRolesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/UsersAndRoles.xml";
$usersRoles = simplexml_load_file($usersRolesFileName);
// Used to check for duplicates
$userDNs = array();

// Used to check for DNs that are identical apart from right hand whitespace
//$rightWhiteDNs = array();

// Used to check for duplicates
//$DNs = array();
//$users = array();
foreach($usersRoles as $user) {
    $dn = (string) trim($user->CERTDN);
    // Some User DNs are present twice in the output XML
    // This is often because a user has two (or more!) home sites
    if(isset($userDNs[$dn])) {
        continue;
    }
    $userDNs[$dn] = true;

    /* Record the number of DNs
     * that are identical apart from
     * right hand whitespace
     */
//     if(isset($rightWhiteDNs[rtrim($dn)])) {
//     	echo "Identical DN inserted apart from right hand whitespace: "
//     			. $dn . "-----------\r\n";
//     	continue;
//     }
//     $rightWhiteDNs[rtrim($dn)] = true;

    $doctrineUser = new User();
    $doctrineUser->setForename((string) $user->FORENAME);
    $doctrineUser->setSurname((string) $user->SURNAME);
    $doctrineUser->setTitle((string) $user->TITLE);
    $doctrineUser->setEmail((string) $user->EMAIL);
    $doctrineUser->setTelephone((string) $user->TEL);
    $doctrineUser->setWorkingHoursStart((string) $user->WORKING_HOURS_START);
    $doctrineUser->setWorkingHoursEnd((string) $user->WORKING_HOURS_END);
    //$doctrineUser->setCertificateDn((string) $user->CERTDN);
    $doctrineUser->setCertificateDn($dn);
    $doctrineUser->setAdmin(false);
//  echo "DN is " . (string) $doctrineUser->getCertificateDn() . ".\r\n";

    // Roughly half of users don't have a home site set
    if($user->HOMESITE != "" &&  !isBad($user->HOMESITE)) {
        // get the home site entity
        $dql = "SELECT s from Site s WHERE s.shortName = ?1";
        $homeSites = $entityManager->createQuery($dql)
                ->setParameter(1, (string) $user->HOMESITE)
                ->getResult();

        /* Error checking: ensure each "home site" refers to exactly
         * one home site */
        if (count($homeSites) !== 1) {
            throw new Exception(count($homeSites) . " sites found with short name: " .
                    $user->HOMESITE . ". user DN is  " . $user->CERTDN);
        }
        foreach ($homeSites as $result) {
            $homeSite = $result;
        }
        $doctrineUser->setHomeSiteDoJoin($homeSite);
    }

    //Make Dave an admin
    if ($doctrineUser->getCertificateDn()=="/C=UK/O=eScience/OU=CLRC/L=DL/CN=david meredith"){
        $doctrineUser->setAdmin(true);
    }

    $entityManager->persist($doctrineUser);

}



$entityManager->flush();