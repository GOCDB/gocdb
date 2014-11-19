<?php

require_once 'PHPUnit/Extensions/Database/TestCase.php';
require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php';

use Doctrine\ORM\EntityManager;

require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * A template that includes all the setup and tear down functions for writting
 * a PHPUnit test to test doctrine.  
 * 
 * @author David Meredith
 * @author James McCarthy
 */
class DoctrineTestExample extends PHPUnit_Extensions_Database_TestCase {

    private $em;
	
	 /**
     * Overridden. 
     */
    public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		echo "\n\n-------------------------------------------------\n";
        echo "Executing Your Test Name. . .\n";
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
    private function createEntityManager() {
        require dirname(__FILE__) . '/bootstrap_doctrine.php';
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

        foreach ($tables as $tableName) {
            //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            //echo 'row count: '.$result->getRowCount() ; 
            if ($result->getRowCount() != 0)
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
        }
    }

    /**
	* An example test showing the creation of a site and assertation of its data
    */
    public function testDoctrineExample() {
        print __METHOD__ . "\n";
		
		//Create a site
    	$ourSite = TestUtil::createSampleSite("Our Example Site");
		
		//Set some details of the site
		$ourSite->setEmail("myTest@email.com");
		$ourSite->setTelephone("012345678910");
		$ourSite->setLocation("United Kingdom");
		
		//Persist the site in memory
		$this->em->persist($ourSite);
		
		//Get the site ID from the object
		$siteId = $ourSite->getId();   
		
		//Get a PDO database connection	    	
    	$con = $this->getConnection();
		
		//Search the database for this site
	    $sql = "SELECT 1 FROM sites WHERE ID = '$siteId'";
	    $result = $con->createQueryTable('', $sql);
		
		//We expect this query to return no rows as the site does not exist in the database yet
	    $this->assertEquals(0, $result->getRowCount());
		
		//Commit the site to the database
		$this->em->flush();
				
		//Search the database for this site again
	    $sql = "SELECT 1 FROM sites WHERE ID = '$siteId'";
	    $result = $con->createQueryTable('', $sql);
		
		//We expect this query to return 1 rows as the site now exists in the database
	    $this->assertEquals(1, $result->getRowCount());		
    }

}


?>
