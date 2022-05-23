<?php

require_once dirname(__FILE__) . "/bootstrap.php";
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/Role.php';

/**
 *  A collection of helper functions to create common GocDB objects.
 *
 * @author David Meredith
 * @author James McCarthy
 */
class TestUtil {

    public static function createRoleActionRecord(){
         /*$rar = new RoleActionRecord($updatedByUserId, $updatedByUserPrinciple,
                $roleId, $rolePreStatus, $roleNewStatus, $roleTypeId,
                $roleTypeName, $roleTargetOwnedEntityId, $roleTargetOwnedEntityType,
                $roleUserId, $roleUserPrinciple); */
        $rar = new RoleActionRecord(1, '/some/DN/updatedBy',
        2, 'STATUS_PENDING', 'STATUS_GRANTED', 3,
        'Site Administrator', 4, 'Site',
        5, '/some/DN/roleOwner');
        return $rar;
    }

    public static function createSampleDowntime($severity = 'OUTAGE', $classification = 'SCHEDULED'){
        $downtime = new Downtime();
        $downtime->setDescription("A sample skeleton downtime");
        $downtime->setSeverity($severity);
        $downtime->setClassification($classification);
        return $downtime;
    }

    public static function createSampleEndpointLocation(){
        $epl = new EndpointLocation();
        $epl->setUrl("https://google.co.uk");
        $epl->setName("JustSomeEndpoint");
        return $epl;
    }

   public static function createSampleService($label){
        $serv = new Service();
        $serv->setHostName(''.$label);
        $serv->setEmail('sample@em.ail');
        $serv->setBeta(false);
        $serv->setProduction(true);
        $serv->setMonitored(true);
        return $serv;
    }

    public static function createSampleServiceType($name){
         $stype = new ServiceType();
         $stype->setName($name);
         $stype->setDescription('sample service type');
         return $stype;
     }

    public static function createSampleRoleType($name) {
        $rtype = new RoleType($name);
        return $rtype;
    }

    public static function createSampleUser($forename, $surname) {
        $user = new User();
        $user->setForename($forename);
        $user->setSurname($surname);
        $user->setAdmin(FALSE);
        return $user;
    }

    public static function createSampleNGI($label) {
        $doctrineNgi = new NGI();
        $doctrineNgi->setName($label);
        $doctrineNgi->setDescription($label . 'description');
        $doctrineNgi->setEmail($label . 'email');
        $doctrineNgi->setRodEmail($label . 'rodEmail');
        $doctrineNgi->setHelpdeskEmail($label . 'helpdeskEmail');
        $doctrineNgi->setSecurityEmail($label . 'securityEmail');
        return $doctrineNgi;
    }

    public static function createSampleRole(\User $user, \RoleType $roleType, \OwnedEntity $ownedEntity, $roleStatus) {
        return new Role($roleType, $user, $ownedEntity, $roleStatus);
    }

    public static function createSampleSite($label) {
        $site = new Site();
        $v4PK = new PrimaryKey();
        $site->setPrimaryKey($v4PK->getId());
        $site->setShortName($label);
        return $site;
    }

    public static function createSampleServiceGroup($label){
        $sgrp = new ServiceGroup();
        $sgrp->setName($label);
        $sgrp->setMonitored(1);
        $sgrp->setEmail($label."@email.com");
        return $sgrp;
    }

    public static function createSampleSiteProperty($key, $val){
        $prop = new SiteProperty();
        $prop->setKeyName($key);
        $prop->setKeyValue($val);
        return $prop;
    }

    public static function createSampleServiceProperty($name, $key){
        $prop = new ServiceProperty();
        $prop->setKeyName($name);
        $prop->setKeyValue($key);
        return $prop;
    }

    public static function createSampleEndpointProperty($name, $key){
        $prop = new EndpointProperty();
        $prop->setKeyName($name);
        $prop->setKeyValue($key);
        return $prop;
    }

    public static function createSampleServiceGroupProperty($name, $key){
        $prop = new ServiceGroupProperty();
        $prop->setKeyName($name);
        $prop->setKeyValue($key);
        return $prop;
    }

    public static function createSampleCertStatusLog($addedBy = '/some/user'){
        $certStatusLog = new CertificationStatusLog();
        $certStatusLog->setAddedBy($addedBy);
        return $certStatusLog;
    }

    public static function createSampleScope($description, $name){
        $scope = new Scope();
        $scope->setDescription($description);
        $scope->setName($name);
        return $scope;
    }

    public static function createSampleUserIdentifier($name, $key) {
        $identifier = new UserIdentifier();
        $identifier->setKeyName($name);
        $identifier->setKeyValue($key);
        return $identifier;
    }

}

?>
