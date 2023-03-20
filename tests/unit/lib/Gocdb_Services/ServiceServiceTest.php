<?php

namespace org\gocdb\tests;

require_once dirname(__FILE__) . '/../../../doctrine/bootstrap.php';

require_once dirname(__FILE__) . '/../../../../lib/Gocdb_Services/ServiceService.php';
require_once dirname(__FILE__) . '/../../../../lib/Gocdb_Services/RoleActionMappingService.php';
require_once dirname(__FILE__) . '/../../../../lib/Gocdb_Services/RoleActionAuthorisationService.php';
require_once dirname(__FILE__) . '/../../../../lib/Doctrine/entities/Service.php';
require_once dirname(__FILE__) . '/../../../doctrine/TestUtil.php';

use Doctrine\ORM\EntityManager;
use org\gocdb\services\RoleActionAuthorisationService;
use org\gocdb\services\RoleActionMappingService;
use org\gocdb\services\Scope as ScopeService;
use org\gocdb\services\ServiceService;
use PHPUnit_Extensions_Database_Operation_Factory;
use PHPUnit_Extensions_Database_TestCase;
use RuntimeException;
use TestUtil;

/**
 * Test ServiceService functions.
 *
 * @author Ian Neilson
 */
class ServiceServiceTest extends PHPUnit_Extensions_Database_TestCase
{
    private $em;

     /**
     * Overridden.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing ServiceServiceTest. . .\n";
    }

    /**
     * Overridden. Returns the test database connection.
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        require_once __DIR__ . '/../../../doctrine/bootstrap_pdo.php';
        return getConnectionToTestDB();
    }

    /**
     * Overridden. Returns the test dataset.
     * Defines how the initial state of the database should look before each test is executed.
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/../../../doctrine/truncateDataTables.xml');
    }

    /**
     * Overridden.
     */
    protected function getSetUpOperation()
    {
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
    protected function getTearDownOperation()
    {
        // NONE is default
        return PHPUnit_Extensions_Database_Operation_Factory::NONE();
    }

    /**
     * Sets up the fixture, e.g create a new entityManager for each test run
     * This method is called before each test method is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->createEntityManager();
        /**
         * It would be nce to put the database setup here but it creates a rats nest of
         * problems with cleaning the database between tests.
         * ref: https://github.com/sebastianbergmann/dbunit/issues/37
         */
    }
    /**
     * Run after each test function to prevent pile-up of database connections.
     */
    protected function tearDown()
    {
        parent::tearDown();
        if (!is_null($this->em)) {
            $this->em->getConnection()->close();
        }
    }
    /**
     * @todo Still need to setup connection to different databases.
     * @return EntityManager
     */
    private function createEntityManager()
    {
        // Initialise to avoid unused variable warnings
        $entityManager = null;
        require __DIR__ . '/../../../doctrine/bootstrap_doctrine.php';
        return $entityManager;
    }

    /**
     * Called after setUp() and before each test. Used for common assertions
     * across all tests.
     */
    protected function assertPreConditions()
    {
        /**
         * Checks that all tables are empty before we start a test.
         */
        $con = $this->getConnection();
        $fixture = __DIR__ . '/../../../doctrine/truncateDataTables.xml';
        $tables = simplexml_load_file($fixture);

        foreach ($tables as $tableName) {
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            if ($result->getRowCount() != 0) {
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
            }
        }
    }
    private function createTestData()
    {

        //$project1 = new \Project("project1");
        //$this->em->persist($project1);
        //$ngi1 = TestUtil::createSampleNGI("ngi1");
        //$this->em->persist($ngi1);

        $pKey = new \PrimaryKey();
        $this->em->persist($pKey);

        $this->em->flush();

        $site1 = TestUtil::createSampleSite("site1");
        $this->em->persist($site1);
        $site1->setPrimaryKey($pKey->getId());

        $service1 = TestUtil::createSampleService("service1");
        $this->em->persist($service1);

        $type1 = TestUtil::createSampleServiceType("sample service type", "servicetype1");
        $this->em->persist($type1);

        $user1 = TestUtil::createSampleUser("forename1", "surname1", "/cn=dummy1");
        $this->em->persist($user1);
        $user1->setAdmin(true);

        $scope1 = TestUtil::createSampleScope("sample scope1", "scope1");
        $this->em->persist($scope1);

        //$service1->setParentSiteDoJoin($site1);
        //$ngi1->addSiteDoJoin($site1);
        //$project1->addNgi($ngi1);

        $this->em->flush();

        // This seems to be the minimal newvalues array to allow the
        // editService to execute
        $serviceValues = array(
            "hostingSite" => $site1->getId(),
            "serviceType" => $type1->getId(),
            "PRODUCTION_LEVEL" => "Y",
            "IS_MONITORED"     => "Y",
            "BETA"             => false,
            "Scope_ids"        => array($scope1),
            "ReservedScope_ids" => array(),
            "SE"               => array(
                "HOSTNAME"      => "gocdb.somedomain.biz",
                "URL"           => "https://gocdb.somedomain.biz",
                "DESCRIPTION"   => "some text to describe something",
                "HOST_DN"       => "/C=UK/O=HR/CN=me.me.me",
                "HOST_IP"       => "0.0.0.0",
                "HOST_IP_V6"    => "0000:0000:0000:0000:0000:0000:0000:0000",
                "HOST_OS"       => "BSD",
                "HOST_ARCH"     => "x86_64",
                "EMAIL"         => "pooh.bear@gocdb.org"
                )
            );

        $servService = new ServiceService();
        $servService->setEntityManager($this->em);

        $roleAMS = new RoleActionMappingService();

        $roleAAS = new RoleActionAuthorisationService($roleAMS);
        $roleAAS->setEntityManager($this->em);

        $servService->setRoleActionAuthorisationService($roleAAS);

        $scopeService = new ScopeService();
        $scopeService->setEntityManager($this->em);

        $servService->setScopeService($scopeService);

        return array ($servService, $serviceValues, $user1, $type1);
    }

    /**
     * Check that basic add service works
     */
    public function testAddService()
    {

        print __METHOD__ . "\n";

        list ($servService, $serviceValues, $user, ) = $this->createTestData();

        // Check we get a service back.
        $this->assertInstanceOf(
            '\Service',
            $service = $servService->addService($serviceValues, $user)
        );

        $this->assertTrue($service->getProduction());
        $this->assertTrue($service->getMonitored());
    }
    /**
     * Check the default rule that production services must be monitored.
     * @depends testAddService
     */
    public function testMonitoringFlag1()
    {

        print __METHOD__ . "\n";

        list ($servService, $serviceValues, $user, ) = $this->createTestData();

        // Force Monitoring and Production in conflict: should fail.
        $serviceValues["PRODUCTION_LEVEL"] = "Y";
        $serviceValues["IS_MONITORED"]     = "N";

        $this->setExpectedException('Exception');
        $this->assertInstanceOf(
            '\Service',
            $servService->addService($serviceValues, $user)
        );
    }
    /**
     * Check that exceptions to the default rule that production services
     * must be monitored are handled correctly
     * @depends testMonitoringFlag1
     */
    public function testMonitoringFlag2()
    {

        print __METHOD__ . "\n";

        list ($servService, $serviceValues, $user, $type) = $this->createTestData();

        // Force Monitoring and Production in conflict: should fail but ...
        $serviceValues["PRODUCTION_LEVEL"] = "Y";
        $serviceValues["IS_MONITORED"]     = "N";

        // ... set the exception so it doesn't.
        $type->setAllowMonitoringException(1);

        $this->assertInstanceOf(
            '\Service',
            $service = $servService->addService($serviceValues, $user)
        );

        $this->assertTrue($service->getProduction());
        $this->assertFalse($service->getMonitored());
    }
    /**
     * Check that exceptions to the default rule that production services
     * must be monitored are handled correctly
     * @depends testAddService
     */
    public function testEditService()
    {

        print __METHOD__ . "\n";

        list ($servService, $serviceValues, $user, ) = $this->createTestData();

        // Get a service
        $service = $servService->addService($serviceValues, $user);

        // Make some rather arbitrary changes not in conflict
        $serviceValues["PRODUCTION_LEVEL"] = "N";
        $serviceValues["IS_MONITORED"]     = "N";

        $this->assertInstanceOf(
            '\Service',
            $newS = $servService->editService($service, $serviceValues, $user)
        );

        $this->assertFalse($newS->getProduction());
        $this->assertFalse($newS->getMonitored());
    }
    /**
     * Check that exceptions to the default rule that production services
     * must be monitored are handled correctly
     * @depends testEditService
     */
    public function testMonitoringFlag3()
    {

        print __METHOD__ . "\n";

        list ($servService, $serviceValues, $user, ) = $this->createTestData();

        // Get a service
        $service = $servService->addService($serviceValues, $user);

        // Force Monitoring and Production in conflict: should fail.
        $serviceValues["PRODUCTION_LEVEL"] = "Y";
        $serviceValues["IS_MONITORED"]     = "N";

        $this->setExpectedException('Exception');
        $this->assertInstanceOf(
            '\Service',
            $servService->editService($service, $serviceValues, $user)
        );
    }
    /**
     * Check that exceptions to the default rule that production services
     * must be monitored are handled correctly
     * @depends testMonitoringFlag3
     */
    public function testMonitoringFlag4()
    {

        print __METHOD__ . "\n";

        list ($servService, $serviceValues, $user, $type) = $this->createTestData();

        // Get a service
        $service = $servService->addService($serviceValues, $user);

        // Force Monitoring and Production in conflict: should fail but ...
        $serviceValues["PRODUCTION_LEVEL"] = "Y";
        $serviceValues["IS_MONITORED"]     = "N";

        // ... set the exception so it doesn't.
        $type->setAllowMonitoringException(1);

        $this->assertInstanceOf(
            '\Service',
            $newS = $servService->editService($service, $serviceValues, $user)
        );

        $this->assertTrue($newS->getProduction());
        $this->assertFalse($newS->getMonitored());
    }
    public function createValidationEntities()
    {

        $util = new TestUtil();

        $site = $util->createSampleSite("TestSite");
        $this->em->persist($site);

        $service = $util->createSampleService("TestService1");
        $this->em->persist($service);
        $service->setParentSiteDoJoin($site);

        $user = $util->createSampleUser("Test", "Testing");
        $identifier = TestUtil::createSampleUserIdentifier("X.509", "/c=test");
        $user->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $this->em->persist($user);
        $user->setAdmin(true);

        $roleAMS = new RoleActionMappingService();
        $roleAAS = new RoleActionAuthorisationService($roleAMS);
        $roleAAS->setEntityManager($this->em);

        $this->em->flush();

        $serviceService = new ServiceService();
        $serviceService->setEntityManager($this->em);
        $serviceService->setRoleActionAuthorisationService($roleAAS);

        return array($service, $user, $serviceService);
    }
  /**
   * Check the basics -
   * Duplicates some simple testing from ExtensionsTest
   */
    public function testValidateProperty()
    {
        print __METHOD__ . "\n";

        list ($service, $user, $serviceService) = $this->createValidationEntities();
      // Properties are specified as array of arrays of form
      // [[Name,Value],[Name,Value], ... ]
        $values = [];
        $values[0] = array("ValidName1","ValidValue");
        $values[1] = array("ValidName2","<ValidValue><ValidValue>");

        $this->assertTrue($serviceService->addProperties($service, $user, $values) == null);

        $this->assertTrue(count($properties = $service->getServiceProperties()) == 2);

        $this->assertTrue(
            $serviceService->deleteServiceProperties($service, $user, $properties->toArray())
            == null
        );

        $this->assertTrue(count($properties = $service->getServiceProperties()) == 0);
    }
  /**
   * Check that validation of property name is operating as expected
   * Added to test code changes to pass through "<>" chars
   * @depends testValidateProperty
   */
    public function testValidatePropertyNameFails()
    {
        print __METHOD__ . "\n";

        list ($service, $user, $serviceService) = $this->createValidationEntities();
        // Properties are specified as array of arrays of form
        // [[Name,Value],[Name,Value], ... ]
        // < & > are invalid characters in the property name but are valid
        // for the property value.
        $values = [];
        $values[0] = array("<Invalid>","<valid>");

        $this->setExpectedException('Exception');

        $serviceService->addProperties($service, $user, $values);
    }
  /**
   * Check that validation of property value is operating as expected
   * @depends testValidateProperty
   */
    public function testValidatePropertyValueFails()
    {
        print __METHOD__ . "\n";

        list ($service, $user, $serviceService) = $this->createValidationEntities();
        // Properties are specified as array of arrays of form
        // [[Name,Value],[Name,Value], ... ]
        // Quote characters are invalid in property value
        $values = [];
        $values[0] = array("Valid","'Not Valid'");

        $this->setExpectedException('Exception');

        $serviceService->addProperties($service, $user, $values);
    }
}
