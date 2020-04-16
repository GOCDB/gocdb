<?php
/*
 * Copyright (C) 2020 STFC
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
require_once __DIR__ . '/../doctrine/bootstrap.php';
require_once __DIR__ . '/../../lib/Gocdb_Services/Site.php';
require_once __DIR__ . '/../../lib/Gocdb_Services/ServiceService.php';
require_once __DIR__ . '/../../htdocs/PI/write/PIWriteRequest.php';
require_once __DIR__ . '/../doctrine/TestUtil.php';

use Doctrine\ORM\EntityManager;

/**
 * Abstract class for testing the Write API.
 *
 */
abstract class AbstractWriteAPITestClass extends PHPUnit_Extensions_Database_TestCase{

  protected $em;
  protected $validAuthIdent = 'validIdentifierString';

  /**
  * Overridden. Returns the test database connection.
  * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
  */
  protected function getConnection() {
    require_once __DIR__ . '/../doctrine/bootstrap_pdo.php';
    return getConnectionToTestDB();
  }

  /**
  * Overridden. Returns the test dataset.
  * Defines how the initial state of the database should look before each test is executed.
  * @return PHPUnit_Extensions_Database_DataSet_IDataSet
  */
  protected function getDataSet() {
    return $this->createFlatXMLDataSet(__DIR__ . '/../doctrine/truncateDataTables.xml');
  }

  /**
  * Overridden.
  */
  protected function getSetUpOperation() {
    # ::CLEAN_INSERT is default
    #
    # Issue a DELETE from <table> which is more portable than a
    # TRUNCATE table <table> (some DBs require high privileges for truncate statements
    # and also do not allow truncates across tables with FK contstraints e.g. Oracle)
    return PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL();
  }

  /**
  * Overridden.
  */
  protected function getTearDownOperation() {
    # NONE is default
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
    require __DIR__ . '/../doctrine/bootstrap_doctrine.php';
    return $entityManager;
  }

  /**
  * Called after setUp() and before each test. Used for common assertions
  * across all tests.
  */
  protected function assertPreConditions() {
    $con = $this->getConnection();
    $fixture = __DIR__ . '/../doctrine/truncateDataTables.xml';
    $tables = simplexml_load_file($fixture);

    foreach($tables as $tableName) {
      $sql = "SELECT * FROM ".$tableName->getName();
      $result = $con->createQueryTable('results_table', $sql);
      if($result->getRowCount() != 0){
        throw new RuntimeException("Invalid fixture. Table has rows: ".$tableName->getName());
      }
    }
  }

  /**
   * The test class for each method should test the case of unauthenticated requests.
   * Ideally this should be an otherwise valid request and should explicitly
   * check nothing is changed.
   */
  abstract protected function test_APIUnauthenticated();

  /**
   * The test class for each method should test the case of unauthorised requests.
   * Ideally this should be an otherwise valid request and should explicitly
   * check nothing is changed.
   */
  abstract protected function test_APIUnauthorised();

  /**
   * Function to make a call to the write API and return an array containing the
   * contents and response code of the response.
   * @param  string $method          HTTP method, e,g GET
   * @param  string $url             url string we are emulating using to access API
   * @param  string $requestContents contents of the request we are testing against the URL
   * @return array                   array containing response code and API response
   */
  protected function arbitaryValuesWriteAPICall ($method, $url, $requestContents, $authMethod, $authIdent) {

    $siteService =   new org\gocdb\services\Site();
    $siteService->setEntityManager($this->em);
    $serviceService = new org\gocdb\services\ServiceService();
    $serviceService->setEntityManager($this->em);
    $authArray = array('userIdentifier'=>$authIdent,'userIdentifierType'=>$authMethod);

    $piReq = new org\gocdb\services\PIWriteRequest();
    $piReq->setServiceService($serviceService);
    return $piReq->processRequest($method, $url, $requestContents, $siteService, $authArray);

  }


  /**
   * Function to make a call to the API for tests that assumes we are testing
   * functionality and not trying to break it through poorly formatted requests
   * @param  string $method          HTTP method of request
   * @param  string $requestContents HTTP request contents
   * @param  string $authIdent       Authentication identifier
   * @param  string $entType         Type of entity being accessed
   * @param  string $entID           ID of entity being accessed
   * @param  string $entProp         property of entity which is being changed
   * @param  string $entKey          optionally the key to a value of the propery being changed
   * @return array                   array containing response code and API response
   */
  protected function wellFormattedWriteAPICall ($method, $requestContents, $authIdent, $entType, $entID, $entProp, $entKey = null ) {
    #create a url string
    $urlString = 'v5';
    $urlString .= '/' . $entType;
    $urlString .= '/' . $entID;
    $urlString .= '/' . $entProp;
    if (!is_null($entKey)) {
      $urlString .= '/' . $entKey;
    }

    return $this->arbitaryValuesWriteAPICall ($method,$urlString, $requestContents, 'X509', $authIdent);
  }

  /**
   * Create a JSON string in a format acceptable to the Write API for a single value
   * @param  string $value single value for JSON
   * @return string        json string
   */
  protected function singleValToJsonRequest ($value) {
    $jsonArray = array ('value'=>$value);
    $json = json_encode ($jsonArray);
    return $json;
  }

  /**
   * Create a JSON string in a format acceptable to the Write API for a single
   * value which is boolean
   *
   * @param  boolean $bool single value for JSON
   * @return string        json string
   */
  protected function boolValToJsonRequest ($bool) {
    $boolStr = 'false';
    if ($bool) {
      $boolStr = 'true';
    }

    $json = '{"value":' . $boolStr . '}';
    return $json;
  }

  /**
   * Creates a sample site for testing which is a member of a sample NGI.
   * Also creates a sample API authentication entity for testing.
   *
   * @param  string $append append this string to all names - enables multiple
   *                        sample sites without violating unique constraints
   * @return Site the sample site
   */
  protected function createSampleSite($append="") {
    #Create an NGI for the site to beong to
    $NGI = TestUtil::createSampleNGI("SampleNGI" . $append);

    #Create the test site
    $site = TestUtil::createSampleSite("SampleSite" . $append);

    #The site needs associating with the NGI
    $NGI->addSiteDoJoin($site);

    #We need a credential that can access the writeAPI associated with the sites
    $authEnt = new \APIAuthentication();
    $authEnt->setIdentifier($this->validAuthIdent);
    $authEnt->setType('X509');
    $site->addAPIAuthenticationEntitiesDoJoin($authEnt);

    #And then we get Doctrine to update the DB
    $this->em->persist($authEnt);
    $this->em->persist($NGI);
    $this->em->persist($site);
    $this->em->flush();

    #Check that there is an auth entity and that it is associated with the site (Creation of sites is validated in previous tests)
    $con = $this->getConnection();
    $APIAuthId = $authEnt->getId();
    $siteID = $site->getID();
    $sql = "SELECT * FROM APIAuthenticationEntities WHERE parentSite_id = '$siteID' AND Id = '$APIAuthId'";
    $result = $con->createQueryTable('', $sql);
    $this->assertEquals(1, $result->getRowCount());

    return $site;
  }

  /**
  * Creates a sample service for testing against. In doing so, calls other
  * functions to create stack of site/NGI for it to belong to
  *
  * @param  string $append append this string to all names - enables multiple
  *                        sample services without violating unique constraints
  * @return Service service for testing against
  */
  protected function createSampleService($append="") {
    #create a sample site and create a new sample service and join it to that site
    $sampleService = TestUtil::createSampleService("Sample service" . $append);
    $sampleST= TestUtil::createSampleServiceType("sample.service.type" .$append);
    $sampleService->setServiceType($sampleST);
    $sampleSite = $this->createSampleSite($append);
    $sampleSite->addServiceDoJoin($sampleService);

    #Update the DB
    $this->em->persist($sampleST);
    $this->em->persist($sampleService);
    $this->em->persist($sampleSite);
    $this->em->flush();

    return $sampleService;
  }

  /**
   * Creates a sample endpoint for testing against. In doing so, calls other
   * functions to create stack of serivce/site/NGI for it to belong to
   *
   * @return EndpointLocation endpoint for testing against
 */
  protected function createSampleEndpoint() {
    #Create a sample service (and associated gubbins) and create an endpoint associated with it
    $sampleEP = TestUtil::createSampleEndpointLocation();
    $sampleSer  = $this->createSampleService();
    $sampleEP->setServiceDoJoin($sampleSer);

    #update DB
    $this->em->persist($sampleSer);
    $this->em->persist($sampleEP);
    $this->em->flush();

    return $sampleEP;
  }

}
