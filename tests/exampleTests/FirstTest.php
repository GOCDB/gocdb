<?php

    /*______________________________________________________
    *======================================================
    * File: FirstTest.php
    * Description: Demo class to show different examples of unit testing.
    *  Class modified from tutorial by Sylvain.
    * @link http://thelab.athome-training.com/tutoriel/PHP-unit-testing-with-PHPunit-1-2
    *
    * License information
    *
    * Copyright � 2010 STFC
    * Licensed under the Apache License, Version 2.0 (the "License");
    * you may not use this file except in compliance with the License.
    * You may obtain a copy of the License at
    * http://www.apache.org/licenses/LICENSE-2.0
    * Unless required by applicable law or agreed to in writing, software
    * distributed under the License is distributed on an "AS IS" BASIS,
    * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    * See the License for the specific language governing permissions and
    * limitations under the License.
    *
    /*====================================================== */



/**
 * Demo class to show different examples of unit testing.
 * Class modified from tutorial by Sylvain.
 * 
 * Usage: /<GOCDBHOME>/htdocs/web_portal$ phpunit UnitTest ../../tests/sampleTests/FirstTest.php
 * 
 * @link http://www.sylvainartois.fr.nf/PHP/Unit-testing-with-PHPUnit-part-one
 * @copyright 2010 STFC
 * @todo Has not been fully implemented yet.
 * @author David Meredith
 */
class FirstTest extends PHPUnit_Framework_TestCase {


    private $aArray = null;
    //private $oCSVReader = null;
    //private $sFileName = "fixture.csv";
    private $xXML = '<?xml version="1.0" encoding="UTF-8"?>
                        <root>
                            <message><![CDATA[Data inserted successfully]]></message>
                        </root>';

    /**
     * Called once, before any of the tests are executed.
     */
    public static function setUpBeforeClass() {
        print __METHOD__ . "\n";
        // define a variable so that example tests below can be conditionally
        // skipped if the ENV var does not have a specific value.
        define( 'ENV', 'DEV' );
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before each test method is executed.
     */
    protected function setUp() {
        print __METHOD__ . "\n";

        // each time, re-create $this->aArray so that we can execute the
        // tests using known data.
        $this->aArray = array(
                'login' => 'Sylvain',
                'password'=> 'my_password',
                'email' => 'sylvain@email.email.com'
        );
    }

    /**
     * Like setUp(), this is called before each test method to
     * assert any pre-conditions required by tests.
     */
    protected function assertPreConditions() {
        print __METHOD__ . "\n";
        //$this->assertTrue(ENV === 'DEV');
    }

        protected function assertPostConditions() {
        print __METHOD__ . "\n";
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        print __METHOD__ . "\n";
    }

    /**
     * executed only once, after all the testing methods
     */
    public static function tearDownAfterClass() {
        print __METHOD__ . "\n";
    }

    protected function onNotSuccessfulTest(Exception $e) {
        print __METHOD__ . "\n";
        throw $e;
    }


    public function testQuickTest(){
       print __METHOD__ . "\n"; 
       $res[1] = $this->returnArray(); // store returned associative array in 1st element of  
       print_r($res[1]); 

       $counts=array("ok" => 0, "warn" => 0, "error" => 0);

        foreach ($res as $r){
            $counts[$r["status"]]++;
        }
        print_r($counts); 

       echo "done"; 
    }
    private function returnArray(){
        //$retval["status"] = "ok";
        //$retval["message"] = "everything is well";
        $retval["status"] = "error";
        $retval["message"] = "everything is not well";
        return $retval; 
    }
    
    public function testExtactPKNumber(){
         print __METHOD__ . "\n";
         $v4pks = array('1GO', '20GO', '300GO');
         $largestV4Pk = 0; 
         foreach ($v4pks as $v4pkGO) {
             $v4pk = (int)substr($v4pkGO, 0, strlen($v4pkGO)-2);  
             if($v4pk > $largestV4Pk){
                $largestV4Pk = $v4pk; 
             }
             echo $v4pk; 
         }
         $this->assertEquals(300, $largestV4Pk);  
    }
    
    
    public function testBitwiseAnd(){
        print __METHOD__ . "\n";
        if(3 & 1){
            print '3 & 1 true'; 
        }
        if(3 & 2){
            print '3 & 2 true'; 
        }
        if(3 & 4){
            fail('3 & 4 should not be true'); 
        }
        if(3 & 8){
            fail('3 & 8 should not be true'); 
        }
        
        if(4 & 1){
            fail('4 & 1 should not be true'); 
        }
        if(4 & 2){
            fail('4 & 2 should not be true'); 
        }
        if(4 & 4){
            print '4 & 4 true'; 
        }
        if(4 & 8){
            fail('4 & 8 should not be true'); 
        }
    }


    /**
     * Our first test method that shows various assertion tests.
     * Tests can be pre-appended with 'test' or
     * annotated with @test in its comments. See aMethodThatNameDoesntBeginWithTest
     * below for an example.
     * @return array A copy of this->aArray
     *   We return an array because the next method, testShowPHPUnitCanHandleTestDependencies,
     *   holds an annotation: "@depends", with the this method name as argument, testVariousAssertion.
     *   The returned array will be passed to the method that has declared a dependancy.
     */
    public function testVariousAssertion() {
        print __METHOD__ . "\n";
        $this->assertTrue( is_array( $this->aArray ) );
        $this->assertArrayHasKey('login', $this->aArray );
        $this->assertArrayNotHasKey( 'whatever', $this->aArray );
        $this->assertContains( 'Sylvain', $this->aArray );
        $this->assertNotContains( 'whatever', $this->aArray );
        $this->assertContainsOnly('string', $this->aArray, FALSE, "A custom error message" );
        // temporarily comment out in order to support phpunit 3.4.5 (requires 3.5.5)
        //$this->assertNotEmpty( $this->aArray );
        $this->assertEquals( $this->aArray['email'],'sylvain@email.email.com' );
        $this->assertFalse( count( $this->aArray ) === 4 );
        $aArrayCopy = $this->aArray;
        $this->aArray = null;
        $this->assertNull( $this->aArray );
        return $aArrayCopy;
    }

    /**
     * @depends testVariousAssertion
     */
    public function testShowPHPUnitCanHandleTestDependencies( array $aArray ) {
        $this->assertGreaterThan(2, count( $aArray ) );
        $this->assertGreaterThanOrEqual(3, count( $aArray ) );
        // temporarily comment out in order to support phpunit 3.4.5 (requires 3.5.5)
        //$this->assertInternalType('array', $aArray );
        $this->assertStringStartsWith('S', $aArray[ 'login' ] );
    }

    /**
     * It is also possible to declare a function as the provider of a test method.
     * For this, we must add the annotation "@dataProvider", with an argument
     * that is the name of the function that serves as provider.
     * This one must return an array of arrays, like testDataProvider.
     * Each row in the array will be exploded and passed to the requested function.
     * Within testDataProvider, I used assertThat. This assertion is useful for
     * composing complex assertions. See the documentation for more information.
     *
     * @dataProvider provider
     */
    public function testDataProvider( $title, $body ) {
        print __METHOD__ . "\n";

        $this->assertLessThan( 255, strlen($title) );
        $this->assertRegExp('/[a-zA-Z0-9]+/', $title);
        $this->assertStringEndsWith('...', $body );
        $this->assertNotSame($title, $body);

        //From PHPUnit doc: More complex assertions can be formulated using the PHPUnit_Framework_Constraint classes.
        //They can be evaluated using the assertThat() method.
        //The next example shows how the logicalNot() and equalTo() constraints can be used to express the same assertion as assertNotEquals().
        //@see http://www.phpunit.de/manual/current/en/api.html#api.assert.assertThat
        $this->assertThat(
                $title,
                $this->logicalNot(
                $this->equalTo($body)
                )
        );
    }

    /**
     * This method is used as a provider for tests.
     * @see testDataProvider
     * @return array Data used in tests. 
     */
    public function provider() {
        return array(
                array('China Emerges as a Scapegoat in Campaign Advertisements', 'With many Americans anxious over economic decline, Democrats and Republicans are blaming one another for allowing the export of jobs...'),
                array('Door-to-Door in Levittown', 'In a Pennsylvania town, 2008 seems long ago. And a Democratic incumbent feels the chill...'),
                array('Number of Competitive House Races Doubles', 'Around 87 House races can be seen as competitive this year, nearly twice as high as in recent years...')
        );
    }

    /**
     * Annotations are also useful for declaring that a method should throw an
     * exception, as is the case with TestException. We can get the same result with
     * <pre>$this->setExpectedException( 'InvalidArgumentException' );</pre>
     *
     * @expectedException InvalidArgumentException
     */
    public function testException() {
        throw new InvalidArgumentException( 'Invalid Argument Exception' );
    }

    /**
     * This method shows that a test does not have to begin with test, but
     * instead uses the @test annotation.
     *
     * @test
     */
    public function aMethodThatNameDoesntBeginWithTest() {
        // this fails if test is ran from another dir, thus provide full path
        //$this->assertFileExists('fixture.txt')
        $this->assertFileExists(__DIR__.'/fixture.txt');
        $this->assertStringEqualsFile(__DIR__.'/fixture.txt', 'Fake content');

        // didn't find this Test.xml file, so i return above (TODO).
        if(true)return; 

        // didn't find this Test.xml file, so i return above.
        $this->assertXmlStringEqualsXmlFile( 'Test.xml', $this->xXML );
        $this->assertXmlStringEqualsXmlString('<foo><bar/></foo>', '<foo><bar/></foo>');

        $this->assertStringMatchesFormat('%i', '-154');
        $this->assertStringMatchesFormat('%d', '154');
        $this->assertStringMatchesFormat('%c', 'c');
        $this->assertStringMatchesFormat('%f', '154.12');

        // Matcher that asserts that there is a "div", with an "ul" ancestor and a "li"
        // parent (with class="enum"), and containing a "span" descendant that contains
        // an element with id="my_test" and the text "Hello World".
        $matcher = array('id' => 'my_id');
        $html = '<div id="my_id"></div>';

        // PHP is often used to generate HTML. PHPUnit offers us a powerful tool
        // to test the generated code: "assertTag". It takes at least 2 argument,
        // an array that describes the expected tags, and an HTML string.
        $this->assertTag($matcher, $html);

        $matcher = array(
                'tag'        => 'div',
                'ancestor'   => array('tag' => 'ul'),
                'parent'     => array(
                        'tag'        => 'li',
                        'attributes' => array('class' => 'enum')
                ),
                'descendant' => array(
                        'tag'   => 'span',
                        'child' => array(
                                'id'      => 'my_test',
                                'content' => 'Hello World'
                        )
                )
        );

        $html = '<ul><li class="enum"><div><span><strong id="my_test">Hello World</strong></span></div></li></ul>';

        $this->assertTag($matcher, $html);

        $matcher = array(
                'tag' => 'message',
                'parent' => array(
                        'tag' => 'root'
                )
        );

        $this->assertTag( $matcher, $this->xXML, '', FALSE );
    }


    public function testThatShowThatYouCanSkipTestIfAConditionISNotOK() {
        if( ENV === 'DEV' ) {
            $this->markTestSkipped(
                    'This test is only available on prod env'
            );
        }
    }

    public function testShowingTestInDevelopment() {
        $this->markTestIncomplete( 'This test has not been implemented yet.' );
    }


}


?>
