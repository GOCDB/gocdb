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
    
    public static function createSampleDowntime($severity = 'OUTAGE', $classification = 'SCHEDULED'){
        $downtime = new Downtime(); 
        $downtime->setDescription("A sample skeleton downtime"); 
        $downtime->setSeverity($severity); 
        $downtime->setClassification($classification); 
        return $downtime; 
    }
    
    public static function createSampleEndpointLocation(){
        $el = new EndpointLocation(); 
        $el->setUrl("https://google.co.uk"); 
        return $el; 
    }
    
   public static function createSampleService($label){
        $se = new Service(); 
        $se->setHostName(''.$label); 
        $se->setBeta(false); 
        $se->setProduction(true); 
        $se->setMonitored(true);
        return $se; 
    }
    
    public static function createSampleRoleType($name) {
        $rt = new RoleType($name);
        return $rt;
    }

    public static function createSampleUser($forename, $surname, $dn) {
        $u = new User();
        $u->setForename($forename);
        $u->setSurname($surname);
        $u->setCertificateDn($dn);
        $u->setAdmin(FALSE); 
        return $u;
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
        $r = new Role($roleType, $user, $ownedEntity, $roleStatus); 
        return $r;
    }

    public static function createSampleSite($label) {
        $site = new Site();
        $v4PK = new PrimaryKey(); 
        $site->setPrimaryKey($v4PK->getId());  
        $site->setShortName($label);
        return $site;
    }
	
	public static function createSampleServiceGroup($label){
		$sg = new ServiceGroup();
		$sg->setName($label);
		$sg->setMonitored(1);
		$sg->setEmail($label."@email.com");
		return $sg;
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

}

?>

