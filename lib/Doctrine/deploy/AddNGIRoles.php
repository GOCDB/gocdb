<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";

$usersRolesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] .
    "/UsersAndRoles.xml";
$usersRoles = simplexml_load_file($usersRolesFileName);

foreach ($usersRoles as $user) {
    foreach ($user->USER_ROLE as $role) {
        // Check for blank role, skip if it's blank
        if ((string) $role->USER_ROLE == "") {
            continue;
        }

        // Skip all non-ngi roles
        if ((string) $role->ENTITY_TYPE !== "ngi") {
            continue;
        }

        // get roletype entity
        $dql = "SELECT rt FROM RoleType rt WHERE rt.name = :roleType";
        $roleTypes = $entityManager->createQuery($dql)
                                   ->setParameter(
                                       ':roleType',
                                       (string) $role->USER_ROLE
                                   )
                                   ->getResult();
        // /* Error checking: ensure each role type refers to exactly
         // * one role type*/
        if (count($roleTypes) !== 1) {
            throw new Exception(count($roleTypes) . " role types found " .
                "with name: " . $role->USER_ROLE);
        }

        foreach ($roleTypes as $result) {
            $roleType = $result;
        }

        // Get user entity
        $dql = "SELECT u FROM User u JOIN u.userIdentifiers " .
            "up WHERE up.keyValue = :keyValue";
        $users = $entityManager->createQuery($dql)
                               ->setParameter(
                                   'keyValue',
                                   trim((string) $user->CERTDN)
                               )
                               ->getResult();

        // /* Error checking: ensure each "user" refers to exactly
         // * one user */
        if (count($users) !== 1) {
            throw new Exception(count($users) . " users found with DN: " .
                $user->CERTDN);
        }

        foreach ($users as $doctrineUser) {
            $doctrineUser = $doctrineUser;
        }

        // Check for invalid NGIs and skip
        // typically these are decomissioned ROCs
        if (
            $role->ON_ENTITY == 'GridIreland'
            || $role->ON_ENTITY == 'NGS'
            || $role->ON_ENTITY == 'LondonT2'
            || $role->ON_ENTITY == 'Tier1A'
            || $role->ON_ENTITY == 'Tier1A'
        ) {
            continue;
        }

        // get ngi entity
        $ngiName = (string) $role->ON_ENTITY;
        $dql = "SELECT n FROM NGI n WHERE n.name = :ngi";
        $ngis = $entityManager->createQuery($dql)
                              ->setParameter(
                                  'ngi',
                                  $ngiName
                              )
                              ->getResult();
        // /* Error checking: ensure each "ngi" refers to exactly
         // * one ngi */
        if (count($ngis) !== 1) {
            throw new Exception(count($ngis) . " ngis found name: " .
                $ngiName);
        }

        foreach ($ngis as $ngi) {
            $ngi = $ngi;
        }

        //check that the role is not a duplicate (v4 data contaisn duplicates)
        $ExistingUserRoles = $doctrineUser->getRoles();
        $thisIsADuplicateRole=false;
        foreach ($ExistingUserRoles as $role) {
            if (
                $role->getRoleType() == $roleType
                and $role->getOwnedEntity() == $ngi
                and $role->getStatus() == 'STATUS_GRANTED'
            ) {
                $thisIsADuplicateRole = true;
            }
        }

        if (!$thisIsADuplicateRole) {
            $doctrineRole = new Role(
                $roleType,
                $doctrineUser,
                $ngi,
                'STATUS_GRANTED'
            );
            $entityManager->persist($doctrineRole);
        }
    }
}

$entityManager->flush();
