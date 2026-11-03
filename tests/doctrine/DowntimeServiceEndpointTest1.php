<?php

require_once dirname(__FILE__) . '/TestUtil.php';
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/Site.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/NGI.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/ServiceService.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/ServiceDAO.php';

/**
 * Service, EndpointLocation and Downtime cascade deletion tests.
 *
 * @author David Meredith
 */
class DowntimeServiceEndpointTest1 extends \PHPUnit\Framework\TestCase
{
    private $em;

  /**
  * Overridden.
  */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing DowntimeServiceEndpointTest1. . .\n";
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
  * Delete service1 and ensure the DAO cascade deletes the service's
  * joined endpoints and downtimes, leaving downtimes that are either reachable
  * from other services and any orphan downtimes that were never joined
  * to the service in the first place.
  */
    public function testServiceDAO_removeServiceAndJoinedDTs()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData4.php';

      // Remove Service
      // Impt: When deleting a service, we can't rely solely on the
      // 'onDelete=cascade' defined on the 'EndpointLocation->service'
      // to correctly cascade-delete the EL. This is because downtimes can also be linked
      // to the EL.  Therefore, if we don't invoke an $em->remove() on the EL
      // (either via cascade="remove" or manually invoking em->remove() on each EL),
      // Doctrine will not have flagged the EL as removed and so will not automatically delete the
      // relevant row(s) in 'DOWNTIMES_ENDPOINTLOCATIONS' join table.
      // This would cause a FK integrity/violation constraint exception
      // on the 'DOWNTIMES_ENDPOINTLOCATIONS.ENDPOINTLOCATION_ID' FK column.
      // This is why we need to do a managed delete using the ServiceDAO
        $serviceDao = new ServiceDAO();
        $serviceDao->setEntityManager($this->em);
        $serviceDao->removeService($service1);
        $this->em->flush();

      // use DB connection to check data has been deleted
        $con = $this->getConnection();
        $result = $con->query("SELECT * FROM EndpointLocations")->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $con->query("SELECT * FROM Downtimes")->fetchAll();
        // 3 DTs linked to service2 and orphanDT
        $this->assertTrue(count($result) == 4);
    }

  /**
  * Delete service2 and ensure the DAO cascade deletes the service's
  * joined endpoints and downtimes, leaving downtimes that are either reachable
  * from other services and any orphan downtimes that were never joined
  * to the service in the first place.
  */
    public function testServiceDAO_removeService2AndJoinedDTs()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData4.php';

        $serviceDao = new ServiceDAO();
        $serviceDao->setEntityManager($this->em);
        $serviceDao->removeService($service2);
        $this->em->flush();

      // use DB connection to check data has been deleted
        $con = $this->getConnection();
        $result = $con->query("SELECT * FROM EndpointLocations")->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $con->query("SELECT * FROM Downtimes")->fetchAll();
        // 6 DTs linked to service1 and orphanDT
        $this->assertTrue(count($result) == 7);
    }

  /**
  * Delete both services and enure the DAO cascade deletes both servcies
  * joined endpoints and downtimes, leaving only the sites and the single orphan downtime.
  */
    public function testServiceDAO_removeService1And2AndJoinedDTs()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData4.php';

        $serviceDao = new ServiceDAO();
        $serviceDao->setEntityManager($this->em);
        $serviceDao->removeService($service2);
        $serviceDao->removeService($service1);
        $this->em->flush();

      // use DB connection to check data has been deleted
        $con = $this->getConnection();
        $result = $con->query("SELECT * FROM EndpointLocations")->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $con->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 1); // orphanDT
        $result = $con->query("SELECT * FROM Sites")->fetchAll();
        $this->assertTrue(count($result) == 2); // site1 and site2
    }

  /**
  * Delete site1 and ensure only site2 and the orphan downtime remain.
  */
    public function testSiteService_removeSite()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData4.php';

        $adminUser = TestUtil::createSampleUser('some', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/some/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

        $siteService = new org\gocdb\services\Site();
        $siteService->setEntityManager($this->em);
        $siteService->deleteSite($site1, $adminUser, false);

      // use DB connection to check data has been deleted
        $con = $this->getConnection();
        $result = $con->query("SELECT * FROM EndpointLocations")->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $con->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 1); // orphanDT
        $result = $con->query("SELECT * FROM Sites")->fetchAll();
        $this->assertTrue(count($result) == 1); // site2
    }

  /**
  * Delete the parent NGI and ensure all sites, servcies, endponts and downtimes
  * are deleted leaving only the orphan dowmtime.
  */
    public function testNgiService_removeNgi()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData4.php';

        $adminUser = TestUtil::createSampleUser('some', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/some/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

        $ngiService = new org\gocdb\services\NGI();
        $ngiService->setEntityManager($this->em);
        $ngiService->deleteNgi($ngi, $adminUser, false);

      // use DB connection to check data has been deleted
        $con = $this->getConnection();
        $result = $con->query("SELECT * FROM EndpointLocations")->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $con->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 1); // orphanDT
        $result = $con->query("SELECT * FROM Sites")->fetchAll();
        $this->assertTrue(count($result) == 0); // site2
    }


  /**
  * Test the <code>$serviceDAO->removeEndpoint($endpoint)</code> method.
  */
    public function testServiceDAORemoveEndpoint()
    {
        print __METHOD__ . "\n";
      /* create a linked entity graph as beow:
      *
      *         se  -----|    (1 service)
      *       / | \      |
      *    el1 el2 el3   |    (3 endpoints)
      *     |  \ | /     |
      *    dt0  dt1  ----|    (1 downtime)
      */

        $dt1 = new Downtime();
        $dt1->setDescription('downtime description');
        $dt1->setSeverity("WARNING");
        $dt1->setClassification("UNSCHEDULED");

        $dt0 = new Downtime();
        $dt0->setDescription('downtime description');
        $dt0->setSeverity("WARNING");
        $dt0->setClassification("UNSCHEDULED");

        $se = TestUtil::createSampleService('service1');
        $el1 = TestUtil::createSampleEndpointLocation();
        $el2 = TestUtil::createSampleEndpointLocation();
        $el3 = TestUtil::createSampleEndpointLocation();
        $se->addEndpointLocationDoJoin($el1);
        $se->addEndpointLocationDoJoin($el2);
        $se->addEndpointLocationDoJoin($el3);
      //$se->_addDowntime($dt1); // we don't call this in client code !

        $dt1->addEndpointLocation($el1);
        $dt1->addEndpointLocation($el2);
        $dt1->addEndpointLocation($el3);
        $dt1->addService($se);

        $dt0->addEndpointLocation($el1);
      //$dt0->addService($se); // note we don't add this relationship for purposes of the test

      // persist and flush
        $this->em->persist($se);
        $this->em->persist($el1);
        $this->em->persist($el2);
        $this->em->persist($el3);
        $this->em->persist($dt1);
        $this->em->persist($dt0);
        $this->em->flush();

      // create DAO to test
        $serviceDao = new ServiceDAO();
        $serviceDao->setEntityManager($this->em);

      // remove first endpoint causing dt0 to be orphaned
        $serviceDao->removeEndpoint($el1);
        $this->em->flush();

      /* Assert expected object graph
      *       se   -----|   (1 service)
      *      |  \       |
      *     el2 el3     |   (2 endpoints)
      *       |  |      |
      *     dt0 dt1-----|   (2 downtime)
      */
        $testConn = $this->getConnection();
        $result = $testConn->query(
            "SELECT * FROM EndpointLocations"
        )->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $testConn->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $testConn->query(
            "SELECT * FROM Downtimes_EndpointLocations"
        )->fetchAll();
        $this->assertTrue(count($result) == 2);

       // Assert expected object graph in ORM Mem
        $this->assertEquals(1, count($el2->getDowntimes()));
        $this->assertEquals(1, count($el3->getDowntimes()));
        $this->assertEquals(2, count($se->getEndpointLocations()));
        $this->assertEquals(1, count($se->getDowntimes()));
        $this->assertEquals(2, count($dt1->getEndpointLocations()));
        $this->assertEquals(1, count($dt1->getServices()));

      // remove another el
        $serviceDao->removeEndpoint($el2);
        $this->em->flush();

      /* Assert expected object graph in DB
      *      se -----| (1 service)
      *        \     |
      *         el3  | (1 endpoints)
      *         /    |
      *  dt0 dt1-----| (2 downtime)
      */
        $testConn = $this->getConnection();
        $result = $testConn->query(
            "SELECT * FROM EndpointLocations"
        )->fetchAll();
        $this->assertTrue(count($result) == 1);
        $result = $testConn->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $testConn->query(
            "SELECT * FROM Downtimes_EndpointLocations"
        )->fetchAll();
        $this->assertTrue(count($result) == 1);

      // Assert expected object graph in ORM Mem
        $this->assertEquals(1, count($el3->getDowntimes()));
        $this->assertEquals(1, count($se->getEndpointLocations()));
        $this->assertEquals(1, count($se->getDowntimes()));
        $this->assertEquals(1, count($dt1->getEndpointLocations()));
        $this->assertEquals(1, count($dt1->getServices()));

      // remove another el
        $serviceDao->removeEndpoint($el3);
        $this->em->flush();

      /* Assert expected object graph in DB
      *      se -----| (1 service)
      *              |
      *              | (1 endpoints)
      *              |
      *  dt0 dt1-----| (2 downtime)
      */
        $testConn = $this->getConnection();
        $result = $testConn->query(
            "SELECT * FROM EndpointLocations"
        )->fetchAll();
        $this->assertTrue(count($result) == 0);
        $result = $testConn->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 2);
        $result = $testConn->query(
            "SELECT * FROM Downtimes_EndpointLocations"
        )->fetchAll();
        $this->assertTrue(count($result) == 0);

      // Assert expected object graph in ORM Mem
        $this->assertEquals(0, count($se->getEndpointLocations()));
        $this->assertEquals(1, count($se->getDowntimes()));
        $this->assertEquals(0, count($dt1->getEndpointLocations()));
        $this->assertEquals(1, count($dt1->getServices()));
    }
}
