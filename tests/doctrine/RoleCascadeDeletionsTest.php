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
 * Test the Role cascade delete functionality. The Role entity defines
 * onDelete=CASCADE on its FK mappings to OwnedEntity and User. Therefore,
 * when a User or OwnedEntity is deleted, the corresponding Roles are also
 * cascade deleted.
 *
 * This test case truncates the test database (a clean insert with no seed data)
 * and performs subsequent CRUD operations using Doctrine ORM.
 * Usage:
 * Run the recreate.sh to create the sample database first (create tables etc), then run:
 * '$phpunit TestRoleCascadeDeletions.php'
 *
 * @author David Meredith
 */
class RoleCascadeDeletionsTest extends \PHPUnit\Framework\TestCase
{
    private $em;

  /**
   * Overridden.
   */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing RoleCascadeDeletionsTest. . .\n";
    }

  /**
   * Returns the test database connection.
   * @return \PDO
   */
    protected function getConnection()
    {
        require_once dirname(__FILE__) . '/bootstrap_pdo.php';
        return getConnectionToTestDB();
    }

  /**
   * Sets up the fixture, e.g create a new entityManager for each test run
   * This method is called before each test method is executed.
   */
    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->createEntityManager();
        (new \Doctrine\Common\DataFixtures\Purger\ORMPurger($this->em))->purge();
    }
  /**
   * Run after each test function to prevent pile-up of database connections.
   */
    protected function tearDown(): void
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
        require dirname(__FILE__) . '/bootstrap_doctrine.php';
        return $entityManager;
    }

  /**
   * Called after setUp() and before each test. Used for common assertions
   * across all tests.
   */
    protected function assertPreConditions(): void
    {
        $con = $this->getConnection();
        $fixture = dirname(__FILE__) . '/truncateDataTables.xml';
        $tables = simplexml_load_file($fixture);

        foreach ($tables as $tableName) {
          //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->query($sql)->fetchAll();
          //echo 'row count: '.count($result) ;
            if (count($result) != 0) {
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
            }
        }
    }

  /**
   * Create a linked OwnedEntity graph with user with Roles over those OwnedEntities.
   * Next, delete the User and assert that all the user's Roles were also deleted by the domain
   * model's automatic onDelete=CASCADE defined on the Role's FK mapping to User.
   */
    public function testRolesCascadeDelete_OnUserDeletion()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

      // Delete the user. The onDelete=CASCADE defined in Role's user FK mapping
      // will remove all the user's roles.
        $this->em->remove($userWithRoles);
        $this->em->flush();

      // Need to clear the identity map (all objects become detached) so that
      // when we re-fetch the user, it will be looked from db not served by entity map
        $this->em->clear();

        $userWithRoles = $this->em->find("User", $userId);
        $this->assertNull($userWithRoles);

        $testConn = $this->getConnection();
        $result = $testConn->query("SELECT * FROM Users")->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $testConn->query("SELECT * FROM Roles")->fetchAll();
        $this->assertTrue(count($result) == 0);
    }

  /**
   * Create a linked OwnedEntity graph with user with Roles over those OwnedEntities.
   * Next, delete selected OwnedEntities and assert that all the Roles that
   * were linked to the deleted OwnedEntites were also deleted by the domain
   * model's automatic onDelete=CASCADE defined on the Role's FK mapping to OwnedEntity.
   *
   * Delete all the OwnedEntities
   */
    public function testRolesCascadeDelete_OnOwnedEntityDeletion1()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

      // Queue Deletion of ngi, site1 (and its services) and assert that the relevant
      // user roles were also deleted as expected by the onDelete=cascades configured in the entity model.
      // Note, we are not removing site2 as we want to keep this site and user's roles.
        $siteDAO = new SiteDAO();
        $serviceDAO = new ServiceDAO();
        $ngiDAO = new NGIDAO();
        $siteDAO->setEntityManager($this->em);
        $serviceDAO->setEntityManager($this->em);
        $ngiDAO->setEntityManager($this->em);

      // ordering of removal is NOT significant here !
        $ngiDAO->removeNGI($ngi);
        $siteDAO->removeSite($site1);
        $siteDAO->removeSite($site2);
        $serviceDAO->removeService($service1);
        $serviceDAO->removeService($service2);

        $this->em->flush();

      // Need to clear the identity map (all objects become detached) so that
      // when we re-fetch the user, it will be looked from db not served by entity map
        $this->em->clear();

      // Need to re-fetch the user from the DB again, if don't, then user already
      // has his eargerly fetched roles present in UserProxy object
        $userWithRoles = $this->em->find("User", $userId);
        $this->assertEquals(0, count($userWithRoles->getRoles()));

        $testConn = $this->getConnection();
        $result = $testConn->query("SELECT * FROM Roles")->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $testConn->query("SELECT * FROM Sites")->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $testConn->query("SELECT * FROM NGIs")->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $testConn->query("SELECT * FROM Services")->fetchAll();
        $this->assertTrue(count($result) == 0);
    }

  /**
   * Delete both Services but not the ngi
   * @see testRolesCascadeDelete_OnOwnedEntityDeletion1
   */
    public function testRolesCascadeDelete_OnOwnedEntityDeletion2()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

        $siteDAO = new SiteDAO();
        $serviceDAO = new ServiceDAO();
        $ngiDAO = new NGIDAO();
        $siteDAO->setEntityManager($this->em);
        $serviceDAO->setEntityManager($this->em);
        $ngiDAO->setEntityManager($this->em);

      // ordering of removal is NOT significant here !
        $serviceDAO->removeService($service1);
        $serviceDAO->removeService($service2);
        $siteDAO->removeSite($site1);
        $siteDAO->removeSite($site2);
      //$ngiDAO->removeNGI($ngi); // don't remove NGI

        $this->em->flush();

      // Need to clear the identity map (all objects become detached) so that
      // when we re-fetch the user, it will be looked from db not served by entity map
        $this->em->clear();

      // Need to re-fetch the user from the DB again, if don't, then user already
      // has his eargerly fetched roles present in UserProxy object
        $userWithRoles = $this->em->find("User", $userId);
      // user should have 2 remaining roles still from ngi
        $this->assertEquals(2, count($userWithRoles->getRoles()));

        $testConn = $this->getConnection();
        $result = $testConn->query("SELECT * FROM Roles")->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $testConn->query("SELECT * FROM NGIs")->fetchAll();
        $this->assertTrue(count($result) == 1);
    }

  /**
   * Delete the ngi but not the sites
   * @see testRolesCascadeDelete_OnOwnedEntityDeletion1
   */
    public function testRolesCascadeDelete_OnOwnedEntityDeletion3()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

      // Queue Deletion of ngi and assert that the relevant
      // ngi user roles were also deleted.
        $ngiDAO = new NGIDAO();
        $ngiDAO->setEntityManager($this->em);

      // need to delete the relationships between ngi and sites before deleting
      // the ngi - remember to unlink from both sides of the relationship
        $ngi->getSites()->removeElement($site1);
        $site1->setNgiDoJoin(null);
        $ngi->getSites()->removeElement($site2);
        $site2->setNgiDoJoin(null);

      // Now delete the NGI
        $ngiDAO->removeNGI($ngi);

        $this->em->flush();

      // Need to clear the identity map (all objects become detached) so that
      // when we re-fetch the user, it will be looked from db not served by entity map
        $this->em->clear();

      // Need to re-fetch the user from the DB again, if don't, then user already
      // has his eargerly fetched roles present in UserProxy object
        $userWithRoles = $this->em->find("User", $userId);
      // user should have 4 remaining roles still from sites that exist in DB
        $this->assertEquals(4, count($userWithRoles->getRoles()));

        $testConn = $this->getConnection();
        $result = $testConn->query("SELECT * FROM Roles")->fetchAll();
        $this->assertTrue(count($result) == 4);
        $result = $testConn->query("SELECT * FROM Sites")->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $testConn->query("SELECT * FROM NGIs")->fetchAll();
        $this->assertTrue(count($result) == 0);
    }
}
