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
 * Test the CertStatusLog cascade delete functionality.
 *
 * This test case truncates the test database (a clean insert with no seed data)
 * and performs subsequent CRUD operations using Doctrine ORM.
 * Usage:
 * Run the recreate.sh to create the sample database first (create tables etc), then run:
 * '$phpunit TestSite_CertStatusLogCascadeDeletions.php'
 *
 * @author David Meredith
 */
class Site_CertStatusLogCascadeDeletionsTest extends \PHPUnit\Framework\TestCase
{
    private $em;

    //private $egiScope;
    //private $localScope;
    //private $eudatScope;

    /**
     * Overridden.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing Site_CertStatusLogCascadeDeletionsTest. . .\n";
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


    public function testCertStatusLogDeleted_OnSiteDeletion()
    {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData1.php';

        // delete site2 - the certStatusLogs
        $siteDAO = new SiteDAO();
        $siteDAO->setEntityManager($this->em);
        $siteDAO->removeSite($site2);

        $this->em->flush();

        // Need to clear the identity map (all objects become detached) so that
        // when we re-fetch the user, it will be looked from db not served by entity map
        $this->em->clear();

        $result = $testConn->query("SELECT * FROM Sites")->fetchAll();
        $this->assertTrue(count($result) == 1); // site1 not deleted
        $result = $testConn->query("SELECT * FROM CertificationStatusLogs")->fetchAll();
        $this->assertTrue(count($result) == 0);
    }
}
