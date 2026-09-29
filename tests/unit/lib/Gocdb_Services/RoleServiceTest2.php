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
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use org\gocdb\services\Role;
use org\gocdb\services\RoleActionAuthorisationService;
use org\gocdb\services\RoleActionMappingService;
use org\gocdb\services\Site;

//use org\gocdb\services\User;
//use User;

/**
 * DBUnit test class for the {@see \org\gocdb\services\Role} service.
 *
 * @author Ian Neilson (after David Meredith)
 */

class RoleServiceTest2 extends \PHPUnit\Framework\TestCase
{
    private $entityManager;
    private $user;
    private $site;
    private $ngi;
  /**
   * Overridden.
   */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing RoleServiceTest. . .\n";
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
        (new ORMPurger($this->entityManager))->purge();
        $this->util = new TestUtil();
        $this->user = $this->util->createSampleUser("Alpha", "Beta");
        $this->user->setAdmin(true);

        $identifier = $this->util->createSampleUserIdentifier('X.509', "/CN=Alpha Beta");
        $this->entityManager->persist($identifier);

        $this->user->addUserIdentifierDoJoin($identifier);
        $this->entityManager->persist($this->user);

        $this->ngi = $this->util->createSampleNGI('NGI1');

        $this->site = $this->util->createSampleSite("Site1");

        $this->ngi->addSiteDoJoin($this->site);

        $this->entityManager->persist($this->site);
        $this->entityManager->persist($this->ngi);

        $this->roleServ = new Role();
        $this->roleServ->setEntityManager($this->entityManager);
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
  /**
   * Create some roles
   */
    private function createTestRoles(array $roleNames)
    {
        $roles = array();
        foreach ($roleNames as $type) {
            $roleType = $this->util->createSampleRoleType($type);
            $role = $this->util->createSampleRole($this->user, $roleType, $this->site, \RoleStatus::GRANTED);
            $this->entityManager->persist($roleType);
            $this->entityManager->persist($role);
            $roles[] = $role;
        };

        $this->entityManager->flush();

        return $roles;
    }
  /**
   * Attach an APIAuthentication credential to user and site
   */
    private function addAPIAuthEntity()
    {
      // The only way to add an APIAuthentication credential is by creating
      // a site service ...

        $siteServ = new Site();
        $siteServ->setEntityManager($this->entityManager);

      // No role mappings are created or tested - we rely in user being an admin
        $ram = new RoleActionMappingService();
        $ras = new RoleActionAuthorisationService($ram);

        $ras->setEntityManager($this->entityManager);

        $siteServ->setRoleActionAuthorisationService($ras);

        $siteServ->addAPIAuthEntity(
            $this->site,
            $this->user,
            array(
            "IDENTIFIER" => $this->user->getUserIdentifiers()[0]->getKeyValue(),
            "TYPE" => "X.509",
            "ALLOW_WRITE" => false
            )
        );
        return;
    }
  /**
   * Tests begin here
   */
    public function testCheckOrphanAPIAuth()
    {
        print __METHOD__ . "\n";

        $roles = $this->createTestRoles(array("Manager", "Administrator"));

      // No APIAuthentication credentials are yet assigned so the
      // check should pass.
        $this->assertCount(0, $this->roleServ->checkOrphanAPIAuth($roles[0]));

        $this->addAPIAuthEntity();

      // User has two roles so the check should pass.
        $this->assertCount(0, $this->roleServ->checkOrphanAPIAuth($roles[0]));
    }
  /**
   * @depends testCheckOrphanAPIAuth
   */
    public function testFailCheckOrphanAPIAuth()
    {
        print __METHOD__ . "\n";

        $roles = $this->createTestRoles(array("Manager"));

        $this->addAPIAuthEntity();

        // Should fail because there is only one role.
        $this->assertCount(1, $this->roleServ->checkOrphanAPIAuth($roles[0]));
    }
}
