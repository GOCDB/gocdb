<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/**
 * AddEgiRoles.php: Loads a list of roles from the get_user PI
 * query output (XML), finds whether they are EGI roles or not
 * and if they are, adds them to the EGI project and 
 * insert them into the doctrine prototype.
 * If they are not an EGI role, they are not added to a project
 * XML format is the output from get_roc_list PI query, e.g:


 <EGEE_USER USER_ID="468344" PRIMARY_KEY="22434G0">
        <FORENAME>Yuki</FORENAME>
        <SURNAME>Tsunoda</SURNAME>
        <TITLE></TITLE>
        <DESCRIPTION></DESCRIPTION>
        <GOCDB_PORTAL_URL>https://testing.host.com/portal/index.php?Page_Type=View_Object&amp;object_id=113158&amp;grid_id=0</GOCDB_PORTAL_URL>
        <EMAIL>Yuki.Tsunoda@izolamrf.si</EMAIL>
        <TEL>+55(21)21417419</TEL>
        <WORKING_HOURS_START></WORKING_HOURS_START>
        <WORKING_HOURS_END></WORKING_HOURS_END>
        <CERTDN>/C=BR/O=ICPEDU/O=UFF BrGrid CA/O=CBPF/OU=LAFEX/CN=Yuki Tsunoda</CERTDN>
        <APPROVED></APPROVED>
        <ACTIVE></ACTIVE>
        <HOMESITE>Izola MRF</HOMESITE>
        <USER_ROLE>
            <USER_ROLE>Chief Operations Officer</USER_ROLE>
            <ON_ENTITY>EGI</ON_ENTITY>
            <ENTITY_TYPE>project</ENTITY_TYPE>
        </USER_ROLE>
        <USER_ROLE>
            <USER_ROLE>Site Operations Manager</USER_ROLE>
            <ON_ENTITY>Izola MRF</ON_ENTITY>
            <ENTITY_TYPE>site</ENTITY_TYPE>
        </USER_ROLE>
    </EGEE_USER>
 */
$usersRolesFileName = __DIR__ . "/" . $GLOBALS['dataDir'] . "/UsersAndRoles.xml";
$usersRoles = simplexml_load_file($usersRolesFileName);

// Find the EGI project object
$egi = $entityManager->getRepository('Project')->findOneBy(array("name" => "EGI"));

foreach($usersRoles as $user) {
    foreach($user->USER_ROLE as $role) {
        // Check for blank role, skip if it's blank
        if((string) $role->USER_ROLE == "") {
            continue;
        }

        // Skip all non-site roles
        if ((string) $role->ENTITY_TYPE !== "project") {
            continue;
        }

        // get roletype entity
        $dql = "SELECT rt FROM RoleType rt WHERE rt.name = :roleType";
        $roleTypes = $entityManager->createQuery($dql)
                                     ->setParameter(':roleType', (string) $role->USER_ROLE)
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

        if ((string) $role->ON_ENTITY == "EGI") {
            $doctrineRole = new Role($roleType, $doctrineUser, $egi, 'STATUS_GRANTED');
            $entityManager->persist($doctrineRole);
        } else {
            continue;
        }
    }
}

$entityManager->flush();
