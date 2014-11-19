<?php

/**
 * Test the regex for parsing the 'expression' PI parameter.
 * 
 * Usage: $phpunit UnitTest ../../tests/sampleTests/PI_Extensions_Param_ParsingTest.php
 * 
 * @link http://www.sylvainartois.fr.nf/PHP/Unit-testing-with-PHPUnit-part-one
 * @copyright 2013 STFC
 * @author David Meredith
 * @author James McCarthy
 */
class PI_Extensions_Param_ParsingTest extends PHPUnit_Framework_TestCase {


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
	
	public function testValidateFullExpressionAndExtractCaptures(){
         print __METHOD__ . "\n";
       
         $anchLeftToCapture='/';  
         $anchRightToCapture='/i';  
         $anchLeftFull='/';  
         $anchRightFull='$/'; 
         // key is quite restrictive, only alpha-numerics and some chars considered useful for keys
         $keyregex="[a-zA-Z0-9\s@_\-\[\]\+\.]{1,255}"; 
         // val is any char except parenthesis () and the following to protect against sql injection  "';`
         $valregex="[^'\";\(\)`]{1,255}";
         // A single key=value pair
         $keyVal = "\(".$keyregex."=".$valregex."\)"; 
         // must specify at least 1 kv pair
         $regexKeyVal = "(".$keyVal.")+"; 
         $regexOperator = "(AND|OR|NOT)?";
         
         // This regex can be used to extract the captures 
         $regexCapture = $anchLeftToCapture."(".$regexOperator.$regexKeyVal.")".$anchRightToCapture; 
         // This regex can be used to test that the whole string in full passes (using full left and right anchors) 
         $regexFull = $anchLeftFull."(".$regexOperator.$regexKeyVal.")".$anchRightFull;
         //print $regexFull . "\n"; 
        
         $pattern ="(1VO=test1,test2,test3,test4)";  
         $this->assertEquals(1, preg_match($regexFull, $pattern)); 
         $this->assertEquals(1, preg_match_all($regexCapture, $pattern, $matches));
         $this->assertEquals($matches[0][0],$pattern); 
         print_r($matches);  

         
         $pattern="(dave=1)(dave=2)(dave=3)";  
         $this->assertEquals(1, preg_match($regexFull, $pattern)); 
         $this->assertEquals(1, preg_match_all($regexCapture, $pattern, $matches));
         $this->assertEquals($matches[0][0],$pattern); 
         print_r($matches);  

         
         $pattern="OR(VO=test1)(VO=test2)(VO=test3)"; 
         $this->assertEquals(1, preg_match($regexFull, $pattern)); 
         $this->assertEquals(1, preg_match_all($regexCapture, $pattern, $matches));
         $this->assertEquals($matches[0][0],$pattern); 
         print_r($matches);  

         
         $p1="OR(VO=test)(VO=test)(VO=test)"; 
         $p2="OR(VO=test)(VO=test)(VO=test)"; 
         $p3="AND(VO=test)(VO=test)(VO=test)";
         $pattern = $p1.$p2.$p3; 
         $this->assertEquals(1, preg_match($regexFull, $pattern)); 
         $this->assertEquals(3, preg_match_all($regexCapture, $pattern, $matches));
         $this->assertEquals($matches[0][0],$p1); 
         $this->assertEquals($matches[0][1],$p2); 
         $this->assertEquals($matches[0][2],$p3); 
         print_r($matches);  

         
         $p1="(VO=test)(VO=test)(VO=test)";
         $p2="OR(VO=test)(VO=test)(VO=test)"; 
         $p3="AND(VO=test)(VO=test)(VO=test)"; 
         $pattern = $p1.$p2.$p3; 
         $this->assertEquals(1, preg_match($regexFull, $pattern)); 
         $this->assertEquals(3, preg_match_all($regexCapture, $pattern, $matches));
         $this->assertEquals($matches[0][0],$p1); 
         $this->assertEquals($matches[0][1],$p2); 
         $this->assertEquals($matches[0][2],$p3); 
         print_r($matches);  
     }

    
}


?>
