<?php

require_once __DIR__."/../bootstrap.php";
require_once __DIR__."/AddUtils.php";

/** 
 * AddEgiRoles.php: Loads a list of roles from the get_user PI
 * query output (XML), finds the EGI roles and inserts them into
 * the doctrine prototype.
 * XML format is the output from get_roc_list PI query, e.g:
 

 <EGEE_USER USER_ID=" " PRIMARY_KEY="63777G0">
        <FORENAME>Patricia</FORENAME>
        <SURNAME>Gomes</SURNAME>
        <TITLE>Miss</TITLE>
        <DESCRIPTION></DESCRIPTION>
        <GOCDB_PORTAL_URL>https://testing.host.com/portal/index.php?Page_Type=View_Object&amp;object_id=113158&amp;grid_id=0</GOCDB_PORTAL_URL>
        <EMAIL>pgomes@cbpf.br</EMAIL>
        <TEL>+55(21)21417419</TEL>
        <WORKING_HOURS_START></WORKING_HOURS_START>
        <WORKING_HOURS_END></WORKING_HOURS_END>
        <CERTDN>/C=BR/O=ICPEDU/O=UFF BrGrid CA/O=CBPF/OU=LAFEX/CN=Patricia Gomes</CERTDN>
        <APPROVED></APPROVED>
        <ACTIVE></ACTIVE>
        <HOMESITE></HOMESITE>
        <USER_ROLE>
            <USER_ROLE>Regional First Line Support</USER_ROLE>
            <ON_ENTITY>ROC_LA</ON_ENTITY>
            <ENTITY_TYPE>group</ENTITY_TYPE>
        </USER_ROLE>
        <USER_ROLE>
            <USER_ROLE>Site Operations Deputy Manager</USER_ROLE>
            <ON_ENTITY>CBPF</ON_ENTITY>
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
        if((string) $role->ENTITY_TYPE !== "group") {
            continue;
        }
        
        // Skip all non-EGI level roles
        if((string) $role->ON_ENTITY != "EGI") {
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
        
        
        // get user entity
        $dql = "SELECT u FROM User u WHERE u.certificateDn = ?1";
        $users = $entityManager->createQuery($dql)
                                     ->setParameter(1, trim((string) $user->CERTDN))
                                     ->getResult();
        // /* Error checking: ensure each "user" refers to exactly
         // * one user */
        if(count($users) !== 1) {
            foreach($users as $u) {
                echo "Certificate DN is " . $u->getCertificateDn() . "-------";
            }
            throw new Exception(count($users) . " users found with DN: " . 
                $user->CERTDN);
        }
        foreach($users as $doctrineUser) {
            $doctrineUser = $doctrineUser;
        }
        
        $doctrineRole = new Role($roleType, $doctrineUser, $egi, 'STATUS_GRANTED'); 
        $entityManager->persist($doctrineRole);
    }
}

$entityManager->flush();