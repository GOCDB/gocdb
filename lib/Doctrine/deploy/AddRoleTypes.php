<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/AddUtils.php";
require_once __DIR__ . "/../../Gocdb_Services/RoleConstants.php";
/* AddRoleTypes.php: Manually inserts role types into
 * the doctrine prototype.
 */

/*$roleTypeArray = array (
    array("Site Administrator", "C"),
    array("Site Security Officer", "C'"),
    array("Site Operations Deputy Manager", "C'"),
    array("Site Operations Manager", "C'"),

    array("Regional First Line Support", "D"),
    array("Regional Staff (ROD)", "D"),
    array("NGI Security Officer", "D'"),
    array("NGI Operations Deputy Manager", "D'"),
    array("NGI Operations Manager", "D'"),

    array("COD Staff", "E"),
    array("COD Administrator", "E"),
    array("EGI CSIRT Officer", "E"),
    array("Chief Operations Officer", "E"),

    array("Service Group Administrator", "ServiceGroupC'"),

    // "Other" roles that have slipped by us
    array("CIC Staff", ""),
    array("Regional Staff", "")
);

foreach ($roleTypeArray as $roleType) {
    $rt = new RoleType($roleType[0], $roleType[1]);
    //$rt->setName($roleType[0]);
    //$rt->setClassification($roleType[1]);
    $entityManager->persist($rt);
}*/

$roleTypeArray = RoleTypeName::getAsArray();

foreach ($roleTypeArray as $key => $value) {
    $rt = new RoleType($value);
    //echo $value;
    if ($value != RoleTypeName::GOCDB_ADMIN){
        $entityManager->persist($rt);
    }
}

$entityManager->flush();
