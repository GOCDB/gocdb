<?php
//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php'; 

use Doctrine\ORM\EntityManager; 
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/Role.php'; 
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/Site.php'; 
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/RoleActionMappingService.php'; 
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/RoleActionAuthorisationService.php'; 

/**
 * Test the role functionality. 
 * This test case truncates the test database (a clean insert with no seed data)
 * and performs subsequent CRUD operations using Doctrine ORM. 
 * Usage: 
 * Run the recreate.sh to create the sample database first (create tables etc), then run:
 * '$phpunit TestRoles.php' 
 *
 * @author David Meredith
 * @author John Casson  
 */
class RoleServiceTest extends PHPUnit_Extensions_Database_TestCase {
   private $em; 
   private $egiScope; 
   private $localScope; 
   private $eudatScope; 


    /**
     * Overridden. 
     */
    public static function setUpBeforeClass() {
     	parent::setUpBeforeClass();
		echo "\n\n-------------------------------------------------\n";
        echo "Executing RoleServiceTest. . .\n";
    }
    
    /**
     * Overridden. Returns the test database connection.
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection() {
        require_once dirname(__FILE__) . '/bootstrap_pdo.php';
        return getConnectionToTestDB(); 
    }

    /**
     * Overridden. Returns the test dataset.  
     * Defines how the initial state of the database should look before each test is executed. 
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet() { 
        return $this->createFlatXMLDataSet(dirname(__FILE__) . '/truncateDataTables.xml');
        // Use below to return an empty data set if we don't want to truncate and seed
        //return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }

    /**
     * Overridden. 
     */
    protected function getSetUpOperation() {
        // CLEAN_INSERT is default
        //return PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT();
        //return PHPUnit_Extensions_Database_Operation_Factory::UPDATE();
        //return PHPUnit_Extensions_Database_Operation_Factory::NONE();
        //
        // Issue a DELETE from <table> which is more portable than a 
        // TRUNCATE table <table> (some DBs require high privileges for truncate statements 
        // and also do not allow truncates across tables with FK contstraints e.g. Oracle)
        return PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL();
    }

    /**
     * Overridden. 
     */
    protected function getTearDownOperation() {
        // NONE is default
        return PHPUnit_Extensions_Database_Operation_Factory::NONE();
    }

    /**
     * Sets up the fixture, e.g create a new entityManager for each test run
     * This method is called before each test method is executed.
     */
    protected function setUp() {
        parent::setUp();            
        $this->em = $this->createEntityManager();
    }
    
    /**
     * @todo Still need to setup connection to different databases. 
     * @return EntityManager
     */
    private function createEntityManager(){
        //require dirname(__FILE__).'/../lib/Doctrine/bootstrap.php';
        require dirname(__FILE__).'/bootstrap_doctrine.php';
        return $entityManager; 
    }

    /**
     * Called after setUp() and before each test. Used for common assertions
     * across all tests.
     */
    protected function assertPreConditions() {
        $con = $this->getConnection();
        $fixture = dirname(__FILE__) . '/truncateDataTables.xml';
        $tables = simplexml_load_file($fixture);

        foreach($tables as $tableName) {
            //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM ".$tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            //echo 'row count: '.$result->getRowCount() ; 
            if($result->getRowCount() != 0) 
                throw new RuntimeException("Invalid fixture. Table has rows: ".$tableName->getName());
        }
    }


    /*public function testPersistGetSupportedRoleTypeNameClassPairs(){
       print __METHOD__ . "\n"; 
       $roleTypes = TestUtil::getSupportedRoleTypes(); 
       if(count($roleTypes) == 0){
           throw new LogicException('No roleTypes configured'); 
       }
       // Assert that we can persist all the configured role types. 
       // This is to test the unique constraint on the RoleType.name value 
       // and any other DB col constraints. 
       foreach($roleTypes as $rt){
          $this->em->persist($rt); 
       }
       $this->em->flush();
    }*/


    
    public function test_getPendingRolesUserCanApprove(){
        print __METHOD__ . "\n";
        // Create fixture data
        // RoleTypes
    	$siteAdminRT = TestUtil::createSampleRoleType(RoleTypeName::SITE_ADMIN/*, RoleTypeClass::SITE_USER*/);
        $siteManRT = TestUtil::createSampleRoleType(RoleTypeName::SITE_OPS_MAN/*, RoleTypeClass::SITE_MANAGER*/); 
        $regFLSupportRT = TestUtil::createSampleRoleType(RoleTypeName::REG_FIRST_LINE_SUPPORT/*, RoleTypeClass::REGIONAL_USER*/);
        
        $this->em->persist($regFLSupportRT); 
        $this->em->persist($siteAdminRT);
        $this->em->persist($siteManRT);
        
        // User
    	$u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
    	$this->em->persist($u);
        // Site
    	$site1 = TestUtil::createSampleSite("SITENAME");
    	$this->em->persist($site1);
        $n1 = TestUtil::createSampleNGI("NGI_UK"); 
        $this->em->persist($n1); 
        $p1 = new \Project("EGI");  
        $this->em->persist($p1); 

        $n1->addSiteDoJoin($site1); 
        $p1->addNgi($n1); 
        
        // Create a SITE_OPS_MAN Role and link to the User, RoleType and site 
        $roleSite = TestUtil::createSampleRole($u, $siteManRT, $site1, RoleStatus::GRANTED); 
    	$this->em->persist($roleSite);

        // new user 
        $u2 = TestUtil::createSampleUser("Test2", "Testing2", "/c=test2");
        $this->em->persist($u2);
        // Create a pending SITE_ADMIN Role request for new user  
        $pendingSiteRole = TestUtil::createSampleRole($u2, $siteAdminRT, $site1, RoleStatus::PENDING); 
    	$this->em->persist($pendingSiteRole);
        $this->em->flush();

        // ********NOTE******** Role Service uses a new connection for its transactional methods
        $em2 = $this->createEntityManager(); 
        $roleService = new org\gocdb\services\Role();  
        $roleService->setEntityManager($em2);  
        $roleActionMappingService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionAuthorisationService = new org\gocdb\services\RoleActionAuthorisationService($roleActionMappingService); 
        $roleActionAuthorisationService->setEntityManager($em2);  
        $roleService->setRoleActionAuthorisationService($roleActionAuthorisationService); 
        
        $pendingGrantableRolesU1 = $roleService->getPendingRolesUserCanApprove($u); 
       
        // show that u1 has one pending role request 
        $this->assertTrue(count($pendingGrantableRolesU1) == 1); 
        $this->assertEquals($pendingSiteRole->getId(), $pendingGrantableRolesU1[0]->getId());  
        $this->assertEquals(RoleStatus::PENDING, $pendingGrantableRolesU1[0]->getStatus()); 
        $this->assertEquals(RoleTypeName::SITE_ADMIN, $pendingGrantableRolesU1[0]->getRoleType()->getName()); 
        
        // show that u2 has no pending role requests 
        $pendingGrantableRolesU2 = $roleService->getPendingRolesUserCanApprove($u2);
        $this->assertTrue(count($pendingGrantableRolesU2) == 0); 
    }
   

    
    public function test_getUserRoleNamesOverEntity(){
        print __METHOD__ . "\n";
    	// Create fixture data
        // RoleTypes
    	$siteAdminRT = TestUtil::createSampleRoleType(RoleTypeName::SITE_ADMIN/*, RoleTypeClass::SITE_USER*/);
        $regFLSupportRT = TestUtil::createSampleRoleType(RoleTypeName::REG_FIRST_LINE_SUPPORT/*, RoleTypeClass::REGIONAL_USER*/);
        $this->em->persist($regFLSupportRT); 
        $this->em->persist($siteAdminRT);
        // User
    	$u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
    	$this->em->persist($u);
        // Site
    	$site1 = TestUtil::createSampleSite("SITENAME"/*, "PK01"*/);
    	$this->em->persist($site1);
        // Create a Role and link to the User, ngiRoleType and site 
        $roleSite = TestUtil::createSampleRole($u, $siteAdminRT, $site1, RoleStatus::GRANTED); 
    	$this->em->persist($roleSite);
        $this->em->flush();
       
        // ********NOTE******** Role Service uses a new connection for its transactional methods
        $roleService = new org\gocdb\services\Role(); 
        $roleService->setEntityManager($this->createEntityManager()); 
        
        // Now test that method returns expected results 
        $roleNames = $roleService->getUserRoleNamesOverEntity($site1, $u, \RoleStatus::GRANTED); 
        $this->assertEquals(1, count($roleNames)); 
        $this->assertContains(RoleTypeName::SITE_ADMIN, $roleNames); 

        // Create new NGI
    	$n = TestUtil::createSampleNGI("MYNGI");
    	$this->em->persist($n);
        
    	$roleNgi = TestUtil::createSampleRole($u, $regFLSupportRT, $n, RoleStatus::GRANTED); 
    	$this->em->persist($roleNgi);
        $this->em->flush();

        // Now test that method returns expected results 
        $roleNames = $roleService->getUserRoleNamesOverEntity($n, $u); 
        $this->assertEquals(1, count($roleNames)); 
        $this->assertContains(RoleTypeName::REG_FIRST_LINE_SUPPORT, $roleNames); 
    }

    
    public function testGetUserRoles(){
    	print __METHOD__ . "\n";
    	// Create two roletypes
    	$ngiRoleType = TestUtil::createSampleRoleType("RT1_NAME"/*, RoleTypeClass::SITE_USER*/);
        $siteRoleType = TestUtil::createSampleRoleType("RT2_NAME"/*, RoleTypeClass::REGIONAL_USER*/);
    	$this->em->persist($ngiRoleType);
    	$this->em->persist($siteRoleType);
    
    	// Create a user
    	$u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
    	$this->em->persist($u);
    
    	// Create an NGI
    	$ngi = TestUtil::createSampleNGI("MYNGI");
    	$this->em->persist($ngi);
    
    	// Create a Role and link to the User, ngiRoleType and ngi 
        $roleNgi = TestUtil::createSampleRole($u, $ngiRoleType, $ngi, RoleStatus::GRANTED); 
    	$this->em->persist($roleNgi);

        // Create a site
    	$site1 = TestUtil::createSampleSite("SITENAME"/*, "PK01"*/);
    	$this->em->persist($site1);

        // Create another role and link to the User, siteRoleType and site
        $roleSite = TestUtil::createSampleRole($u, $siteRoleType, $site1, RoleStatus::GRANTED) ; 
    	$this->em->persist($roleSite);

        // Create a second and third sites and add to the NGI, but DO NOT add direct 
        // roles over those sites for the user. The user will still have role 
        // over the sites because they have a role over the NGI ! 
    	$site2 = TestUtil::createSampleSite("SITENAME2"/*, "PK01"*/);
    	$site3 = TestUtil::createSampleSite("SITENAME3"/*, "PK01"*/);
    	$this->em->persist($site2);        
    	$this->em->persist($site3);        
        $ngi->addSiteDoJoin($site2);  
        $ngi->addSiteDoJoin($site3);  
    	
    	$this->em->flush();

        // ********MUST******** start a new connection to test transactional 
        // isolation of RoleService methods.  
        $em = $this->createEntityManager();
        $roleService = new org\gocdb\services\Role();  
        $roleService->setEntityManager($em); 
        // assert that user has expected roles 
        $roles = $roleService->getUserRoles($u, RoleStatus::GRANTED);  
        $this->assertEquals(2, sizeof($roles)); 
        $this->assertTrue(count($roleService->getUserRoleNamesOverEntity($ngi, $u)) == 1);
        $this->assertTrue(count($roleService->getUserRoleNamesOverEntity($site1, $u)) == 1);
        $this->assertTrue(count($roleService->getUserRoleNamesOverEntity($site2, $u)) == 0);
        $this->assertTrue(count($roleService->getUserRoleNamesOverEntity($site3, $u)) == 0);
        
        // assert that the user has an expected site count with roles over those sites  
        $mySites = $roleService->getReachableSitesFromOwnedObjectRoles($u); 
        $this->assertEquals(3, sizeof($mySites)); 
        
        // assert user don't have these pending/revoked roles
        $roles = $roleService->getUserRoles($u, RoleStatus::PENDING);  
        $this->assertEmpty($roles); 
    }

    /**
     * @expectedException \LogicException 
     */
    public function testInvalidRoleStatus(){
    	print __METHOD__ . "\n";
       $roleService = new org\gocdb\services\Role();   
       $this->assertFalse($roleService->isValidRoleStatus("some invalid role"));
       $u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
       $roleService->getUserRoles($u, "some invalid role"); 
    }


    public function test_getReachableProjectsFromOwnedEntity(){
    	print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData5.php';
        $this->em->flush(); 


        // ********MUST******** start a new connection to test transactional 
        // isolation of RoleService methods.  
        $em = $this->createEntityManager();
        $roleService = new org\gocdb\services\Role();  
        $roleService->setEntityManager($em); 

        
        $projects = $roleService->getReachableProjectsFromOwnedEntity($p1); 
        $this->assertEquals($p1, $projects[0]); 
        $this->assertTrue(count($projects) == 1); 
        $projects = $roleService->getReachableProjectsFromOwnedEntity($n1); 
        $this->assertEquals($p1, $projects[0]); 
        $this->assertTrue(count($projects) == 1); 
        $projects = $roleService->getReachableProjectsFromOwnedEntity($s1); 
        $this->assertTrue(count($projects) == 1); 
        $this->assertEquals($p1, $projects[0]); 
 

        $projects = $roleService->getReachableProjectsFromOwnedEntity($p2); 
        $this->assertEquals($p2, $projects[0]); 
        $projects = $roleService->getReachableProjectsFromOwnedEntity($n2); 
        $this->assertTrue(count($projects) == 2); 
        $this->assertTrue(in_array($p2, $projects)); 
        $this->assertTrue(in_array($p3, $projects)); 
        $projects = $roleService->getReachableProjectsFromOwnedEntity($s2); 
        $this->assertTrue(count($projects) == 2); 
        $this->assertTrue(in_array($p2, $projects)); 
        $this->assertTrue(in_array($p3, $projects)); 

        
        $projects = $roleService->getReachableProjectsFromOwnedEntity($n3); 
        $this->assertTrue(count($projects) == 1); 
        $this->assertEquals($p3, $projects[0]); 
        $projects = $roleService->getReachableProjectsFromOwnedEntity($s3); 
        $this->assertTrue(count($projects) == 1); 
        $this->assertEquals($p3, $projects[0]); 
    }


    public function test_getUserRolesByProject(){
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData5.php';

        // create a user 
        $u = TestUtil::createSampleUser("dave", "meredith", "idSTring"); 
        $this->em->persist($u); 
        
        // add some roles to domain model 
        $ngiRoleType = TestUtil::createSampleRoleType("NGI_RT");
        $siteRoleType = TestUtil::createSampleRoleType("SITE_RT");
        $siteRoleType2 = TestUtil::createSampleRoleType("SITE_RT2");
        $projRoleType = TestUtil::createSampleRoleType("PROJ_RT");
    	$this->em->persist($ngiRoleType);
    	$this->em->persist($siteRoleType);
    	$this->em->persist($siteRoleType2);
    	$this->em->persist($projRoleType);
        
        $this->em->flush(); 

        $roleService = new org\gocdb\services\Role();  

        // We could create/inject a new em connection into the roleService 
        // to test the transactional isolation of its methods (rather than injecting
        // $this->em instance), e.g.:  
        //   $em = $this->createEntityManager();
        //   $roleService->setEntityManager($em); 
        //   
        // However, this is problematic for this test which 
        // needs to use the in_array() method to assert that the tested methods return 
        // the expected roles - in_array() uses object-instance-equality to deterine
        // if the role exists in the array, and using a different $em instance 
        // means the returned roles will be considered different      
        // instances. We could get round this by comparing the Ids, but this makes 
        // checking more of hassle as we need to extract all the Ids from the roles  
        // into another array to assert the ids are expected, as in:     
        //   $roleIds = array(); 
        //   foreach($roles as $r){ $roleIds[] = $r->getId(); }
        //   $this->assertTrue(in_array($r1->getId(), $roleIds)); 
        //   $this->assertTrue(in_array($r2->getId(), $roleIds));
        //
        // For this test, we will therefore re-use $this->em to ensure 
        // object-equality:
        $roleService->setEntityManager($this->em); 

        // confirm that function runs with no user roles over entity 
        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEmpty($roles); 


        // Create a Role and link to the User, ngiRoleType and ngi 
        /*
         * p1     p2     p3
         * |      |     /|
         * n1     n2-r1  n3
         * |      |      | 
         * s1     s2     s3 
         */
        $r1 = TestUtil::createSampleRole($u, $ngiRoleType, $n2, RoleStatus::GRANTED); 
    	$this->em->persist($r1);
        $this->em->flush(); 

        $rolesDirect = $u->getRoles(); 
        $this->assertEquals(1, count($rolesDirect));
       
        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEmpty($roles);
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(1, count($roles)); 
        $this->assertEquals($r1->getId(), $roles[0]->getId()); 
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(1, count($roles)); 
        $this->assertEquals($r1->getId(), $roles[0]->getId()); 

        
        /*
         * p1     p2     p3
         * |      |     /|
         * n1     n2-r1  n3-r2
         * |      |      | 
         * s1     s2     s3 
         */
        $r2 = TestUtil::createSampleRole($u, $ngiRoleType, $n3, RoleStatus::GRANTED); 
    	$this->em->persist($r2);
        $this->em->flush(); 
       
        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEmpty($roles);
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(1, count($roles)); 
        $this->assertEquals($r1->getId(), $roles[0]->getId()); 
        
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(2, count($roles)); 
        $roleIds = array(); 
        foreach($roles as $r){ $roleIds[] = $r->getId(); }
        $this->assertTrue(in_array($r1->getId(), $roleIds)); 
        $this->assertTrue(in_array($r2->getId(), $roleIds)); 

        /*
         * p1     p2     p3
         * |      |     /|
         * n1     n2-r1  n3-r2
         * |      |      | 
         * s1     s2     s3-r3 
         */
        $r3 = TestUtil::createSampleRole($u, $siteRoleType, $s3, RoleStatus::GRANTED); 
    	$this->em->persist($r3);
        $this->em->flush(); 

        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEmpty($roles);
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(1, count($roles)); 
        $this->assertEquals($r1->getId(), $roles[0]->getId()); 
        
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(3, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r2, $roles)); 
        $this->assertTrue(in_array($r3, $roles)); 

        /*
         * p1     p2     p3-r4
         * |      |     /|
         * n1     n2-r1  n3-r2
         * |      |      | 
         * s1     s2     s3-r3 
         */
        $r4 = TestUtil::createSampleRole($u, $projRoleType, $p3, RoleStatus::GRANTED); 
    	$this->em->persist($r4);
        $this->em->flush(); 

        $rolesDirect = $u->getRoles(); 
        $this->assertEquals(4, count($rolesDirect));
        
        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEmpty($roles);
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(1, count($roles)); 
        $this->assertEquals($r1->getId(), $roles[0]->getId()); 
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(4, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r2, $roles)); 
        $this->assertTrue(in_array($r3, $roles)); 
        $this->assertTrue(in_array($r4, $roles)); 

        /*
         * p1     p2     p3-r4
         * |      |     /|
         * n1     n2-r1  n3-r2
         * |      |      | 
         * s1     s2-r5  s3-r3 
         */
        $r5 = TestUtil::createSampleRole($u, $siteRoleType, $s2, RoleStatus::GRANTED); 
    	$this->em->persist($r5);
        $this->em->flush(); 

        $rolesDirect = $u->getRoles(); 
        $this->assertEquals(5, count($rolesDirect));

        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEmpty($roles);
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(2, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r5, $roles)); 
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(5, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r2, $roles)); 
        $this->assertTrue(in_array($r3, $roles)); 
        $this->assertTrue(in_array($r4, $roles)); 
        $this->assertTrue(in_array($r5, $roles)); 

       
        /*
         * p1     p2     p3-r4
         * |      |     /|
         * n1     n2-r1  n3-r2
         * |      |      | 
         * s1-r6  s2-r5  s3-r3 
         */
        $r6 = TestUtil::createSampleRole($u, $siteRoleType, $s1, RoleStatus::GRANTED); 
    	$this->em->persist($r6);
        $this->em->flush(); 

        $rolesDirect = $u->getRoles(); 
        $this->assertEquals(6, count($rolesDirect));

        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEquals(1, count($roles)); 
        $this->assertTrue(in_array($r6, $roles));
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(2, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r5, $roles)); 
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(5, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r2, $roles)); 
        $this->assertTrue(in_array($r3, $roles)); 
        $this->assertTrue(in_array($r4, $roles)); 
        $this->assertTrue(in_array($r5, $roles)); 
       
        /*
         * p1     p2     p3-r4
         * |      |     /|
         * n1     n2-r1  n3-r2
         * |      |      | 
         * s1-r6  s2-r5  s3-r3,r7 
         */
        $r7 = TestUtil::createSampleRole($u, $siteRoleType2, $s3, RoleStatus::GRANTED); 
    	$this->em->persist($r7);
        $this->em->flush(); 

        $rolesDirect = $u->getRoles(); 
        $this->assertEquals(7, count($rolesDirect));

        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEquals(1, count($roles)); 
        $this->assertTrue(in_array($r6, $roles));
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(2, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r5, $roles)); 
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(6, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r2, $roles)); 
        $this->assertTrue(in_array($r3, $roles)); 
        $this->assertTrue(in_array($r4, $roles)); 
        $this->assertTrue(in_array($r5, $roles));  
        $this->assertTrue(in_array($r7, $roles));  


        /*
         * p1     p2-r8  p3-r4
         * |      |     /|
         * n1     n2-r1  n3-r2
         * |      |      | 
         * s1-r6  s2-r5  s3-r3,r7 
         */
        $r8 = TestUtil::createSampleRole($u, $projRoleType, $p2, RoleStatus::GRANTED); 
    	$this->em->persist($r8);
        $this->em->flush(); 
        
        $rolesDirect = $u->getRoles(); 
        $this->assertEquals(8, count($rolesDirect));
        
        $roles = $roleService->getUserRolesByProject($u, $p1); 
        $this->assertEquals(1, count($roles)); 
        $this->assertTrue(in_array($r6, $roles));
        $roles = $roleService->getUserRolesByProject($u, $p2); 
        $this->assertEquals(3, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r5, $roles)); 
        $this->assertTrue(in_array($r8, $roles)); 
        $roles = $roleService->getUserRolesByProject($u, $p3); 
        $this->assertEquals(6, count($roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r2, $roles)); 
        $this->assertTrue(in_array($r3, $roles)); 
        $this->assertTrue(in_array($r4, $roles)); 
        $this->assertTrue(in_array($r5, $roles));  
        $this->assertTrue(in_array($r7, $roles)); 

    }



    
    

    /*public function testRoleTypeValues(){
    	print __METHOD__ . "\n";
        $roleService = new org\gocdb\services\Role();  
        
        $roleType = new RoleType(RoleTypeName::SITE_ADMIN, RoleTypeClass::SITE_USER); 
        $this->assertTrue($roleService->isValidRoleType($roleType)); 
        $roleType = new RoleType(RoleTypeName::SITE_SECOFFICER, RoleTypeClass::SITE_MANAGER); 
        $this->assertTrue($roleService->isValidRoleType($roleType)); 
        $roleType = new RoleType(RoleTypeName::SITE_SECOFFICER, \RoleTypeClass::SITE_USER); 
        $this->assertFalse($roleService->isValidRoleType($roleType)); 
    }*/

    /*public function testGetRoleTypeClassificationsByRoleTypeName(){
        print __METHOD__ . "\n";
        $roleService = new org\gocdb\services\Role(); 
        
        $roleNames = $roleService->getRoleTypeNamesByClassification(RoleTypeClass::PROJECT); 
        $this->assertTrue(count($roleNames) == 4); 
        $this->assertTrue(in_array(RoleTypeName::COO, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::COD_ADMIN, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::COD_STAFF, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::EGI_CSIRT_OFFICER, $roleNames));
       
        $roleNames = $roleService->getRoleTypeNamesByClassification(RoleTypeClass::REGIONAL_MANAGER); 
        $this->assertTrue(count($roleNames) == 3); 
        $this->assertTrue(in_array(RoleTypeName::NGI_SEC_OFFICER, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::NGI_OPS_DEP_MAN, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::NGI_OPS_MAN, $roleNames));
        
        $roleNames = $roleService->getRoleTypeNamesByClassification(RoleTypeClass::REGIONAL_USER); 
        $this->assertTrue(count($roleNames) == 2); 
        $this->assertTrue(in_array(RoleTypeName::REG_STAFF_ROD, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::REG_FIRST_LINE_SUPPORT, $roleNames));
        
        $roleNames = $roleService->getRoleTypeNamesByClassification(RoleTypeClass::SITE_MANAGER); 
        $this->assertTrue(count($roleNames) == 3); 
        $this->assertTrue(in_array(RoleTypeName::SITE_OPS_MAN, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::SITE_OPS_DEP_MAN, $roleNames));
        $this->assertTrue(in_array(RoleTypeName::SITE_SECOFFICER, $roleNames));
        
        $roleNames = $roleService->getRoleTypeNamesByClassification(RoleTypeClass::SITE_USER); 
        $this->assertTrue(count($roleNames) == 1); 
        $this->assertTrue(in_array(RoleTypeName::SITE_ADMIN, $roleNames));
        
        $roleNames = $roleService->getRoleTypeNamesByClassification(RoleTypeClass::SERVICEGROUP_ADMIN); 
        $this->assertTrue(count($roleNames) == 1); 
        $this->assertTrue(in_array(RoleTypeName::SERVICEGROUP_ADMIN, $roleNames));
    }*/
        

      
}

?>
