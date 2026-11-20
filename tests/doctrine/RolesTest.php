<?php

require_once dirname(__FILE__) . '/TestUtil.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/ServiceDAO.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/SiteDAO.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/NGIDAO.php';

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * Test the role functionality.
 * This test case truncates the test database (a clean insert with no seed data)
 * and performs subsequent CRUD operations using Doctrine ORM.
 * Usage:
 * Run the recreate.sh to create the sample database first (create tables etc), then run:
 * '$phpunit TestRoles.php'
 *
 * @author David Meredith
 * @author John Casson
 */
class RolesTest extends \PHPUnit\Framework\TestCase
{
    private $em;
    private $egiScope;
    private $localScope;
    private $eudatScope;

  /**
   * Overridden.
   */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing RolesTest. . .\n";
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
        (new ORMPurger($this->em))->purge();
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
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->query($sql)->fetchAll();
            if (count($result) != 0) {
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
            }
        }
    }

  /**
   * Test Role's discriminator column
   * Add a role type, user, site and a role linking
   * them all together. Assert that $newRole->getOwnedEntity()
   * returns an instanceof Site.
   */
    public function testRoleDiscriminatorSite()
    {
        print __METHOD__ . "\n";
      // Create a roletype
        $rt = TestUtil::createSampleRoleType("ROLENAME");
        $this->em->persist($rt);

      // Create a user
        $u = TestUtil::createSampleUser("Test", "Testing");
        $identifier = TestUtil::createSampleUserIdentifier("X.509", "/c=test");
        $u->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $this->em->persist($u);

      // Create a site
        $s = TestUtil::createSampleSite("SITENAME"/*, "PK01"*/);
        $this->em->persist($s);

      // Create a role and link to the user, role type and site
        $r = TestUtil::createSampleRole($u, $rt, $s, RoleStatus::GRANTED);
        $this->em->persist($r);

        $this->em->flush();

      // New reference to the freshly created role entity
        $dbRole = $this->em->find("Role", $r->getId());
        if (!$dbRole->getOwnedEntity() instanceof Site) {
            $this->fail();
        }
      // if we've reached this point without error the test
      // has passed.
    }

  /**
  * Test Role's discriminator column
  * Add a role type, user, NGI and a role linking
  * them all together. Assert that $newRole->getOwnedEntity()
  * returns an instance of NGI.
  */
    public function testRoleDiscriminatorNGI()
    {
        print __METHOD__ . "\n";
      // Create a roletype
        $rt = TestUtil::createSampleRoleType("Name");
        $this->em->persist($rt);

      // Create a user
        $u = TestUtil::createSampleUser("Test", "Testing");
        $identifier = TestUtil::createSampleUserIdentifier("X.509", "/c=test");
        $u->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $this->em->persist($u);

      // Create an NGI
        $n = TestUtil::createSampleNGI("MYNGI");
        $this->em->persist($n);

      // Create a role and link to the user, role type and site
        $r = TestUtil::createSampleRole($u, $rt, $n, RoleStatus::GRANTED);

        $this->em->persist($r);

        $this->em->flush();

      // New reference to the freshly created role entity
        $dbRole = $this->em->find("Role", $r->getId());
        if (!$dbRole->getOwnedEntity() instanceof NGI) {
            $this->fail();
        }
      // if we've reached this point without error the test
      // has passed.
    }

  /**
   * Test Role's discriminator column
   * Add a role type, user, NGI and a role linking
   * them all together. Assert that $newRole->getOwnedEntity()
   * returns an instance of NGI.
   */
    public function testRoleTypeIntegrityConstraint()
    {
        $this->expectException(\Doctrine\DBAL\Exception::class);
        print __METHOD__ . "\n";
      // Create a roletype
        $rt = TestUtil::createSampleRoleType("NAME");
        $this->em->persist($rt);

      // Create a user
        $u = TestUtil::createSampleUser("Test", "Testing");
        $identifier = TestUtil::createSampleUserIdentifier("X.509", "/c=test");
        $u->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $this->em->persist($u);

      // Create an NGI
        $n = TestUtil::createSampleNGI("MYNGI");
        $this->em->persist($n);

      // Create a role and link to the user, role type and ngi
        $r = TestUtil::createSampleRole($u, $rt, $n, RoleStatus::GRANTED);
        $this->em->persist($r);
        $this->em->flush();

      // try to delete the role type before deleting
      // the dependant role
        $this->em->remove($rt);
        $this->em->flush();
    }

  /**
   * Ensure no duplicate role types are inserted
   */
    public function testDuplicateRoleTypes()
    {
        $this->expectException(\Doctrine\DBAL\Exception::class);
        print __METHOD__ . "\n";
      // Should throw an expected exception because the role type Name value
      // must be unique
        $rt1 = TestUtil::createSampleRoleType("RoleName"/*, RoleTypeClass::SITE_USER*/);
        $rt2 = TestUtil::createSampleRoleType("RoleName"/*, RoleTypeClass::REGIONAL_USER*/);
        $this->em->persist($rt1);
        $this->em->persist($rt2);
        $this->em->flush();
    }

    public function testRoleConstants()
    {
        print __METHOD__ . "\n";

        $roleNames = RoleTypeName::getAsArray();
        $this->assertEquals(RoleTypeName::SITE_ADMIN, $roleNames['SITE_ADMIN']);
        $this->assertEquals(RoleTypeName::COD_ADMIN, $roleNames['COD_ADMIN']);

        $roleStatusVals = RoleStatus::getAsArray();
        $this->assertEquals(RoleStatus::GRANTED, $roleStatusVals['GRANTED']);
        $this->assertEquals(RoleStatus::PENDING, $roleStatusVals['PENDING']);
    }
}
