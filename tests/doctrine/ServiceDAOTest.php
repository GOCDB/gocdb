<?php

require_once dirname(__FILE__) . '/TestUtil.php';
use Doctrine\ORM\EntityManager;
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/Site.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/NGI.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/ServiceService.php';
require_once dirname(__FILE__) . '/../../lib/DAOs/ServiceDAO.php';

/**
 * Test the ServiceDAO, in particular the cascade delete behaviour between
 * Service, EndpointLocation and Downtime.
 *
 * @author David Meredith
 */
class ServiceDAOTest extends \PHPUnit\Framework\TestCase
{
    private $em;

  /**
  * Overridden.
  */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing ServiceDAOTest. . .\n";
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
      //require dirname(__FILE__).'/../lib/Doctrine/bootstrap.php';
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
  * Test the ServiceDAO->removeService();
  * Impt: A cascade=remove is configured between Service and EndpointLocation
  * so that when a Service is removed, its associated ELs are also removed.
  * <p>
  * Note, no cascade remove behaviour is configured between EndpointLocation and
  * Downtime because we need to have fine-grained programmatic control over
  * which downtimes are deleted when a service EL is deleted (i.e. we only
  * want to delete those DTs that exclusively link to one EL only and which
  * would subsequently be orphaned). We do this managed deletion of DTs in ServiceDAO->removeService();
  */
    public function testServiceDAO_removeService()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

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
        $this->assertTrue(count($result) == 0);
        $result = $con->query("SELECT * FROM Downtimes")->fetchAll();
        $this->assertTrue(count($result) == 0);
    }

    public function testNgiService_removeNgi()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

        $adminUser = TestUtil::createSampleUser('some', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/some/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

        $ngiService = new org\gocdb\services\NGI();
        $ngiService->setEntityManager($this->em);
        $ngiService->deleteNgi($ngi, $adminUser, false);
    }
}
