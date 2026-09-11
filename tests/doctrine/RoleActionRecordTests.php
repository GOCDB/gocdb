<?php

//require_once 'PHPUnit/Extensions/Database/TestCase.php';
//require_once 'PHPUnit/Extensions/Database/DataSet/DefaultDataSet.php';
require_once dirname(__FILE__) . '/TestUtil.php';
require_once dirname(__FILE__) . '/bootstrap.php';

use Doctrine\ORM\EntityManager;


/*
 * Copyright (C) 2012 STFC
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

/**
 * Description of RoleActionRecordTests
 *
 * @author David Meredith
 */
class RoleActionRecordTests extends \PHPUnit\Framework\TestCase {

    private $em;

    /**
     * Overridden.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        echo "\n\n-------------------------------------------------\n";
        echo "Executing RoleActionRecordTests. . .\n";
    }

    /**
     * Returns the test database connection.
     * @return \PDO
     */
    protected function getConnection() {
        require_once dirname(__FILE__) . '/bootstrap_pdo.php';
        return getConnectionToTestDB();
    }

    /**
     * Sets up the fixture, e.g create a new entityManager for each test run
     * This method is called before each test method is executed.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->em = $this->createEntityManager();
        (new \Doctrine\Common\DataFixtures\Purger\ORMPurger($this->em))->purge();
    }

    /**
     * @todo Still need to setup connection to different databases.
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
    protected function assertPreConditions(): void {
        $con = $this->getConnection();
        $fixture = dirname(__FILE__) . '/truncateDataTables.xml';
        $tables = simplexml_load_file($fixture);

        foreach ($tables as $tableName) {
            //print $tableName->getName() . "\n";
            $sql = "SELECT * FROM " . $tableName->getName();
            $result = $con->query($sql)->fetchAll();
            //echo 'row count: '.count($result) ;
            if (count($result) != 0){
                throw new RuntimeException("Invalid fixture. Table has rows: " . $tableName->getName());
            }
        }
    }


    public function testRoleActionRecordCreateDelete() {
        print __METHOD__ . "\n";
        for($i=0; $i<10; ++$i){
            $rar = TestUtil::createRoleActionRecord();
            $this->em->persist($rar);
        }

        //Commit the entites to the database
        $this->em->flush();

        //Check this via the database
        $con = $this->getConnection();

        $result = $con->query("SELECT * FROM RoleActionRecords")->fetchAll();
        //Assert that data exist in the database for this service
        $this->assertEquals(10, count($result));
    }

    public function testGetOwnedEntityDiscriminator() {
        print __METHOD__ . "\n";
        $entity = TestUtil::createSampleNGI("NGI_1");

        // get the disriminator value
        $cmf = $this->em->getMetadataFactory();
        $meta = $cmf->getMetadataFor(get_class($entity));
        $entityTypeName = $meta->discriminatorValue;
        $this->assertEquals('ngi', $entityTypeName);
        $this->assertEquals('ngi', $entity->getType());
    }


}
