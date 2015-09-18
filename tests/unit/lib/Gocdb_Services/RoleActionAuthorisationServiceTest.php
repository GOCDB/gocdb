<?php

/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once __DIR__ . '/../../../doctrine/TestUtil.php'; 
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Role.php'; 
require_once __DIR__ . '/../../../../lib/Gocdb_Services/RoleActionMappingService.php'; 
require_once __DIR__ . '/../../../../lib/Gocdb_Services/RoleActionAuthorisationService.php'; 

use Doctrine\ORM\EntityManager; 
require_once __DIR__ . '/../../../doctrine/bootstrap.php';


/**
 * Description of RoleActionAuthorisationServiceTest
 *
 * @author djm76
 */
class RoleActionAuthorisationServiceTest  extends PHPUnit_Extensions_Database_TestCase{
    private $em; 



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
        require_once __DIR__ . '/../../../doctrine/bootstrap_pdo.php';
        return getConnectionToTestDB(); 
    }

    /**
     * Overridden. Returns the test dataset.  
     * Defines how the initial state of the database should look before each test is executed. 
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet() { 
        return $this->createFlatXMLDataSet(__DIR__ . '/../../../doctrine/truncateDataTables.xml');
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
        require __DIR__ . '/../../../doctrine/bootstrap_doctrine.php';
        return $entityManager; 
    }

    /**
     * Called after setUp() and before each test. Used for common assertions
     * across all tests.
     */
    protected function assertPreConditions() {
        $con = $this->getConnection();
        $fixture = __DIR__ . '/../../../doctrine/truncateDataTables.xml';
        $tables = simplexml_load_file($fixture);

        foreach($tables as $tableName) {
            //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM ".$tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            //echo 'row count: '.$result->getRowCount() ; 
            if($result->getRowCount() != 0){ 
                throw new RuntimeException("Invalid fixture. Table has rows: ".$tableName->getName());
            }
        }
    }


    public function test_authoriseAction(){
        print __METHOD__ . "\n";
    	// Create roletypes
    	$siteAdminRT = TestUtil::createSampleRoleType(TestRoleTypeName::SITE_ADMIN);
    	$siteDepManAdminRT = TestUtil::createSampleRoleType(TestRoleTypeName::SITE_OPS_DEP_MAN);
        $ngiManRT = TestUtil::createSampleRoleType(TestRoleTypeName::NGI_OPS_MAN);
        $rodRT = TestUtil::createSampleRoleType(TestRoleTypeName::REG_STAFF_ROD);
        $codRT = TestUtil::createSampleRoleType(TestRoleTypeName::COD_ADMIN);
        $cooRT = TestUtil::createSampleRoleType(TestRoleTypeName::COO);
    	$this->em->persist($siteAdminRT); // edit site1 (but not cert status) 
    	$this->em->persist($ngiManRT); // edit owned site1/site2 and cert status
    	$this->em->persist($rodRT);  // edit owned sites 1and2 (but not cert status)
    	$this->em->persist($codRT);  // edit all sites cert status only  
    	$this->em->persist($cooRT);  // edit all sites cert status only  
    	$this->em->persist($siteDepManAdminRT);  // edit all sites cert status only  
   

        // Create a user
    	$u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
    	$this->em->persist($u);

        /*
         * Create test data with the following structure (note p3 has two ngis) 
         * 
         * p1  p2  p3
         * |   |  /|
         * n1  n2  n3
         * |   |   | 
         * s1  s2  s3 
         */
        include __DIR__ . '/../../resources/sampleFixtureData6.php';

        // Build dependencies and inject business objects: 
        // start a new connection to test transactional isolation of RoleService methods.  
        //$em2 = $this->createEntityManager();
        $em2 = $this->em;

        // Create RoleActionMappingService with non-default roleActionMappings file
        $roleActionMappingService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionMappingService->setRoleActionMappingsXmlPath(
                __DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings6.xml"); 

        //Create Role Service 
        //$roleService = new org\gocdb\services\Role(); 
        //$roleService->setEntityManager($em2); 
        
        // Create RoleActionAuthorisationService with dependencies 
        $roleAuthServ = new org\gocdb\services\RoleActionAuthorisationService
                ($roleActionMappingService/*, $roleService*/);  
        $roleAuthServ->setEntityManager($em2); 


        // Create role and link user and owned entities (user link not shown) 
        /*
         *    p1  p2  p3
         *    |    |  /|
         * r1-n1  n2  n3
         *    |   |   | 
         *    s1  s2  s3 
         */
        $r1 = TestUtil::createSampleRole($u, $ngiManRT, $n1, RoleStatus::GRANTED); 
    	$this->em->persist($r1);
    	$this->em->flush();
        
        // Assert user can edit s1 using r1 
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s1, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r1, $enablingRoles)); 
        $this->assertEquals(TestRoleTypeName::NGI_OPS_MAN, $enablingRoles[0]->getRoleType()->getName()); 
        
        // Assert user can edit n1 using r1
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n1, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r1, $enablingRoles)); 
        $this->assertEquals(TestRoleTypeName::NGI_OPS_MAN, $enablingRoles[0]->getRoleType()->getName()); 

        // Assert user can change s1 cert status with r1
        //$this->assertEquals(1, count($roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s1, $u))); 
        $this->assertTrue(in_array($r1, $roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s1, $u)) ); 
        
        // Assert user can't edit other sites/ngis/projects 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p1, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p3, $u))); 
       
         /*
         *    p1  p2  p3
         *    |    |  /|
         * r1-n1  n2  n3
         *    |   |   | 
         * r2-s1  s2  s3 
         */
        $r2 = TestUtil::createSampleRole($u, $siteAdminRT, $s1, RoleStatus::GRANTED) ; 
    	$this->em->persist($r2);
        $this->em->flush(); 

        // Assert user can edit s1 using r1 + r2 
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s1, $u); 
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array($r1, $enablingRoles));  
        $this->assertTrue(in_array($r2, $enablingRoles));  
        //$enablingRoleNames = $this->getRoleTypeNames($enablingRoles); 
        //$this->assertTrue(in_array(TestRoleTypeName::NGI_OPS_MAN, $enablingRoleNames)); 
        //$this->assertTrue(in_array(TestRoleTypeName::SITE_ADMIN, $enablingRoleNames)); 
        
        // Assert user can change site cert status with r1 
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s1, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r1, $enablingRoles));  

        // Assert user can't edit other sites/ngis/projects 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p1, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p3, $u))); 

        /*
         *    p1     p2  p3
         *    |      |  /|
         * r1-n1     n2  n3
         *    |      |   | 
         * r2-s1  r3-s2  s3  
         */
        $r3 = TestUtil::createSampleRole($u, $siteAdminRT, $s2, RoleStatus::GRANTED) ; 
    	$this->em->persist($r3);
        $this->em->flush(); 

        // Assert user can edit s1 via r1 + r2 
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s1, $u); 
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array($r1, $enablingRoles));  
        $this->assertTrue(in_array($r2, $enablingRoles));  

        // Assert user can edit s2 via r3 
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s2, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r3, $enablingRoles));  
        
        // Assert user can change s1 cert status via r1
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s1, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r1, $enablingRoles));  
       
        // Assert user cant change s2 cert status 
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s2, $u); 
        $this->assertEquals(0, count($enablingRoles)); 

        // Assert user can't edit other sites/ngis/projects 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p1, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p3, $u))); 


        /*
         *    p1  r4-p2  p3
         *    |      |  /|
         * r1-n1     n2  n3
         *    |      |   | 
         * r2-s1  r3-s2  s3  
         */
        $r4 = TestUtil::createSampleRole($u, $cooRT, $p2, RoleStatus::GRANTED); 
    	$this->em->persist($r4);
        $this->em->flush(); 

        // Assert user can edit p2 via r4
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p2, $u);
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r4, $enablingRoles));  

        // Assert user can grantRole on p2 via r4
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::GRANT_ROLE, $p2, $u);
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r4, $enablingRoles));
        
        // Assert user can grantRole on n2 via r4
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::GRANT_ROLE, $n2, $u);
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r4, $enablingRoles));

        // Assert user can't edit other sites/ngis/projects 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p3, $u))); 
       
        /*
         *    p1  r4-p2  p3-r5
         *    |      |  /|
         * r1-n1     n2  n3
         *    |      |   | 
         * r2-s1  r3-s2  s3  
         */
        $r5 = TestUtil::createSampleRole($u, $codRT, $p3, RoleStatus::GRANTED); 
    	$this->em->persist($r5);
        $this->em->flush(); 

        // Assert user can edit p3 with r5 
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $p3, $u);
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r5, $enablingRoles));
        
        // Assert user can grant role on n2 + n3 via r5
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::GRANT_ROLE, $n2, $u);
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array($r5, $enablingRoles));
        $this->assertTrue(in_array($r4, $enablingRoles));

        // Assert user can't edit other sites/ngis/projects 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n2, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u))); 
        $this->assertEquals(0, count($roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $n3, $u))); 



        /*
         *    p1  r4-p2  p3-r5
         *    |      |  /|
         * r1-n1     n2  n3-r6
         *    |      |   | 
         * r2-s1  r3-s2  s3  
         */
        $r6 = TestUtil::createSampleRole($u, $ngiManRT, $n3, RoleStatus::GRANTED); 
    	$this->em->persist($r6);
        $this->em->flush(); 

        /*
         *    p1  r4-p2  p3-r5
         *    |      |  /|
         * r1-n1     n2  n3-r6
         *    |      |   | 
         * r2-s1  r3-s2  s3-r7  
         */
        $r7 = TestUtil::createSampleRole($u, $siteAdminRT, $s3, RoleStatus::GRANTED); 
    	$this->em->persist($r7);
        $this->em->flush(); 

        // Assert user can edit s3 with r6 + r7
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u);
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array($r7, $enablingRoles));
        $this->assertTrue(in_array($r6, $enablingRoles));


        /*
         *    p1  r4-p2  p3-r5
         *    |      |  /|
         * r1-n1  r8-n2  n3-r6
         *    |      |   | 
         * r2-s1  r3-s2  s3-r7  
         */
        $r8 = TestUtil::createSampleRole($u, $ngiManRT, $n2, RoleStatus::GRANTED); 
    	$this->em->persist($r8);
        $this->em->flush(); 
        
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s2, $u);
        $this->assertEquals(3, count($enablingRoles)); 
        $this->assertTrue(in_array($r8, $enablingRoles));
        $this->assertTrue(in_array($r4, $enablingRoles));
        $this->assertTrue(in_array($r5, $enablingRoles));
        
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s3, $u);
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array($r6, $enablingRoles));
        $this->assertTrue(in_array($r5, $enablingRoles));
        
        /*
         *    p1  r4-p2  p3-r5
         *    |      |  /|
         * r1-n1  r8-n2  n3-r6
         *    |      |   | 
         * r2-s1  r3-s2  s3-r7,r9  
         */
        $r9 = TestUtil::createSampleRole($u, $siteDepManAdminRT, $s3, RoleStatus::GRANTED); 
    	$this->em->persist($r9);
        $this->em->flush(); 

        // Assert user can edit s3 with r7, r9, r6
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u);
        $this->assertEquals(3, count($enablingRoles)); 
        $this->assertTrue(in_array($r7, $enablingRoles));
        $this->assertTrue(in_array($r9, $enablingRoles));
        $this->assertTrue(in_array($r6, $enablingRoles));

        // Assert user can change s3 cert status with r6 + r5
        $enablingRoles = $roleAuthServ->authoriseAction(\Action::SITE_EDIT_CERT_STATUS, $s3, $u); 
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array($r6, $enablingRoles)); 
        $this->assertTrue(in_array($r5, $enablingRoles)); 

        /*
         *    p1  r4-p2  p3-r5
         *    |      |  /|
         * r1-n1  r8-n2  n3-r6,r10
         *    |      |   | 
         * r2-s1  r3-s2  s3-r7,r9  
         */
        $r10 = TestUtil::createSampleRole($u, $rodRT, $n3, RoleStatus::GRANTED); 
    	$this->em->persist($r10);
        $this->em->flush(); 

        $enablingRoles = $roleAuthServ->authoriseAction(\Action::EDIT_OBJECT, $s3, $u); 
        $this->assertEquals(4, count($enablingRoles)); 
        $this->assertTrue(in_array($r6, $enablingRoles)); 
        $this->assertTrue(in_array($r10, $enablingRoles)); 
        $this->assertTrue(in_array($r7, $enablingRoles)); 
        $this->assertTrue(in_array($r9, $enablingRoles)); 

        $enablingRoles = $roleAuthServ->authoriseAction(\Action::NGI_ADD_SITE, $n3, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array($r6, $enablingRoles)); 

        
        
        
    }


    
    public function test_getUserRolesOnAndAboveEntity(){
        print __METHOD__ . "\n";
        include __DIR__ . '/../../resources/sampleFixtureData5.php';
        
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

        
        $roleActionMappingService = new org\gocdb\services\RoleActionMappingService(); 
        // Create RoleActionAuthorisationService with dependencies 
        $roleAuthServ = new org\gocdb\services\RoleActionAuthorisationService
                ($roleActionMappingService/*, $roleService*/);  


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
        $roleAuthServ->setEntityManager($this->em); 

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $p1); 
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

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s1); 
        $this->assertEmpty($roles); 

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s2); 
        $this->assertEquals(1, count($roles)); 
        $this->assertTrue(in_array($r1, $roles));  

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

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s2); 
        $this->assertEquals(1, count($roles)); 
        $this->assertTrue(in_array($r1, $roles));  

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s3); 
        $this->assertEquals(1, count($roles)); 
        $this->assertTrue(in_array($r2, $roles));  

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

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s3); 
        $this->assertEquals(2, count($roles)); 
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

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s1); 
        $this->assertEquals(1, count($roles)); 
        $this->assertTrue(in_array($r6, $roles)); 
        
        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s2); 
        $this->assertEquals(4, count($roles)); 
        $this->assertTrue(in_array($r5, $roles)); 
        $this->assertTrue(in_array($r1, $roles)); 
        $this->assertTrue(in_array($r8, $roles)); 
        $this->assertTrue(in_array($r4, $roles)); 

        $roles = $roleAuthServ->getUserRolesReachableFromEntityASC($u, $s3); 
        $this->assertEquals(4, count($roles)); 
        $this->assertTrue(in_array($r2, $roles)); 
        $this->assertTrue(in_array($r3, $roles)); 
        $this->assertTrue(in_array($r4, $roles)); 
        $this->assertTrue(in_array($r7, $roles)); 
       
    }





    
    /*private function getRoleTypeNames($enablingRoles) {
        $enablingRoleNames = array();
        foreach ($enablingRoles as $role) {
            $enablingRoleNames[] = $role->getRoleType()->getName();
        }
        return $enablingRoleNames;
    }*/

}

class TestRoleTypeName {

    const GOCDB_ADMIN = 'GOCDB_ADMIN'; 
    
    // Roles for Sites 
    const SITE_ADMIN = 'Site Administrator'; // C
    
    const SITE_SECOFFICER = 'Site Security Officer'; // C'
    const SITE_OPS_DEP_MAN = 'Site Operations Deputy Manager'; // C'
    const SITE_OPS_MAN = 'Site Operations Manager'; // C'
    
    // Roles for NGIs 
    const REG_FIRST_LINE_SUPPORT = 'Regional First Line Support'; // D
    const REG_STAFF_ROD = 'Regional Staff (ROD)'; // D
    
    const NGI_SEC_OFFICER = 'NGI Security Officer'; // D'
    const NGI_OPS_DEP_MAN = 'NGI Operations Deputy Manager'; // D'
    const NGI_OPS_MAN = 'NGI Operations Manager'; // D'
    
    // Roles for Projects
    const COD_STAFF = 'COD Staff'; // E
    const COD_ADMIN = 'COD Administrator'; // E
    const EGI_CSIRT_OFFICER = 'EGI CSIRT Officer'; // E
    const COO = 'Chief Operations Officer'; // E
    
    // Roles for ServiceGroups
    const SERVICEGROUP_ADMIN = 'Service Group Administrator'; // ServiceGroupC'
    
    // "Other" roles that have slipped by us (see AddRoleTypes.php) 
    const CIC_STAFF = 'CIC Staff'; // Pretty sure this role is not required anymore: https://rt.egi.eu/rt/Ticket/Display.html?id=931 
    const REG_STAFF = 'Regional Staff';

    /**
     * private constructor to limit instantiation
     */
    private function __construct() {
    }

    /**
     * Get an associative array of the class constants. Array keys are the constant
     * names, array values are the constant values.
     * @return array
     */
    public static function getAsArray() {
        $tmp = new ReflectionClass(get_called_class());
        $a = $tmp->getConstants();        //$b = array_flip($a)
        return $a;
    }

}
