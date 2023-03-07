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

use DateInterval;
use DateTime;
use DateTimeZone;
use org\gocdb\scripts\ManageAPICredentialsActions;
use org\gocdb\tests\ServiceTestUtil;
use PHPUnit_Extensions_Database_Operation_Factory;
use PHPUnit_Extensions_Database_TestCase;

require_once __DIR__ . '/../unit/lib/Gocdb_Services/ServiceTestUtil.php';
require_once __DIR__ . '/../../resources/ManageAPICredentials/ManageAPICredentialsActions.php';

class ManageAPICredentialsTest extends PHPUnit_Extensions_Database_TestCase
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
        echo "Executing ManageAPICredentialsTest. . .\n";
    }
    /**
     * Overridden. Returns the test database connection.
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        require_once __DIR__ . '/../doctrine/bootstrap_pdo.php';
        return getConnectionToTestDB();
    }
    /**
     * Overridden. Returns the test dataset.
     * Defines how the initial state of the database should look before each test is executed.
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $dataset = $this->createFlatXMLDataSet(__DIR__ . '/../doctrine/truncateDataTables.xml');
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

        date_default_timezone_set("UTC");
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
        require __DIR__ . '/../doctrine/bootstrap_doctrine.php';
        return $entityManager;
    }
    private function createTestAuthEnts($number)
    {
        /**
         * Create a number of unique API authentication credentials, evenly spaced each
         * with last used time 6 months before the previous, starting 6 months behind
         * the current time.
         *
         * @return \DateTime Time used as 'current' time.
         */

        list($user, $site, $siteService) =
            $this->serviceTestUtil->createGocdbEntities($this->entityManager);

        $baseTime = new DateTime('now', new DateTimeZone('UTC'));

        $type = 'X.509';

        $useTime = clone $baseTime;

        for ($count = 1; $count <= $number; $count++) {
            // $useTime will be decremented by 6M for each loop
            $useTime->sub(new DateInterval('P6M'));
            $ident = '/CN=A Dummy Subject ' . $count;
            $authEnt = $siteService->addAPIAuthEntity(
                $site,
                $user,
                array(
                    'IDENTIFIER' =>  $ident,
                    'TYPE' => $type,
                    'ALLOW_WRITE' => false
                )
            );

            $authEnt->setLastUseTime($useTime);
        }
        return $baseTime;
    }
    public function testLastUseTime()
    {
        print __METHOD__ . "\n";

        $baseTime = $this->createTestAuthEnts(3);

        $entityManager = $this->createEntityManager();

        $actions = new ManageAPICredentialsActions(false, $entityManager, $baseTime);

        // Fetch credentials not used in the last 7 months - should be 2
        $creds = $actions->getCreds(7);

        $this->assertCount(
            2,
            $creds,
            'Failed to filter credentials by time.'
        );

        // remove credentials last used more than 13 months ago
        // there should be one left after this operation
        $creds = $actions->deleteCreds($creds, 13);

        $this->assertCount(
            1,
            $creds,
            'Failed to delete credential by use time.'
        );

        // If we now repeat the original query there should be just one
        // credential following the delete.
        $creds = $actions->getCreds(7);

        $this->assertCount(
            1,
            $creds,
            'Unexpected credential count following deletion.'
        );
    }
}
