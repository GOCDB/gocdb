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

        // Skip all non-serviceGroup roles
        if ((string) $role->ENTITY_TYPE !== "serviceGroup") {
            continue;
        }

        // get roletype entity
        $userRole = (string) $role->USER_ROLE;
        $dql = "SELECT rt FROM RoleType rt WHERE rt.name = :roleType";
        $roleTypes = $entityManager->createQuery($dql)
                                   ->setParameter(':roleType', $userRole)
                                   ->getResult();

        /*
         * Error checking: ensure each role type refers to exactly
         * one role type
         */
        if (count($roleTypes) !== 1) {
            throw new Exception(count($roleTypes) . " role types " .
                "found with name: " . $userRole);
        }

        // Set $roleType as the first and only role type in the
        // roleTypes array
        $roleType = $roleTypes[0];

        // Get user entity
        $userDN = (string) $user->CERTDN;
        $dql = "SELECT u FROM User u JOIN u.userIdentifiers up " .
            "WHERE up.keyValue = :keyValue";
        $users = $entityManager->createQuery($dql)
                               ->setParameter('keyValue', trim($userDN))
                               ->getResult();

        /*
         * Error checking: ensure each "user" refers to exactly
         * one user
         */
        if (count($users) !== 1) {
            throw new Exception(count($users) . " users found with DN: " .
                $userDN);
        }

        // Set $doctrineUser as the first and only user in the users array
        $doctrineUser = $users[0];

        // get serviceGroup entity
        $sgName = (string) $role->ON_ENTITY;
        $dql = "SELECT sg FROM ServiceGroup sg WHERE sg.name = :service_group";
        $serviceGroups = $entityManager->createQuery($dql)
                                       ->setParameter('service_group', $sgName)
                                       ->getResult();

        /*
         * Error checking: ensure each "service group" refers to exactly
         * one service group
         */
        if (count($serviceGroups) !== 1) {
            throw new Exception(count($serviceGroups) . " Service Groups " .
                "found name: " . $sgName);
        }

        // Set $serviceGroup as the first and only service group in the
        // serviceGroups array
        $serviceGroup = $serviceGroups[0];

        $doctrineRole = new Role(
            $roleType,
            $doctrineUser,
            $serviceGroup,
            'STATUS_GRANTED'
        );
        $entityManager->persist($doctrineRole);
    }
}

$entityManager->flush();
