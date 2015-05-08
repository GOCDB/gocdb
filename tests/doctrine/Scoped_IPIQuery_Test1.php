<?php

//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php';

use Doctrine\ORM\EntityManager;

require_once dirname(__FILE__) . '/bootstrap.php';
//require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/PI/QueryBuilders/ScopeQueryBuilder.php';
//require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/PI/QueryBuilders/Helpers.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/PI/GetNGI.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/PI/GetSite.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/PI/GetService.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/PI/GetServiceGroup.php';
//
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/Factory.php';
require_once dirname(__FILE__) . '/../../lib/Gocdb_Services/PI/QueryBuilders/ExtensionsParser.php';


/**
 * Creates selected <code>IPIQuery</code> objects that perform scoped queries on 
 * <code>IScopedEntity</code> objects, and assert that the query objects return 
 * the expected number of scoped entities when querying against known fixture data. 
 * <p>
 * Covers the following IPIQuery objects: GetNGI, GetSite, GetServiceEndpoint, GetServiceGroup
 * 
 * @author David Meredith
 */
class Scoped_IPIQuery_Test1 extends PHPUnit_Extensions_Database_TestCase {

    private $em;

    /**
     * Overridden. 
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing Scoped_IPIQuery_Test1. . .\n";
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
     * @return EntityManager
     */
    private function createEntityManager() {
        require dirname(__FILE__) . '/bootstrap_doctrine.php';
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

        foreach ($tables as $tableName) {
            //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->createQueryTable('results_table', $sql);
            //echo 'row count: '.$result->getRowCount() ; 
            if ($result->getRowCount() != 0)
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
        }
    }

   /**
     * See class doc. 
     */
    public function testGet_Scoped_IPIQuery() {
        print __METHOD__ . "\n";
        include __DIR__ . '/resources/sampleFixtureData2.php';

        $query = new \org\gocdb\services\GetNGI($this->em);
        $this->doScopedQueryCalls($query);

        $query = new \org\gocdb\services\GetSite($this->em);
        $this->doScopedQueryCalls($query);

        $query = new \org\gocdb\services\GetService($this->em);
        $this->doScopedQueryCalls($query);

        $query = new \org\gocdb\services\GetServiceGroup($this->em);
        $this->doScopedQueryCalls($query);

        //$query = new \org\gocdb\services\GetDowntime($this->em);
        //$this->doScopedQueryCalls($query);
    }


    private function doScopedQueryCalls(\org\gocdb\services\IPIQuery $query) {

        // empty scope string is a wildcard for 'any' value (i.e. scope can be any value) 
        $this->queryForIScopedEntity($query, array('scope' => '', 'scope_match' => 'all'), 5); 
        $this->queryForIScopedEntity($query, array('scope' => '', 'scope_match' => 'any'), 5); 
        try {
            $this->queryForIScopedEntity($query, null, 5);  
            $this->fail("Expected and InvalidArgumentException"); 
        } catch(InvalidArgumentException $ex){
            // ok, we expected this so continue on below 
        }
        // TODO - All Tests below involve missing/empty/null 'scope' and 'scope_match' 
        // values. This causes the IPIQuery object to invoke the Factory::getConfigService() 
        // to get the default 'scope' and 'scope_match' values. 
        // If we were being strict OO practitioners, then the IPIQuery 
        // objects shouldn't really depend on the Factory or ConfigService. 
        // Instead, these defaults could be injected from calling/client code 
        // (with an additional a hardwired fallback of 'scope_match = all' and 
        // 'no' default scope defined within the IPIQuery).  
        // 
        // 5 queries below use the default scope and default scope_match values  
        $this->queryForIScopedEntity($query, array('scope_match' => 'all'), 0); // no scope (uses default) 
        $this->queryForIScopedEntity($query, array('scope_match' => ''), 0); // no scope and empty scope_match
        $this->queryForIScopedEntity($query, array(), 0); // no scope, no scope_match (uses defaults)  
        $this->queryForIScopedEntity($query, array('scope' => null, 'scope_match' => 'any'), 0); // no scope (uses default) 
        $this->queryForIScopedEntity($query, array('scope' => null, 'scope_match' => 'all'), 0);// no scope (uses default)  

        // Test with scope_match = all  
        $this->queryForIScopedEntity($query, array('scope' => 'Scope0', 'scope_match' => 'all'), 4);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope0,Scope1', 'scope_match' => 'all'), 3);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope2', 'scope_match' => 'all'), 2);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope3,Scope4,Scope5', 'scope_match' => 'all'), 1);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope0,Scope1,ScopeX', 'scope_match' => 'all'), 0);
        // Test with scope_match = any 
        $this->queryForIScopedEntity($query, array('scope' => 'Scope0', 'scope_match' => 'any'), 4);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope0,Scope1,ScopeX', 'scope_match' => 'any'), 4);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope1', 'scope_match' => 'any'), 3);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope1,ScopeX,ScopeXX', 'scope_match' => 'any'), 3);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope1,Scope2', 'scope_match' => 'any'), 3);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope2,Scope3,Scope4', 'scope_match' => 'any'), 2);
        $this->queryForIScopedEntity($query, array('scope' => 'Scope5,ScopeX,ScopeXX', 'scope_match' => 'any'), 1);
        $this->queryForIScopedEntity($query, array('scope' => 'ScopeX0,ScopeX1,ScopeX1', 'scope_match' => 'any'), 0);
    }

    private function queryForIScopedEntity(\org\gocdb\services\IPIQuery $query, $params, $expectedCount) {
        $query->validateParameters($params);
        $query->createQuery();
        $results = $query->executeQuery();
        $this->assertTrue($expectedCount == count($results));
        return $results; 
    }

}

//close class
?>
