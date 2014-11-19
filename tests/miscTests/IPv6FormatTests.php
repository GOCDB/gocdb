<?php

require_once __DIR__.'/../../lib/Gocdb_Services/validation/IPv6Validator.php';

/**
 * Test the parsing and validation of IP addresses.
 * 
 * Usage: $phpunit UnitTest ../../tests/miscTests/IPv6FormatTests.php
 * 
 * @copyright 2013 STFC
 * @author David Meredith
 */
class IPv6FormatTests extends PHPUnit_Framework_TestCase {


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
	

    public function testIPv6Validator(){
        print __METHOD__ . "\n";
        $validator = new IPv6Validator(); 
        $errors = array(); 

        // Valid: 
        $errors = $validator->validate('1:2::3:4/64', $errors ); 
        $this->assertTrue(count($errors) == 0); 

        $errors = $validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $errors ); 
        $this->assertTrue(count($errors) == 0); 

        $errors = $validator->validate('2001:0db8:85a3::8a2e:0370:7334', $errors ); 
        $this->assertTrue(count($errors) == 0); 

        $errors = $validator->validate('fe80::2000:aff:fea7:f7c', $errors ); 
        $this->assertTrue(count($errors) == 0); 

        $errors = $validator->validate('1:2::3:4/64', $errors ); 
        $this->assertTrue(count($errors) == 0); 
        
        $errors = $validator->validate('2001:adb8:85a3:7334:0000:0000:0000:0000', $errors ); 
        $this->assertTrue(count($errors) == 0); 

        $errors = $validator->validate('2001:adb8:85a3:7334:0000:0000:0000:0000/40', $errors ); 
        $this->assertTrue(count($errors) == 0); 
        
 

        // Invalid:  
        $errors = $validator->validate('123:', $errors ); 
        $this->assertTrue(count($errors) == 1);
        
        $errors = $validator->validate('fe80::2000:aff:fea7:f7c/0', $errors ); 
        $this->assertTrue(count($errors) == 2); 
        
        $errors = $validator->validate('fe80::2000:aff:fea7:f7c/illegal', $errors ); 
        $this->assertTrue(count($errors) == 3); 
        
        $errors = $validator->validate('invalid', $errors ); 
        $this->assertTrue(count($errors) == 4); 

        $errors = $validator->validate('', $errors ); 
        $this->assertTrue(count($errors) == 5); 
        
        $errors = $validator->validate(' ', $errors ); 
        $this->assertTrue(count($errors) == 6); 
    }

   
}


?>
