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
 require_once __DIR__ . '/extensionPropertyAbstract.php';

/**
 * DBUnit test class for Service methods of the write API
 *
 */
class WriteAPIserviceMethodsTests extends extensionPropertyAbstract {
  #Some sample data to test with. Case mixing is deliberate - the API should be case insensitive
  private $sampleValuesMandatoryFields = array('hoSTNAME' => 'tEST.123.abc',
                                               'eMail'=>'some_thing@SomeDomain.OR.Other');
  private $sampleValuesMandatoryFieldsUpdate = array('hostname' => 'tEST.123.abcupdate',
                                                     'email'=>'some_thing@SomeNewDomain.OR.Other');
  private $sampleValuesOptionalFields = array('DescriptioN'=> 'Just S0me Service, you know',
                                              'hoST_dn'=>'/something=Something else/somethingmmore=something',
                                              'Host_IP'=>'123.1.23.456',
                                              'Host_IP_V6'=>'1234:5678:90ab:0000:0000:0003:0020:00f0',
                                              'Host_OS'=>'best OS',
                                              'Host_Arch'=> 'archEtecture.',
                                              'URL'=>'https://some.url');
  private $sampleValuesOptionalFieldsUpdate = array('DescriptioN'=> 'Just S0me Service, you knowUpdate',
                                                    'host_dn'=>'/something=Something else/somethingmmore=somethingupdated',
                                                    'host_ip'=>'123.1.23.457',
                                                    'host_ip_v6'=>'1234:5678:90ab:0000:0000:0003:0020:00f1',
                                                    'host_os'=>'best OS Updated',
                                                    'host_arch'=> 'archEtecture. Updated',
                                                    'url'=>'https://some.urlupdated');
  #The order here matters! (the tests will update each in turn and certain combinations are forbidden)
  private $boolFields = array('pRoductIOn'=>false,'moNitOred'=>false,  'BEta'=>true,  'NotIfy'=>true);
  private $boolFieldsStart = array('notify'=>false, 'beta'=>false, 'monitored'=>true, 'production'=>true);



  /**
  * Overridden.
  */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    echo "\n\n-------------------------------------------------\n";
    echo "Executing WriteAPIserviceMethodsTests. . .\n";
  }


  /**
   * Tests relating to extension properties in the parent class require an
   * entity to test against. This provides it.
   * @return Service service to test extension properties against
   */
  protected function getSampleEntity(){
    return $this->createSampleService();
  }

  /**
   * Tests relating to extension properties in the parent class require need to
   * know what type of entity they are dealing with.
   *
   * @return string 'service'
   */
  protected function getSampleEntityType(){
    return 'service';
  }

  /**
   * The fieldnames used for services by the GOCDB schema, and so by the API, do
   * not match the names we use internally for or DB fields in all cases. This
   * function takes the name used in the API and if there is a known mismatch,
   * corrects it. The db fields are also all lower case, so we correct for that as well.
   * @param  string $APIField field name as found in API
   * @return string           field name as used in DB, assuming missmatches all known
   */
  private function mapToDBFieldName($APIField) {
    switch (strtolower($APIField)) {
      case 'host_dn':
        return 'dn';
      case 'host_ip':
        return 'ipaddress';
      case 'host_ip_v6':
        return 'ipv6address';
      case 'host_os':
        return 'operatingsystem';
      case 'host_arch':
        return 'architecture';
      default:
        return strtolower($APIField);
    }
  }

  /**
   * Assert that the service as found in the database contains the values as
   * updated by the API
   * @param  integer $serviceId     id of the service
   * @param  array   $serviceValues (DB Column Name =>  Expected Value);
   */
  public function assertServiceAsExpected($serviceId, $serviceValues) {
    $con = $this->getConnection();

    foreach ($serviceValues as $valueType => $value) {
      $valueType = $this->mapToDBFieldName($valueType);
      $sql = "SELECT * FROM Services WHERE id = '$serviceId' AND $valueType = '$value'";
      $result = $con->createQueryTable('', $sql);
      $this->assertEquals(1, $result->getRowCount());
    }
  }

  /**
   * Assert the boolean properties of the service are as expected
   * @param  integer $serviceId     id of the service
   * @param  array   $serviceValues (DB Column Name =>  Expected Value);
   */
  public function assertServiceBooleansAsExpected($serviceId, $serviceBoolVals) {
    $serviceValsArray = array();
    foreach ($serviceBoolVals as $field => $bool) {
      if ($bool) {
        $serviceValsArray[$field] = '1';
      } else {
        $serviceValsArray[$field] = '0';
      }
    }

    $this->assertServiceAsExpected ($serviceId, $serviceValsArray);
  }

  public function test_postSingleServiceProps() {
    print __METHOD__ . "\n";

    $service = $this->createSampleService();

    #It should be possible to update each of the non-mandatory, non-boolean service Site_Properties
    foreach ($this->sampleValuesOptionalFields as $field => $value) {
      #case mixuing intentional as write API should be mostly case insensitve
      $APIOutput = $this->wellFormattedWriteAPICall ('PoST', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'serVice', $service->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      #check the change happened
      $this->assertServiceAsExpected($service->getId(),array($field =>$value));
    }

    #Now try putting properties that are already defined for our sample service
    #First, store the values in the mandatory fields are the start so we can check
    #they are unchanged later
    $startingMandFileds =  array('hostname' => $service->getHostname(), 'email'=>$service->getEmail());

    #create array of fields we will try and change and the values we will change them to
    $changes = $this->sampleValuesOptionalFieldsUpdate + $this->sampleValuesMandatoryFieldsUpdate;

    #Try and change each field
    foreach ($changes as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('POST', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'service', $service->getId(), $field);

      #The field is already set, so for a post request we expect a 409 response
      $this->assertequals(409,$APIOutput['httpResponseCode']);
    }

    #Then we check starting values are unchanged
    $this->assertServiceAsExpected($service->getId(),$this->sampleValuesOptionalFields+$startingMandFileds);


    #Now try putting with some boolean properties. These should fail for POST
    #Try and change each field
    foreach ($this->boolFields as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('POST', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'service', $service->getId(), $field);

      #Expect 405 error
      $this->assertequals(405,$APIOutput['httpResponseCode']);
    }

    #Then we check none of the above values have changed
    $this->assertServiceBooleansAsExpected($service->getId(),$this->boolFieldsStart);
  }

  /**
   * Check for a correct repsonse for a post request on a service where the
   * field name is incorrect
   */
  public function test_postSingleIncorrectlyNamedSerProp(){
    print __METHOD__ . "\n";

    $service = $this->createSampleService();

    #First, store the values in the mandatory fields are the start so we can check
    #they are unchanged later
    $startingMandFileds =  array('hostname' => $service->getHostname(), 'email'=>$service->getEmail());

    $APIOutput = $this->wellFormattedWriteAPICall ('POST', $this->singleValToJsonRequest('string'), $this->validAuthIdent, 'service', $service->getId(), 'notARealField');

    #That isn't a real field, so we expect a 400 response
    $this->assertequals(400,$APIOutput['httpResponseCode']);

    #Then we check none of the starting values have changed
    $this->assertServiceAsExpected($service->getId(),$startingMandFileds);
    $this->assertServiceBooleansAsExpected($service->getId(),$this->boolFieldsStart);
  }



  public function test_putSingleServiceProps() {
    print __METHOD__ . "\n";

    $service = $this->createSampleService();

    #It should be possible to update each of the non-boolean service Site_Properties
    foreach ($this->sampleValuesOptionalFields + $this->sampleValuesMandatoryFields as $field => $value) {
      #case mixing intentional as write API should be mostly case insensitive
      $APIOutput = $this->wellFormattedWriteAPICall ('PUt', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'serVice', $service->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      #check the change happened
      $this->assertServiceAsExpected($service->getId(),array($field =>$value));
    }

    #It should also be possible to update each of the booleans service properties
    foreach ($this->boolFields as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'service', $service->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      $this->assertServiceBooleansAsExpected($service->getId(),array($field =>$value));
    }

    #And try switching the boolean values back (we have had a number of bugs
    #where switching in one direction works but not the other)
    foreach ($this->boolFieldsStart as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'service', $service->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      $this->assertServiceBooleansAsExpected($service->getId(),array($field =>$value));
    }

    #Try and change all non-boolean properties again and check it is successful (which it should be for PUT)

    #create array of fields we will try and change and the values we will change them to
    $changes = $this->sampleValuesOptionalFieldsUpdate + $this->sampleValuesMandatoryFieldsUpdate;

    #Try and change each field
    foreach ($changes as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'service', $service->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      #check the change happened
      $this->assertServiceAsExpected($service->getId(),array($field =>$value));
    }
  }


  /**
   * Check for a correct repsonse for a post request on a service where the
   * field name is incorrect
   */
  public function test_putSingleIncorrectlyNamedSerProp(){
    print __METHOD__ . "\n";

    $service = $this->createSampleService();

    #First, store the values in the mandatory fields are the start so we can check
    #they are unchanged later
    $startingMandFileds =  array('hostname' => $service->getHostname(), 'email'=>$service->getEmail());

    $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest('string'), $this->validAuthIdent, 'service', $service->getId(), 'notARealField');

    #That isn't a real field, so we expect a 400 response
    $this->assertequals(400,$APIOutput['httpResponseCode']);

    #Then we check none of the starting values have changed
    $this->assertServiceAsExpected($service->getId(),$startingMandFileds);
    $this->assertServiceBooleansAsExpected($service->getId(),$this->boolFieldsStart);
  }

}
