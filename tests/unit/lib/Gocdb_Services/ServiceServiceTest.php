<?php
/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once __DIR__ . '/../../../doctrine/TestUtil.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/ServiceService.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
use Doctrine\ORM\EntityManager;
require_once __DIR__ . '/../../../doctrine/bootstrap.php';

/**
 * DBUnit test class
 *
 */
class ServiceServiceTest extends PHPUnit_Extensions_Database_TestCase{
  private $eMan;

  /**
  * Overridden.
  */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    echo "\n\n-------------------------------------------------\n";
    echo "Executing ServiceServiceTest. . .\n";
  }

  /**
  * Overridden. Returns the test database connection.
  * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
  */
  protected function getConnection() {
    require_once __DIR__ . '/../../../doctrine/bootstrap_pdo.php';
    return getConnectionToTestDB();
  }

  /**
  * Overridden. Returns the test dataset.
  * Defines how the initial state of the database should look before each test is executed.
  * @return PHPUnit_Extensions_Database_DataSet_IDataSet
  */
  protected function getDataSet() {
    return $this->createFlatXMLDataSet(__DIR__ . '/../../../doctrine/truncateDataTables.xml');
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
    $this->eMan = $this->createEntityManager();
  }

  /**
  * @todo Still need to setup connection to different databases.
  * @return EntityManager
  */
  private function createEntityManager(){
    // Initialise to avoid unused variable warnings
    $entityManager = NULL;
    require __DIR__ . '/../../../doctrine/bootstrap_doctrine.php';
    return $entityManager;
  }

  /**
  * Called after setUp() and before each test. Used for common assertions
  * across all tests.
  */
  protected function assertPreConditions() {
    $con = $this->getConnection();
    $fixture = __DIR__ . '/../../../doctrine/truncateDataTables.xml';
    $tables = simplexml_load_file($fixture);

    foreach($tables as $tableName) {
      $sql = "SELECT * FROM ".$tableName->getName();
      $result = $con->createQueryTable('results_table', $sql);
      if($result->getRowCount() != 0){
        throw new RuntimeException("Invalid fixture. Table has rows: ".$tableName->getName());
      }
    }
  }

  public function createValidationEntities(){

    $util = new TestUtil();

    $site = $util->createSampleSite("TestSite");
    $this->eMan->persist($site);

    $service = $util->createSampleService("TestService1");
    $this->eMan->persist($service);
    $service->setParentSiteDoJoin($site);

    $user = $util->createSampleUser("Test", "Testing", "/c=test");
    $this->eMan->persist($user);
    $user->setAdmin(TRUE);

    $roleAMS = new org\gocdb\services\RoleActionMappingService();
    $roleAAS = new org\gocdb\services\RoleActionAuthorisationService($roleAMS);
    $roleAAS->setEntityManager($this->eMan);

    $this->eMan->flush();

    $serviceService = new org\gocdb\services\ServiceService();
    $serviceService->setEntityManager($this->eMan);
    $serviceService->setRoleActionAuthorisationService($roleAAS);

    return array($service, $user, $serviceService);
  }
  /**
   * Check the basics -
   * Duplicates some simple testing from ExtensionsTest
   */
  public function testValidateProperty(){
    print __METHOD__ . "\n";

    list ($service, $user, $serviceService) = $this->createValidationEntities();
    // Properties are specified as array of arrays of form
    // [[Name,Value],[Name,Value], ... ]
    $values[0] = array("ValidName1","ValidValue");
    $values[1] = array("ValidName2","<ValidValue><ValidValue>");

    $this->assertTrue($serviceService->addProperties($service, $user, $values) == NULL);

    $this->assertTrue(count($properties = $service->getServiceProperties()) == 2);

    $this->assertTrue(
      $serviceService->deleteServiceProperties($service, $user, $properties->toArray())
                      == NULL);

    $this->assertTrue(count($properties = $service->getServiceProperties()) == 0);
  }
  /**
   * Check that validation of property name is operating as expected
   * Added to test code changes to pass through "<>" chars
   * @depends testValidateProperty
   */
  public function testValidatePropertyNameFails(){
    print __METHOD__ . "\n";

    list ($service, $user, $serviceService) = $this->createValidationEntities();
    // Properties are specified as array of arrays of form
    // [[Name,Value],[Name,Value], ... ]
    // < & > are invalid characters in the property name but are valid
    // for the property value.
    $values[0] = array("<Invalid>","<valid>");

    $this->setExpectedException('Exception');

    $serviceService->addProperties($service, $user, $values);

  }
  /**
   * Check that validation of property value is operating as expected
   * @depends testValidateProperty
   */
  public function testValidatePropertyValueFails(){
    print __METHOD__ . "\n";

    list ($service, $user, $serviceService) = $this->createValidationEntities();
    // Properties are specified as array of arrays of form
    // [[Name,Value],[Name,Value], ... ]
    // Quote characters are invalid in property value
    $values[0] = array("Valid","'Not Valid'");

    $this->setExpectedException('Exception');

    $serviceService->addProperties($service, $user, $values);

  }
}
