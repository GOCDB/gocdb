<?php
require_once dirname(__FILE__) . '/TestUtil.php';
require_once dirname(__FILE__). '/../../lib/DAOs/ServiceDAO.php';
require_once dirname(__FILE__). '/../../lib/DAOs/SiteDAO.php';
require_once dirname(__FILE__). '/../../lib/DAOs/NGIDAO.php';

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
class RolesTest extends PHPUnit_Extensions_Database_TestCase {
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
        echo "Executing RolesTest. . .\n";
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
            $sql = "SELECT * FROM ".$tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            if($result->getRowCount() != 0)
                throw new RuntimeException("Invalid fixture. Table has rows: ".$tableName->getName());
        }
    }

    /**
     * Test Role's discriminator column
     * Add a role type, user, site and a role linking
     * them all together. Assert that $newRole->getOwnedEntity()
     * returns an instanceof Site.
     */
    public function testRoleDiscriminatorSite() {
        print __METHOD__ . "\n";
        // Create a roletype
        $rt = TestUtil::createSampleRoleType("ROLENAME");
        $this->em->persist($rt);

        // Create a user
        $u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
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
        if(!$dbRole->getOwnedEntity() instanceof Site) {
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
    public function testRoleDiscriminatorNGI() {
        print __METHOD__ . "\n";
        // Create a roletype
        $rt = TestUtil::createSampleRoleType("Name");
        $this->em->persist($rt);

        // Create a user
        $u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
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
        if(!$dbRole->getOwnedEntity() instanceof NGI) {
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
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testRoleTypeIntegrityConstraint() {
        print __METHOD__ . "\n";
        // Create a roletype
        $rt = TestUtil::createSampleRoleType("NAME");
        $this->em->persist($rt);

        // Create a user
        $u = TestUtil::createSampleUser("Test", "Testing", "/c=test");
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
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testDuplicateRoleTypes() {
        print __METHOD__ . "\n";
        // Should throw an expected exception because the role type Name value
        // must be unique
        $rt1 = TestUtil::createSampleRoleType("RoleName"/*, RoleTypeClass::SITE_USER*/);
        $rt2 = TestUtil::createSampleRoleType("RoleName"/*, RoleTypeClass::REGIONAL_USER*/);
        $this->em->persist($rt1);
        $this->em->persist($rt2);
        $this->em->flush();
    }




    public function testRoleConstants(){
        print __METHOD__ . "\n";

        $roleNames = RoleTypeName::getAsArray();
        $this->assertEquals(RoleTypeName::SITE_ADMIN, $roleNames['SITE_ADMIN'] );
        $this->assertEquals(RoleTypeName::COD_ADMIN, $roleNames['COD_ADMIN'] );

        $roleStatusVals = RoleStatus::getAsArray();
        $this->assertEquals(RoleStatus::GRANTED, $roleStatusVals['GRANTED']);
        $this->assertEquals(RoleStatus::PENDING, $roleStatusVals['PENDING']);
    }


}
?>
