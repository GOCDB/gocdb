<?php
require_once 'PHPUnit/Extensions/Database/TestCase.php';
require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php'; 

use Doctrine\ORM\EntityManager; 
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/Role.php'; 
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/Site.php'; 
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/ServiceService.php'; 

/**
 * Test the authorizeAction service methods. 
 * This test case truncates the test database (a clean insert with no seed data)
 * and performs subsequent CRUD operations using Doctrine ORM. 
 * Usage: 
 * Run the recreate.sh to create the sample database first (create tables etc), then run:
 * '$phpunit TestServiceAuthorizeAction.php' 
 *
 * @author David Meredith
 * @author John Casson  
 */
class ServiceAuthorizeActionTest extends PHPUnit_Extensions_Database_TestCase {
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
        echo "Executing ServiceAuthorizeActionTest. . .\n";
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

 
    /**
     * Persist some seed data - roletypes, user, Project, NGI, sites and SEs and 
     * assert that the user has the expected number of roles that grant specific 
     * actions over the owned objects. For example, assert that the user has 'n' 
     * number of roles that allow a particular site to be edited, or 'n' number 
     * of roles that allow an NGI certification status change.  
     */
    public function testAuthorizeAction1(){
    	print __METHOD__ . "\n";
    	// Create roletypes
    	$siteAdminRT = TestUtil::createSampleRoleType(RoleTypeName::SITE_ADMIN/*, RoleTypeClass::SITE_USER*/);
        $ngiManRT = TestUtil::createSampleRoleType(RoleTypeName::NGI_OPS_MAN/*, RoleTypeClass::REGIONAL_MANAGER*/);
        $rodRT = TestUtil::createSampleRoleType(RoleTypeName::REG_STAFF_ROD/*, RoleTypeClass::REGIONAL_USER*/);
        $codRT = TestUtil::createSampleRoleType(RoleTypeName::COD_ADMIN/*, RoleTypeClass::PROJECT*/);
    	$this->em->persist($siteAdminRT); // edit site1 (but not cert status) 
    	$this->em->persist($ngiManRT); // edit owned site1/site2 and cert status
    	$this->em->persist($rodRT);  // edit owned sites 1and2 (but not cert status)
    	$this->em->persist($codRT);  // edit all sites cert status only  
    
    	// Create a user
    	$u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
    	$this->em->persist($u);
    
    	// Create a linked object graph 
        // NGI->Site1->SE 
        //   |->Site2
    	$ngi = TestUtil::createSampleNGI("MYNGI");
    	$this->em->persist($ngi);
    	$site1 = TestUtil::createSampleSite("SITENAME"/*, "PK01"*/);
        //$site1->setNgiDoJoin($ngi); 
        $ngi->addSiteDoJoin($site1); 
    	$this->em->persist($site1);
        $se1 = TestUtil::createSampleService('somelabel'); 
        $site1->addServiceDoJoin($se1); 
    	$this->em->persist($se1);
        $site2_userHasNoDirectRole = TestUtil::createSampleSite("SITENAME_2"/*, "PK01"*/);
        $ngi->addSiteDoJoin($site2_userHasNoDirectRole); 
        //$site2_userHasNoDirectRole->setNgiDoJoin($ngi);
    	$this->em->persist($site2_userHasNoDirectRole);

    
    	// Create ngiManagerRole, ngiUserRole, siteAdminRole and link user and owned entities  
        $ngiManagerRole = TestUtil::createSampleRole($u, $ngiManRT, $ngi, RoleStatus::GRANTED); 
    	$this->em->persist($ngiManagerRole);
        $rodUserRole = TestUtil::createSampleRole($u, $rodRT, $ngi, RoleStatus::GRANTED); 
    	$this->em->persist($rodUserRole);
        $siteAdminRole = TestUtil::createSampleRole($u, $siteAdminRT, $site1, RoleStatus::GRANTED) ; 
    	$this->em->persist($siteAdminRole);
    	
    	$this->em->flush();

        // ********MUST******** start a new connection to test transactional 
        // isolation of RoleService methods.  
        $em = $this->createEntityManager();
        $siteService = new org\gocdb\services\Site();  
        $siteService->setEntityManager($em); 
        
        // Assert user can edit site using 3 enabling roles
        $enablingRoles = $siteService->authorizeAction(\Action::EDIT_OBJECT, $site1, $u); 
        $this->assertEquals(3, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::SITE_ADMIN, $enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::NGI_OPS_MAN, $enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::REG_STAFF_ROD, $enablingRoles)); 

        // Assert user can only edit cert status through his NGI_OPS_MAN role 
        $enablingRoles = $siteService->authorizeAction(\Action::SITE_EDIT_CERT_STATUS, $site1, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::NGI_OPS_MAN, $enablingRoles)); 
 
        // Add a new project and link ngi and give user COD_ADMIN Project role (use $this->em to isolate)
        // Project->NGI->Site1->SE 
        //            |->Site2
        $proj = new Project('EGI project'); 
        $proj->addNgi($ngi); 
        //$ngi->addProject($proj); // not strictly needed
        $this->em->persist($proj); 
        $codRole = TestUtil::createSampleRole($u, $codRT, $proj, RoleStatus::GRANTED); 
    	$this->em->persist($codRole); 
        $this->em->flush();
       
        // Assert user now has 2 roles that enable SITE_EDIT_CERT_STATUS change action 
        $enablingRoles = $siteService->authorizeAction(\Action::SITE_EDIT_CERT_STATUS, $site1, $u); 
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::NGI_OPS_MAN, $enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::COD_ADMIN, $enablingRoles)); 

        // Assert user can edit SE using SITE_ADMIN, NGI_OPS_MAN, REG_STAFF_ROD roles (but not COD role)  
        $seService = new org\gocdb\services\ServiceService();  
        $seService->setEntityManager($em); 
        $enablingRoles = $seService->authorizeAction(\Action::EDIT_OBJECT, $se1, $u);  
        $this->assertEquals(3, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::SITE_ADMIN, $enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::NGI_OPS_MAN, $enablingRoles));  
        $this->assertTrue(in_array(\RoleTypeName::REG_STAFF_ROD, $enablingRoles)); 
    
        // Assert User can only edit Site2 through his 2 indirect ngi roles 
        // (user don't have any direct site level roles on this site and COD don't give edit perm)
        $enablingRoles = $siteService->authorizeAction(\Action::EDIT_OBJECT, $site2_userHasNoDirectRole, $u); 
        $this->assertEquals(2, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::NGI_OPS_MAN, $enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::REG_STAFF_ROD, $enablingRoles));  

        // Delete the user's Project COD role 
        $this->em->remove($codRole);  
        $this->em->flush();
        
        // Assert user can only SITE_EDIT_CERT_STATUS through 1 role for both sites
        $enablingRoles = $siteService->authorizeAction(\Action::SITE_EDIT_CERT_STATUS, $site2_userHasNoDirectRole, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::NGI_OPS_MAN, $enablingRoles)); 
        $enablingRoles = $siteService->authorizeAction(\Action::SITE_EDIT_CERT_STATUS, $site1, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::NGI_OPS_MAN, $enablingRoles));  
        
        // Delete the user's NGI manager role 
        $this->em->remove($ngiManagerRole);  
        $this->em->flush();

        // Assert user can't edit site2 cert status  
        $enablingRoles = $siteService->authorizeAction(\Action::SITE_EDIT_CERT_STATUS, $site2_userHasNoDirectRole, $u); 
        $this->assertEquals(0, count($enablingRoles)); 
        // Assert user can still edit site via his ROD role 
        $enablingRoles = $siteService->authorizeAction(\Action::EDIT_OBJECT, $site2_userHasNoDirectRole, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::REG_STAFF_ROD, $enablingRoles));  

        // Delete the user's NGI ROD role 
        $this->em->remove($rodUserRole);  
        $this->em->flush(); 

        // User can't edit site2
        $enablingRoles = $siteService->authorizeAction(\Action::EDIT_OBJECT, $site2_userHasNoDirectRole, $u); 
        $this->assertEquals(0, count($enablingRoles)); 
       
        // Assert user can still edit SITE1 through his direct site level role (this role has not been deleted)  
        $enablingRoles = $siteService->authorizeAction(\Action::EDIT_OBJECT, $site1, $u); 
        $this->assertEquals(1, count($enablingRoles)); 
        $this->assertTrue(in_array(\RoleTypeName::SITE_ADMIN, $enablingRoles)); 
        
        // Delete user's remaining Site role 
        $this->em->remove($siteAdminRole);  
        $this->em->flush();  
        
        // User can't edit site1 
        $enablingRoles = $siteService->authorizeAction(\Action::EDIT_OBJECT, $site1, $u); 
        $this->assertEquals(0, count($enablingRoles)); 
         
    }



      
}

?>
