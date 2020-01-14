<?php

//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';

require_once dirname(__FILE__) . '/../../../doctrine/TestUtil.php';

use Doctrine\ORM\EntityManager;

require_once dirname(__FILE__) . '/../../../doctrine/bootstrap.php';

require_once dirname(__FILE__) . '/../../../../lib/Gocdb_Services/ServiceType.php';

//require_once dirname(__FILE__) . '/../../../../lib/Gocdb_Services/RoleActionMappingService.php';
//require_once dirname(__FILE__) . '/../../../../lib/Gocdb_Services/RoleActionAuthorisationService.php';

//require_once dirname(__FILE__) . '/../../../../lib/Doctrine/entities/Service.php';

/**
 * Test ServiceService functions.
 *
 * @author Ian Neilson
 */
class ServiceTypeServiceTest extends PHPUnit_Extensions_Database_TestCase {

    private $em;

     /**
     * Overridden.
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing ServiceTypeServiceTest. . .\n";
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
        /**
         * It would be nce to put the database setup here but it creates a rats nest of
         * problems with cleaning the database between tests.
         * ref: https://github.com/sebastianbergmann/dbunit/issues/37
         */
    }

    /**
     * @todo Still need to setup connection to different databases.
     * @return EntityManager
     */
    private function createEntityManager() {
        require __DIR__ . '/../../../doctrine/bootstrap_doctrine.php';
        return $entityManager;
    }

    /**
     * Called after setUp() and before each test. Used for common assertions
     * across all tests.
     */
    protected function assertPreConditions() {
        /**
         * Checks that all tables are empty before we start a test.
         */
        $con = $this->getConnection();
        $fixture = __DIR__ . '/../../../doctrine/truncateDataTables.xml';
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
    private function createTestData() {

        //$project1 = new \Project("project1");
        //$this->em->persist($project1);
        //$ngi1 = TestUtil::createSampleNGI("ngi1");
        //$this->em->persist($ngi1);

        $pk = new \PrimaryKey();
        $this->em->persist($pk);

        $this->em->flush();
        
        $user1 = TestUtil::createSampleUser("forename1","surname1","/cn=dummy1");
        $this->em->persist($user1);
        $user1->setAdmin(TRUE);

        $this->em->flush();

        $values = array (
            "Name" => "serviceType1",
            "Description" => "description of service type 1",
            "AllowMonitoringException" => FALSE );

        return array ($values, $user1);
    }

    /**
     * Check that basic add service works and monitoring is not allowed
     * for production services.
     */
    public function testAddServiceType1() {

        print __METHOD__ . "\n";

        list ($values, $user) = $this->createTestData();

        $this->assertInstanceOf('org\gocdb\services\ServiceType',
                    $s = new org\gocdb\services\ServiceType());
        
        $s->setEntityManager($this->em);

        // Check we get a service type back.
        $this->assertInstanceOf('\ServiceType',
            $st = $s->addServiceType($values, $user));

        $this->assertFalse($st->getAllowMonitoringException());
    }
    /**
     * Check that duplicates fail
     * @depends testAddServiceType1
     */
    public function testAddServiceType2() {

        print __METHOD__ . "\n";

        list ($values, $user) = $this->createTestData();

        $s = new org\gocdb\services\ServiceType();
        
        $s->setEntityManager($this->em);
        $s->addServiceType($values, $user);

        // Finally - trying to add the same again should fail
        $this->setExpectedException('Exception');
        $st = $s->addServiceType($values, $user);
    }
    /**
     * Check that admin privileges are needed
     * @depends testAddServiceType1
     */
    public function testAddServiceType3() {

        print __METHOD__ . "\n";

        list ($values, $user) = $this->createTestData();

        $user->setAdmin(FALSE);

        $s = new org\gocdb\services\ServiceType();
        
        $s->setEntityManager($this->em);

        $this->setExpectedException('Exception');
        $s->addServiceType($values, $user);
    }
    /**
     * Check that admin privileges are needed
     * @depends testAddServiceType1
     */
    public function testEditServiceType1() {

        print __METHOD__ . "\n";

        list ($values, $user) = $this->createTestData();

        $s = new org\gocdb\services\ServiceType();
        
        $s->setEntityManager($this->em);

        $st = $s->addServiceType($values, $user);

        // Change the name and check the edit succeeds
        $newName = $values['Name'] . '0';
        $values['Name'] = $newName;

        $s->editServiceType($st, $values, $user);

        $this->assertEquals($newName, $st->getName());
    }
}

?>
