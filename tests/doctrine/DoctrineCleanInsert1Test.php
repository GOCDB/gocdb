<?php

require_once dirname(__FILE__) . '/TestUtil.php';
require_once dirname(__FILE__) . "/bootstrap.php";
use Doctrine\ORM\EntityManager;

/**
 * This test case truncates the test database (a clean insert with no seed data)
 * and performs subsequent CRUD operations using Doctrine ORM.
 * Usage:
 * Run the recreate.sh to create the sample database first (create tables etc), then run:
 * '$phpunit TestDoctrineCleanInsert1.php'
 * <p>
 * Note, this class shows the correct usage of the Doctrine ORM under certain
 * conditions rather than testing the overlying GOCDB DAO and Service layers.
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author Johnn Casson <john.casson@stfc.ac.uk>
 */
class DoctrineCleanInsert1Test extends PHPUnit_Extensions_Database_TestCase
{
    private $em;
    private $egiScope;
    private $localScope;
    private $eudatScope;

  /**
   * Overridden.
   */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        echo "\n\n-------------------------------------------------\n";
        echo "Executing DoctrineCleanInsert1Test. . .\n";
    }

  /**
   * Overridden. Returns the test database connection.
   * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
   */
    protected function getConnection()
    {
        require_once dirname(__FILE__) . '/bootstrap_pdo.php';
        return getConnectionToTestDB();
    }

  /**
   * Overridden. Returns the test dataset.
   * Defines how the initial state of the database should look before each test is executed.
   * @return PHPUnit_Extensions_Database_DataSet_IDataSet
   */
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__) . '/truncateDataTables.xml');
      // Use below to return an empty data set if we don't want to truncate and seed
      //return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }

  /**
   * Overridden.
   */
    protected function getSetUpOperation()
    {
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
    protected function getTearDownOperation()
    {
      // NONE is default
        return PHPUnit_Extensions_Database_Operation_Factory::NONE();
    }

  /**
   * Sets up the fixture, e.g create a new entityManager for each test run
   * This method is called before each test method is executed.
   */
    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->createEntityManager();
    }

  /**
   * Run after each test function to prevent pile-up of database connections.
   */
    protected function tearDown()
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
      //require dirname(__FILE__).'/../bootstrap.php';
        require dirname(__FILE__) . '/bootstrap_doctrine.php';
        return $entityManager;
    }

  /**
   * Called after setUp() and before each test. Used for common assertions
   * across all tests.
   */
    protected function assertPreConditions()
    {
        $con = $this->getConnection();
        $fixture = dirname(__FILE__) . '/truncateDataTables.xml';
        $tables = simplexml_load_file($fixture);

        foreach ($tables as $tableName) {
          //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
          //echo 'row count: '.$result->getRowCount() ;
            if ($result->getRowCount() != 0) {
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
            }
        }
    }

    public function testAssertTestEntityMangersAreDifferent()
    {
        print __METHOD__ . "\n";
        $em1 = $this->createEntityManager();
        $em2 = $this->createEntityManager();
      // Below asserts that two variables do not have the same type and value.
      // Used on objects, it asserts that two variables do not reference the same object.
        $this->assertNotSame($em1, $em2);
        if ($em1 === $em2) {
            $this->fail();
        }
    }


    public function testJoinServicesToSite_RefetchSiteLazyLoadSEs()
    {
        print __METHOD__ . "\n";
      // create a sinlge site and add 3 service endpoints
        $n = 3;
        $site = TestUtil::createSampleSite('site' . $n);
        $this->em->persist($site);
        for ($i = 0; $i < $n; $i++) {
            $se = TestUtil::createSampleService("serv" . $i);
            $site->addServiceDoJoin($se);
            $se->setParentSiteDoJoin($site);
            $this->em->persist($se);
        }
        $seCount = count($site->getServices());
        $this->assertTrue($seCount == $n);
        $this->em->flush();
        $this->em->clear();
        $this->em->getConnection()->close();
        $this->em->close();

        $em2 = $this->createEntityManager();
        $refetchedSite = $em2->getRepository('Site')->findOneBy(array('shortName' => 'site' . $n));
        $this->assertNotNull($refetchedSite);
        $em2->clear();
        $em2->close();
      // close causes all managed entities ($refetchedSite) to become detached
      // and the em2 can't be used again.

                                            /** Why the following works **/
      /* When doctrine fetches an entity its default is to lazy load related entities - it does
       * not directly hydrate them it creates a proxy. Although the entity manager is closed the
       * code below is able to get the service count, name and parent site. This is because when
       * doctrine creates a proxy the proxy also contains an instance of the EntityManager and it's
       * dependancies which can then be used when the proxy is called to hydrate the entity and provide
       * the required data.
       */


        $seList = $refetchedSite->getServices();

        $this->assertCount(3, $seList);
    }


  /**
   * @link http://stackoverflow.com/questions/7877987/read-objects-persisted-but-not-yet-flushed-with-doctrine issue with doctrine
   */
    public function testShowDoctrineReadFailiureUntilCommitted()
    {
        print __METHOD__ . "\n";
        $n = 1;
        // create a new site and persist (but do not flush yet)
        $site = TestUtil::createSampleSite('site' . $n/*, 'pk' . $n*/);
        $this->em->beginTransaction();
        $this->em->persist($site);
        // After persist, Doctrine querying does not return our site until we flush, hence we get 0
        // returned from our repository.
        $this->assertEquals(0, count($this->em->getRepository('Site')->findBy(array('shortName' => 'site' . $n))));
        // When we flush or commit we get one.
        $this->em->commit();
        $this->em->flush();
        $this->assertEquals(1, count($this->em->getRepository('Site')->findBy(array('shortName' => 'site' . $n))));
    }


  /**
   * Show how BiDirectional relationships must be correctly managed in
   * userland/application code. (See Doctrine the tutorial:
   * "Consistency of bi-directional references on the inverse side of a
   * relation have to be managed in userland application code. Doctrine
   * cannot magically update your collections to be consistent.").
   */
    public function testShowBiDirectionalJoinConsitencyManagement()
    {
        print __METHOD__ . "\n";
        $ngi = TestUtil::createSampleNGI('myngi');
        $site = TestUtil::createSampleSite('mysite'/*, 'pk1'*/);

      // Important:
      // The inverse side of a bi-directional relationship MUST be correctly managed.
      // If  $ngi->addSiteDoJoin($site) is not called (try commenting out next line),
      // the commented out assertion on next line would fail as $ngi->getSites()
      // will return 0 when running in same TX.
      // "$siteCount = $ngi->getSites(); $this->assertTrue($siteCount == 1);"
        $ngi->addSiteDoJoin($site);
      //$site->setNgiDoJoin($ngi);  // optional, calling this does no harm.

      // Important:
      // Similarly, two invocations of "$ngi->addSiteDoJoin($site)" in the same TX
      // will add two sites to NGI causing getSites() to return 2 !
      // (looks like Doctrine's ArrayCollection can
      // only distinguish object instances based on obj FK ID).

        $this->em->persist($site); // is now managed
        $this->em->persist($ngi);  // is now managed
        $siteCount = count($ngi->getSites());
        $this->assertTrue($siteCount == 1);
        $this->em->flush();

      // start new TX
        $this->em = $this->createEntityManager();
        $this->assertTrue(
            count($this->em->getRepository('NGI')->findOneBy(array('name' => 'myngi'))->getSites()) == 1
        );

        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM NGIs");
        $this->assertTrue($result->getRowCount() == 1);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
        $this->assertTrue($result->getRowCount() == 1);

      // Assert that the FK joins worked as expected.
        $result = $testConn->createQueryTable(
            'results_table',
            "SELECT * FROM NGIs inner join Sites on NGIs.id = Sites.ngi_id"
        );
        $this->assertTrue($result->getRowCount() == 1);
    }

    public function testJoinServicesToSite()
    {
        print __METHOD__ . "\n";

        $n = 3;
        $site = TestUtil::createSampleSite('site' . $n/*, 'pk' . $n*/);
        $this->em->persist($site);
        for ($i = 0; $i < $n; $i++) {
            $se = TestUtil::createSampleService("serv" . $i);
            $site->addServiceDoJoin($se);
          // below is ok but not striclty required.
            $se->setParentSiteDoJoin($site);
            $this->em->persist($se);
        }
        $seCount = count($site->getServices());
      //print 'debug '.$seCount."\n";
        $this->assertTrue($seCount == $n);
        $this->em->flush();

      // with same connection
        $this->assertTrue(
            count($this->em->getRepository('Site')->findOneBy(array('shortName' => 'site' . $n))->getServices()) == $n
        );

      // start new connection
        $this->em->getConnection()->close();
        $this->em->close();
        $this->em = $this->createEntityManager();
        $this->assertTrue(
            count($this->em->getRepository('Site')->findOneBy(array('shortName' => 'site' . $n))->getServices()) == $n
        );
    }

    public function testJoinCertStatusLogToSite()
    {
        print __METHOD__ . "\n";
        $n = 3;
        $site = TestUtil::createSampleSite('mysite1');
        $this->em->persist($site);
        for ($i = 0; $i < $n; $i++) {
            $certLog = new \CertificationStatusLog();
            $certLog->setAddedBy("/some/dn_$i");
            $this->em->persist($certLog);
            $site->addCertificationStatusLog($certLog);
        }
        $this->em->merge($site);
        $logCount = count($site->getCertificationStatusLog());
        $this->assertTrue($logCount == $n);
        $this->em->flush();

       // start new connection
        $this->em->getConnection()->close();
        $this->em->close();
        $this->em = $this->createEntityManager();

       // fetch the site and check that the logs are present
        $siteRefetched = $this->em->getRepository('Site')->findOneBy(array('shortName' => 'mysite1'));
        $this->assertTrue(count($siteRefetched->getCertificationStatusLog()) == $n);

       // check that the cascade delete removes all the certStatusLogs too
        $this->em->remove($siteRefetched);
        $this->em->flush();

        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable(
            'results_table',
            "SELECT CertificationStatusLogs.id FROM CertificationStatusLogs"
        );
        $this->assertTrue($result->getRowCount() == 0);

        // check deletion of cert log don't delete site
    }


  /**
   * Ensure that a Site may have many scope associations
   */
    public function testSiteScopeMultiplicity()
    {
        print __METHOD__ . "\n";
        $this->createAndPersistScopes();
        $n = 1;
        $this->em->beginTransaction();
        $site = TestUtil::createSampleSite('site' . $n/*, 'pk' . $n*/);
        $site->addScope($this->localScope);
        $site->addScope($this->egiScope);
        $site->addScope($this->eudatScope);
        $this->em->persist($site);
        $this->em->commit();
        $this->em->flush();
    }

  /**
   * Ensure that a SE may have many scope associations
   */
    public function testSEScopeMultiplicity()
    {
        print __METHOD__ . "\n";
        $this->createAndPersistScopes();
        $this->em->beginTransaction();
        $se = TestUtil::createSampleService("serv");
        $se->addScope($this->localScope);
        $se->addScope($this->egiScope);
        $se->addScope($this->eudatScope);
        $this->em->persist($se);
        $this->em->commit();
        $this->em->flush();
    }

    public function testJoinSiteToScopes()
    {
        print __METHOD__ . "\n";
        $this->createAndPersistScopes();
        $n = 1; // TODO - change this to 3 and un-comment the add egiScope and eudatScope lines below
        $site = TestUtil::createSampleSite('site' . $n/*, 'pk' . $n*/);
        $site->addScope($this->localScope);
      //$site->addScope($this->egiScope);
      //$site->addScope($this->eudatScope);

        $this->em->persist($site);
        $this->em->flush();
        $this->assertTrue(count($site->getScopes()) == $n);

        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable(
            'results_table',
            "SELECT Sites.id FROM Sites
       inner join Sites_Scopes
       on Sites.id = Sites_Scopes.site_id
       inner join Scopes
       on Scopes.id = Sites_Scopes.scope_id"
        );
        $this->assertTrue($result->getRowCount() == $n);
    }


    public function testJoinServiceToScopes()
    {
        print __METHOD__ . "\n";
        $n = 1; // TODO - change this to 3 and un-comment the add egiScope and eudatScope lines below
        $this->createAndPersistScopes();
        $se = TestUtil::createSampleService("serv");
        $se->addScope($this->egiScope);
      //$se->addScope($this->localScope);
      //$se->addScope($this->eudatScope);
        $this->assertTrue(count($se->getScopes()) == $n);
        $this->em->persist($se);
        $this->em->flush();
        $this->assertTrue(count($se->getScopes()) == $n);

        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable(
            'results_table',
            "SELECT Services.id FROM Services
       inner join Services_Scopes
       on Services.id = Services_Scopes.service_id
       inner join Scopes
       on Scopes.id = Services_Scopes.scope_id"
        );
        $this->assertTrue($result->getRowCount() == $n);
    }

  /**
   * Add multiple endpointLocations to a single Service
   */
    public function testJoinServiceToManyEndpointLocations()
    {
        print __METHOD__ . "\n";
        $n = 3; // we will want to be able to add many endpointLocations to a service
        $se = TestUtil::createSampleService("myservicehostname");
        $this->em->persist($se);
        for ($i = 0; $i < $n; $i++) {
            $el = TestUtil::createSampleEndpointLocation();
            $se->addEndpointLocationDoJoin($el);
          // below is ok but not striclty required.
          //$el->setServiceDoJoin($se);
            $this->em->persist($el);
        }
        $elCount = count($se->getEndpointLocations());
        $this->assertTrue($elCount == $n);
      // flush is the line that throws the expected exe
        $this->em->flush();

      // in same TX
        $this->assertTrue(
            count($this->em->getRepository('Service')->findOneBy(array('hostName' => 'myservicehostname'))->getEndpointLocations()) == $n
        );

      // start new TX
        $this->em->getConnection()->close();
        $this->em->close();
        $this->em = $this->createEntityManager();
        $this->assertTrue(
            count($this->em->getRepository('Service')->findOneBy(array('hostName' => 'myservicehostname'))->getEndpointLocations()) == $n
        );
    }

  /**
   * Test that rollback works as expected by creating a site, adding services,
   * persisting then calling rollback (note am not flushing or commiting) and
   * checking that there are no sites and servcies in the DB.
   */
    public function testRollback()
    {
        print __METHOD__ . "\n";
        $n = 3;
        $site = TestUtil::createSampleSite('site' . $n/*, 'pk' . $n*/);
      // need to start new TX to do a later rollback.
        $this->em->beginTransaction();
        $this->em->persist($site); // is now managed
        for ($i = 0; $i < $n; $i++) {
            $se = TestUtil::createSampleService("serv" . $i);
            $site->addServiceDoJoin($se);
          // below is ok but not striclty required.
            $se->setParentSiteDoJoin($site);
            $this->em->persist($se);  // is now managed
        }
        $seCount = count($site->getServices());
        $this->assertTrue($seCount == $n);
        $this->em->rollback();

        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
        $this->assertTrue($result->getRowCount() == 0);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Services");
        $this->assertTrue($result->getRowCount() == 0);
    }


  /**
   * Tests asserts that we shouldn't be able to delete a site with child services
   * because we do not use a cascade-delete rule when deleting the site. We
   * first have to delete the services that holds the FK to the site (one site
   * to many services).
   *
   * @expectedException \Doctrine\DBAL\Exception
   */
    public function testExpectedFK_ViolationOnSiteDeleteWithoutCascade()
    {
        print __METHOD__ . "\n";
        $n = 1;
        $site = TestUtil::createSampleSite('site' . $n/*, 'pk' . $n*/);
        $this->em->persist($site); // is now managed
        for ($i = 0; $i < $n; $i++) {
            $se = TestUtil::createSampleService("serv" . $i);
            $site->addServiceDoJoin($se);
          // below is ok but not striclty required.
            $se->setParentSiteDoJoin($site);
            $this->em->persist($se);  // is now managed
        }
        $this->em->flush();

      // start new TX
        $this->em->getConnection()->close();
        $this->em->close();
        $this->em = $this->createEntityManager();
        $refetchedSite = $this->em->getRepository('Site')->findOneBy(array('shortName' => 'site' . $n));
        $this->assertTrue($refetchedSite->getShortName() == 'site' . $n);

      // Now try to delete site.
      // We expect a FK violation wrapped as a DBALException.
      // If the fail statement is called below, then it could be an issue with
      // with the DB. For example, Sqlite with Doctrine does not enforce FK constraints !
      // see: http://stackoverflow.com/a/4599894

      // We shouldn't be able to remove the site as we need to remove the
      // services first as we have no cascade-delete option set.
        $this->em->remove($refetchedSite);
        $this->em->flush();
        $this->fail(
            'Should not get to this point - \Doctrine\DBAL\Exception expected'
        );
    }


  /**
   * Assert that deletion of an OwnedEntity superclass will delete its joined
   * extending class (e.g. Site and NGI).
   */
    public function testCascadeDeleteFromOwnedEntity()
    {
        print __METHOD__ . "\n";
      // create and commit a new site
        $s = TestUtil::createSampleSite("SITENAME"/*, "PK01"*/);
        $this->em->persist($s);
        $this->em->flush();

      // lookup the site's super class (OwnedEntity)
        $siteID = $s->getId();
        $ownedEntity = $this->em->find("OwnedEntity", $siteID);
        $this->assertNotNull($ownedEntity);
        $this->assertTrue($ownedEntity instanceof OwnedEntity);
        $this->assertEquals($siteID, $ownedEntity->getId());

      // remove the super class - this should cascade delete the site
        $this->em->remove($ownedEntity);
        $this->em->flush();

      // Assert that the super class was deleted and site was cascade deleted
      // first using doctrine
        $siteAgain = $this->em->find("Site", $siteID);
        $this->assertNull($siteAgain);
        $ownedEntityAgain = $this->em->find("OwnedEntity", $siteID);
        $this->assertNull($ownedEntityAgain);
      // second using separate db connection
        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM OwnedEntities");
        $this->assertTrue($result->getRowCount() == 0);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
        $this->assertTrue($result->getRowCount() == 0);
    }


  /**
   * @expectedException \Doctrine\ORM\ORMInvalidArgumentException
   */
    public function testShowMergeIsRequiredBetweenDifferentPersistenceCtxt()
    {
        print __METHOD__ . "\n";
      // User
        $u = TestUtil::createSampleUser("Test", "Testing");
        $identifier = TestUtil::createSampleUserIdentifier("X.509", "/c=test");
        $u->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);

        $regFLSupportRT = TestUtil::createSampleRoleType(RoleTypeName::REG_FIRST_LINE_SUPPORT/*, RoleTypeClass::REGIONAL_USER*/);
        $this->em->persist($u);
        $this->em->persist($regFLSupportRT);
        $this->em->flush();

      // If we create a new $this->em as below, we would need to merge detatched $u
      // and $regFLSupportRT entities back into this persistence context
      // before we can call a persist again (a persist on these entities
      // called either by a CASCADE or direct call)!
        $this->em = $this->createEntityManager(); // simply requires bootstrap_doctrine.php
      //$u = $this->em->merge($u);
      //$regFLSupportRT = $this->em->merge($regFLSupportRT);

      // Create new NGI
        $n = TestUtil::createSampleNGI("MYNGI");
        $this->em->persist($n);

        $roleNgi = TestUtil::createSampleRole($u, $regFLSupportRT, $n, RoleStatus::GRANTED);
        $this->em->persist($roleNgi);
      // the flush below is what causes the expected exception
        $this->em->flush();
    }

    public function testSiteV4PK()
    {
        print __METHOD__ . "\n";
        $this->em->getConnection()->beginTransaction();
        $pk = new \PrimaryKey();
        $this->em->persist($pk);
      // Below line is interesting - Oracle returns a value for $pk->getId()
      // (i.e. before flush) while MySQL does not return a value until the
      // flush. Either way, the subsequent rollback causes the PK to be removed
      // which is the required behaviour.
      //$this->assertEmpty($pk->getId());
      //print "the pk is before: [".$pk->getId()."]\n";

        $this->em->flush(); // sync in-memory state with db so that the pk Id has a value
      //print "the pk is after: [".$pk->getId()."]\n";
      // We expect after the flush the pk to have an id value
        $this->assertNotEmpty($pk->getId());
        $site = new \Site();
        $site->setPrimaryKey($pk->getId() . "G0");
        $site->setShortName('testshortname');
        $this->em->persist($site);
        $this->em->flush();

      // Rollback so that we don't commit the new pks and site to the DB
        $this->em->getConnection()->rollback();

        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
        $this->assertTrue($result->getRowCount() == 0);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM PrimaryKeys");
        $this->assertTrue($result->getRowCount() == 0);
    }

  /**
   * Assert that the onDelete="CASCADE" FK mapping defined on 'EndpointLocation.service'
   * attribute does actually cascade delete a services endpoints when those
   * endpoints don't have any other joined associations, e.g. with Downtimes.
   */
    public function testServiceToEndpointCascadeDelete_WithNoDTs()
    {
        print __METHOD__ . "\n";
      // create a linked entity graph as below:
      //
      //     se        (1 service)
      //    / | \
      // el1 el2 el3   (3 endpoints)

        $se = TestUtil::createSampleService('service1');
        $el1 = TestUtil::createSampleEndpointLocation();
        $el2 = TestUtil::createSampleEndpointLocation();
        $el3 = TestUtil::createSampleEndpointLocation();
        $se->addEndpointLocationDoJoin($el1);
        $se->addEndpointLocationDoJoin($el2);
        $se->addEndpointLocationDoJoin($el3);

      // persist and flush
        $this->em->persist($se);
        $this->em->persist($el1);
        $this->em->persist($el2);
        $this->em->persist($el3);
        $this->em->flush();

      // Delete the service, and assert the endpoints are cascade deleted too !
      // Impt: This should work because we have NOT joined any downtimes to the
      // endpoints and so the cascade is free to work at the DB level using
      // the onDelete="CASCADE" FK mapping defined on 'EndpointLocation.service' attribute.
      // see: See: http://docs.doctrine-project.org/en/2.0.x/reference/working-with-objects.html#removing-entities
      //
        $this->em->remove($se);
        $this->em->flush();

      // Assert that there are still three EndpointLocations and one Downtimes in the database
        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM EndpointLocations");
        $this->assertTrue($result->getRowCount() == 0);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Downtimes");
        $this->assertTrue($result->getRowCount() == 0);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Services");
        $this->assertTrue($result->getRowCount() == 0);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Downtimes_EndpointLocations");
        $this->assertTrue($result->getRowCount() == 0);
    }

  /**
   * Assert that the onDelete="CASCADE" FK mapping defined on 'EndpointLocation.service'
   * annotation fails due to a FK violation exception caused by the endpoint's
   * association to a downtime (this relationship would need to be deleted first
   * to allow the endpoint to be deleted cleanly by the cascade).
   *
   * @expectedException \Doctrine\DBAL\Exception
   */
    public function testExpectedFK_ViolationOnServiceToEndpointCascadeDelete_WithDTs()
    {
        print __METHOD__ . "\n";
      // create a linked entity graph as beow:
      //
      //     se        (1 service)
      //    /
      // el1           (1 endpoints)
      //    \
      //     dt1       (1 downtime)

        $se = TestUtil::createSampleService('service1');
        $el1 = TestUtil::createSampleEndpointLocation();
        $se->addEndpointLocationDoJoin($el1);

        $dt1 = new Downtime();
        $dt1->setDescription('downtime description');
        $dt1->setSeverity("WARNING");
        $dt1->setClassification("UNSCHEDULED");

        $dt1->addEndpointLocation($el1);

      // persist and flush
        $this->em->persist($se);
        $this->em->persist($el1);
        $this->em->persist($dt1);
        $this->em->flush();

      // Assert that there are expected EndpointLocations and one Downtimes in the database
        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM EndpointLocations");
        $this->assertTrue($result->getRowCount() == 1);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Downtimes");
        $this->assertTrue($result->getRowCount() == 1);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Downtimes_EndpointLocations");
        $this->assertTrue($result->getRowCount() == 1);

      // Try and delete the service
      // We expect a FK violation wrapped as a DBALException.
      // If the fail statement is called below, then it could be an issue with
      // with the DB. For example, Sqlite with Doctrine does not enforce FK constraints !
      // see: http://stackoverflow.com/a/4599894

      // We shouldn't be able to remove the site as we need to remove the
      // services first as we have no cascade-delete option set.
        $this->em->remove($se);
        $this->em->flush();
        $this->fail(
            'Should not get to this point - \Doctrine\DBAL\Exception expected'
        );
      }

  /**
   * Show how Bidirectional relationships must be correctly managed in
   * userland/application code. (See Doctrine the tutorial:
   * "Consistency of bi-directional references on the inverse side of a
   * relation have to be managed in userland application code. Doctrine
   * cannot magically update your collections to be consistent.").
   */
    public function testDowntimeEndpoint_BidirectionalJoinConsistencyManagement()
    {
        print __METHOD__ . "\n";
      // create a linked entity graph as beow:
      //
      //     se        (1 service)
      //    / | \
      // el1 el2 el3   (3 endpoints)
      //    \ | /
      //     dt1       (1 downtime)

        $se = TestUtil::createSampleService('service1');
        $el1 = TestUtil::createSampleEndpointLocation();
        $el2 = TestUtil::createSampleEndpointLocation();
        $el3 = TestUtil::createSampleEndpointLocation();
        $se->addEndpointLocationDoJoin($el1);
        $se->addEndpointLocationDoJoin($el2);
        $se->addEndpointLocationDoJoin($el3);

        $dt1 = new Downtime();
        $dt1->setDescription('downtime description');
        $dt1->setSeverity("WARNING");
        $dt1->setClassification("UNSCHEDULED");

        $dt1->addEndpointLocation($el1);
        $dt1->addEndpointLocation($el2);
        $dt1->addEndpointLocation($el3);

      // persist and flush
        $this->em->persist($se);
        $this->em->persist($el1);
        $this->em->persist($el2);
        $this->em->persist($el3);
        $this->em->persist($dt1);
        $this->em->flush();

      // Now delete the relationship between dt1 and el1 + el2 using OWNING
      // SIDE ONLY; since downtime is the OWNING side we must remove el1 + el2
      // from downtime to actually delete the relationships in the DB
      // (on the subsequent flush). Since we have only removed the relationship
      // on the downtime side, our in-mem entity model is now inconsistent !
        $dt1->getEndpointLocations()->removeElement($el1);
      //$el1->getDowntimes()->removeElement($dt1);
        $dt1->getEndpointLocations()->removeElement($el2);
      //$el2->getDowntimes()->removeElement($dt1);

        $this->em->flush();

      // Entity model in DB now looks like this:
      //     se
      //    / | \
      // el1 el2 el3
      //        /
      //     dt1      (note FKs/relationships have been removed)

      // Assert that there are still three EndpointLocations and one Downtimes in the database
        $testConn = $this->getConnection();
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM EndpointLocations");
        $this->assertTrue($result->getRowCount() == 3);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Downtimes");
        $this->assertTrue($result->getRowCount() == 1);
        $result = $testConn->createQueryTable('results_table', "SELECT * FROM Downtimes_EndpointLocations");
        $this->assertTrue($result->getRowCount() == 1);

      // Assert that our in-mem entity model is now inconsistent with DB
      // when VIEWED FROM THE ENDPOINT SIDE.
        $this->assertEquals(1, count($el1->getDowntimes()));
        $this->assertEquals(1, count($el2->getDowntimes()));
        $this->assertEquals(1, count($el3->getDowntimes()));

      // Assert that our in-mem entity model is consistent with DB
      // when VIEWED FROM THE DOWNTIME SIDE:
      // dt1 still has el3 linked (as expected) but not el1 or el2.
      // Note we use first() method to fetch first array collection entry!
      // (calling get(0) would not work as expected because the collection
      // is a map so array key values are preserved - el3 was added 3rd so it
      // preserves its zero-offset key value of 2).
        $this->assertEquals(1, count($dt1->getEndpointLocations()));
        $this->assertSame($el3, $dt1->getEndpointLocations()->first());
        $this->assertSame($el3, $dt1->getEndpointLocations()->get(2));

      // Next lines keep our in-mem inverse side consistent with DB. If we
      // didn't do this, then our first assertion on the following lines below
      // would fail! This shows that you have to keep your bi-directional
      // relationships consistent in userland/application code, doctrine
      // won't do this for you!
        $el1->getDowntimes()->removeElement($dt1);
        $el2->getDowntimes()->removeElement($dt1);

      // After updating the 'in-memory' entity model, check that el1 and el2
      // have no linked downtimes while el3 still has dt linked.
      // This mirrors what we have in the DB.
        $this->assertEquals(0, count($el1->getDowntimes()));
        $this->assertEquals(0, count($el2->getDowntimes()));
        $this->assertEquals(1, count($el3->getDowntimes()));

      // our collection key value is still same
        $this->assertSame($el3, $dt1->getEndpointLocations()->get(2));
    }

    private function createAndPersistScopes()
    {
        $this->egiScope = new Scope();
        $this->egiScope->setName('EGI');
        $this->egiScope->setDescription('The egi project');

        $this->localScope = new Scope();
        $this->localScope->setDescription('Local scope means no project');
        $this->localScope->setName('Local');

        $this->eudatScope = new Scope();
        $this->eudatScope->setName('EUDAT');
        $this->eudatScope->setDescription('The eudat project');

        $this->em->persist($this->egiScope);
        $this->em->persist($this->localScope);
        $this->em->persist($this->eudatScope);
    }


  // below two tests have now been deprecated, but they are good to keep
  // commented out because they are still useful to explain join behaviour

  /*public function testNgiSiteNotJoined(){
      print __METHOD__ . "\n";
      $this->doNgiSiteJoins(false);
  }
  public function testNgiSiteJoined(){
    print __METHOD__ . "\n";
    $this->doNgiSiteJoins(true);
  }*/

  /**
   * Helper function to show that setting the join/assocition is only set
   * on one side of a bi-directional relationship. If $setJoinCorrectly is
   * true, then the newly created sites will be joined to the NGIs. If false,
   * then the the join will not be set.
   *
   * @deprecated since the entity model was updated, but good to keep for reference.
   * @param boolean $setJoinCorrectly
   */
  /*private function doNgiSiteJoins($setJoinCorrectly) {
      $n = 1;
      for ($i = 0; $i < $n; $i++) {
          $ngi = $this->createSampleNGI('ngi' . $i);
          $site = $this->createSampleSite('site' . $i, 'pk' . $i);
          if ($setJoinCorrectly) {
              // This will set the join/association
              $site->setNgiDoJoin($ngi);
              //
              // **Note** setNgiDoJoin() MUST have the following definition for this test to work as expected:
              //
              //   public function setNgiDoJoin(NGI $ngi) {
          //       $this->ngi = $ngi;
              //       $ngi->addSiteNoJoin($this);
          //   }
          } else {
              // This will NOT set the join/association (only setting on the inverse side)
              $ngi->addSiteNoJoin($site);
              //
              // **Note** addSiteNoJoin() MUST have the following definition for this test to work as expected:
              //
              //   public function addSiteNoJoin(Site $site) {
      //       $this->sites[] = $site;
          //   }
          }
          $this->em->persist($site); // is now managed
          $this->em->persist($ngi);  // is now managed
          $this->assertTrue($this->em->contains($site));
          $this->assertTrue($this->em->contains($ngi));
      }
      $this->em->flush(); // commit

      // Do not trust what Doctrine tells us and assert the rows have
      // actually been added using test connection
      $testConn = $this->getConnection();
      $result = $testConn->createQueryTable('results_table', "SELECT * FROM NGIs");
      $this->assertTrue($result->getRowCount() == $n);
      $result = $testConn->createQueryTable('results_table', "SELECT * FROM Sites");
      $this->assertTrue($result->getRowCount() == $n);

      // Assert that the FK joins worked as expected.
      $result = $testConn->createQueryTable('results_table',
              "SELECT * FROM NGIs inner join Sites on NGIs.id = Sites.ngi_id");
      if($setJoinCorrectly) {
          $this->assertTrue($result->getRowCount() == $n);
      } else {
          $this->assertTrue($result->getRowCount() == 0);
      }
  }*/
}
