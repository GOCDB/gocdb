<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";
require __DIR__ . "/../../Gocdb_Services/Factory.php";

$em = \Factory::getEntityManager();
$serv = \Factory::getUserService();

$usersRolesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/UsersAndRoles.xml";
$usersRoles = simplexml_load_file($usersRolesFileName);
// Used to check for duplicates
$idStrings = array();

$em->getConnection()->beginTransaction();
try {
    foreach ($usersRoles as $user) {

        // CERTDN remains the XML tag for X.509
        // All ID strings are currently certificate DNs
        $idString = (string) trim($user->CERTDN);
        $authType = 'X.509';

        // Some User ID strings are present twice in the output XML
        // This is often because a user has two (or more!) home sites
        if (isset($idStrings[$idString])) {
            continue;
        }
        $idStrings[$idString] = true;

        $doctrineUser = new \User();
        $identifierArr = array($authType, $idString);
        $doctrineUser->setForename((string) $user->FORENAME);
        $doctrineUser->setSurname((string) $user->SURNAME);
        $doctrineUser->setTitle((string) $user->TITLE);
        $doctrineUser->setEmail((string) $user->EMAIL);
        $doctrineUser->setTelephone((string) $user->TEL);
        $doctrineUser->setWorkingHoursStart((string) $user->WORKING_HOURS_START);
        $doctrineUser->setWorkingHoursEnd((string) $user->WORKING_HOURS_END);
        $doctrineUser->setAdmin(false);

        // Roughly half of users don't have a home site set
        if ($user->HOMESITE !== "" &&  !isBad($user->HOMESITE)) {
            // Get the home site entity
            $dql = "SELECT s from Site s WHERE s.shortName = ?1";
            $homeSites = $em->createQuery($dql)
                    ->setParameter(1, (string) $user->HOMESITE)
                    ->getResult();

            /* Error checking: ensure each "home site" refers to exactly
            * one home site */
            if (count($homeSites) !== 1) {
                throw new \Exception(count($homeSites) . " sites found with short name: " .
                        $user->HOMESITE . ". user DN is  " . $user->CERTDN);
            }
            foreach ($homeSites as $result) {
                $homeSite = $result;
            }
            $doctrineUser->setHomeSiteDoJoin($homeSite);
        }

        $em->persist($doctrineUser);
        $em->flush();
        $serv->addUserIdentifier($doctrineUser, $identifierArr, $doctrineUser);
    }
    $em->getConnection()->commit();
} catch (\Exception $e) {
    $em->getConnection()->rollback();
    $em->close();
    throw $e;
}
