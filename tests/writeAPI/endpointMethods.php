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
 * DBUnit test class for endpoint methods of Write API
 *
 */
class WriteAPIendpointMethodsTests extends extensionPropertyAbstract {
  #Some sample data to test with. Case mixing is deliberate - the API should be case insensitive
  private $sampleValuesMandatoryFields = array('NaMe' => 'SoMe Name',
                                               'UrL'=>'https://some.url');
  private $sampleValuesMandatoryFieldsUpdate = array('name' => 'Some New Name',
                                               'url'=>'https://some.new.url');
  private $sampleValuesOptionalFields = array('INTerfacEName' => 'some-Name',
                                              'DescriptioN'=> 'Just S0me Endpoint, you know',
                                              'EMaiL'=>'some@user.here');
  private $sampleValuesOptionalFieldsUpdate = array('interfacename' => 'some-NewName',
                                                    'description'=> 'Just Some new Endpoint, you know',
                                                    'emaiL'=>'somenew@user.here');
  private $boolFields = array('moNitOred'=>true);
  private $boolFieldsStart = array('monitored'=>false);



  /**
  * Overridden.
  */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    echo "\n\n-------------------------------------------------\n";
    echo "Executing WriteAPIendpointMethodsTests. . .\n";
  }

  /**
   * Tests relating to extension properties in the parent class require an
   * entity to test against. This provides it.
   * @return Endpoint endpoint to test extension properties against
   */
  protected function getSampleEntity(){
    return $this->createSampleEndpoint();
  }

  /**
   * Tests relating to extension properties in the parent class need to
   * know what type of entity they are dealing with.
   *
   * @return string 'endpoint'
   */
  protected function getSampleEntityType(){
    return 'endpoint';
  }

  /**
   * Assert that the endpoint as found in the database contains the values as
   * updated by the API
   * @param  integer $endpointId    id of the endpoint
   * @param  array $endpointValues  (DB Column Name =>  Expected Value);
   */
  public function assertEndpointAsExpected($endpointId, $endpointValues) {
    $con = $this->getConnection();

    foreach ($endpointValues as $valueType => $value) {
      $sql = "SELECT * FROM EndpointLocations WHERE id = '$endpointId' AND $valueType = '$value'";
      $result = $con->createQueryTable('', $sql);
      $this->assertEquals(1, $result->getRowCount());
    }
  }

  /**
   * Assert the boolean propertues of the endpoint are as expected
   * @param  integer $endpointId     id of the endpoint
   * @param  array   $endpointValues (DB Column Name =>  Expected Value);
   */
  public function assertEndpointBooleansAsExpected($endpointId, $endpointBoolVals) {
    $this->assertEndpointAsExpected ($endpointId, $this->intValArray($endpointBoolVals));
  }

  /**
   * Assert that there is only one endpoint associated with $service, then checked
   * the values of that endpoint against $values
   * @param  SERVICE $service the service to check for an endpoint
   * @param  array   $values  values to check  array('NameOfFild'=>'ValueOfField',...);
   */
  public function assertWholeAndOnlyEndpointAsExpected($service, $values) {
    $serviceId = $service->getId();

    $con = $this->getConnection();
    $sql = "SELECT * FROM EndpointLocations WHERE service_id = '$serviceId' ";
    $result = $con->createQueryTable('', $sql);

    #Assert that there is one and only one endpoint associated with our service
    $this->assertEquals(1, $result->getRowCount());

    #Check the values against those in the database
    $resultArray = array_change_key_case($result->getRow(0));

        foreach (array_change_key_case($values) as $field => $value) {
      $this->assertEquals($value, $resultArray[$field]);
    }
  }

  /**
   * Takes an array and returns an array with the same keys, but with integer
   * values
   * @param  array $array
   * @return array
   */
  private function intValArray ($array) {
    $ints = array ();
    foreach ($array as $key => $value) {
        $ints[$key] = intval($value);
    }
    return $ints;
  }

  /**
   * Assert that if an endpoint should have been deleted it has, and if it shouldn't
   * it hasn't.
   *
   * @param  bool    $shouldBedeleted if true the endpoint should no longer exist
   * @param  integer $endpointId      id of the endpoint to be checked
   */
  public function assertEndpointDeletionStatus($shouldBeDeleted, $endpointId) {
    $con = $this->getConnection();
    $sql = "SELECT * FROM EndpointLocations WHERE id = '$endpointId'";
    $result = $con->createQueryTable('', $sql);
    if ($shouldBeDeleted) {
      $this->assertEquals(0, $result->getRowCount());
    } else {
      $this->assertEquals(1, $result->getRowCount());
    }
  }

  /**
   * Test use of POST to add and attempt to replace a whole endpoint. Include an
   * interface name
   */
  public function test_PostWholeEndpointWithInterface() {
    print __METHOD__ . "\n";
    $this->addWholeEndpoint('PoSt', true);
  }

  /**
   * Test use of POST to add and attempt to replace a whole endpoint without
   * including an interface name
   */
  public function test_PostWholeEndpointWithoutInterface() {
    print __METHOD__ . "\n";
    $this->addWholeEndpoint('PoSt', false);
  }

  /**
   * Test use of PUT to add and attempt to replace a whole endpoint. Include an
   * interface name
   */
  public function test_PutWholeEndpointWithInterface() {
    print __METHOD__ . "\n";
    $this->addWholeEndpoint('PUt', true);
  }

  /**
   * Test use of PUT to add and attempt to replace a whole endpoint without
   * including an interface name
   */
  public function test_PutWholeEndpointWithoutInterface() {
    print __METHOD__ . "\n";
    $this->addWholeEndpoint('PUt', false);
  }

  /**
   * Add a whole endpoint with the API and check it has behaved as expected.
   * Then add an endpoint with the same name as the first anf check behavoiour
   * @param string  $method        POST or PUT
   * @param boolean $withInterface should an interfacename be included with the request
   */
  private function addWholeEndpoint($method, $withInterface) {
    $service = $this->createSampleService();

    $name = $this->sampleValuesMandatoryFields['NaMe'];
    $valuesLessBool = $this->sampleValuesOptionalFields + $this->sampleValuesMandatoryFields ;

    if(!$withInterface) {
      unset($valuesLessBool['INTerfacEName']);
    }

    $values = $valuesLessBool + $this->boolFieldsStart;

    #mixed case deliberate - the API should be case insensive
    $APIOutput = $this->wellFormattedWriteAPICall ($method, json_encode($values), $this->validAuthIdent, 'servIce', $service->getId(), 'enDpoint');

    #It was a valid request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutput['httpResponseCode']);

    #check the new endpoint was created with the correct values
    $valuesToCheck = $valuesLessBool + $this->intValArray($this->boolFieldsStart);
    #If no interface name is provided, check it defaulted to the service type of the parent service
    if(!$withInterface) {
      $valuesToCheck['interfacename'] =  $service->getServiceType();
    }
    $this->assertWholeAndOnlyEndpointAsExpected($service, $valuesToCheck);

    #Now we will try adding an endpoint with the same name as the previous one
    $newValuesLessBool = $this->sampleValuesOptionalFieldsUpdate + $this->sampleValuesMandatoryFieldsUpdate;
    $newValuesLessBool['name'] = $name;
    if(!$withInterface) {
      unset($newValuesLessBool['interfacename']);
    }
    $newValues = $newValuesLessBool + $this->boolFields;


    $APIOutput = $this->wellFormattedWriteAPICall ($method, json_encode($newValues), $this->validAuthIdent, 'service', $service->getId(), 'endpoint');

    #An endpoint with that name exists, so it should fail for POST and overwrite for PUT
    if(strtolower($method)=='put') {
      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      #check the change happened
      $valuesToCheck = $newValuesLessBool + $this->intValArray($this->boolFields);
      $this->assertWholeAndOnlyEndpointAsExpected($service, $valuesToCheck);
    } else {
      $this->assertequals(409,$APIOutput['httpResponseCode']);

      #check the endpoint remains unchanged
      $valuesToCheck = $valuesLessBool + $this->intValArray($this->boolFieldsStart);
      $this->assertWholeAndOnlyEndpointAsExpected($service, $valuesToCheck);
    }
  }

  /**
   * Test deleting an endpoint whilst not providing a request body
   */
  public function test_endpointDeletionNoRequest(){
    print __METHOD__ . "\n";
    #null is what the value the request body will have in an API call when no body
    #is present
    $this->successfulEndpointDeletion(null);
  }

  /**
   * Test deleting an endpoint whilst providing an empty string for the request body
   */
  public function test_endpointDeletionEmptyRequest(){
    print __METHOD__ . "\n";
    $this->successfulEndpointDeletion('');
  }

  private function successfulEndpointDeletion($request){
    $endpoint = $this->createSampleEndpoint();
    $this->endpointDeletion(true, $endpoint->getId(), $endpoint->getService()->getId(),$request, 204);
  }

  /**
   * Attempt to delete an endpoint, but specify the wrong endpoint ID specify no
   * request contents
   */
  public function test_endpointDeletionWrongEndpointIdNoRquest(){
    print __METHOD__ . "\n";
    #null is what the value the request body will have in an API call when no body
    #is present
    $this->endpointDeletionWrongEndpointId(null);
  }

  /**
   * Attempt to delete an endpoint, but specify the wrong endpoint ID specify
   * empty contents
   */
  public function test_endpointDeletionWrongEndpointIdEmptyRquest(){
    print __METHOD__ . "\n";
    $this->endpointDeletionWrongEndpointId('');
  }

  /**
   * Attempt to delete an endpoint with wrong ID specified
   * @param  [type] $request body of http request for attempt
   */
  private function endpointDeletionWrongEndpointId($request) {
    $endpoint = $this->createSampleEndpoint();
    $wrongId = $endpoint->getId() + 1;
    $this->endpointDeletion(false, $wrongId, $endpoint->getService()->getId(),$request, 404, $endpoint->getId());
  }

  /**
   * Attempt to delete an endpoint, but specify the wrong service ID specify no
   * request contents
   */
  public function test_endpointDeletionWrongServiceIdNoRquest(){
    print __METHOD__ . "\n";
    #null is wht the value the request body will have in an API call when no body
    #is present
    $this->endpointDeletionWrongServiceId(null);
  }

  /**
   * Attempt to delete an endpoint, but specify the wrong service ID specify
   * empty contents
   */
  public function test_endpointDeletionWrongServiceIdEmptyRquest(){
    print __METHOD__ . "\n";
    $this->endpointDeletionWrongServiceId('');
  }

  /**
   * Attempt to delete an endpoint with wrong service ID specified
   * @param  string $request body of http request for attempt
   */
  private function endpointDeletionWrongServiceId($request) {
    $endpoint = $this->createSampleEndpoint();
    $wrongService = $this->createSampleService("2");
    $this->endpointDeletion(false, $endpoint->getId(), $wrongService->getId(),$request, 400);
  }

  /**
   * Attempt to delete endpoint whilst specify request contents
   */
   public function test_endpointDeletionWithRequestContents() {
     print __METHOD__ . "\n";
     $endpoint = $this->createSampleEndpoint();
     $request = json_encode ($this->sampleValuesMandatoryFields + $this->sampleValuesOptionalFields + $this->boolFieldsStart);
     $this->endpointDeletion(false, $endpoint->getId(), $endpoint->getService()->getId(),$request, 400);
   }

  /**
   * Test the deletion of an endpoint
   * @param  boolean $shouldSucceed        true if deletion should succede
   * @param  integer $endpointID           id of endpoint to be deleted
   * @param  integer $serviceID            if of parent service
   * @param          $request              http request contents
   * @param  integer $expectedHttpResponse http response expected
   * @param  integer $endpointIdToCheck    the endpoint ID we will check to see
   *                                       if it exists (ennables testing wrong IDs)
   */
  private function endpointDeletion($shouldSucceed, $endpointId, $serviceId, $request, $expectedHttpResponse, $endpointIdToCheck=null){
    #case mixing intentional as write API should be mostly case insensitive
    $APIOutput = $this->wellFormattedWriteAPICall ('DelEte', $request, $this->validAuthIdent, 'ServICe', $serviceId, 'EndPOint', $endpointId);

    #Check the response code is as we expect
    $this->assertequals($expectedHttpResponse,$APIOutput['httpResponseCode']);

    if(is_null($endpointIdToCheck)) {
      $endpointIdToCheck = $endpointId;
    }

    $this->assertEndpointDeletionStatus($shouldSucceed, $endpointIdToCheck);
  }


  /**
   * Test updating each of the properties associated with an endpoint one at a
   * time using POST
   */
  public function test_postSingleEndpointProps() {
    print __METHOD__ . "\n";

    $endpoint = $this->createSampleEndpoint();

    #It should be possible to update each of the non-mandatory, non-boolean endpoint properties
    foreach ($this->sampleValuesOptionalFields as $field => $value) {
      #case mixing intentional as write API should be mostly case insensitive
      $APIOutput = $this->wellFormattedWriteAPICall ('PoST', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'endPoiNt', $endpoint->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      #check the change happened
      $this->assertEndpointAsExpected($endpoint->getId(),array($field =>$value));
    }

    #Now try putting properties that are already defined for our sample endpoint
    #First, store the values in the mandatory fields are the start so we can check
    #they are unchanged later
    $startingMandFileds =  array('name' => $endpoint->getname(), 'url'=>$endpoint->getUrl());

    #create array of fields we will try and change and the values we will change them to
    $changes = $this->sampleValuesOptionalFieldsUpdate + $this->sampleValuesMandatoryFieldsUpdate;

    #Try and change each field
    foreach ($changes as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('POST', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'endpoint', $endpoint->getId(), $field);

      #The field is already set, so for a post request we expect a 409 response
      $this->assertequals(409,$APIOutput['httpResponseCode']);
    }

    #Then we check starting values are unchanged
    $this->assertEndpointAsExpected($endpoint->getId(),$this->sampleValuesOptionalFields+$startingMandFileds);


    #Now try putting with some boolean properties. These should fail for POST
    #Try and change each field
    foreach ($this->boolFields as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('POST', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'endpoint', $endpoint->getId(), $field);

      #Expect 405 error
      $this->assertequals(405,$APIOutput['httpResponseCode']);
    }

    #Then we check none of the above values have changed
    $this->assertEndpointBooleansAsExpected($endpoint->getId(),$this->boolFieldsStart);
  }

  /**
   * Check for a correct repsonse for a post request on a endpoint where the
   * field name is incorrect
   */
  public function test_postSingleIncorrectlyNamedSerProp(){
    print __METHOD__ . "\n";

    $endpoint = $this->createSampleEndpoint();

    #First, store the values in the mandatory fields are the start so we can check
    #they are unchanged later
    $startingMandFileds =  array('name' => $endpoint->getname(), 'url'=>$endpoint->getUrl());

    $APIOutput = $this->wellFormattedWriteAPICall ('POST', $this->singleValToJsonRequest('string'), $this->validAuthIdent, 'endpoint', $endpoint->getId(), 'notARealField');

    #That isn't a real field, so we expect a 400 response
    $this->assertequals(400,$APIOutput['httpResponseCode']);

    #Then we check none of the starting values have changed
    $this->assertEndpointAsExpected($endpoint->getId(),$startingMandFileds);
    $this->assertEndpointBooleansAsExpected($endpoint->getId(),$this->boolFieldsStart);
  }



  public function test_putSingleEndpointProps() {
    print __METHOD__ . "\n";

    $endpoint = $this->createSampleEndpoint();

    #It should be possible to update each of the non-boolean endpoint Site_Properties
    foreach ($this->sampleValuesOptionalFields + $this->sampleValuesMandatoryFields as $field => $value) {
      #case mixing intentional as write API should be mostly case insensitive
      $APIOutput = $this->wellFormattedWriteAPICall ('PUt', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'endPOint', $endpoint->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      #check the change happened
      $this->assertEndpointAsExpected($endpoint->getId(),array($field =>$value));
    }

    #It should also be possible to update each of the booleans endpoint properties
    foreach ($this->boolFields as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'endpoint', $endpoint->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      $this->assertEndpointBooleansAsExpected($endpoint->getId(),array($field =>$value));
    }

    #And try switching the boolean values back (we have had a number of bugs
    #where switching in one direction works but not the other)
    foreach ($this->boolFieldsStart as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'endpoint', $endpoint->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      $this->assertEndpointBooleansAsExpected($endpoint->getId(),array($field =>$value));
    }

    #Try and change all non-boolean properties again and check it is successful (which it should be for PUT)

    #create array of fields we will try and change and the values we will change them to
    $changes = $this->sampleValuesOptionalFieldsUpdate + $this->sampleValuesMandatoryFieldsUpdate;

    #Try and change each field
    foreach ($changes as $field => $value) {
      $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest($value), $this->validAuthIdent, 'endpoint', $endpoint->getId(), $field);

      #It was a valid request, so the API should return a response code of 204 (for now)
      $this->assertequals(204,$APIOutput['httpResponseCode']);

      #check the change happened
      $this->assertEndpointAsExpected($endpoint->getId(),array($field =>$value));
    }
  }


  /**
   * Check for a correct repsonse for a post request on a endpoint where the
   * field name is incorrect
   */
  public function test_putSingleIncorrectlyNamedSerProp(){
    print __METHOD__ . "\n";

    $endpoint = $this->createSampleEndpoint();

    #First, store the values in the mandatory fields are the start so we can check
    #they are unchanged later
    $startingMandFileds =  array('name' => $endpoint->getName(), 'url'=>$endpoint->getUrl());

    $APIOutput = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest('string'), $this->validAuthIdent, 'endpoint', $endpoint->getId(), 'notARealField');

    #That isn't a real field, so we expect a 400 response
    $this->assertequals(400,$APIOutput['httpResponseCode']);

    #Then we check none of the starting values have changed
    $this->assertEndpointAsExpected($endpoint->getId(),$startingMandFileds);
    $this->assertEndpointBooleansAsExpected($endpoint->getId(),$this->boolFieldsStart);
  }

}
