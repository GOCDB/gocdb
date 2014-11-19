<?php
use org\gocdb\services\Site;
use org\gocdb\services\ServiceService;
require_once 'PHPUnit/Extensions/Database/TestCase.php';
require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php'; 

use Doctrine\ORM\EntityManager; 
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/Site.php'; 
require_once dirname(__FILE__). '/../../lib/Gocdb_Services/ServiceService.php';

/**
 * 
 *Test site ownership transfer between NGIs
 *
 * @author George Ryall
 * @author David Meredith
 * @author John Casson  
 */
class ServiceMoveTest extends PHPUnit_Extensions_Database_TestCase {
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
        echo "Executing ServiceMoveTest. . .\n";
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

    /*
     * Inserts Two NGIs, three sites, and a service endpoint.
     * Checks the inserted data is there.
     *  Moves two of the sites between NGIs.
     *  Checks the sites are under the right NGIs.
     */
    public function testServiceMoves() {
		
    	print __METHOD__ . "\n";	
    	
    	//Insert initial data
    	$S1 = TestUtil::createSamplesite("Site1");
    	$S2 = TestUtil::createSamplesite("Site2");
    	$SE1 = TestUtil::createSampleService("SEP1");
    	$SE2 = TestUtil::createSampleService("SEP2");
    	$SE3 = TestUtil::createSampleService("SEP3");
    	$dummy_user = TestUtil::createSampleUser('Test', 'User', '/Some/string');
    	
    	//Make dummy user a GOCDB admin so it can perfirm site moves etc.
    	$dummy_user->setAdmin(true);
    	
    	// Assign the service end points to the sites
    	$S1->addServiceDoJoin($SE1);
    	$S2->addServiceDoJoin($SE2);
    	$S2->addServiceDoJoin($SE3);   	
    		
    	//Persist initial data
    	$this->em->persist($S1);
    	$this->em->persist($S2);
    	$this->em->persist($SE1);
    	$this->em->persist($SE2);
    	$this->em->persist($SE3);
    	$this->em->persist($dummy_user);
    	$this->em->flush();
    	
    	//Use DB connection to check data
    	$con = $this->getConnection();
	    	
	    	/*
	    	 * Check both that each Site is present and that the ID matches the doctrine one
	    	 */
    		$S1_ID = $S1->getId();
	    	$sql = "SELECT 1 FROM sites WHERE shortname = 'Site1' AND ID = '$S1_ID'";
	        $result = $con->createQueryTable('', $sql);
	    	$this->assertEquals(1, $result->getRowCount());
			
    		$S2_ID = $S2->getId();
	    	$sql = "SELECT 1 FROM sites WHERE shortname = 'Site2' AND ID = '$S2_ID'";
	        $result = $con->createQueryTable('', $sql);
	    	$this->assertEquals(1, $result->getRowCount());
	    	
	    	/*
	    	 * Check each service endpoint is: present, has the right ID & parent Site
	    	 */
	    	$SE1_id= $SE1->getId();
	    	$sql = "SELECT 1 FROM services WHERE hostname = 'SEP1' AND ID = '$SE1_id' AND PARENTSITE_ID = '$S1_ID'";
	        $result = $con->createQueryTable('', $sql);
	    	$this->assertEquals(1, $result->getRowCount());
	    	
	    	$SE2_id= $SE2->getId();
	    	$sql = "SELECT 1 FROM services WHERE hostname = 'SEP2' AND ID = '$SE2_id' AND PARENTSITE_ID = '$S2_ID'";
	        $result = $con->createQueryTable('', $sql);
	    	$this->assertEquals(1, $result->getRowCount());
	    	
	    	$SE3_id= $SE3->getId();
	    	$sql = "SELECT 1 FROM services WHERE hostname = 'SEP3' AND ID = '$SE3_id' AND PARENTSITE_ID = '$S2_ID'";
	        $result = $con->createQueryTable('', $sql);
	    	$this->assertEquals(1, $result->getRowCount());

 	   	
    	//Move service endpoints
    	$serv = new org\gocdb\services\ServiceService;
	   	$serv->setEntityManager($this->em);
    	$serv->moveService($SE1, $S2, $dummy_user);
    	$serv->moveService($SE2, $S1, $dummy_user);
    	$serv->moveService($SE3, $S2, $dummy_user); //No change
    	
     	
    	//flush movement
    	$this->em->flush();
    	
    	//Use doctrine to check movement
  	 		//Check correct Site for each service endpoint
    	    $this->assertEquals($S2, $SE1->getParentSite());
    	    $this->assertEquals($S1, $SE2->getParentSite());
    	    $this->assertEquals($S2, $SE3->getParentSite());
    	    
        	    
    	    //Check correct service endpoints for each Site
	    	    //Site1
	    	    $SiteSEPs = $S1->getServices();
	    	    foreach ($SiteSEPs as $SEP){
	    	    	$this->assertEquals($SE2, $SEP); 
	    	    }
	        	//Site2
	    	    $SiteSEPs = $S2->getServices();
	    	    foreach ($SiteSEPs as $SEP){
	    	    	$this->assertTrue(($SEP==$SE1) or ($SEP==$SE3));
	    	    }
    	    
   	
    	//Use database connection to check movememrnt
    		$con = $this->getConnection();
    		
    		//Check Sites are still present and their ID is unchanged
	    	$sql = "SELECT 1 FROM sites WHERE shortname = 'Site1' AND ID = '$S1_ID'";
	        $result = $con->createQueryTable('', $sql);
	    	$this->assertEquals(1, $result->getRowCount());
			
		   	$sql = "SELECT 1 FROM sites WHERE shortname = 'Site2' AND ID = '$S2_ID'";
	        $result = $con->createQueryTable('', $sql);
	    	$this->assertEquals(1, $result->getRowCount());
  		  	
    		
    		//Check each Site has the correct number of service endpoints
		    	//Site 1
		    	$sql = "SELECT 1 FROM services WHERE parentsite_id = '$S1_ID'";
		        $result = $con->createQueryTable('', $sql);
		    	$this->assertEquals(1, $result->getRowCount());	    	
		    	
				//Site 2
		    	$sql = "SELECT 1 FROM services WHERE parentsite_id = '$S2_ID'";
		        $result = $con->createQueryTable('', $sql);
		    	$this->assertEquals(2, $result->getRowCount());	 
		    	
	    	//check Site IDs are unchanged and they are assigned to the correct NGI
		    	//Site 1
		    	$sql = "SELECT 1 FROM services WHERE hostname = 'SEP1' AND id = '$SE1_id' AND parentsite_id = '$S2_ID'";
		        $result = $con->createQueryTable('', $sql);
		    	$this->assertEquals(1, $result->getRowCount());
		    	
		    	//Site 2
		    	$sql = "SELECT 1 FROM services WHERE hostname = 'SEP2' AND id = '$SE2_id' AND parentsite_id = '$S1_ID'";
		    	$result = $con->createQueryTable('', $sql);
		    	$this->assertEquals(1, $result->getRowCount());
		    	
		    	//Site 3
		    	$sql = "SELECT 1 FROM services WHERE hostname = 'SEP3' AND id = '$SE3_id' AND parentsite_id = '$S2_ID'";
		    	$result = $con->createQueryTable('', $sql);
		    	$this->assertEquals(1, $result->getRowCount());				
  	
	}//close function
}//close class

?>
