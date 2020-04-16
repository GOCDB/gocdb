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
 * DBUnit test class for site methods of the write API
 *
 */
class WriteAPIsiteMethodsTests extends extensionPropertyAbstract {

  /**
  * Overridden.
  */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    echo "\n\n-------------------------------------------------\n";
    echo "Executing WriteAPIsiteMethodsTests. . .\n";
  }

  /**
   * Tests relating to extension properties in the parent class require an
   * entity to test against. This provides it.
   * @return Site site to test extension properties against
   */
  protected function getSampleEntity(){
    return $this->createSampleSite();
  }

  /**
   * Tests relating to extension properties in the parent class require need to
   * know what type of entity they are dealing with.
   *
   * @return string 'site'
   */
  protected function getSampleEntityType(){
    return 'site';
  }
}
