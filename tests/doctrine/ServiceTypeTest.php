<?php

namespace org\gocdb\tests;

//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';

require_once dirname(__FILE__) . '/TestUtil.php';

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use RuntimeException;
use TestUtil; // Extensive changes needed to put TestUtil in org\gocdb\tests;

require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * A template that includes all the setup and tear down functions for writting
 * a PHPUnit test to test doctrine.
 *
 * @author David Meredith
 */
class ServiceTypeTest extends \PHPUnit\Framework\TestCase
{
    private $em;

     /**
     * Overridden.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing MonitorExceptionTest. . .\n";
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
     * @todo Still need to setup connection to different databases.
     * @return EntityManager
     */
    private function createEntityManager()
    {
        $entityManager = null;
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
     * Any function with test at the start of the name will execute with PHPUnit
     */
    public function testServiceType()
    {
        print __METHOD__ . "\n";

        /**
         * Check some logic related to monitoring exceptions in ServiceType entity
         */

        $type1 = TestUtil::createSampleServiceType(
            'this is a test serviceType',
            'type1'
        );

        // Default assumed to disallow monitoring exception
        $this->assertEquals($type1->getAllowMonitoringException(), 0);
        // Set to true and check return is the current state
        $this->assertEquals($type1->setAllowMonitoringException(1), 0);
        // Check that it's changed
        $this->assertEquals($type1->getAllowMonitoringException(), 1);
    }
}
