<?php

/**
 * Test the regex for parsing the 'expression' PI parameter.
 * Note, this is unfinished and the ExtensionsParser needs to be udpated with 
 * the new method/regex. 
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


    // useful links for lexial/regex parsing for tokenizing 
    // http://stackoverflow.com/questions/10208694/regex-to-parse-string-with-escaped-characters
    // http://blog.angeloff.name/post/2012/08/05/php-recursive-patterns/ 
    // https://github.com/nikic/Phlexy
    // http://stackoverflow.com/questions/16387277/language-parser-library-written-in-php 
    // http://nikic.github.io/2011/10/23/Improving-lexing-performance-in-PHP.html
    // http://stackoverflow.com/questions/546433/regular-expression-to-match-outer-brackets
	
    /**
     * Capture outer curly bracket groups using a repeating pattern that can contain: 
     * - nested balanced curly brackets (no nested unbalanced escaped curly brackets are allowed) 
     * - any other char that is not a curly bracket 
     * Groups can be prefixed with an optional prefix that starts with @media. 
     */
    public function testRecursivePattern(){
        print __METHOD__ . "\n"; 
        $string = <<<CSS
        body { color: #888; }
        @media print { body { color: #333; } }
        code { color: blue; }
CSS;
        //see: http://blog.angeloff.name/post/2012/08/05/php-recursive-patterns/ 
        //$pattern = '/{(?:[^{}]+|(?R))*}/';
        $pattern = '/(?:@media[^{]+)?'     # @media is optional, e.g., when we have descended into it.
         . '{(?:[^{}]+|(?R))*}/s';
        preg_match_all($pattern, $string, $groups);
        //print_r($groups); 
       
        $this->assertEquals('{ color: #888; }', $groups[0][0]); 
        $this->assertEquals('@media print { body { color: #333; } }', $groups[0][1]); 
        $this->assertEquals('{ color: blue; }', $groups[0][2]); 

    }

    /**
     * Capture outer parenthesis groups using a repeating pattern that can contain: 
     * - nested balanced parenthesis (no nested unbalanced escaped parenthesis are allowed) 
     * - any other char that is not a parenthesis  
     */
    public function testRecursiveWithParaentheis(){
        print __METHOD__ . "\n"; 
        $string = <<<AAA
        body ( color: #888; )
        @media print ( body ( color: #333; ) )
        code ( color: blue; )
AAA;
        $pattern = '/\((?:[^()]+|(?R))*\)/s';
        preg_match_all($pattern, $string, $groups);
        //print_r($groups); 
        $this->assertEquals('( color: #888; )', $groups[0][0]); 
        $this->assertEquals('( body ( color: #333; ) )', $groups[0][1]); 
        $this->assertEquals('( color: blue; )', $groups[0][2]); 
    }


    /**
     * Capture outer parenthesis groups using a repeating pattern that can contain: 
     * - nested balanced parenthesis (no nested unbalanced escaped parenthesis are allowed) 
     * - any other char that is not a parenthesis 
     * Groups can be prefixed with an optional AND prefix. 
     */
    public function testRecursiveWithParenthesisWithOptionalPrefix(){
        print __METHOD__ . "\n"; 
        $string = 
        "AND ( color: #888; )
        AND( body ( color: #333; ) ) 
        don't capture me ( color: blue; )"; 
        //$pattern = '/\((?:[^()]+|(?R))*\)/s';
        $pattern = '/(?:AND[\s]*)?' . '\((?:[^()]+|(?R))*\)/s';
        # AND followed by space is optional, e.g., when we have descended into it.
        preg_match_all($pattern, $string, $groups);
        //print_r($groups); 
        $this->assertEquals('AND ( color: #888; )', $groups[0][0]); 
        $this->assertEquals('AND( body ( color: #333; ) )', $groups[0][1]); 
        $this->assertEquals('( color: blue; )', $groups[0][2]); 
    }

    /**
     * Capture outer parenthesis groups using a repeating pattern that can contain: 
     * - nested balanced parenthesis (starts a new repeating group) 
     * - escaped (un)balanced parenthesis 
     * - any other char except parenthesis  
     * Groups can be prefixed with an optional AND|OR|NOT prefix.
     */ 
    public function testRecursiveAllowNestedBalancedParentheis_AndEscapedUnbalancedParentheis(){
        print __METHOD__ . "\n"; 
        $string =  
        "AND ( color: \) #888; )
        OR ( body ( color: \( #333; ) ) 
        NOT ( body \( color: \( #333; \) ) 
        ( help me (forest) help me ) 
        don't capture me ( color: blue;\(\) )"; 
 
        //$pattern = '/\((?:[^()]+|(?R))*\)/s';
        #$pattern = '/(?:AND[\s]*)?' . '\((?:[^()]+|(?R))*\)/s';
        # AND followed by space is optional, e.g., when we have descended into it.
        
        $pattern = '/(?:(?:AND|OR|NOT)[\s]*)?' . 
                '\((?:(?:\\\\[()]|[^()])+|(?R))*\)'.
                '/s';
                
        // 2nd line: 
        /*
         * \(                      # find first opening of '(' 
         *   (?:                   # | start a new group but don't capture group
         *      (?:                # |      |start a new group but don't capture group 
         *          \\\\[()]       # |      |  allow escaped parenthesis in group e.g. '\(' or '\)' 
         *            |            # |      |     or 
         *          [^()]          # |      |  allow any char except parenthesis in group
         *      )+                 # |      |close group, must occur once or more 
         *      |                  # |  or
         *      (?R)               # |  we may be at the start of a new group, repeat whole pattern.
         *   )*                    # close group, occurs zero or many 
         * \)                      # expect balanced closing ')'
         */
        preg_match_all($pattern, $string, $groups);
        //print_r($groups); 
        $this->assertEquals('AND ( color: \) #888; )', $groups[0][0]); 
        $this->assertEquals('OR ( body ( color: \( #333; ) )', $groups[0][1]); 
        $this->assertEquals('NOT ( body \( color: \( #333; \) )', $groups[0][2]); 
        $this->assertEquals('( help me (forest) help me )', $groups[0][3]); 
        $this->assertEquals('( color: blue;\(\) )', $groups[0][4]); 
    }
    



    /**
     * Capture outer parenthesis groups that can contain: 
     * - escaped (un)balanced parenthesis 
     * - any other char except parenthesis 
     * - Note, nested balanced parenthesis that are NOT escaped are NOT allowed
     * Groups can be prefixed with an optional AND|OR|NOT prefix.
     * 
     * Note, this method is functionally equal to {@link testPreParse6_equalto_PreParse5()} 
     * but has a slightly different regex.  
     */
    public function testPreParse5_equalto_PreParse6(){
        print __METHOD__ . "\n"; 
        $string = 
        "AND ( color: \) #888; )
        OR ( body \( color: \( #333; \) ) 
        NOT ( body \( color=\= \( #333; \) ) 
        ( help\\ me \(forest\) help me ) 
        don't capture me ( color: blue;\(\) \n\n\n )
        ( color: blue;\(\) \ ); 
        ( \ )
        ( \\ )
        ( tab\ttab )"; 
                
        // http://php.net/manual/en/regexp.reference.escape.php
        
        $pattern = '/(?:(?:AND|OR|NOT)[\s]*)?' .
                "\((?:\\\\[()]|[^()])+\)/s";
                
        /*
         * \(                      # find first opening of '(' 
         *      (?:                # |      |start a new group but don't capture group 
         *          \\\\[()]       # |      |  allow escaped parenthesis in group e.g. '\(' or '\)' 
         *            |            # |      |     or 
         *          [^()]          # |      |  allow any char except parenthesis in group
         *      )+                 # |      |close group, must occur once or more 
         * \)                      # expect balanced closing ')'
         */
        preg_match_all($pattern, $string, $groups);
        //print_r($groups); 
        
        $this->assertEquals("AND ( color: \) #888; )", $groups[0][0]); 
        $this->assertEquals("OR ( body \( color: \( #333; \) )", $groups[0][1]); 
        $this->assertEquals("NOT ( body \( color=\= \( #333; \) )", $groups[0][2]); 
        $this->assertEquals("( help\\ me \(forest\) help me )", $groups[0][3]); 
        $this->assertEquals("( color: blue;\(\) \n\n\n )", $groups[0][4]);  // note newlines are shown when printing to screen, which is correct !  
        $this->assertEquals("( color: blue;\(\) \ )", $groups[0][5]); 
        $this->assertEquals("( \ )", $groups[0][6]); 
        $this->assertEquals("( \\ )", $groups[0][7]);   // note, output shows only a single backslash when printing to screen, which is correct !  
        $this->assertEquals("( tab\ttab )", $groups[0][8]);   // note, output shows 'tab    tab' when printed to screen, which is correct !   
    }


    /**
     * Capture outer parenthesis groups that can contain: 
     * - escaped (un)balanced parenthesis 
     * - any other char except parenthesis 
     * - Note, nested balanced parenthesis that are NOT escaped are NOT allowed
     *   which is probably preferable compared to allowing nested balanced parenthesis 
     *   in the 'extensions' URL parameter value. 
     * Groups can be prefixed with an optional AND|OR|NOT prefix.
     * 
     * Note, this method is functionally equal to {@link testPreParse5_equalto_PreParse6()} 
     * but has a slightly different regex that may be more efficient.  
     */
    public function testPreParse6_equalto_PreParse5(){
        print __METHOD__ . "\n"; 
        // http://stackoverflow.com/questions/10208694/regex-to-parse-string-with-escaped-characters
        $string = 
        "AND ( color: \) #888; )
        OR ( body \( color: \( #333; \) ) 
        NOT ( body \( color=\= \( #333; \) ) 
        ( help\\ me \(forest\) help me ) 
        don't capture me ( color: blue;\(\) \n\n\n )
        ( color: blue;\(\) \ ); 
        ( \ )
        ( \\ )
        ( tab\ttab )"; 
                
        // http://php.net/manual/en/regexp.reference.escape.php
        
        $pattern = '/(?:(?:AND|OR|NOT)[\s]*)?' .
                "\((?:\\\\.|[^()\\\\])+\)/s";
                
        /*
         * \(                      # find first opening of '(' 
         *      (?:                # |      |start a new group but don't capture group 
         *          \\\\.          # |      |  allow any escaped char  (
         *            |            # |      |     or 
         *          [^()\\\\]      # |      |  allow any char except parenthesis or backslash in group
         *      )+                 # |      |close group, must occur once or more 
         * \)                      # expect balanced closing ')'
         */
        preg_match_all($pattern, $string, $groups);
        //print_r($groups); 

        $this->assertEquals("AND ( color: \) #888; )", $groups[0][0]); 
        $this->assertEquals("OR ( body \( color: \( #333; \) )", $groups[0][1]); 
        $this->assertEquals("NOT ( body \( color=\= \( #333; \) )", $groups[0][2]); 
        $this->assertEquals("( help\\ me \(forest\) help me )", $groups[0][3]); 
        $this->assertEquals("( color: blue;\(\) \n\n\n )", $groups[0][4]);  // note newlines are shown when printing to screen, which is correct !  
        $this->assertEquals("( color: blue;\(\) \ )", $groups[0][5]); 
        $this->assertEquals("( \ )", $groups[0][6]); 
        $this->assertEquals("( \\ )", $groups[0][7]);   // note, output shows only a single backslash when printing to screen, which is correct !  
        $this->assertEquals("( tab\ttab )", $groups[0][8]);   // note, output shows 'tab    tab' when printed to screen, which is correct ! 

        // next step to implement new ExtensionParser: 
        // iterate the groups,
        // record optional leading prefix (AND|OR|NOT) (throw if unexpected prefix), 
        // trim and remove leading/trailing parenthesis (throw if no leading/trailing parenthesis) 
        // spit on first equals '=' char to separate key and value (throw if no equals char present) 
        // check for allowed/illegal chars in key and value (throw if illegal chars present  
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
