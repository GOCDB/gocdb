<?php

//use org\gocdb\services\NGI;
//use org\gocdb\services\Site;
//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php';

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/Site.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/NGI.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/ServiceService.php';

/**
 *
 *Test site ownership transfer between NGIs
 *
 * @author George Ryall
 * @author David Meredith
 * @author John Casson
 */
class SiteMoveTest extends \PHPUnit\Framework\TestCase
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
        echo "Executing SiteMoveTest. . .\n";
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

    /*
     * Inserts Two NGIs, three sites, and a service endpoint.
     * Checks the inserted data is there.
     *  Moves two of the sites between NGIs.
     *  Checks the sites are under the right NGIs.
     */
    public function testSiteMoves()
    {

        print __METHOD__ . "\n";

        //Insert initial data
        $N1 = TestUtil::createSampleNGI("NGI1");
        $N2 = TestUtil::createSampleNGI("NGI2");
        $S1 = TestUtil::createSampleSite("Site1");
        $S2 = TestUtil::createSampleSite("Site2");
        $S3 = TestUtil::createSampleSite("Site3");
        $SE1 = TestUtil::createSampleService("SEP1");
        $dummy_user = TestUtil::createSampleUser('Test', 'User');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/Some/string');
        $dummy_user->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);

        //Make dummy user a GOCDB admin so it can perfirm site moves etc.
        $dummy_user->setAdmin(true);

        /*
         * Current code in TestUtil does not set sites up as being owned by an an NGI by default.
         *Add them to NGI
         */
        $N1->addSiteDoJoin($S1);
        $N2->addSiteDoJoin($S2);
        $N2->addSiteDoJoin($S3);

        //Add service end point to service 1
        $S1->addServiceDoJoin($SE1);

        //Persist initial data
        $this->em->persist($N1);
        $this->em->persist($N2);
        $this->em->persist($S1);
        $this->em->persist($S2);
        $this->em->persist($S3);
        $this->em->persist($SE1);
        $this->em->persist($dummy_user);
        $this->em->flush();

        //Use DB connection to check data
        $con = $this->getConnection();

            /*
             * Check both that each NGI is present and that the ID matches the doctrine one
             */
            $N1_ID = $N1->getId();
            $sql = "SELECT 1 FROM NGIs WHERE name = 'NGI1' AND ID = '$N1_ID'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));

            $N2_ID = $N2->getId();
            $sql = "SELECT 1 FROM NGIs WHERE name = 'NGI2' AND ID = '$N2_ID'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));

            /*
             * Check each site is: present, has the right ID & parent NGI
             */
            $S1_id = $S1->getId();
            $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site1' AND ID = '$S1_id' AND NGI_ID = '$N1_ID'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));

            $S2_id = $S2->getId();
            $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site2' AND ID = '$S2_id' AND NGI_ID = '$N2_ID'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));

            $S3_id = $S3->getId();
            $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site3' AND ID = '$S3_id' AND NGI_ID = '$N2_ID'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));

            //Check the SEP has correct id and Site
            $sql = "SELECT 1 FROM Services WHERE hostname = 'SEP1' AND parentsite_id = '$S1_id'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));

        //Move sites
        $serv =  new org\gocdb\services\Site();
        $serv->setEntityManager($this->em);
        $serv->moveSite($S1, $N2, $dummy_user);
        $serv->moveSite($S2, $N1, $dummy_user);
        $serv->moveSite($S3, $N2, $dummy_user); //No change


        //flush movement
        $this->em->flush();

        //Use doctrine to check movement
            //Check correct NGI for each site
            $this->assertEquals($N2, $S1->getNgi());
            $this->assertEquals($N1, $S2->getNgi());
            $this->assertEquals($N2, $S3->getNgi());

            //Check correct sites for each NGI
                //NGI1
                $ngisites = $N1->getSites();
        foreach ($ngisites as $site) {
            $this->assertEquals($S2, $site);
        }
                //NGI2
                $ngisites = $N2->getSites();
        foreach ($ngisites as $site) {
            $this->assertTrue(($site == $S1) or ($site == $S3));
        }

            //check Service End Point
            $this->assertEquals($S1, $SE1->getParentSite());


        //Use database connection to check movememrnt
            $con = $this->getConnection();

            //Check NGIs are still present and their ID is unchanged
            $sql = "SELECT 1 FROM NGIs WHERE name = 'NGI1' AND ID = '$N1_ID'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));

            $sql = "SELECT 1 FROM NGIs WHERE name = 'NGI2' AND ID = '$N2_ID'";
            $result = $con->query($sql)->fetchAll();
            $this->assertEquals(1, count($result));


            //Check each NGI has the correct number of sites
                //NGI1
                $sql = "SELECT 1 FROM Sites WHERE NGI_ID = '$N1_ID'";
                $result = $con->query($sql)->fetchAll();
                $this->assertEquals(1, count($result));

                //NGI2
                $sql = "SELECT 1 FROM Sites WHERE NGI_ID = '$N2_ID'";
                $result = $con->query($sql)->fetchAll();
                $this->assertEquals(2, count($result));

            //check Site IDs are unchanged and they are assigned to the correct NGI
                //Site 1
                $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site1' AND ID = '$S1_id' AND NGI_ID = '$N2_ID'";
                $result = $con->query($sql)->fetchAll();
                $this->assertEquals(1, count($result));

                //Site 2
                $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site2' AND ID = '$S2_id' AND NGI_ID = '$N1_ID'";
                $result = $con->query($sql)->fetchAll();
                $this->assertEquals(1, count($result));

                //Site 3
                $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site3' AND ID = '$S3_id' AND NGI_ID = '$N2_ID'";
                $result = $con->query($sql)->fetchAll();
                $this->assertEquals(1, count($result));
    }//close function
}//close class
