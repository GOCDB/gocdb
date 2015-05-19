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

/**
 * Test/display PHP's current timezone conversions. You may need to update 
 * the PHP timezonedb.so|dll file to be up to date with the current Olsen IANA
 * timezone daylight saving values. 
 *
 * @author David Meredith
 */
class DateTimeConvertTests extends PHPUnit_Framework_TestCase {
    //put your code here

    const FORMAT = 'd/m/Y H:i';
     
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

    /**
     * Print what PHP thinks are the current times in the specified timezones. 
     */
    public function testCurrentTimeInTimezones(){
        print __METHOD__ . "\n";
        print "To check your daylight timezone settings are correct and that "
        . "your php timezone.db is up to date, compare the output below with "
                . "the current real times on the web. You may need to update your"
                . "timezone.db lib. \n"; 
        // see: http://stackoverflow.com/questions/2532729/daylight-saving-time-and-time-zone-best-practices

        //date_default_timezone_set("UTC");
        $targetTz1 = new \DateTimeZone("Europe/Moscow"); 
        $targetTz2 = new \DateTimeZone("Europe/Amsterdam"); 
        $targetTz3 = new \DateTimeZone("America/New_York"); 
        $targetTz4 = new \DateTimeZone("America/Denver"); 
        
        $nowTz = new \DateTime(null, $targetTz1); 
        print_r($nowTz);  
        $nowTz = new \DateTime(null, $targetTz2); 
        print_r($nowTz);  
        $nowTz = new \DateTime(null, $targetTz3); 
        print_r($nowTz);  
        $nowTz = new \DateTime(null, $targetTz4); 
        print_r($nowTz);  
    }

    public function ntestDateConvert1(){
        print __METHOD__ . "\n";

         // convert start and end into UTC 
        $UTC = new \DateTimeZone("UTC");
        //$sourceTZ = new \DateTimeZone("Europe/Moscow");  // not ok
        //$sourceTZ = new \DateTimeZone("Europe/Amsterdam");
        $sourceTZ = new \DateTimeZone("America/New_York");
        //$sourceTZ = new \DateTimeZone("America/Denver");
        //$sourceTZ = new \DateTimeZone("Asia/Qatar");
        
        $start = \DateTime::createFromFormat($this::FORMAT, '14/05/2015 16:00', $sourceTZ);
        
        print_r($start); 
        
        // reset the TZ to UTC
        $start->setTimezone($UTC); 

        print_r($start); 
        print_r('startEnd in UTC: '.$start->format($this::FORMAT)); 
    }
}
