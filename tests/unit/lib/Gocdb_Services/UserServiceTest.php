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
require_once __DIR__ . '/../../../../lib/Doctrine/entities/User.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/User.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Config.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';

use Doctrine\ORM\EntityManager;
//use org\gocdb\services\User;
//use User;

/**
 * DBUnit test class for the {@see \org\gocdb\services\User} service.
 *
 * @author Ian Neilson (after David Meredith)
 */
class UserServiceTest extends PHPUnit_Extensions_Database_TestCase
{
    private $entityManager;
    private $dbOpsFactory;

    function __construct()
    {
        parent::__construct();
      // Use a local instance to avoid Mess Detector's whinging about avoiding
      // static access.
        $this->dbOpsFactory = new PHPUnit_Extensions_Database_Operation_Factory();
    }
  /**
  * Overridden.
  */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing UserServiceTest. . .\n";
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
   * Create some test user data
   */
    private function getUserData()
    {

        $userData = array (
        'TITLE' => 'President',
        'FORENAME' => 'Forename',
        'SURNAME' => 'Surname',
        'EMAIL' => 'forename.surname@somedomain.net',
        'TELEPHONE' => '01234 56789'
        );

        return $userData;
    }
  /**
   * Create a minimal identifier
   */
    private function getUserIdentifier()
    {

        $userIdentifier = array ('NAME' => 'X.509','VALUE' => '/CN=Forename Surname');

        return ($userIdentifier);
    }

    private function createAndRegisterUser($userData, $userIdentifier)
    {

        $userService = new org\gocdb\services\User();
        $userService->setEntityManager($this->entityManager);
        $userService->register($userData, $userIdentifier);

        return $userService;
    }
  /*
  * Tests begin here
  */
    public function testRegisterUser()
    {
        print __METHOD__ . "\n";

        $userData = $this->getUserData();
        $userIdentifier = $this->getUserIdentifier();
        $userService = $this->createAndRegisterUser($userData, $userIdentifier);

        $user = $userService->getUserByPrinciple($userIdentifier['VALUE']);

      // Check that we get an instance of User class in the correct namespace returned.
        $this->assertTrue($user instanceof \User, 'User service failed to registered and return a User');
    }
  /**
   * @depends testRegisterUser
   */
    public function testAddAPIAuthentication()
    {
        print __METHOD__ . "\n";

        $userData = $this->getUserData();
        $userIdentifier = $this->getUserIdentifier();
        $userService = $this->createAndRegisterUser($userData, $userIdentifier);

        $authEnt = new \APIAuthentication();
        $authEnt->setIdentifier($userIdentifier['VALUE']);
        $authEnt->setType("X509");

        $user = $userService->getUserByPrinciple($userIdentifier['VALUE']);

        $user->addAPIAuthenticationEntitiesDoJoin($authEnt);

        $authUser = $authEnt->getUser();
      // Check that both sides of the relationship match
        $this->assertEquals($user->getId(), $authUser->getId());
    }
}
