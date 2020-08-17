<?php
/*
 * Copyright (C) 2020 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http:#www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once __DIR__ . '/abstractClass.php';

/**
 * Abstract class for tests of methods for entities that have extension properties
 *
 */
abstract class extensionPropertyAbstract extends AbstractWriteAPITestClass{
  private $sampleExtensionProps =  array('someKey' => 'someValue', 'SOmEOTHer  Key'=>'some valuE', 'Another .-_@Key'=>'some .-_@value' );
  private $sampleExtensionPropsWDup =  array('someKey' => 'This is a duplicate', 'New Key 1'=>'val', 'New Key 2'=>'val' );
  private $sampleExtensionPropKey = 'Some. -@_Key';
  private $sampleExtensionPropVal = 'Some. -@_ Value';
  private $sampleExtensionPropNewVal = 'Some. -@_ new Value';

  /*
   *This function provides each instance of the class a method to feed in an instance
   *of the type of entity the class was created to test the methods for.
   */
  abstract protected function getSampleEntity();

  /*
   *This function provides each instance of the class a method to feed in a strings
   *saying what type of entity it is.
   */
  abstract protected function getSampleEntityType();

  /*
   * Implements abstract function. A well formatted PI request for an entity
   * type that exists should be rejected if unauthenticated (as we don't
   * currently support GET requests). Extension properties are as good a test for
   * this as any.
   */
  public function test_APIUnauthenticated(){
    print __METHOD__ . "\n";

    $entity = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    foreach (array('POST','PUT','DELETE') as $method) {
      #The write API index uses null for the authentication identifier if the user is unauthenticated
      $APIOutput = $this->wellFormattedWriteAPICall ($method, json_encode($this->sampleExtensionProps), null, $entType, $entity->getId(), 'extensionproperties');

      #As it is unauthenticated it should return a 403
      $this->assertequals(403,$APIOutput['httpResponseCode']);

      #Check that creating was unsuccessful
      $this->assertEntityPropertiesCorrect(false, $entType, $entity->getId(), $this->sampleExtensionProps);
    }
  }

  /*
   * Implements abstract function. A well formatted PI request for an entity
   * type that exists should be rejected if unauthorised (as we don't
   * currently support GET requests). Extension properties are as good a test for
   * this as any.
   */
  public function test_APIUnauthorised(){
    print __METHOD__ . "\n";

    $entity = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    foreach (array('POST','PUT','DELETE') as $method) {
      $APIOutput = $this->wellFormattedWriteAPICall ($method, json_encode($this->sampleExtensionProps), 'definitly not an authenticated user', $entType, $entity->getId(), 'extensionproperties');

      #As it is unauthenticated it should return a 403
      $this->assertequals(403,$APIOutput['httpResponseCode']);

      #Check that creating wasn't successful
      $this->assertEntityPropertiesCorrect(false, $entType, $entity->getId(), $this->sampleExtensionProps);
    }
  }


  /**
   * Use the API to add extension properties using POST method and test associated behaviour
   */
  public function test_postExtensionProps () {
    print __METHOD__ . "\n";
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #case variation in method and key is deliberate as API should be insensitive
    $APIOutputService = $this->wellFormattedWriteAPICall ('PoSt', json_encode($this->sampleExtensionProps), $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties');

    #It was a properly formatted request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutputService['httpResponseCode']);

    #Check that the values have actually changed
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionProps);


    #Now try adding a new set of props, one of which has the same key as the previous
    $APIOutputService = $this->wellFormattedWriteAPICall ('POST', json_encode($this->sampleExtensionPropsWDup), $this->validAuthIdent, $entType, $ent->getId(), 'extensionproperties');

    #The duplicate key means this should fail, so the API should return a response code of 409
    $this->assertequals(409,$APIOutputService['httpResponseCode']);

    #The previous extension properties should be in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionProps);

    #The attempted ones should not
    $this->assertEntityPropertiesCorrect(false, $entType, $ent->getId(), $this->sampleExtensionPropsWDup);
  }


  /**
  * Use the API to add extension properties using PUT method and test associated behaviour
   * been added
   */
  public function test_putExtensionProps () {
    print __METHOD__ . "\n";
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #case variation in method and key is deliberate as API should be insensitive
    $APIOutputService = $this->wellFormattedWriteAPICall ('Put', json_encode($this->sampleExtensionProps), $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties');

    #It was a properly formatted request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutputService['httpResponseCode']);

    #Check that the values have actually changed
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionProps);


    #Now try adding a new set of props, one of which has the same key as the previous
    $APIOutputService = $this->wellFormattedWriteAPICall ('PUT', json_encode($this->sampleExtensionPropsWDup), $this->validAuthIdent, $entType, $ent->getId(), 'extensionproperties');

    #The duplicate key should succeed for PUT, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutputService['httpResponseCode']);

    #The new extension properties should be in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionPropsWDup);

    #The replaced extension property shgould no longer be present
    $this->assertEntityPropertiesCorrect(false, $entType, $ent->getId(), array_intersect_key($this->sampleExtensionProps,$this->sampleExtensionPropsWDup));

    #However, the unchanged previous extension properties should still be in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), array_diff($this->sampleExtensionProps,array_intersect_key($this->sampleExtensionProps,$this->sampleExtensionPropsWDup)));
  }


  /**
   * Use the API to add extension properties using POST method and test associated behaviour
   */
  public function test_postSingleExtensionProp () {
    print __METHOD__ . "\n";
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #case variation in method and key is deliberate as API should be insensitive
    $APIOutputService = $this->wellFormattedWriteAPICall ('PoSt', $this->singleValToJsonRequest($this->sampleExtensionPropVal), $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties', $this->sampleExtensionPropKey );

    #It was a properly formatted request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutputService['httpResponseCode']);

    #Check that the new property has actually been added
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), array($this->sampleExtensionPropKey=>$this->sampleExtensionPropVal));


    #Now try adding a new property where the key is the same as the one we allready added
    $APIOutputService = $this->wellFormattedWriteAPICall ('POST', $this->singleValToJsonRequest($this->sampleExtensionPropNewVal), $this->validAuthIdent, $entType, $ent->getId(), 'extensionproperties', $this->sampleExtensionPropKey );

    #The duplicate key means this should fail, so the API should return a response code of 409
    $this->assertequals(409,$APIOutputService['httpResponseCode']);

    #The previous extension property should be present
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), array($this->sampleExtensionPropKey=>$this->sampleExtensionPropVal));

    #The attempted one should not
    $this->assertEntityPropertiesCorrect(false, $entType, $ent->getId(), array($this->sampleExtensionPropKey=>$this->sampleExtensionPropNewVal));

  }

  /**
   * Use the API to add extension properties using POST method and test associated behaviour
   */
  public function test_putSingleExtensionProp () {
    print __METHOD__ . "\n";
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #case variation in method and key is deliberate as API should be insensitive
    $APIOutputService = $this->wellFormattedWriteAPICall ('PUt', $this->singleValToJsonRequest($this->sampleExtensionPropVal), $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties', $this->sampleExtensionPropKey );

    #It was a properly formatted request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutputService['httpResponseCode']);

    #Check that the new property has actually been added
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), array($this->sampleExtensionPropKey=>$this->sampleExtensionPropVal));


    #Now try adding a new property where the key is the same as the one we allready added
    $APIOutputService = $this->wellFormattedWriteAPICall ('PUT', $this->singleValToJsonRequest($this->sampleExtensionPropNewVal), $this->validAuthIdent, $entType, $ent->getId(), 'extensionproperties', $this->sampleExtensionPropKey );

    #The duplicate key should succeed for PUT, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutputService['httpResponseCode']);

    #The new key/value pair should be present
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), array($this->sampleExtensionPropKey=>$this->sampleExtensionPropNewVal));

    #The old one should not
    $this->assertEntityPropertiesCorrect(false, $entType, $ent->getId(), array($this->sampleExtensionPropKey=>$this->sampleExtensionPropVal));
  }

  /**
   * Test deleting all the extension properties for an entity, providing no httpResponseCode
   * request contents
   */
  public function test_deleteAllExtensionPropsNoRequest(){
    print __METHOD__ . "\n";
    $this->deleteAllExtensionProps(true);
  }

  /**
   * Test deleting all the extension properties for an entity using an empty strings
   * as the HTTP request contents
   */
  public function test_deleteAllExtensionPropsEmptyRequest(){
    print __METHOD__ . "\n";
    $this->deleteAllExtensionProps(false);
  }

  /**
   * Use the API to delete all extension properties
   * @param bool    $nullRequest if true we make the delete request with no
   *                             request contents. If false we use an empty
   *                             string for the request contents.
   */
  private function deleteAllExtensionProps($nullRequest) {
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #Add sample props to the entity
    $this->createExtensionProperties($ent, $entType, $this->sampleExtensionProps);

    if($nullRequest) {
      #(null replicates the output of file_get_contents('php://input') when
      #(there is no request contents)
      $requestContents = null;
    } else {
      $requestContents = '';
    }

    #case variation in method and key is deliberate as API should be insensitive
    $APIOutputService = $this->wellFormattedWriteAPICall ('DEleTE', $requestContents, $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties');

    #It was a properly formatted request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutputService['httpResponseCode']);

    #Check that the properties have actually been deleted
    $this->assertNoEntityProperties($entType, $ent->getId());
  }

  /**
   * Test delting several properties whilst specifying their current values
   */
  public function test_deleteSelectExtensionPropsWithVals(){
    print __METHOD__ . "\n";
    $this->deleteSelectExtensionProps(true);
  }

  /**
   * Test delting several properties whilst specifying empty strings instead of
   * their current values
   */
  public function test_deleteSelectExtensionPropsWithoutVals(){
    print __METHOD__ . "\n";
    $this->deleteSelectExtensionProps(false);
  }

  /**
   * Use the API to delete select extension properties
   * @param bool    $withVals true if we are testing with the values for the extension
   *                          properties provided to the API, false if we are testing
   *                          with empty strings
   */
  private function deleteSelectExtensionProps($withVals) {
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #Add the props to the entity
    $this->createExtensionProperties($ent, $entType, $this->sampleExtensionProps);

    #we want to leave one extension property behind to check we aren't just delting all, so create smaller sample dataset
    $sampleExtensionPropsSubset = array_slice ($this->sampleExtensionProps, 1);

    #If we are providing empty strings for values, create array with this, else use subset with values
    if(!$withVals) {
      $propsToDelete = array();
      foreach ($sampleExtensionPropsSubset as $key => $value) {
        $propsToDelete[$key]='';
      }
    } else {
      $propsToDelete = $sampleExtensionPropsSubset;
    }

    #Use the API. (case variation in method and key is deliberate as API should be insensitive)
    $APIOutput = $this->wellFormattedWriteAPICall ('DEleTE', json_encode($propsToDelete), $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties');

    #It was a properly formatted request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutput['httpResponseCode']);

    #Check that the properties have actually been deleted
    $this->assertEntityPropertiesCorrect(false, $entType, $ent->getId(), $propsToDelete);

    #Check that the property that wasn't meant to be deleted remains in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), array_slice($this->sampleExtensionProps,0,1));
  }

  /**
   * Ensure the correct response when deleting several properties whilst providing
   * their current values when one of them is not currently defined for that entity.
   * @return [type] [description]
   */
  public function test_deleteExtensionPropsWrongKeysWithVals(){
    print __METHOD__ . "\n";
    $this->deleteExtensionPropsWrongKeys(true);
  }

  /**
   * Ensure the correct response when deleting several properties whilst providing
   * empty strings for values when one of them is not currently defined for that entity.
   * @return [type] [description]
   */
  public function test_deleteExtensionPropsWrongKeysWithoutVals(){
    print __METHOD__ . "\n";
    $this->deleteExtensionPropsWrongKeys(false);
  }

  /**
   * Use the API to delete extension properties, but sepcify Entity properties
   * that don't exist (ie theire is no property with that key associated with
   * that entity)
   * @param bool    $withVals true if we are testing with the values for the extension
   *                          properties provided to the API, false if we are testing
   *                          with empty strings
   */
  public function deleteExtensionPropsWrongKeys($withVals) {
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #Add the props to the entity
    $this->createExtensionProperties($ent, $entType, $this->sampleExtensionProps);

    #Construct a set of extension properties to delete where one has a different key to the ones we added
    $propsToDelete = array_slice($this->sampleExtensionProps, 1);
    $newKeyArray = array_keys(array_slice($this->sampleExtensionProps, 0, 1));
    $newValueArray = array_values(array_slice($this->sampleExtensionProps, 0, 1));
    $propsToDelete[$newKeyArray[0].'Not the same now'] = $newValueArray[0];

    #Use the API. (case variation in method and key is deliberate as API should be insensitive)
    $APIOutput = $this->wellFormattedWriteAPICall ('DEleTE', json_encode($propsToDelete), $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties');

    #It was a properly formatted request, but one of the properties does not exist,
    #so the API should return a response code of 404
    $this->assertequals(404,$APIOutput['httpResponseCode']);

    #Check that the original properties remain in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionProps);
  }

  /**
   * Use the API to delete extension properties, but sepcify Entity properties
   * using correct keys with the wrong values. This should fail.
   */
  public function test_deleteExtensionPropsWrongVal() {
    print __METHOD__ . "\n";
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #Add the props to the entity
    $this->createExtensionProperties($ent, $entType, $this->sampleExtensionProps);

    $newPropKeyArray = array_keys(array_slice($this->sampleExtensionProps, 0, 1));
    $newPropValArray = array_values(array_slice($this->sampleExtensionProps, 0, 1));
    #Construct a set of extension properties to delete where one has a different value to the ones we added
    $propsToDelete = array_slice($this->sampleExtensionProps, 1);
    $propsToDelete[$newPropKeyArray[0]] = $newPropValArray[0].'Not the Same';

    #Use the API. (case variation in method and key is deliberate as API should be insensitive)
    $APIOutput = $this->wellFormattedWriteAPICall ('DEleTE', json_encode($propsToDelete), $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties');

    #It was a properly formatted request, but one of the properties values did not
    #match so the API should return a response code of 409
    $this->assertequals(409,$APIOutput['httpResponseCode']);

    #Check that the original properties remain in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionProps);
  }

  /**
   * Test the deletion of a single extension property by providing the key in
   * the URL string. Provide the current value in the http request contents.
   */
  public function test_deleteExtensionPropWithVal(){
    print __METHOD__ . "\n";
    $this->deleteExtensionProp(true);
  }

  /**
   * Test the deletion of a single extension property by providing the key in
   * the URL string. Provide no http request contents.
   */
  public function test_deleteExtensionPropWithoutValNoRequest(){
    print __METHOD__ . "\n";
    $this->deleteExtensionProp(false, false);
  }

  /**
   * Test the deletion of a single extension property by providing the key in
   * the URL string. Provide empty http request contents.
   */
  public function test_deleteExtensionPropWithoutValEmptyRequest(){
    print __METHOD__ . "\n";
    $this->deleteExtensionProp(false, true);
  }

  /**
   * Use the API to delete a single extension property for the specified entity.
   * Note: both boolean inputs should never both be true. $emptyReq is treated
   * as if false when $withVal is true.
   * @param bool    $withVal  true if we are testing with the value for the extension
   *                          property provided to the API
   * @param bool    $EmptyReq If true we test with an emptystring for request body.
   *                          If false we test with no requst body
   */
  private function deleteExtensionProp($withVal, $emptyReq=false) {
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #Add the sample props to the entity
    $this->createExtensionProperties($ent, $entType, $this->sampleExtensionProps);

    #Construct the desired request body
    if ($withVal) {
      $propForReq = array_values(array_slice($this->sampleExtensionProps, 0, 1));
      $reqBody = $this->singleValToJsonRequest($propForReq[0]);
    } elseif ($emptyReq) {
      $reqBody = '';
    } else {
      #(null replicates the output of file_get_contents('php://input') when
      #(there are no request contents)
      $reqBody = null;
    }

    $propKeyArray = array_keys(array_slice($this->sampleExtensionProps, 0, 1));
    $propKey = $propKeyArray [0];

    #Use the API. (case variation in method and key is deliberate as API should be insensitive)
    $APIOutput = $this->wellFormattedWriteAPICall ('DEleTE', $reqBody, $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties',$propKey);

    #It was a properly formatted request, so the API should return a response code of 204 (for now)
    $this->assertequals(204,$APIOutput['httpResponseCode']);

    #Check that the properties have actually been deleted
    $this->assertEntityPropertiesCorrect(false, $entType, $ent->getId(), array_slice($this->sampleExtensionProps, 0, 1));

    #Check that the remaining properties remain in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), array_slice($this->sampleExtensionProps, 1));
  }

  /**
   * Test for correct behaviour when attempting to delete a single extension
   * property with the key in the URL string when that extension property is not
   * currently defined for the entity. Provide a value in the http
   * request contents.
   */
  public function test_deleteNonexistentExtensionPropWithVal(){
    print __METHOD__ . "\n";
    $this->deleteNonexistentExtensionProp(true);
  }

  /**
   * Test for correct behaviour when attempting to delete a single extension
   * property with the key in the URL string when that extension property is not
   * currently defined for the entity. Provide no http request contents.
   */
  public function test_deleteNonexistentExtensionPropWithoutValNoRequest(){
    print __METHOD__ . "\n";
    $this->deleteNonexistentExtensionProp(false, false);
  }

  /**
   * Test for correct behaviour when attempting to delete a single extension
   * property with the key in the URL string when that extension property is not
   * currently defined for the entity. Provide empty http request contents.
   */
  public function test_deleteNonexistentExtensionPropWithoutValEmptyRequest(){
    print __METHOD__ . "\n";
    $this->deleteNonexistentExtensionProp(false, true);
  }

  /**
   * Use the API to delete a single extension property for the specified entity
   * that doesn't exist. Checks that the correct errors are thrown
   * Note: both boolean inputs should never both be true. $emptyReq is treated
   * as if false when $withVal is true.
   * @param bool    $withVal  true if we are testing with the value for the extension
   *                          property provided to the API
   * @param bool    $EmptyReq If true we test with an emptystring for request body.
   *                          If false we test with no requst body
   */
  private function deleteNonexistentExtensionProp( $withVal, $emptyReq=false) {
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #Add the sample props to the entity
    $this->createExtensionProperties($ent, $entType, $this->sampleExtensionProps);

    #Construct the desired request body
    if ($withVal) {
      $propValArray = array_values(array_slice($this->sampleExtensionProps, 0, 1));
      $reqBody = $this->singleValToJsonRequest($propValArray[0]);
    } elseif ($emptyReq) {
      $reqBody = '';
    } else {
      #(null replicates the output of file_get_contents('php://input') when
      #(there are no request contents)
      $reqBody = null;
    }

    $propKeyArray = array_keys(array_slice($this->sampleExtensionProps, 0, 1));
    $propKey = $propKeyArray [0] . 'this doesnt exist';

    #Use the API. (case variation in method and key is deliberate as API should be insensitive)
    $APIOutput = $this->wellFormattedWriteAPICall ('DEleTE', $reqBody, $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties',$propKey);

    #It was a properly formatted request, but one of the properties does not exist,
    #so the API should return a response code of 404
    $this->assertequals(404,$APIOutput['httpResponseCode']);

    #Check that the properties all remain
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionProps);
  }

  /**
   * Use the API to delete a single extension property, but specify the property
   * using the wrong value with the correct key. This should fail.
   */
  public function test_deleteExtensionPropWrongVal() {
    print __METHOD__ . "\n";
    $ent = $this->getSampleEntity();
    $entType = $this->getSampleEntityType();

    #Add the props to the entity
    $this->createExtensionProperties($ent, $entType, $this->sampleExtensionProps);

    #Construct the request body with the wrong value
    $propValArray = array_values(array_slice($this->sampleExtensionProps, 0, 1));
    $reqBody = $this->singleValToJsonRequest($propValArray[0].'not correct now');

    #define the correct key
    $propKeyArray = array_keys(array_slice($this->sampleExtensionProps, 0, 1));
    $propKey = $propKeyArray [0];

    #Use the API. (case variation in method and key is deliberate as API should be insensitive)
    $APIOutput = $this->wellFormattedWriteAPICall ('DEleTE', $reqBody, $this->validAuthIdent, $entType, $ent->getId(), 'EXTENSIONproperties', $propKey);

    #It was a properly formatted request, but one of the properties values did not
    #match so the API should return a response code of 409
    $this->assertequals(409,$APIOutput['httpResponseCode']);

    #Check that the original properties remain in place
    $this->assertEntityPropertiesCorrect(true, $entType, $ent->getId(), $this->sampleExtensionProps);
  }

  /**
   * Add extension properties to the entity provided with keys and values as per
   * array given.
   * @param         $entity     entity to which we are adding extension properties
   * @param  string $entityType Type of entity prop we are looking at.
   *                            Should be 'site', 'service', or 'endpoint'
   * @param  array  $props      'key' =>'value'
   */
  protected function createExtensionProperties($entity, $entityType, $props) {
    foreach ($props as $key => $value) {
      switch ($entityType) {
        case 'site':
          $prop = new \SiteProperty();
          $prop->setKeyName($key);
          $prop->setKeyValue($value);
          $entity->addSitePropertyDoJoin($prop);
          break;
        case 'service':
          $prop = new \ServiceProperty();
          $prop->setKeyName($key);
          $prop->setKeyValue($value);
          $entity->addServicePropertyDoJoin($prop);
          break;
        case 'endpoint':
          $prop = new \EndpointProperty();
          $prop->setKeyName($key);
          $prop->setKeyValue($value);
          $entity->addEndpointPropertyDoJoin($prop);
          break;
        default:
          throw new \Exception("Unsupported enitity type, you shouldn't end up here", 1);
      }

      $this->em->persist($prop);
    }
    $this->em->persist($entity);
    $this->em->flush();

    #check that they have been created by directly querying databases
    $this->assertEntityPropertiesCorrect(true, $entityType, $entity->getId(), $props);
  }

  /**
   * Depending which entity we are looking at the name of the extension property
   * table is different. This function returns the correct name
   * @param  string $entityType Type of entity prop we are looking at.
   *                            Should be 'site', 'service', or 'endpoint'
   * @return string             name of the extension properties table for that entity
   */
  protected static function getTableName($entityType){
    switch ($entityType) {
      case 'site':
        return 'Site_Properties';
      case 'service':
        return 'Service_Properties';
      case 'endpoint':
        return 'Endpoint_Properties';
      default:
        throw new \Exception("Unsupported enitity type, you shouldn't end up here!", 1);
    }
  }

  /**
   * Depending which entity we are looking at the name of the extension property
   *  FK varies. This function returns the correct name
   * @param  string $entityType Type of entity prop we are looking at.
   *                            Should be 'site', 'service', or 'endpoint'
   * @return string             name of the extension properties FK
   */
  protected static function getParentIdName($entityType){
    switch ($entityType) {
      case 'site':
        return 'parentSite_id';
      case 'service':
        return 'parentService_id';
      case 'endpoint':
        return 'parentEndpoint_id';
      default:
        throw new \Exception("Unsupported enitity type, you shouldn't end up here!", 1);
    }
  }

  /**
   * Assert that the etity key value properties are as we would expect in the
   * database. As the property values are implmented almost identically for the
   * three entities that have them, one function exists for all three.
   * @param  boolean $shouldExist if true, we check that the array of entity
   *                              props exists. If false, we check it doesn't
   * @param  string  $entityType  Type of entity prop we are looking at.
   *                              Should be 'site', 'service', or 'endpoint'
   * @param  integer $entityId    ID of parent entity that should (or should not)
   *                              have the specified properties
   * @param  array   $arrayName = array('' => , ); $EntityProps [description]
   */
  protected function assertEntityPropertiesCorrect($shouldExist, $entityType, $entityId, $EntityProps) {
    #If the properties shouldn't exist then we expect our SQL to return 0 lines
    $expectedNo = 0;
    if ($shouldExist) {
      $expectedNo = 1;
    }

    #Depending which entity we are looking at the name of the parent ID field
    #is different and we will want to look at a different table in each case
    $parentIDName = $this->getParentIdName($entityType);
    $table = $this->getTableName($entityType);

    $con = $this->getConnection();

    foreach ($EntityProps as $propKey => $propValue) {
      $sql = "SELECT * FROM $table
              WHERE $parentIDName = '$entityId'
              AND keyName = '$propKey'
              AND keyValue = '$propValue'";
      $result = $con->createQueryTable('', $sql);
      $this->assertEquals($expectedNo, $result->getRowCount());
    }
  }

  /**
   * Assert that the given entity has no extension properties. As the property
   * values are implemented almost identically for the three entities that have
   *  them, one function exists for all three.
   * @param  string  $entityType  Type of entity prop we are looking at.
   *                              Should be 'site', 'service', or 'endpoint'
   * @param  integer $entityId    ID of parent entity that should (or should not)
   *                              have the specified properties
   */
  protected function assertNoEntityProperties($entityType, $entityId) {
    #Depending which entity we are looking at the name of the parent ID field
    #is different and we will want to look at a different table in each case
    $parentIDName = $this->getParentIdName($entityType);
    $table = $this->getTableName($entityType);

    $con = $this->getConnection();
    $sql = "SELECT * FROM $table
            WHERE $parentIDName = '$entityId'";
    $result = $con->createQueryTable('', $sql);
    $this->assertEquals(0, $result->getRowCount());
  }

}
