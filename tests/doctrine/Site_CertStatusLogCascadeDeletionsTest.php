<?php

//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/ServiceDAO.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/SiteDAO.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/NGIDAO.php';

use Doctrine\ORM\EntityManager;

require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * Test the CertStatusLog cascade delete functionality.    
 * 
 * This test case truncates the test database (a clean insert with no seed data)
 * and performs subsequent CRUD operations using Doctrine ORM. 
 * Usage: 
 * Run the recreate.sh to create the sample database first (create tables etc), then run:
 * '$phpunit TestSite_CertStatusLogCascadeDeletions.php' 
 *
 * @author David Meredith
 */
class Site_CertStatusLogCascadeDeletionsTest extends PHPUnit_Extensions_Database_TestCase {

    private $em;

    //private $egiScope; 
    //private $localScope; 
    //private $eudatScope; 

    /**
     * Overridden. 
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing Site_CertStatusLogCascadeDeletionsTest. . .\n";
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
    private function createEntityManager() {
        //require dirname(__FILE__).'/../lib/Doctrine/bootstrap.php';
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


     public function testCertStatusLogDeleted_OnSiteDeletion() {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

        // delete site2 - the certStatusLogs
        $siteDAO = new SiteDAO();
        $siteDAO->setEntityManager($this->em);
        $siteDAO->removeSite($site2);

        $this->em->flush();

        // Need to clear the identity map (all objects become detached) so that 
        // when we re-fetch the user, it will be looked from db not served by entity map
        $this->em->clear();

        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
        $this->assertTrue($result->getRowCount() == 1); // site1 not deleted
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM CertificationStatusLogs");
        $this->assertTrue($result->getRowCount() == 0);

    }

    
}

?>
