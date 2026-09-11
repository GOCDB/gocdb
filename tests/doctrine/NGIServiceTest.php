<?php

require_once dirname(__FILE__) . '/TestUtil.php';

use Doctrine\ORM\EntityManager;

require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/NGI.php';

/**
 * Test the NGI service, in particular the cascade delete behaviour when
 * deleting an ngi (i.e. cascading to Service, EndpointLocation, Downtime, Roles etc).
 *
 * @author David Meredith
 */
class NGIServiceTest extends \PHPUnit\Framework\TestCase
{
    private $em;

  /**
   * Overridden.
   */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing NGIServiceTest. . .\n";
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
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->query($sql)->fetchAll();
            if (count($result) != 0) {
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
            }
        }
    }

  /**
   * Test the NGI service deleteNGI() method which recursively deletes child
   * sites and services, roles etc.
   */
    public function testNgiService_deleteNgi()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

      // create an admin user (required to call the NGI service)
        $adminUser = TestUtil::createSampleUser('some', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/some/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

      // Now delete the ngi using the NGI service.
        $ngiService = new org\gocdb\services\NGI();
        $ngiService->setEntityManager($this->em);
        $ngiService->deleteNgi($ngi, $adminUser, false);


      // since we deleted the NGI, we expect an empty DB !
        $result = $testConn->query("SELECT * FROM Roles")->fetchAll();
        $this->assertTrue(count($result) == 0);

        $result = $testConn->query("SELECT * FROM NGIs")->fetchAll();
        $this->assertTrue(count($result) == 0);

        $result = $testConn->query("SELECT * FROM Sites")->fetchAll();
        $this->assertTrue(count($result) == 0);

        $result = $testConn->query("SELECT * FROM Services")->fetchAll();
        $this->assertTrue(count($result) == 0);

        $result = $testConn->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 0);

        $result = $testConn->query("SELECT * FROM EndpointLocations")->fetchAll();
        $this->assertTrue(count($result) == 0);

        $result = $testConn->query("SELECT * FROM CertificationStatusLogs")->fetchAll();
        $this->assertTrue(count($result) == 0);
    }
}
