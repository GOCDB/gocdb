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
class ServiceDAOTest extends PHPUnit_Extensions_Database_TestCase {
  private $em;

  /**
  * Overridden.
  */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    echo "\n\n-------------------------------------------------\n";
    echo "Executing ServiceDAOTest. . .\n";
  }

  /**
  * Overridden. Returns the test database connection.
  * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
  */
  protected function getConnection() {
    require_once dirname(__FILE__) . '/bootstrap_pdo.php';
    return getConnectionToTestDB();
  }

  /**
  * Overridden. Returns the test dataset.
  * Defines how the initial state of the database should look before each test is executed.
  * @return PHPUnit_Extensions_Database_DataSet_IDataSet
  */
  protected function getDataSet() {
    return $this->createFlatXMLDataSet(dirname(__FILE__) . '/truncateDataTables.xml');
    // Use below to return an empty data set if we don't want to truncate and seed
    //return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
  }

  /**
  * Overridden.
  */
  protected function getSetUpOperation() {
    // CLEAN_INSERT is default
    //return PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT();
    //return PHPUnit_Extensions_Database_Operation_Factory::UPDATE();
    //return PHPUnit_Extensions_Database_Operation_Factory::NONE();
    //
    // Issue a DELETE from <table> which is more portable than a
    // TRUNCATE table <table> (some DBs require high privileges for truncate statements
    // and also do not allow truncates across tables with FK contstraints e.g. Oracle)
    return PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL();
  }

  /**
  * Overridden.
  */
  protected function getTearDownOperation() {
    // NONE is default
    return PHPUnit_Extensions_Database_Operation_Factory::NONE();
  }

  /**
  * Sets up the fixture, e.g create a new entityManager for each test run
  * This method is called before each test method is executed.
  */
  protected function setUp() {
    parent::setUp();
    $this->em = $this->createEntityManager();
  }

  /**
  * @todo Still need to setup connection to different databases.
  * @return EntityManager
  */
  private function createEntityManager() {
    //require dirname(__FILE__).'/../lib/Doctrine/bootstrap.php';
    require dirname(__FILE__) . '/bootstrap_doctrine.php';
    return $entityManager;
  }

  /**
  * Called after setUp() and before each test. Used for common assertions
  * across all tests.
  */
  protected function assertPreConditions() {
    $con = $this->getConnection();
    $fixture = dirname(__FILE__) . '/truncateDataTables.xml';
    $tables = simplexml_load_file($fixture);

    foreach ($tables as $tableName) {
      //print $tableName->getName() . "\n";
      $sql = "SELECT * FROM " . $tableName->getName();
      $result = $con->createQueryTable('results_table', $sql);
      //echo 'row count: '.$result->getRowCount() ;
      if ($result->getRowCount() != 0)
        throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
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
  public function testServiceDAO_removeService() {
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
    $result = $con->createQueryTable('results_table', "SELECT * FROM EndpointLocations");
    $this->assertTrue($result->getRowCount() == 0);
    $result = $con->createQueryTable('results_table', "SELECT * FROM Downtimes");
    $this->assertTrue($result->getRowCount() == 0);
  }

  public function testNgiService_removeNgi() {
    print __METHOD__ . "\n";
    include __DIR__ . '/resources/sampleFixtureData1.php';

    $adminUser = TestUtil::createSampleUser('some', 'admin', '/some/admin');
    $adminUser->setAdmin(TRUE);
    $this->em->persist($adminUser);

    $ngiService = new org\gocdb\services\NGI();
    $ngiService->setEntityManager($this->em);
    $ngiService->deleteNgi($ngi, $adminUser, FALSE);
  }
}

?>
