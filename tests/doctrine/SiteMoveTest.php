<?php
//use org\gocdb\services\NGI;
//use org\gocdb\services\Site;
//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php';

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
class SiteMoveTest extends PHPUnit_Extensions_Database_TestCase {
   private $em;
   private $egiScope;
   private $localScope;
   private $eudatScope;


    /**
     * Overridden.
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing SiteMoveTest. . .\n";
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
    private function createEntityManager(){
        //require dirname(__FILE__).'/../lib/Doctrine/bootstrap.php';
        require dirname(__FILE__).'/bootstrap_doctrine.php';
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

        foreach($tables as $tableName) {
            //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM ".$tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            //echo 'row count: '.$result->getRowCount() ;
            if($result->getRowCount() != 0)
                throw new RuntimeException("Invalid fixture. Table has rows: ".$tableName->getName());
        }
    }

    /*
     * Inserts Two NGIs, three sites, and a service endpoint.
     * Checks the inserted data is there.
     *  Moves two of the sites between NGIs.
     *  Checks the sites are under the right NGIs.
     */
    public function testSiteMoves() {

        print __METHOD__ . "\n";

        //Insert initial data
        $N1 = TestUtil::createSampleNGI("NGI1");
        $N2 = TestUtil::createSampleNGI("NGI2");
        $S1 = TestUtil::createSampleSite("Site1");
        $S2 = TestUtil::createSampleSite("Site2");
        $S3 = TestUtil::createSampleSite("Site3");
        $SE1= TestUtil::createSampleService("SEP1");
        $dummy_user = TestUtil::createSampleUser('Test', 'User', '/Some/string');

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
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());

            $N2_ID = $N2->getId();
            $sql = "SELECT 1 FROM NGIs WHERE name = 'NGI2' AND ID = '$N2_ID'";
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());

            /*
             * Check each site is: present, has the right ID & parent NGI
             */
            $S1_id= $S1->getId();
            $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site1' AND ID = '$S1_id' AND NGI_ID = '$N1_ID'";
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());

            $S2_id= $S2->getId();
            $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site2' AND ID = '$S2_id' AND NGI_ID = '$N2_ID'";
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());

            $S3_id= $S3->getId();
            $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site3' AND ID = '$S3_id' AND NGI_ID = '$N2_ID'";
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());

            //Check the SEP has correct id and Site
            $sql = "SELECT 1 FROM Services WHERE hostname = 'SEP1' AND parentsite_id = '$S1_id'";
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());

        //Move sites
        $serv =  new org\gocdb\services\Site();
        $serv->setEntityManager($this->em);
        $serv->moveSite($S1,$N2, $dummy_user);
        $serv->moveSite($S2,$N1, $dummy_user);
        $serv->moveSite($S3,$N2, $dummy_user); //No change


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
                foreach ($ngisites as $site){
                    $this->assertEquals($S2, $site);
                }
                //NGI2
                $ngisites = $N2->getSites();
                foreach ($ngisites as $site){
                    $this->assertTrue(($site==$S1) or ($site==$S3));
                }

            //check Service End Point
            $this->assertEquals($S1,$SE1->getParentSite());


        //Use database connection to check movememrnt
            $con = $this->getConnection();

            //Check NGIs are still present and their ID is unchanged
            $sql = "SELECT 1 FROM NGIs WHERE name = 'NGI1' AND ID = '$N1_ID'";
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());

            $sql = "SELECT 1 FROM NGIs WHERE name = 'NGI2' AND ID = '$N2_ID'";
            $result = $con->createQueryTable('', $sql);
            $this->assertEquals(1, $result->getRowCount());


            //Check each NGI has the correct number of sites
                //NGI1
                $sql = "SELECT 1 FROM Sites WHERE NGI_ID = '$N1_ID'";
                $result = $con->createQueryTable('', $sql);
                $this->assertEquals(1, $result->getRowCount());

                //NGI2
                $sql = "SELECT 1 FROM Sites WHERE NGI_ID = '$N2_ID'";
                $result = $con->createQueryTable('', $sql);
                $this->assertEquals(2, $result->getRowCount());

            //check Site IDs are unchanged and they are assigned to the correct NGI
                //Site 1
                $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site1' AND ID = '$S1_id' AND NGI_ID = '$N2_ID'";
                $result = $con->createQueryTable('', $sql);
                $this->assertEquals(1, $result->getRowCount());

                //Site 2
                $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site2' AND ID = '$S2_id' AND NGI_ID = '$N1_ID'";
                $result = $con->createQueryTable('', $sql);
                $this->assertEquals(1, $result->getRowCount());

                //Site 3
                $sql = "SELECT 1 FROM Sites WHERE shortname = 'Site3' AND ID = '$S3_id' AND NGI_ID = '$N2_ID'";
                $result = $con->createQueryTable('', $sql);
                $this->assertEquals(1, $result->getRowCount());

    }//close function
}//close class

?>
