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
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Scope.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Config.php';

use Doctrine\ORM\EntityManager;
require_once __DIR__ . '/../../../doctrine/bootstrap.php';

/**
 * DBUnit test class for the {@see \org\gocdb\services\Scope} service.
 *
 * @author David Meredith
 */
class ScopeServiceTest extends PHPUnit_Extensions_Database_TestCase{
     private $em;



    /**
     * Overridden.
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing ScopeServiceTest. . .\n";
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
        $this->em = $this->createEntityManager();
    }

    /**
     * @todo Still need to setup connection to different databases.
     * @return EntityManager
     */
    private function createEntityManager(){
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
            //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM ".$tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            //echo 'row count: '.$result->getRowCount() ;
            if($result->getRowCount() != 0){
                throw new RuntimeException("Invalid fixture. Table has rows: ".$tableName->getName());
            }
        }
    }

    private function insertTestScopes(){
        $scopeCount = 10;
        $scopes = array();
        // create scopes and persist
        for($i=0; $i<$scopeCount; $i++){
            $scopes[] = TestUtil::createSampleScope("scope ".$i, "Scope".$i);
            $this->em->persist($scopes[$i]);
        }
    }


    public function testGetScopes(){
        print __METHOD__ . "\n";
        $this->insertTestScopes();
        $this->em->flush();

        $scopeService = new \org\gocdb\services\Scope();
        $scopeService->setEntityManager($this->em);

        $scopes = $scopeService->getScopes();
        $this->assertEquals(10, count($scopes));
        $scopeIds = array();
        foreach($scopes as $scope){
            $scopeIds[] = $scope->getId();
        }
        $scopes = $scopeService->getScopes($scopeIds);
        $this->assertEquals(10, count($scopes));
//        foreach($scopes as $scope){
//            echo $scope->getId().' '.$scope->getName()."\n";
//        }

    }

    public function testGetScopesFilterByParams1() {
        print __METHOD__ . "\n";
        $this->insertTestScopes();
        $this->em->flush();

        $scopeService = new \org\gocdb\services\Scope();
        $scopeService->setEntityManager($this->em);
        $configService = new \org\gocdb\services\Config();
        $configService->setLocalInfoFileLocation(__DIR__ . '/../../resources/sample_local_info1.xml');
        $scopeService->setConfigService($configService);

        // exclude all the 'Reserved' scopes
        $filterParams = array('excludeReserved' => true);
        $excludedScopeNames = $configService->getReservedScopeList();//'Scope0', 'Scope1', 'Scope2', 'Scope3'
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, null);
        foreach($filteredScopes as $scope){
            //echo $scope->getName()."\n";
            if(in_array($scope->getName(), $excludedScopeNames)){
               $this->fail("Reserved scope returned");
            }
        }
        $this->assertEquals(6, count($filteredScopes));

        // excluding all the 'Normal/Non-Reserved' scopes should leave 0
        $filterParams = array('excludeNonReserved' => true);
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, $filteredScopes);
        $this->assertEquals(0, count($filteredScopes));
    }



    public function testGetScopesFilterByParams2() {
        print __METHOD__ . "\n";
        $this->insertTestScopes();
        $this->em->flush();

        $scopeService = new \org\gocdb\services\Scope();
        $scopeService->setEntityManager($this->em);
        $configService = new \org\gocdb\services\Config();
        $configService->setLocalInfoFileLocation(__DIR__ . '/../../resources/sample_local_info1.xml');
        $scopeService->setConfigService($configService);

        // exclude all the 'Normal/Non-Reserved' scopes:
        $filterParams = array('excludeNonReserved' => true);
        $excludedScopeNames = array('Scope4', 'Scope5', 'Scope6', 'Scope7', 'Scope8', 'Scope9');
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, null);
        foreach($filteredScopes as $scope){
            //echo $scope->getName()."\n";
            if(in_array($scope->getName(), $excludedScopeNames)){
               $this->fail("Normal/Non-Reserved scope returned");
            }
        }
        $this->assertEquals(4, count($filteredScopes));

        // excluding all the 'Reserved' scopes should leave 0
        $filterParams = array('excludeReserved' => true);
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, $filteredScopes);
        $this->assertEquals(0, count($filteredScopes));
    }


    public function testGetScopesFilterByParams3() {
        print __METHOD__ . "\n";
        $this->insertTestScopes();
        $this->em->flush();

        $scopeService = new \org\gocdb\services\Scope();
        $scopeService->setEntityManager($this->em);
        $configService = new \org\gocdb\services\Config();
        $configService->setLocalInfoFileLocation(__DIR__ . '/../../resources/sample_local_info1.xml');
        $scopeService->setConfigService($configService);

        // exclude all the 'Default' scopes:
        $filterParams = array('excludeDefault' => true);
        $excludedScopeNames = array('Scope0');
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, null);
        foreach($filteredScopes as $scope){
            //echo $scope->getName()."\n";
            if(in_array($scope->getName(), $excludedScopeNames)){
               $this->fail("Default scope returned");
            }
        }
        $this->assertEquals(9, count($filteredScopes));

        // excluding all the 'Non Default' scopes should leave 0
        $filterParams = array('excludeNonDefault' => true);
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, $filteredScopes);
        $this->assertEquals(0, count($filteredScopes));
    }

    public function testGetScopesFilterByParams4() {
        print __METHOD__ . "\n";
        $this->insertTestScopes();
        $this->em->flush();

        $scopeService = new \org\gocdb\services\Scope();
        $scopeService->setEntityManager($this->em);
        $configService = new \org\gocdb\services\Config();
        $configService->setLocalInfoFileLocation(__DIR__ . '/../../resources/sample_local_info1.xml');
        $scopeService->setConfigService($configService);

        // exclude all the 'Non Default' scopes:
        $filterParams = array('excludeNonDefault' => true);
        $excludedScopeNames = array('Scope1','Scope2','Scope3','Scope4','Scope5','Scope6','Scope7','Scope8','Scope9');
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, null);
        foreach($filteredScopes as $scope){
            //echo $scope->getName()."\n";
            if(in_array($scope->getName(), $excludedScopeNames)){
               $this->fail("Default scope returned");
            }
        }
        $this->assertEquals(1, count($filteredScopes));

        // excluding all the 'Default' scopes should leave 0
        $filterParams = array('excludeDefault' => true);
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, $filteredScopes);
        $this->assertEquals(0, count($filteredScopes));
    }

    public function testGetScopesFilterByParams5() {
        print __METHOD__ . "\n";
        $this->insertTestScopes();
        $this->em->flush();

        $scopeService = new \org\gocdb\services\Scope();
        $scopeService->setEntityManager($this->em);
        $configService = new \org\gocdb\services\Config();
        $configService->setLocalInfoFileLocation(__DIR__ . '/../../resources/sample_local_info1.xml');
        $scopeService->setConfigService($configService);

        $filterParams = array('excludeDefault' => true, 'excludeNonReserved' => true);
        $excludedScopeNames = array('Scope0', 'Scope4','Scope5','Scope6','Scope7','Scope8','Scope9');
        // $expectedScopeNames=array('Scope1','Scope2','Scope3');
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, null);
        foreach($filteredScopes as $scope){
            //echo $scope->getName()."\n";
            if(in_array($scope->getName(), $excludedScopeNames)){
               $this->fail("Default scope returned");
            }
        }
        $this->assertEquals(3, count($filteredScopes));
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetScopesFilterByParamsUnsupportedParam() {
        print __METHOD__ . "\n";
        $scopeService = new \org\gocdb\services\Scope();
        $scopeService->setEntityManager($this->em);
        $configService = new \org\gocdb\services\Config();
        $configService->setLocalInfoFileLocation(__DIR__ . '/../../resources/sample_local_info1.xml');
        $scopeService->setConfigService($configService);

        $filterParams = array('_excludeNonDefault' => true);
        $filteredScopes = $scopeService->getScopesFilterByParams($filterParams, null);
    }

}
