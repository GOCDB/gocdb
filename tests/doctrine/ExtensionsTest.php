<?php

require_once dirname(__FILE__) . '/TestUtil.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/ServiceService.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/RoleActionMappingService.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/RoleActionAuthorisationService.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/User.php';
use Doctrine\ORM\EntityManager;
require_once dirname(__FILE__) . '/bootstrap.php';

/**
* A template that includes all the setup and tear down functions for writting
* a PHPUnit test to test doctrine.
*
* @author James McCarthy
*/
class ExtensionsTest extends PHPUnit_Extensions_Database_TestCase
{
    private $em;

  /**
   * Overridden.
   */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing ExtensionsTest. . .\n";
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
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            if ($result->getRowCount() != 0) {
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
            }
        }
    }

  /**
   * An example test showing the creation of a site and properties and that
   * all data is removed on deletion of a site or property
   */
    public function testSitePropertyDeletions()
    {
        print __METHOD__ . "\n";

      //Create a site
        $site = TestUtil::createSampleSite("TestSite");
      //Create site property
        $prop1 = TestUtil::createSampleSiteProperty("VO-ATLAS", "true");
        $prop2 = TestUtil::createSampleSiteProperty("VO-CMS", "true");
        $prop3 = TestUtil::createSampleSiteProperty("VO-ALICE", "true");

        $site->addSitePropertyDoJoin($prop1);
        $site->addSitePropertyDoJoin($prop2);
        $site->addSitePropertyDoJoin($prop3);

      //Set some extra details of the site
        $site->setEmail("myTest@email.com");
        $site->setTelephone("012345678910");
        $site->setLocation("United Kingdom");

      //Persist the site & property in the entity manager
        $this->em->persist($site);
        $this->em->persist($prop1);
        $this->em->persist($prop2);
        $this->em->persist($prop3);

      //Commit the site to the database
        $this->em->flush();

      //Check that the site has 3 properties associated with it
        $properties = $site->getSiteProperties();
        $this->assertTrue(count($properties) == 3);


      //Create an admin user that can delete a property
        $adminUser = TestUtil::createSampleUser('my', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/my/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

      //flush the user to the DB - so that the RoleAuthorisationService can find it in the DB
        $this->em->flush();

      //Delete the property from the site
        $siteService = new org\gocdb\services\Site();
        $siteService->setEntityManager($this->em);
        $roleActionMappingService = new org\gocdb\services\RoleActionMappingService();
        $roleActionAuthService = new org\gocdb\services\RoleActionAuthorisationService($roleActionMappingService);
        $roleActionAuthService->setEntityManager($this->em);
        $siteService->setRoleActionAuthorisationService($roleActionAuthService);
        $siteService->deleteSiteProperties($site, $adminUser, array($prop1));

      //Check that the site now only has 2 properties
        $properties = $site->getSiteProperties();
        $this->assertTrue(count($properties) == 2);
        $this->em->flush();

      //Check this via the database
        $con = $this->getConnection();

      //Get site id to use in sql statements
        $siteId = $site->getId();

        $result = $con->createQueryTable('results', "SELECT * FROM Site_Properties WHERE PARENTSITE_ID = '$siteId'");
      //Assert that only 2 site properties exist in the database for this site
        $this->assertEquals(2, $result->getRowCount());

      //Now delete the site and check that it cascades the delete to remove the sites associated properties
        $siteService->deleteSite($site, $adminUser, false);
        $this->em->flush();

      //Check site is gone
        $result = $con->createQueryTable('results', "SELECT * FROM Sites WHERE ID = '$siteId'");
        $this->assertEquals(0, $result->getRowCount());

      //Check properties are gone
        $result = $con->createQueryTable('results', "SELECT * FROM Site_Properties WHERE PARENTSITE_ID = '$siteId'");
        $this->assertEquals(0, $result->getRowCount());
    }

  /**
   * An example test showing the creation of a service and properties and that
   * all data is removed on deletion of a service or property
   */
    public function testServicePropertyDeletions()
    {
        print __METHOD__ . "\n";

      //Create a service
        $service = TestUtil::createSampleService("TestService");

      //Create a NGI
        $ngi = TestUtil::createSampleNGI("TestNGI");
      //Create a site
        $site = TestUtil::createSampleSite("TestSite");

      //Join service to site, and site to NGI.
        $ngi->addSiteDoJoin($site);

        $site->addServiceDoJoin($service);

      //Create service property
        $prop1 = TestUtil::createSampleServiceProperty("VO-Atlas", "true");
        $prop2 = TestUtil::createSampleServiceProperty("VO-CMS", "true");
        $prop3 = TestUtil::createSampleServiceProperty("VO-Alice", "true");

        $service->addServicePropertyDoJoin($prop1);
        $service->addServicePropertyDoJoin($prop2);
        $service->addServicePropertyDoJoin($prop3);

      //Persist the service & property in the entity manager
        $this->em->persist($service);
        $this->em->persist($ngi);
        $this->em->persist($site);
        $this->em->persist($prop1);
        $this->em->persist($prop2);
        $this->em->persist($prop3);

      //Commit the service to the database
        $this->em->flush();

      //Check that the service has 3 properties associated with it
        $properties = $service->getServiceProperties();
        $this->assertTrue(count($properties) == 3);

      //Create an admin user that can delete a property
        $adminUser = TestUtil::createSampleUser('my', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/my/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

      //flush the user to the DB - so that the RoleAuthorisationService can find it in the DB
        $this->em->flush();

      //Delete the property from the service
        $serviceService = new org\gocdb\services\ServiceService();
        $serviceService->setEntityManager($this->em);
        $roleActionMappingService = new org\gocdb\services\RoleActionMappingService();
        $roleActionAuthService = new org\gocdb\services\RoleActionAuthorisationService($roleActionMappingService);
        $roleActionAuthService->setEntityManager($this->em);
        $serviceService->setRoleActionAuthorisationService($roleActionAuthService);
      //$serviceService->deleteServiceProperty($service, $adminUser, $prop1);
        $serviceService->deleteServiceProperties($service, $adminUser, array($prop1));

      //Check that the service now only has 2 properties
        $properties = $service->getServiceProperties();
        $this->assertTrue(count($properties) == 2);
        $this->em->flush();

      //Check this via the database
        $con = $this->getConnection();

      //Get service id to use in sql statements
        $servId = $service->getId();

        $result = $con->createQueryTable('results', "SELECT * FROM Service_Properties WHERE PARENTSERVICE_ID = '$servId'");
      //Assert that only 2 service properties exist in the database for this service
        $this->assertEquals(2, $result->getRowCount());

      //Now delete the service and check that it cascades the delete to remove the services associated properties
        $serviceService->deleteService($service, $adminUser, true);
        $this->em->flush();

      //Check service is gone
        $result = $con->createQueryTable('results', "SELECT * FROM Services WHERE ID = '$servId'");
        $this->assertEquals(0, $result->getRowCount());

      //Check properties are gone
        $result = $con->createQueryTable('results', "SELECT * FROM Service_Properties WHERE PARENTSERVICE_ID = '$servId'");
        $this->assertEquals(0, $result->getRowCount());
    }

  /**
   * An example test showing the creation of a service group and properties
   * and that all data is removed on deletion of a service group or property
   */
    public function testServiceGroupPropertyDeletions()
    {
        print __METHOD__ . "\n";

      //Create a service
        $service = TestUtil::createSampleService("TestService");

      //Create a NGI
        $ngi = TestUtil::createSampleNGI("TestNGI");
      //Create a site
        $site = TestUtil::createSampleSite("TestSite");

      //Create a service group
        $sg = TestUtil::createSampleServiceGroup("TestServiceGroup");

      //Join service to site, and site to NGI.
        $ngi->addSiteDoJoin($site);
        $site->addServiceDoJoin($service);

      //Finally add service to service group
        $sg->addService($service);

      //Create service group properties
        $prop1 = TestUtil::createSampleServiceGroupProperty("VO-Atlas", "true");
        $prop2 = TestUtil::createSampleServiceGroupProperty("VO-CMS", "true");
        $prop3 = TestUtil::createSampleServiceGroupProperty("VO-Alice", "true");

        $sg->addServiceGroupPropertyDoJoin($prop1);
        $sg->addServiceGroupPropertyDoJoin($prop2);
        $sg->addServiceGroupPropertyDoJoin($prop3);

      //Persist the service, ngi, site, service group & property in the entity manager
        $this->em->persist($service);
        $this->em->persist($ngi);
        $this->em->persist($site);
        $this->em->persist($sg);
        $this->em->persist($prop1);
        $this->em->persist($prop2);
        $this->em->persist($prop3);

      //Commit the entites to the database
        $this->em->flush();

      //Check that the service group has 3 properties associated with it
        $properties = $sg->getServiceGroupProperties();
        $this->assertTrue(count($properties) == 3);

      //Create an admin user that can delete a property
        $adminUser = TestUtil::createSampleUser('my', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/my/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

      //flush the user to the DB - so that the RoleAuthorisationService can find it in the DB
        $this->em->flush();

      //Delete the property from the service group
        $roleActionMappingService = new org\gocdb\services\RoleActionMappingService();
        $roleService = new org\gocdb\services\Role();
        $roleService->setEntityManager($this->em);
        $authService = new org\gocdb\services\RoleActionAuthorisationService($roleActionMappingService/* , $roleService */);
        $authService->setEntityManager($this->em);
        $sgService = new org\gocdb\services\ServiceGroup($authService);
        $sgService->setEntityManager($this->em);
        $sgService->setRoleActionAuthorisationService($authService);

      //$sgService->deleteServiceGroupProperty($sg, $adminUser, $prop1);
        $sgService->deleteServiceGroupProperties($sg, $adminUser, array($prop1));

      //Check that the sg now only has 2 properties
        $properties = $sg->getServiceGroupProperties();
        $this->assertTrue(count($properties) == 2);
        $this->em->flush();

      //Check this via the database
        $con = $this->getConnection();

      //Get servicegroup id to use in sql statements
        $sgId = $sg->getId();

        $result = $con->createQueryTable('results', "SELECT * FROM ServiceGroup_Properties WHERE PARENTSERVICEGROUP_ID = '$sgId'");
      //Assert that only 2 service group properties exist in the database for this service
        $this->assertEquals(2, $result->getRowCount());

      //Now delete the service group and check that it cascades the delete to remove the services associated properties
        $sgService->deleteServiceGroup($sg, $adminUser, true);
        $this->em->flush();

      //Check service group is gone
        $result = $con->createQueryTable('results', "SELECT * FROM ServiceGroups WHERE ID = '$sgId'");
        $this->assertEquals(0, $result->getRowCount());

      //Check properties are gone
        $result = $con->createQueryTable('results', "SELECT * FROM ServiceGroup_Properties WHERE PARENTSERVICEGROUP_ID = '$sgId'");
        $this->assertEquals(0, $result->getRowCount());
    }

  /**
   * Show the creation of an endpoint and properties and that
   * all data is removed on deletion of an endpoint or property
   */
    public function testEndpointPropertyDeletions()
    {
        print __METHOD__ . "\n";

        $service = TestUtil::createSampleService("TestService");
        $ngi = TestUtil::createSampleNGI("TestNGI");
        $site = TestUtil::createSampleSite("TestSite");
        $endpoint = TestUtil::createSampleEndpointLocation();

      //Join Service endpoint to site, service to site, and site to NGI.
        $ngi->addSiteDoJoin($site);
        $site->addServiceDoJoin($service);
        $service->addEndpointLocationDoJoin($endpoint);

      //Create properties
        $prop1 = TestUtil::createSampleEndpointProperty("VO-Atlas", "true");
        $prop2 = TestUtil::createSampleEndpointProperty("VO-CMS", "true");
        $prop3 = TestUtil::createSampleEndpointProperty("VO-Alice", "true");

        $endpoint->addEndpointPropertyDoJoin($prop1);
        $endpoint->addEndpointPropertyDoJoin($prop2);
        $endpoint->addEndpointPropertyDoJoin($prop3);


      //Persist in the entity manager
        $this->em->persist($service);
        $this->em->persist($ngi);
        $this->em->persist($site);
        $this->em->persist($endpoint);
        $this->em->persist($prop1);
        $this->em->persist($prop2);
        $this->em->persist($prop3);


      //Commit to the database
        $this->em->flush();

      //Check endpoint has 3 properties associated with it
        $properties = $endpoint->getEndpointProperties();
        $this->assertTrue(count($properties) == 3);


      //Create an admin user that can delete a property
        $adminUser = TestUtil::createSampleUser('my', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/my/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

      //flush the user to the DB - so that the RoleAuthorisationService can find it in the DB
        $this->em->flush();

      //Delete the property from the service
        $serviceService = new org\gocdb\services\ServiceService();
        $serviceService->setEntityManager($this->em);
        $roleActionMappingService = new org\gocdb\services\RoleActionMappingService();
        $roleActionAuthService = new org\gocdb\services\RoleActionAuthorisationService($roleActionMappingService);
        $roleActionAuthService->setEntityManager($this->em);
        $serviceService->setRoleActionAuthorisationService($roleActionAuthService);

        $serviceService->deleteEndpointProperties($service, $adminUser, array($prop1));

      //Check that the service endpoint now only has 2 properties
        $properties = $endpoint->getEndpointProperties();
        $this->assertTrue(count($properties) == 2);
        $this->em->flush();

      //Check this via the database
        $con = $this->getConnection();

      //Get service id to use in sql statements
        $endpointId = $endpoint->getId();

        $result = $con->createQueryTable('results', "SELECT * FROM Endpoint_Properties WHERE PARENTENDPOINT_ID = '$endpointId'");
      //Assert that only 2 service properties exist in the database for this service
        $this->assertEquals(2, $result->getRowCount());

      //Now delete the endpont and check that it cascade deletes
      //the endpoint's associated properties
        $serviceService->deleteEndpoint($endpoint, $adminUser);
        $this->em->flush();

      //Check endpoint is gone
        $result = $con->createQueryTable('results', "SELECT * FROM EndpointLocations WHERE ID = '$endpointId'");
        $this->assertEquals(0, $result->getRowCount());

      //Check properties are gone
        $result = $con->createQueryTable('results', "SELECT * FROM Endpoint_Properties WHERE PARENTENDPOINT_ID = '$endpointId'");
        $this->assertEquals(0, $result->getRowCount());
    }

  /**
   * An example test showing the creation of a user and identifiers and that
   * all data is removed on deletion of a user or identifier
   */
    public function testUserIdentifierDeletions()
    {
        print __METHOD__ . "\n";

      // Create a user
        $user = TestUtil::createSampleUser("Test", "Testing");
      // Create user identifiers
        $identifier1 = TestUtil::createSampleUserIdentifier("Auth type 1", "/c=test1");
        $identifier2 = TestUtil::createSampleUserIdentifier("Auth type 2", "/c=test2");
        $identifier3 = TestUtil::createSampleUserIdentifier("Auth type 3", "/c=test3");

        $user->addUserIdentifierDoJoin($identifier1);
        $user->addUserIdentifierDoJoin($identifier2);
        $user->addUserIdentifierDoJoin($identifier3);

      // Set some extra details of the user
        $user->setEmail("myTest@email.com");
        $user->setTelephone("012345678910");

      // Persist the user & identifiers in the entity manager
        $this->em->persist($user);
        $this->em->persist($identifier1);
        $this->em->persist($identifier2);
        $this->em->persist($identifier3);

      // Commit the user to the database
        $this->em->flush();

      // Check that the user has 3 identifiers associated with it
        $identifiers = $user->getUserIdentifiers();
        $this->assertTrue(count($identifiers) === 3);


      // Create an admin user that can delete an identifier
        $adminUser = TestUtil::createSampleUser('my', 'admin');
        $identifier = TestUtil::createSampleUserIdentifier('X.509', '/my/admin');
        $adminUser->addUserIdentifierDoJoin($identifier);
        $this->em->persist($identifier);
        $adminUser->setAdmin(true);
        $this->em->persist($adminUser);

      // Flush the user to the DB - so that the RoleAuthorisationService can find it in the DB
        $this->em->flush();

      // Delete the identifier from the user
        $userService = new org\gocdb\services\User();
        $userService->setEntityManager($this->em);
        $userService->deleteUserIdentifier($user, $identifier1, $adminUser);

      // Check that the user now only has 2 identifiers
        $identifiers = $user->getUserIdentifiers();
        $this->assertTrue(count($identifiers) == 2);
        $this->em->flush();

      // Check this via the database
        $con = $this->getConnection();

      // Get user id to use in sql statements
        $userId = $user->getId();

        $result = $con->createQueryTable('results', "SELECT * FROM User_Identifiers WHERE PARENTUSER_ID = '$userId'");
      // Assert that only 2 user identifiers exist in the database for this user
        $this->assertEquals(2, $result->getRowCount());

      // Now delete the user and check that it cascades the delete to remove the user's associated identifiers
        $userService->deleteUser($user, $adminUser);
        $this->em->flush();

      // Check user is gone
        $result = $con->createQueryTable('results', "SELECT * FROM Users WHERE ID = '$userId'");
        $this->assertEquals(0, $result->getRowCount());

      // Check identifiers are gone
        $result = $con->createQueryTable('results', "SELECT * FROM User_Identifiers WHERE PARENTUSER_ID = '$userId'");
        $this->assertEquals(0, $result->getRowCount());
    }
}
