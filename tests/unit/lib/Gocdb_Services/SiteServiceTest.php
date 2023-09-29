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

require_once __DIR__ . '/../../../doctrine/TestUtil.php';
require_once __DIR__ . '/ServiceTestUtil.php';
require_once __DIR__ . '/../../../../lib/Doctrine/entities/User.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Site.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Config.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Scope.php';

use Doctrine\ORM\EntityManager;
use org\gocdb\tests\ServiceTestUtil;
use PHPUnit_Extensions_Database_Operation_Factory;
use PHPUnit_Extensions_Database_TestCase;
use RuntimeException;
use TestUtil;

/**
 * DBUnit test class for the {@see \org\gocdb\services\Site} service.
 *
 * @author Ian Neilson (after David Meredith)
 */
class SiteServiceTest extends PHPUnit_Extensions_Database_TestCase
{
    private $entityManager;
    private $dbOpsFactory;
  /** @var TestUtil $testUtil */
    private $testUtil;
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
        echo "Executing SiteServiceTest. . .\n";
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
        $this->testUtil = new TestUtil();
      // Pass the Entity Manager into the Factory to allow Gocdb_Services
      // to use other Gocdb_Service.
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
  /**
   * Helper function to persist a test object
   */
    protected function persistAndFlush($instance)
    {
        $this->entityManager->persist($instance);
        $this->entityManager->flush();
    }

  /*
  * Tests begin here
  * First basic check we can instantiate a Site Service and create a site with it.
  */
    public function testAddSite()
    {
        print __METHOD__ . "\n";

        $siteData = $this->serviceTestUtil->getSiteData($this->entityManager);
        $siteService = $this->serviceTestUtil->getSiteService($this->entityManager);

      // The most basic check
        $this->assertTrue(
            $siteService instanceof \org\gocdb\services\Site,
            'Site Service failed to create and return a Site service'
        );

        $this->serviceTestUtil->createAndAddSite($this->entityManager, $siteData);

      // Check
      // N.B. Although getSitesFilterByParams says all the filters are optional,
      // in fact, if you don't specify a scope 'EGI' is forced on you :-(

        $this->assertCount(1, $siteService->getSitesFilterByParams(array('scope' => 'Scope1')));
    }
  /**
   * @depends testAddSite
   * Check that authentication entities can be added and removed correctly using the Site Service
   */
    public function testAddAPIAuthentication()
    {
        print __METHOD__ . "\n";

      /** @var \User */
        $user = $this->testUtil->createSampleUser('Beta', 'User', '/Beta.User');
      // We don't want to test all the roleAction logic here so simply make us an admin
        $user->setAdmin(true);
        $this->persistAndFlush($user);

        $siteData = $this->serviceTestUtil->getSiteData($this->entityManager);
        $siteService = $this->serviceTestUtil->getSiteService($this->entityManager);
        $this->serviceTestUtil->createAndAddSite($this->entityManager, $siteData);

        $sites = $siteService->getSitesFilterByParams(array('scope' => 'Scope1'));
        $site = $sites[0];

      // Check we can add an authenticationEntity to a site and it is properly
      // associated with the user.

        $authEnt = $siteService->addAPIAuthEntity(
            $site,
            $user,
            array('IDENTIFIER' => '/CN=A Dummy Subject' ,
            'TYPE' => 'X.509',
            'ALLOW_WRITE' => false)
        );

        $this->assertTrue(
            $authEnt instanceof \APIAuthentication,
            'Site Service failed to add APIAuthentication'
        );
        $siteAuthEnts = $site->getAPIAuthenticationEntities();
        $userAuthEnts = $user->getAPIAuthenticationEntities();
        $this->assertTrue(
            $userAuthEnts[0] === $siteAuthEnts[0],
            'Site Service failed to link user and site APIAuthenticationEntity'
        );

      // Check the delete and cleanup on site and user sides goes ok

        $this->assertNull(
            $siteService->deleteAPIAuthEntity($authEnt, $user),
            'Site Service failed to delete APIAuthenticationEntity'
        );
        $this->assertEmpty(
            $user->getAPIAuthenticationEntities(),
            'Site Service failed to remove APIAuthenticationEntity from User'
        );
        $this->assertEmpty(
            $site->getAPIAuthenticationEntities(),
            'Site Service failed to remove APIAuthenticationEntity from Site'
        );
    }
}
