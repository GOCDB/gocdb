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
namespace org\gocdb\tests;

require_once __DIR__ . '/ServiceTestUtil.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/APIAuthenticationService.php';

use Doctrine\ORM\EntityManager;
use org\gocdb\services\APIAuthenticationService;
use PHPUnit_Extensions_Database_Operation_Factory;
use PHPUnit_Extensions_Database_TestCase;
use RuntimeException;
use org\gocdb\tests\ServiceTestUtil;
use TestUtil;

/**
 * DBUnit test class for the {@see \org\gocdb\services\Site} service.
 *
 * @author Ian Neilson (after David Meredith)
 */
class APIAuthEnticationServiceTest extends PHPUnit_Extensions_Database_TestCase
{
    private $entityManager;
    private $dbOpsFactory;
    private $serviceTestUtil;

    public function __construct()
    {
        parent::__construct();
      // Use a local instance to avoid Mess Detector's whinging about avoiding
      // static access.
        $this->dbOpsFactory = new PHPUnit_Extensions_Database_Operation_Factory();
        $this->serviceTestUtil = new ServiceTestUtil();
    }
  /**
  * Overridden.
  */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing APIAuthEntServiceTest. . .\n";
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
        $dataset = $this->createFlatXMLDataSet(__DIR__ . '/../../../doctrine/truncateDataTables.xml');
        return $dataset;
      // Use below to return an empty data set if we don't want to truncate and seed
      //return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
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
        return $this->dbOpsFactory->DELETE_ALL();
    }

  /**
  * Overridden.
  */
    protected function getTearDownOperation()
    {
      // NONE is default
        return $this->dbOpsFactory->NONE();
    }

  /**
  * Sets up the fixture, e.g create a new entityManager for each test run
  * This method is called before each test method is executed.
  */
    protected function setUp()
    {
        parent::setUp();
        $this->entityManager = $this->createEntityManager();
      // Pass the Entity Manager into the Factory to allow Gocdb_Services
      // to use other Gocdb_Services.
        \Factory::setEntityManager($this->entityManager);
    }
  /**
   * Run after each test function to prevent pile-up of database connections.
   */
    protected function tearDown()
    {
        parent::tearDown();
        if (!is_null($this->entityManager)) {
            $this->entityManager->getConnection()->close();
        }
    }
  /**
  * @return EntityManager
  */
    private function createEntityManager()
    {
        $entityManager = null; // Initialise in local scope to avoid unused variable warnings
        require __DIR__ . '/../../../doctrine/bootstrap_doctrine.php';
        return $entityManager;
    }

  /**
  * Called after setUp() and before each test. Used for common assertions
  * across all tests.
  */
    protected function assertPreConditions()
    {
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
    public function testGetAPIAuthentication()
    {
        print __METHOD__ . "\n";

        list($user, $site, $siteService, $authEntServ) =
          $this->serviceTestUtil->createGocdbEntities($this->entityManager);

        $this->assertTrue(
            $authEntServ instanceof APIAuthenticationService,
            'Failed to create APIAuthenticationService'
        );

        $ident = '/CN=A Dummy Subject';
        $type = 'X.509';
        // Start with no APIAuthentication entities to be found
        $this->assertCount(
            0,
            $authEntServ->getAPIAuthentication($ident),
            "Non-zero count returned when searching for APIAuthentication entity " .
            "for id:{$ident} when expected none."
        );

        $authEnt = $siteService->addAPIAuthEntity(
            $site,
            $user,
            array(
                'IDENTIFIER' =>  $ident,
                'TYPE' => $type,
                'ALLOW_WRITE' => false
            )
        );

        $this->assertTrue(
            $authEnt instanceof \APIAuthentication,
            "Failed to add APIAuthentication entity for id:{$ident}."
        );

        $authEntMatched = $authEntServ->getAPIAuthentication($ident);

        $this->assertCount(
            1,
            $authEntMatched,
            "Failed to return single APIAuthentication entity searching for id:{$ident}."
        );

        $this->assertTrue(
            $authEnt === $authEntMatched[0],
            "Failed to return matching APIAuthentication entity searching for for id:{$ident}."
        );
    }
}
