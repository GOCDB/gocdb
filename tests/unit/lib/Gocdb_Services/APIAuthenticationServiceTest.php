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
use RuntimeException;
use org\gocdb\tests\ServiceTestUtil;
use TestUtil;

/**
 * DBUnit test class for the {@see \org\gocdb\services\Site} service.
 *
 * @author Ian Neilson (after David Meredith)
 */
class APIAuthEnticationServiceTest extends \PHPUnit\Framework\TestCase
{
    private $entityManager;
    private $serviceTestUtil;
  /**
  * Overridden.
  */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing APIAuthEntServiceTest. . .\n";
    }

  /**
  * Returns the test database connection.
  * @return \PDO
  */
    protected function getConnection()
    {
        require_once __DIR__ . '/../../../doctrine/bootstrap_pdo.php';
        return getConnectionToTestDB();
    }

  /**
  * Sets up the fixture, e.g create a new entityManager for each test run
  * This method is called before each test method is executed.
  */
    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->createEntityManager();
        $this->serviceTestUtil = new ServiceTestUtil();
        (new \Doctrine\Common\DataFixtures\Purger\ORMPurger($this->entityManager))->purge();
      // Pass the Entity Manager into the Factory to allow Gocdb_Services
      // to use other Gocdb_Services.
        \Factory::setEntityManager($this->entityManager);
    }
  /**
   * Run after each test function to prevent pile-up of database connections.
   */
    protected function tearDown(): void
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
    protected function assertPreConditions(): void
    {
        $con = $this->getConnection();
        $fixture = __DIR__ . '/../../../doctrine/truncateDataTables.xml';
        $tables = simplexml_load_file($fixture);

        foreach ($tables as $tableName) {
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->query($sql)->fetchAll();
            if (count($result) != 0) {
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
