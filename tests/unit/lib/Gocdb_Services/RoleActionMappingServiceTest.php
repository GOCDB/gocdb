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

 require_once __DIR__.'/../../../../lib/Gocdb_Services/RoleActionMappingService.php';

/**
 * Test case for the {@see \org\gocdb\services\RoleActionMappingService} service class. 
 * The tests utilise the sample xml files in test/unit/resources.  
 *
 * @author David Meredith 
 */
class RoleActionMappingServiceTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Called once, before any of the tests are executed.
     */
    public static function setUpBeforeClass() {
        //print __METHOD__ . "\n";
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before each test method is executed.
     */
    protected function setUp() {
    }

    /**
     * Like setUp(), this is called before each test method to
     * assert any pre-conditions required by tests.
     */
    protected function assertPreConditions() {
        //print __METHOD__ . "\n";
    }

    protected function assertPostConditions() {
        //print __METHOD__ . "\n";
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        //print __METHOD__ . "\n";
    }

    /**
     * executed only once, after all the testing methods
     */
    public static function tearDownAfterClass() {
        //print __METHOD__ . "\n";
    }

    protected function onNotSuccessfulTest(Exception $e) {
        print __METHOD__ . "\n";
        throw $e;
    }


    public function testValidateRoleActionMappingsAgainstSchema() {
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionMappingService(); 
        $errors = $roleActionService->validateRoleActionMappingFileAgainstXsd(); 
        $this->assertEmpty($errors); 
        /*$xml = new \DOMDocument();
        $xml->load(__DIR__."/../../../../config/RoleActionMappings.xml");
        if (!$xml->schemaValidate(__DIR__."/../../../../config/RoleActionMappingsSchema.xsd")) {
            $this->fail('could not validate instance doc');
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                print libxml_display_error($error);
            }
        }*/
    }

    public function testGetRoleNamesForProject(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml"); 
        
        $rolenamesOver = $roleActionService->getRoleTypeNamesForProject('EGI'); 
        $this->assertEquals(14, count($rolenamesOver)); 
        //print_r($rolenamesOver); 
        
        $rolenamesOver = $roleActionService->getRoleTypeNamesForProject('WLCG'); 
        $this->assertEquals(4, count($rolenamesOver)); 
        //print_r($rolenamesOver); 
        
        $rolenamesOver = $roleActionService->getRoleTypeNamesForProject('EUDAT'); 
        $this->assertEquals(5, count($rolenamesOver)); 
        //print_r($rolenamesOver); 
    }
    

    public function testGetEnablingRolesForTargetedAction1(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml"); 
        
        $expected = array('COD Staff', 'COD Administrator', 'EGI CSIRT Officer', 'Chief Operations Officer');
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'PRoJect', 'EGI'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        $expected = array('COD Staff', 'COD Administrator', 'EGI CSIRT Officer', 'Chief Operations Officer');
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_GRANT_ROLE", 'ngi', 'EGI'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        $expected = array('Service Group Administrator');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'SErviceGroup', 'EGI'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        $expected = array(
            'NGI Operations Manager' => 'Ngi', 
            'NGI Operations Deputy Manager' => 'Ngi', 
            'NGI Security Officer' => 'Ngi', 
            'Regional Staff (ROD)' => 'Ngi', 
            'Regional First Line Support' => 'Ngi');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'ngi', 'egi'); 
        $this->assertArraySubset($expected, ($enablingRoleTypeNames)); 

        // the action don't exist in the XML doc, so expect an empty array 
        $expected = array();  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_dont_exist", 'ngi', 'egi'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        // the Target entityType don't exist in the XML doc, so expect an empty array
        $expected = array();  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'ngix_dont_exist', 'egi'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 


        $expected = array(
            'NGI Operations Deputy Manager' => 'Ngi', 
            'NGI Operations Manager' => 'Ngi', 
            'NGI Security Officer' => 'Ngi', 
            'Site Security Officer' => 'Site',
            'Site Operations Deputy Manager' => 'Site', 
            'Site Operations Manager' => 'Site', 
            ); 
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_GRANT_ROLE", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleTypeNames);
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_REJECT_ROLE", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleTypeNames);
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_REVOKE_ROLE", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleTypeNames);

        $expected = array(
            'NGI Operations Deputy Manager' => 'Ngi', 
            'NGI Operations Manager' => 'Ngi', 
            'NGI Security Officer' => 'Ngi', 
            'COD Staff' => 'Project', 
            'COD Administrator' => 'Project', 
            'EGI CSIRT Officer' => 'Project', 
            'Chief Operations Officer' => 'Project'
            ); 
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_SITE_EDIT_CERT_STATUS", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleTypeNames);

    }


    public function testGetEnablingRolesForTargetedAction2(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings4.xml"); 

        // EGI project 
        $expected = array('RoleH' => 'Project');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("AZ", 'project', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleTypeNames); 
        //print_r($enablingRoleNames); 
        
        $expected = array();  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("AZ", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleTypeNames); 

        // EGI2 project
        // to do action 'AX' on a 'site' requires roles: 
        $expected = array('RoleA','RoleB','RoleC','RoleD','RoleE');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("AX", 'site', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 
        //print_r($enablingRoleNames); 

        // to do action 'AX' on a 'service' requires roles: 
        $expected = array('RoleA','RoleB','RoleC','RoleD','RoleE');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("AX", 'service', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        // to do action 'AX' on a 'project' requires roles: 
        $expected = array('RoleD','RoleE');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("AX", 'project', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        // to do action 'A1' on a 'site' requires roles: 
        $expected = array('RoleA','RoleB','RoleC');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("A1", 'site', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        // to do action 'A5' on a 'site' requires roles: 
        $expected = array('RoleD','RoleE');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("A5", 'site', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        // to do action 'A1' on a 'service' requires roles: 
        $expected = array('RoleA','RoleB','RoleC');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("A1", 'service', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

        // to do action 'AY' on a 'project' requires roles: 
        $expected = array('RoleF');  
        $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("AY", 'project', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames)); 

    }
 
    /**
     * @expectedException \LogicException
     * @expectedExceptionCode 22
     */
    public function testInvalidDocMultipleDefaultElements(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings1.xml"); 
        $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("action", "hello", "world"); 

    }

    public function testInvalidDocMultipleRoleActionMappingsForProject(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestInvalidRoleActionMappings2.xml"); 
        try { 
            $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("action", "hello", "EGI"); 
            $this->fail("shouldn't have got here"); 
        } catch (\Exception $ex){
            //print_r($ex->getMessage()) ; 
            //$errors = libxml_get_errors(); 
            //var_dump($errors); 
        }
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionCode 21 
     */
    public function testRequestedProjectDontExist(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionMappingService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings3.xml"); 
        $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("action", "hello", "projDontExist"); 
    }


    
}
