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
  }

  protected function assertPostConditions() {
  }

  /**
  * Tears down the fixture, for example, closes a network connection.
  * This method is called after a test is executed.
  */
  protected function tearDown() {
  }

  /**
  * executed only once, after all the testing methods
  */
  public static function tearDownAfterClass() {
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
  }

  public function testGetRoleNamesForProject(){
    print __METHOD__ . "\n";
    $roleActionService = new org\gocdb\services\RoleActionMappingService();
    $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml");

    $rolenamesOver = $roleActionService->getRoleTypeNamesForProject('EGI');
    $this->assertEquals(14, count($rolenamesOver));
    $rolenamesOver = $roleActionService->getRoleTypeNamesForProject(null);
    $this->assertEquals(14, count($rolenamesOver));
  }


  public function testGetEnablingRolesForTargetedAction1(){
    print __METHOD__ . "\n";
    $roleActionService = new org\gocdb\services\RoleActionMappingService();
    $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml");

    $expected = array('COD Staff', 'COD Administrator', 'EGI CSIRT Officer', 'Chief Operations Officer');
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'PRoJect', 'EGI');
    $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames));
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));

    $expected = array('COD Staff', 'COD Administrator', 'EGI CSIRT Officer', 'Chief Operations Officer', 'NGI Operations Manager', 'NGI Operations Deputy Manager', 'NGI Security Officer');
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_GRANT_ROLE", 'ngi', 'EGI');
    $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames));
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));

    $expected = array('Service Group Administrator');
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'SErviceGroup', 'EGI');
    $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames));
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));

    $expected = array(
      'NGI Operations Manager' => 'Ngi',
      'NGI Operations Deputy Manager' => 'Ngi',
      'NGI Security Officer' => 'Ngi',
      'Regional Staff (ROD)' => 'Ngi',
      'Regional First Line Support' => 'Ngi'
    );
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'ngi', 'egi');
    $this->assertArraySubset($expected, ($enablingRoleTypeNames));
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));

    // the action don't exist in the XML doc, so expect an empty array
    $expected = array();
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_dont_exist", 'ngi', 'egi');
    $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames));
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));

    // the Target entityType don't exist in the XML doc, so expect an empty array
    $expected = array();
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'ngix_dont_exist', 'egi');
    $this->assertArraySubset($expected, array_keys($enablingRoleTypeNames));
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));


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
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_REJECT_ROLE", 'site', 'egi');
    $this->assertArraySubset($expected, $enablingRoleTypeNames);
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_REVOKE_ROLE", 'site', 'egi');
    $this->assertArraySubset($expected, $enablingRoleTypeNames);
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));

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
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));

    $expected = array(
      'Service Group Administrator' => 'ServiceGroup',
    );
    $enablingRoleTypeNames = $roleActionService->getRoleTypeNamesThatEnableActionOnTargetObjectType("ACTION_EDIT_OBJECT", 'serviceGroup', NULL);
    $this->assertArraySubset($expected, $enablingRoleTypeNames);
    $this->assertEquals(count($expected), count($enablingRoleTypeNames));
  }


  public function testGetEnablingRolesForTargetedAction2(){
    print __METHOD__ . "\n";
    $roleActionService = new org\gocdb\services\RoleActionMappingService();
    $roleActionService->setRoleActionMappingsXsdPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings4.xsd");
    $roleActionService->setRoleActionMappingsXmlPath(__DIR__."/../../resources/roleActionMappingSamples/TestRoleActionMappings4.xml");

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


  public function testGetEnabledActionsForRoleType1() {
    print __METHOD__ . "\n";
    $roleActionService = new org\gocdb\services\RoleActionMappingService();
    $roleActionService->setRoleActionMappingsXsdPath(__DIR__ . "/../../resources/roleActionMappingSamples/TestRoleActionMappings4.xsd");
    $roleActionService->setRoleActionMappingsXmlPath(__DIR__ . "/../../resources/roleActionMappingSamples/TestRoleActionMappings4.xml");

    $enabledActionsOnTargets = $roleActionService->getEnabledActionsForRoleType('RoleA');


    $expectedActionsOnTargets = array();
    $expectedActionsOnTargets[] = array('A1', 'site');
    $expectedActionsOnTargets[] = array('A1', 'service');
    $expectedActionsOnTargets[] = array('A2', 'site');
    $expectedActionsOnTargets[] = array('A2', 'service');
    $expectedActionsOnTargets[] = array('A3', 'site');
    $expectedActionsOnTargets[] = array('A3', 'service');
    $expectedActionsOnTargets[] = array('AX', 'site');
    $expectedActionsOnTargets[] = array('AX', 'service');
    // assert
    $this->assertEquals($expectedActionsOnTargets, $enabledActionsOnTargets);

    $enabledActionsOnTargets = $roleActionService->getEnabledActionsForRoleType('RoleD');
    $expectedActionsOnTargets = array();
    $expectedActionsOnTargets[] = array('A4', 'site');
    $expectedActionsOnTargets[] = array('A4', 'service');
    $expectedActionsOnTargets[] = array('A4', 'project');
    $expectedActionsOnTargets[] = array('A5', 'site');
    $expectedActionsOnTargets[] = array('A5', 'service');
    $expectedActionsOnTargets[] = array('A5', 'project');
    $expectedActionsOnTargets[] = array('AX', 'site');
    $expectedActionsOnTargets[] = array('AX', 'service');
    $expectedActionsOnTargets[] = array('AX', 'project');
    // assert
    $this->assertEquals($expectedActionsOnTargets, $enabledActionsOnTargets);

  }

  public function testGetEnabledActionsForRoleType2() {
    print __METHOD__ . "\n";
    $roleActionService = new org\gocdb\services\RoleActionMappingService();
    $roleActionService->setRoleActionMappingsXmlPath(__DIR__ . "/../../resources/roleActionMappingSamples/TestRoleActionMappings5.xml");

    $enabledActionsOnTargets = $roleActionService->getEnabledActionsForRoleType('NGI Operations Manager');


    $expectedActionsOnTargets = array();
    $expectedActionsOnTargets[] = array('ACTION_EDIT_OBJECT', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_NGI_ADD_SITE', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_GRANT_ROLE', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_REJECT_ROLE', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_REVOKE_ROLE', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_EDIT_OBJECT', 'Site');
    $expectedActionsOnTargets[] = array('ACTION_SITE_ADD_SERVICE', 'Site');
    $expectedActionsOnTargets[] = array('ACTION_SITE_DELETE_SERVICE', 'Site');
    $expectedActionsOnTargets[] = array('ACTION_GRANT_ROLE', 'Site');
    $expectedActionsOnTargets[] = array('ACTION_REJECT_ROLE', 'Site');
    $expectedActionsOnTargets[] = array('ACTION_REVOKE_ROLE', 'Site');
    $expectedActionsOnTargets[] = array('ACTION_SITE_EDIT_CERT_STATUS', 'Site');
    // assert
    $this->assertEquals($expectedActionsOnTargets, $enabledActionsOnTargets);


    $enabledActionsOnTargets = $roleActionService->getEnabledActionsForRoleType('COD Staff');
    $expectedActionsOnTargets = array();
    $expectedActionsOnTargets[] = array('ACTION_EDIT_OBJECT', 'Project');
    $expectedActionsOnTargets[] = array('ACTION_GRANT_ROLE', 'Project');
    $expectedActionsOnTargets[] = array('ACTION_REJECT_ROLE', 'Project');
    $expectedActionsOnTargets[] = array('ACTION_REVOKE_ROLE', 'Project');
    $expectedActionsOnTargets[] = array('ACTION_GRANT_ROLE', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_REJECT_ROLE', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_REVOKE_ROLE', 'Ngi');
    $expectedActionsOnTargets[] = array('ACTION_SITE_EDIT_CERT_STATUS', 'Site');
    // assert
    $this->assertEquals($expectedActionsOnTargets, $enabledActionsOnTargets);
  }

}
