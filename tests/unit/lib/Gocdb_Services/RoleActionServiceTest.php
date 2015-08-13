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

 require_once __DIR__.'/../../../../lib/Gocdb_Services/RoleActionService.php';

/**
 * Description of RoleActionServiceTests
 *
 * @author David Meredith 
 */
class RoleActionServiceTest extends PHPUnit_Framework_TestCase {
    
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
        $roleActionService = new org\gocdb\services\RoleActionService(); 
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
        $roleActionService = new org\gocdb\services\RoleActionService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml"); 
        
        $rolenamesOver = $roleActionService->getRoleNamesForProject('EGI'); 
        $this->assertEquals(14, count($rolenamesOver)); 
        //print_r($rolenamesOver); 
        
        $rolenamesOver = $roleActionService->getRoleNamesForProject('WLCG'); 
        $this->assertEquals(4, count($rolenamesOver)); 
        //print_r($rolenamesOver); 
        
        $rolenamesOver = $roleActionService->getRoleNamesForProject('EUDAT'); 
        $this->assertEquals(5, count($rolenamesOver)); 
        //print_r($rolenamesOver); 
    }
    

    public function testGetEnablingRolesForTargetedAction1(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml"); 
        
        $expected = array('COD Staff', 'COD Administrator', 'EGI CSIRT Officer', 'Chief Operations Officer');
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'PRoJect', 'EGI'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 
        
        $expected = array('COD Staff', 'COD Administrator', 'EGI CSIRT Officer', 'Chief Operations Officer');
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_GRANT_ROLE", 'ngi', 'EGI'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        $expected = array('Service Group Administrator');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'SErviceGroup', 'EGI'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        $expected = array(
            'NGI Operations Manager' => 'Ngi', 
            'NGI Operations Deputy Manager' => 'Ngi', 
            'NGI Security Officer' => 'Ngi', 
            'Regional Staff (ROD)' => 'Ngi', 
            'Regional First Line Support' => 'Ngi');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'ngi', 'egi'); 
        $this->assertArraySubset($expected, ($enablingRoleNames)); 

        // the action don't exist in the XML doc, so expect an empty array 
        $expected = array();  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_dont_exist", 'ngi', 'egi'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        // the Target entityType don't exist in the XML doc, so expect an empty array
        $expected = array();  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'ngix_dont_exist', 'egi'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 


        $expected = array(
            'NGI Operations Deputy Manager' => 'Ngi', 
            'NGI Operations Manager' => 'Ngi', 
            'NGI Security Officer' => 'Ngi', 
            'Site Security Officer' => 'Site',
            'Site Operations Deputy Manager' => 'Site', 
            'Site Operations Manager' => 'Site', 
            ); 
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_GRANT_ROLE", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleNames);
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_REJECT_ROLE", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleNames);
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_REVOKE_ROLE", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleNames);

        $expected = array(
            'NGI Operations Deputy Manager' => 'Ngi', 
            'NGI Operations Manager' => 'Ngi', 
            'NGI Security Officer' => 'Ngi', 
            'COD Staff' => 'Project', 
            'COD Administrator' => 'Project', 
            'EGI CSIRT Officer' => 'Project', 
            'Chief Operations Officer' => 'Project'
            ); 
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("ACTION_SITE_EDIT_CERT_STATUS", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleNames);

    }


    public function testGetEnablingRolesForTargetedAction2(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings4.xml"); 

        // EGI project 
        $expected = array('RoleH' => 'Project');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("AZ", 'project', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleNames); 
        //print_r($enablingRoleNames); 
        
        $expected = array();  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("AZ", 'site', 'egi'); 
        $this->assertArraySubset($expected, $enablingRoleNames); 

        // EGI2 project
        // to do action 'AX' on a 'site' requires roles: 
        $expected = array('RoleA','RoleB','RoleC','RoleD','RoleE');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("AX", 'site', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 
        //print_r($enablingRoleNames); 

        // to do action 'AX' on a 'service' requires roles: 
        $expected = array('RoleA','RoleB','RoleC','RoleD','RoleE');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("AX", 'service', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        // to do action 'AX' on a 'project' requires roles: 
        $expected = array('RoleD','RoleE');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("AX", 'project', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        // to do action 'A1' on a 'site' requires roles: 
        $expected = array('RoleA','RoleB','RoleC');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("A1", 'site', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        // to do action 'A5' on a 'site' requires roles: 
        $expected = array('RoleD','RoleE');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("A5", 'site', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        // to do action 'A1' on a 'service' requires roles: 
        $expected = array('RoleA','RoleB','RoleC');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("A1", 'service', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

        // to do action 'AY' on a 'project' requires roles: 
        $expected = array('RoleF');  
        $enablingRoleNames = $roleActionService->getRolesThatEnableActionOnTargetObjectType("AY", 'project', 'egi2'); 
        $this->assertArraySubset($expected, array_keys($enablingRoleNames)); 

    }
 
    /**
     * @expectedException \LogicException
     * @expectedExceptionCode 22
     */
    public function testInvalidDocMultipleDefaultElements(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings1.xml"); 
        $roleActionService->getRolesThatEnableActionOnTargetObjectType("action", "hello", "world"); 

    }

    /**
     * @expectedException \LogicException
     */
//    public function testInvalidDocMultipleRoleActionMappingsForProject(){
//        print __METHOD__ . "\n";
//        $roleActionService = new org\gocdb\services\RoleActionService(); 
//        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings2.xml"); 
//        $roleActionService->getEnablingRolesForTargetedAction("action", "hello", "EGI"); 
//    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionCode 21 
     */
    public function testRequestedProjectDontExist(){
        print __METHOD__ . "\n";
        $roleActionService = new org\gocdb\services\RoleActionService(); 
        $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings3.xml"); 
        $roleActionService->getRolesThatEnableActionOnTargetObjectType("action", "hello", "projDontExist"); 
    }


    
}
