<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/**
 * AddNGIs.php: Loads a list of Site roles from an XML file and inserts them into
 * the doctrine prototype.
 * XML format is the output from get_user_doctrine PI query.
 */
$usersRolesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/UsersAndRoles.xml";
$usersRoles = simplexml_load_file($usersRolesFileName);

foreach($usersRoles as $user) {
    foreach($user->USER_ROLE as $role) {
        // Check for blank role, skip if it's blank
        if((string) $role->USER_ROLE == "") {
            continue;
        }

        // Skip all non-site roles
        if((string) $role->ENTITY_TYPE !== "site") {
            continue;
        }

        // Find the role type
        // get the roletype entity
        $dql = "SELECT rt FROM RoleType rt WHERE rt.name = ?1";
        $roleTypes = $entityManager->createQuery($dql)
                                     ->setParameter(1, (string) $role->USER_ROLE)
                                     ->getResult();
        // /* Error checking: ensure each role type refers to exactly
         // * one role type*/
        if(count($roleTypes) !== 1) {
            throw new Exception(count($roleTypes) . " role types found with name: " .
                $role->USER_ROLE);
        }
        foreach($roleTypes as $result) {
            $roleType = $result;
        }
        if(!($roleType instanceof RoleType)) {
            throw new Exception("Not a doctrine role type");
        }

        // Get user entity
        $dql = "SELECT u FROM User u JOIN u.userIdentifiers up WHERE up.keyValue = :keyValue";
        $users = $entityManager->createQuery($dql)
                  ->setParameter('keyValue', trim((string) $user->CERTDN))
                  ->getResult();

        // /* Error checking: ensure each "user" refers to exactly
         // * one user */
        if(count($users) !== 1) {
            throw new Exception(count($users) . " users found with DN: " .
                $user->CERTDN);
        }

        foreach($users as $doctrineUser) {
            $doctrineUser = $doctrineUser;
        }

        if(!($doctrineUser instanceof User)) {
            throw new Exception("Not a doctrine user");
        }

        // Check for invalid sites and skip adding this role
        // typically these sites don't have an NGI, country or production status
        if(isBad((string) $role->ON_ENTITY)) {
            continue;
        }

        // get the site entity
        $dql = "SELECT s FROM Site s WHERE s.shortName = ?1";
        $sites = $entityManager->createQuery($dql)
                                     ->setParameter(1, (string) $role->ON_ENTITY)
                                     ->getResult();
        // /* Error checking: ensure each "site" refers to exactly
         // * one site */
        if(count($sites) !== 1) {
            throw new Exception(count($sites) . " sites found with short name: " .
                $role->ON_ENTITY);
        }
        foreach($sites as $doctrineSite) {
            $doctrineSite = $doctrineSite;
        }
        if(!($doctrineSite instanceof Site)) {
            throw new Exception("Not a doctrine site");
        }

        //check that the role is not a duplicate (v4 data contaisn duplicates)
        $ExistingUserRoles = $doctrineUser->getRoles();
        $thisIsADuplicateRole=false;
        foreach($ExistingUserRoles as $role){
            if($role->getRoleType() == $roleType and $role->getOwnedEntity() == $doctrineSite and $role->getStatus() == 'STATUS_GRANTED'){
                $thisIsADuplicateRole = true;
            }
        }

        if(!$thisIsADuplicateRole){
            $doctrineRole = new Role($roleType, $doctrineUser, $doctrineSite, 'STATUS_GRANTED');
            $entityManager->persist($doctrineRole);
        }
    }
}
$entityManager->flush();
