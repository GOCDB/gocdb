<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";

/**
 * AddEgiRoles.php: Loads a list of roles from the get_user PI
 * query output (XML), finds what project entity the role is over,
 * if the project entity refers to exactly one project the role is
 * added to that project and inserted into the doctrine prototype.
 * If the entity is not a project, the project doesn't exist or the
 * project exists more than once, the role is not added to a project.
 */

$usersRolesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] .
    "/UsersAndRoles.xml";
$usersRoles = simplexml_load_file($usersRolesFileName);

foreach ($usersRoles as $user) {
    foreach ($user->USER_ROLE as $role) {
        // Check for blank role, skip if it's blank
        if ((string) $role->USER_ROLE == "") {
            continue;
        }

        // Skip all non-project roles
        if ((string) $role->ENTITY_TYPE !== "project") {
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
        $dql = "SELECT u FROM User u JOIN u.userIdentifiers up " .
            "WHERE up.keyValue = :keyValue";
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

        // Finding the project entity the role is over
        $projectName = (string) $role->ON_ENTITY;

        // Querying the project entity
        $dql = "SELECT p FROM Project p WHERE p.name = :project";
        $projects = $entityManager->createQuery($dql)
                                  ->setParameter(
                                      'project',
                                      $projectName
                                  )
                                  ->getResult();

        // Error check: ensure each 'project' refers to exactly one project
        if (count($projects) !== 1) {
            throw new Exception(count($projects) . " Projects found " .
                "with name: " . $projectName);
        }

        // Finding the project object and adding the role to it
        $getProject = $entityManager->getRepository('Project')
                                    ->findOneBy(
                                        array("name" => $projectName)
                                    );
        $doctrineRole = new Role(
            $roleType,
            $doctrineUser,
            $getProject,
            'STATUS_GRANTED'
        );
        $entityManager->persist($doctrineRole);
    }
}

$entityManager->flush();
