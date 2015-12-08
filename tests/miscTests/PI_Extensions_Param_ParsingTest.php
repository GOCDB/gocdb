<?php
 require_once __DIR__. '/../../lib/Gocdb_Services/PI/QueryBuilders/ExtensionsQueryNormaliser.php';    
 require_once __DIR__.'/../../lib/Gocdb_Services/PI/QueryBuilders/KeyValueValidator.php';
 require_once __DIR__. '/../../lib/Gocdb_Services/PI/QueryBuilders/ExtensionsParser2.php';

/**
 * Demo tests of candiate regex statements for parsing the 'expression' PI parameter.
 * These tests demonstrate the business logic used to implement: 
 * {@see \org\gocdb\services\ExensionsQueryNormaliser::convert($query)}  
 * {@see \org\gocdb\services\KeyValueValidator}
 * {@see \org\gocdb\services\ExtensionsParser2}
 * <p>
 * The tests do not require a test DB. 
 *  
 * Usage: $phpunit UnitTest PI_Extensions_Param_ParsingTest.php
 * 
 * @link http://www.sylvainartois.fr.nf/PHP/Unit-testing-with-PHPUnit-part-one
 * @copyright 2013 STFC
 * @author David Meredith
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

    private $myVals = array(); 
    
    public function testDave(){
       $this->myVals[] = array(0, 'dave');
       print_r($this->myVals); 
       $this->assertNotEmpty($this->myVals); 
       is_array($this->myVals);
       is_array($this->myVals[0]);
    }


    /**
     * Capture outer curly bracket groups using a repeating pattern that can contain: 
     * - nested balanced curly brackets (no nested unbalanced escaped curly brackets are allowed) 
     * - any other char that is not a curly bracket 
     * Groups can be prefixed with an optional prefix that starts with @media. 
     */
    public function testDemoRecursiveRegex(){
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
    public function testDemoRecursiveRegex2(){
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
    public function testDemoRecursiveRegex3(){
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
     * Demo test to show regex capture of a repeating pattern that starts with an 
     * optional AND|OR|NOT and is followed by optional whitespace with an 
     * opening '(' containing any char inc escaped parenthesis and unescaped and 
     * balanced parethesis with a terminating ')'
     * 
     * Importantly, the regex uses repeating-group syntax (?R) that allows nested balanced parenthesis
     * to be captured within the outermost parenthesis, e.g. (((...))) 
     * The outer parenthesis group can contain: 
     * - nested balanced parenthesis (starts a new repeating group) 
     * - escaped (un)balanced parenthesis 
     * - any other char except parenthesis  
     * Groups can be prefixed with an optional AND|OR|NOT prefix.
     */ 
    public function testDemoRecursiveRegex4(){
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
                '\((?:(?:\\\\[()]|[^()])+|(?R))*\)/s';
        
         /* 1st list line: 
         * /                   # start regex
         *   (?:               #    |start a new group but don't catpure group
         *      (?:            #         |start a new group but don't capture group
         *         AND|OR|NOT  #         |  allow AND or OR or NOT 
         *      )              #         |close group
         *      [\s]*          #         allow multiple whitespace 
         *   )?                #    |close group, can occur zero or once (an optional group) 
         */
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
         * /s                  # end regex 
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
     * Demo test to show regex capture of repeating pattern that starts with an 
     * optional AND|OR|NOT and is followed by optional whitespace with an 
     * opening '(' containing any char inc escaped parenthesis with a terminating ')'
     * The parenthesis group can contain: 
     * - escaped parenthesis, both balenced and un-balenced 
     * - any other char except parenthesis 
     * - Note, nested balanced parenthesis that are NOT escaped are NOT allowed
     * Groups can be prefixed with an optional AND|OR|NOT prefix.
     * 
     * Note, this method is functionally equal to {@link testDemoEscapedParenthesis2()} 
     * but has a slightly different regex.  
     */
    public function testDemoEscapedParenthesis1(){
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
            
        /* 1st list line: 
         * /                   # start regex
         *   (?:               #    |start a new group but don't catpure group
         *      (?:            #         |start a new group but don't capture group
         *         AND|OR|NOT  #         |  allow AND or OR or NOT 
         *      )              #         |close group
         *      [\s]*          #         allow multiple whitespace 
         *   )?                #    |close group, can occur zero or once (an optional group) 
         */
        // 2nd line in pattern: 
        /*
         * \(                      # find first opening of '(' 
         *      (?:                # |      |start a new group but don't capture group 
         *          \\\\[()]       # |      |  allow escaped parenthesis in group e.g. '\(' or '\)' note, need 4 backslashes for this http://php.net/manual/en/regexp.reference.escape.php 
         *            |            # |      |     or 
         *          [^()]          # |      |  allow any char except parenthesis in group
         *      )+                 # |      |close group, must occur once or more 
         * \)                      # expect balanced closing ')'
         * /s                  # end regex 
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
     * Demo test to show regex capture of repeating pattern that starts with an 
     * optional AND|OR|NOT and is followed by optional whitespace with an 
     * opening '(' containing any char inc escaped parenthesis with a terminating ')'  
     * The parenthesis group can contain: 
     * - escaped parenthesis, both balanced and un-balanced 
     * - any other char except parenthesis 
     * - Note, nested balanced parenthesis that are NOT escaped are NOT catered for, 
     *   which is probably preferable compared to allowing nested balanced parenthesis 
     *   in the 'extensions' URL parameter value. 
     * Groups can be prefixed with an optional AND|OR|NOT prefix.
     * 
     * Note, this method is functionally equal to {@link testDemoEscapedParenthesis1()} 
     * but has a slightly different regex that may be more efficient.  
     */
    public function testDemoEscapedParenthesis2(){
        print __METHOD__ . "\n"; 

        $string = 
        "   AND ( color: \) #888; )  ".   // note leading/trailing whitespace is not captured
        "  OR ( body \( color: \( #333; \) ) 
        NOT ( body \( color=\= \( #333; \) ) 
        ( help\\ me \(forest\) help me ) 
        don't capture me ( color: blue;\(\) \n\n\n )
        ( color: blue;\(\) \ ); 
        ( \ )
        ( \\ )
        ( tab\ttab )
        (\\\)
        (\\\\)
        NOT(\\\\////)
        this line with unrecognised predicate and empty parentheis is not captured ()";     
        $expectedCaptureCount = 12; // last line is not captured hence 12 not 13   
        
        // Regarding $optionalPredicateAndWhitespace :  
        // Its really important to capture the optional predicate and trailing whitespace
        // in one group because this affects how the pattern is repeatedly 
        // matched - the pattern does NOT include leading whitespace!
        // Therefore, a match will only start with EITHER a leading '(' or the 
        // first char from one of the OR'd predicates: 'A' 'O' 'N'
        $optionalPredicateAndWhitespace = '(?:(?:AND|OR|NOT)[\s]*)?' ; 
        $parethesisGroup = '\((?:\\\\.|[^()\\\\])+\)'; 
       
        $patternMatchRepeat = '/' . $optionalPredicateAndWhitespace . $parethesisGroup . '/s'; 
        
        // http://stackoverflow.com/questions/10208694/regex-to-parse-string-with-escaped-characters
        
        /* $optionalPredicateAndWhitespace
         * /                   # start regex
         *   (?:               #    |start a new group but don't catpure group
         *      (?:            #         |start a new group but don't capture group
         *         AND|OR|NOT  #         |  allow AND or OR or NOT 
         *      )              #         |close group
         *      [\s]*          #         allow multiple whitespace 
         *   )?                #    |close group, can occur zero or once (an optional group) 
         */
        /* $parethesisGroup
         * \(                  #     find first opening of '(' 
         *      (?:            #     |      |start a new group but don't capture group 
         *          \\\\.      #     |      |  allow any escaped char inc escaped parethesis \(  and escaped backslash \\
         *            |        #     |      |     or 
         *          [^()\\\\]  #     |      |  allow any char except parenthesis or backslash in group
         *      )+             #     |      |close group, must be one or more chars in group  
         * \)                  #     find balanced closing ')'
         * /s                  # end regex, /s is dotall mode - a dot metacharacter 
         *                     # in the pattern matches all characters, including newlines. 
         *                     # Without it, newlines are excluded
         */
        // Note we don't capture any groups, therefore the regex captures the 
        // whole (repeating) pattern that starts with an optional AND|OR|NOT and ends with a closing )  
        // Note, to escape a char we need to use 4 backslahses: http://php.net/manual/en/regexp.reference.escape.php
     
        // preg_match_all
        // - Returns the number of full pattern matches (which might be zero),
        // or FALSE if an error occurred. 
        // - Default PREG_PATTERN_ORDER orders results so that $groups[0] is an 
        // array of full pattern matches, $groups[1] is an array of strings 
        // matched by the first parenthesized subgroup, and so on. Since we 
        // are not capturing any sub-groups, only $groups[0] has matched values. 
        $patternMatchCount = preg_match_all($patternMatchRepeat, $string, $groups);
        //print_r($groups); 
        $this->assertEquals($patternMatchCount, $expectedCaptureCount); 

        $this->assertEquals("AND ( color: \) #888; )", $groups[0][0]); 
        $this->assertEquals("OR ( body \( color: \( #333; \) )", $groups[0][1]); 
        $this->assertEquals("NOT ( body \( color=\= \( #333; \) )", $groups[0][2]); 
        $this->assertEquals("( help\\ me \(forest\) help me )", $groups[0][3]); 
        $this->assertEquals("( color: blue;\(\) \n\n\n )", $groups[0][4]);  // note newlines are shown when printing to screen, which is correct !  
        $this->assertEquals("( color: blue;\(\) \ )", $groups[0][5]); 
        $this->assertEquals("( \ )", $groups[0][6]); 
        $this->assertEquals("( \\ )", $groups[0][7]);   // note, output shows only a single backslash when printing to screen, which is correct !  
        $this->assertEquals("( tab\ttab )", $groups[0][8]);   // note, output shows 'tab    tab' when printed to screen, which is correct ! 
        $this->assertEquals("(\\\)", $groups[0][9]);
        $this->assertEquals("(\\\\)", $groups[0][10]);
        $this->assertEquals("NOT(\\\\////)", $groups[0][11]);
        // next step to implement new ExtensionParser: 
        // iterate the groups,
        // record optional leading prefix (AND|OR|NOT) (throw if unexpected prefix), 
        // trim and remove leading/trailing parenthesis (throw if no leading/trailing parenthesis) 
        // spit on first equals '=' char to separate key and value (throw if no equals char present) 
        // check for allowed/illegal chars in key and value e.g. something like below (throw if illegal chars present  
       
        $matches = array_values($groups[0]); //returns all the values from the array and indexes the array numerically
        //foreach($matches as $match){ echo($match."\n"); }
        // foreach prints the following on the command line, notice how the 
        // \t \n escaped slashes are interpreted when printed to screen, which is correct. 
        /*
         AND ( color: \) #888; )
         OR ( body \( color: \( #333; \) )
         NOT ( body \( color=\= \( #333; \) )
         ( help\ me \(forest\) help me )
         ( color: blue;\(\)
 
 
          )
         ( color: blue;\(\) \ )
         ( \ )
         ( \ )
         ( tab   tab )
         (\\)
         (\\)
         NOT(\\////)
         */
        
        // iterate the matches and create expected return format, e.g.  
        /* the query 'AND(VO=Alice)(VO=Atlas)NOT(VO=LHCB)' requires this format:
        Array
        (
            [0] => Array
                (
                    [0] => AND
                    [1] => VO=Alice
                )
        
            [1] => Array
                (
                    [0] => AND
                    [1] => VO=Atlas
                )
        
            [2] => Array
                (
                    [0] => NOT
                    [1] => VO=LHCB
                )
        ) */

        
        
        
        $lastEncounteredPredicate = 'AND';  //default if not specified
        $normalizedKV = array(); 
        foreach($matches as $match){
            $firstChar = substr($match, 0, 1); 
            //echo $firstChar; // one of the following: ( A O N
            if($firstChar == '('){
                $predicate = $lastEncounteredPredicate; 
            } else if(strtolower($firstChar) == 'a'){
                $predicate = 'AND'; 
            } else if(strtolower($firstChar) == 'o'){
                $predicate = 'OR';         
            } else if(strtolower($firstChar) == 'n'){
                $predicate = 'NOT';         
            } else {
                throw new \InvalidArgumentException('Invalid expression, could not extract predicate'); 
            }
            $lastEncounteredPredicate = $predicate; 
            //echo " ".$predicate."\n"; 
            
            // Find the position of the first occurrence of '(' (zero offset) 
            $openingParenthesisPos = strpos($match, '('); 
            if($openingParenthesisPos === FALSE){
                throw new \InvalidArgumentException('Invalid expression, could not extract starting parenthesis'); 
            }
            // extract just the (xxxxxx) part of the expression 
            $keyValPair = substr($match, $openingParenthesisPos, strlen($match) ); 
            // trim the leading and trailing parenthesis 
            $keyValPairNoParenthesis = substr($keyValPair, 1, strlen($keyValPair)-2); 
            //echo $predicate.' '.$keyValPairNoParenthesis."\n"; 
            
            // build expected/normalized array 
            $pred_kv = array(); 
            $pred_kv[] = $predicate; 
            $pred_kv[] = $keyValPairNoParenthesis; 
            $normalizedKV[] = $pred_kv; 
        }
        //print_r($normalizedKV); 

        // Test testDemoEscapedParenthesis2_GreedyMatchWholeExpression() also uses a v.similar pattern but instead 
        // uses a greedy match to test that the whole regex is valid and has 
        // no invalid content such as un-supported predicates and interleaving text. 
        // See next test below. 
    }
  
    /**
     * Demo test that shows how to greedy match a whole extensions expression 
     * (rather than individual predicates and parenthesis groups). 
     * @throws \LogicException
     */
    public function testDemoEscapedParenthesis2_GreedyMatchWholeExpression(){
        print __METHOD__ . "\n"; 
        
        // http://stackoverflow.com/questions/10208694/regex-to-parse-string-with-escaped-characters
        $optionalPredicateAndWhitespaceGroup = '(?:(?:AND|OR|NOT)[\s]*)?' ; // occurs 0 or 1 
        $parethesisGroup = '\((?:\\\\.|[^()\\\\])+\)';  // occurs 1
            
        $patternGreedyMatchAllLine = 
                '/^'.    // anchor left
                  '(?:'.   // start main group but don't capture group
                      '[\s]*'.  // optional whitespace
                      $optionalPredicateAndWhitespaceGroup. // occurs 0 or 1
                      $parethesisGroup.  // occurs 1
                      '[\s]*'.  // optional whitespace
                  ')+'.    // close main group, at least one required 
                '$/s';   // anchor right, keep newlines        

        // This string is valid, there are no invalid predicates or rogue strings
        // interleaved inbetween parenthesis groups 
        $string = 
        "  AND ( color: \) #888; )  ".   // note leading/trailing whitespace is allowed 
        "OR ( body \( color: \( #333; \) ) 
        NOT ( body \( color=\= \( #333; \) ) 
        ( help\\ me \(forest\) help me ) 
        NOT ( color: blue;\(\) \n\n\n )
        ( color: blue;\(\) \ ) 
        ( \ ) 
        ( \\ )
        OR ( tab\ttab )
        (\\\)
        (\\\\)
        NOT(\\\\////)  "; 
        
        // returns 1 if match, 0 if not match, FALSE if error 
        $matchAllString = preg_match($patternGreedyMatchAllLine, $string); 
        if($matchAllString === FALSE){
            throw new \LogicException("An error occurred parsing query"); 
        }
        $this->assertEquals($matchAllString, 1); 

        // This string is invalid, notice the rogue string sitting outside the parethesis 
        $string = " AND ( color: \) #888; )  thisfailmatch ".   // note leading/trailing whitespace is allowed 
        "OR ( body \( color: \( #333; \) ) "; 
        
        $matchAllString = preg_match($patternGreedyMatchAllLine, $string); 
        if($matchAllString === FALSE){
            throw new \LogicException("An error occurred parsing query"); 
        }
        $this->assertEquals($matchAllString, 0); 
    }

    
    public function test_DemoStripSlashes(){
        print __METHOD__ . "\n"; 
        $query = 'NOT  (color: blue;\(\) \n\n\n) ';
        $query = stripslashes($query); 
        // Returns a string with backslashes stripped off. (\' becomes ' and so on.) 
        // Double backslashes (\\) are made into a single backslash (\). 
        $this->assertEquals('NOT  (color: blue;() nnn) ', $query); 

        $query = 'NOT(\\\\////) '; 
        $query = stripslashes($query); 
        $this->assertEquals('NOT(\////) ', $query); 

        $query = '( help\\ me \(forest\) help me )'; 
        $query = stripslashes($query); 
        $this->assertEquals('( help me (forest) help me )', $query); 
        
        $query = '\\\\'; 
        $query = stripslashes($query); 
        $this->assertEquals('\\', $query);  // a single slash (which itself needs to be escaped in the expected value !) 
       
        $query = '\\\\\\\\'; 
        $query = stripslashes($query); 
        $this->assertEquals('\\\\', $query);  // a double slash '\\' (which itself needs to be escaped in the expected value !) 
        
        $query = '( tab\ttab )'; 
        $query = stripslashes($query); 
        $this->assertEquals('( tabttab )', $query); 
    }

    public function test_ExensionsQueryNormaliser(){
        print __METHOD__ . "\n"; 
        $extNorm = new \org\gocdb\services\ExtensionsQueryNormaliser(); 
        
        $query = 'AND(VO=Alice)(VO=Atlas)NOT(VO=LHCB)';  
        $normalisedQuery = $extNorm->convert($query); 
        //print_r($normalisedQuery); 
        $this->assertEquals('AND', $normalisedQuery[0][0]); 
        $this->assertEquals('VO=Alice', $normalisedQuery[0][1]); 
        $this->assertEquals('AND', $normalisedQuery[1][0]); 
        $this->assertEquals('VO=Atlas', $normalisedQuery[1][1]); 
        $this->assertEquals('NOT', $normalisedQuery[2][0]); 
        $this->assertEquals('VO=LHCB', $normalisedQuery[2][1]); 
        
        $query= 
        "  AND ( color: \) #888; )  ".   // note leading/trailing whitespace is allowed 
        "OR ( body \( color: \( #333; \) ) 
        NOT ( body \( color=\= \( #333; \) ) 
        ( help\\ me \(forest\) help me ) 
        NOT ( color: blue;\(\) \n\n\n )
        ( color: blue;\(\) \ ) 
        ( \ ) 
        ( \\ )
        OR ( tab\ttab )
        (\\\)
        (\\\\)
        NOT(\\\\////)  "; 
        $normalisedQuery = $extNorm->convert($query); 
        //print_r($normalisedQuery);  
        $this->assertEquals('AND', $normalisedQuery[0][0]);   
        $this->assertEquals(' color: \) #888; ', $normalisedQuery[0][1]); // note whitespace WITHIN value is preserved, inc leading/trailing  
        $this->assertEquals('OR', $normalisedQuery[1][0]);   
        $this->assertEquals(' body \( color: \( #333; \) ', $normalisedQuery[1][1]); // note whitespace WITHIN value is preserved, inc leading/trailing  
        $this->assertEquals('NOT', $normalisedQuery[2][0]);   
        $this->assertEquals(' body \( color=\= \( #333; \) ', $normalisedQuery[2][1]); 
        $this->assertEquals('NOT', $normalisedQuery[3][0]);   
        $this->assertEquals(' help\\ me \(forest\) help me ', $normalisedQuery[3][1]); 
        $this->assertEquals('NOT', $normalisedQuery[4][0]);   
        $this->assertEquals(" color: blue;\(\) \n\n\n ", $normalisedQuery[4][1]); //note " needed to evaluate newline 
        $this->assertEquals('NOT', $normalisedQuery[5][0]);   
        $this->assertEquals(" color: blue;\(\) \ ", $normalisedQuery[5][1]);  
 
        $this->assertEquals('OR', $normalisedQuery[8][0]);   
        $this->assertEquals(" tab\ttab ", $normalisedQuery[8][1]);   // note " to evaluate tab 
        
        $this->assertEquals('OR', $normalisedQuery[10][0]);   
        $this->assertEquals('\\\\', $normalisedQuery[10][1]);  
        $this->assertEquals('NOT', $normalisedQuery[11][0]);   
        $this->assertEquals('\\\\////', $normalisedQuery[11][1]);  

        // print 
//        foreach($normalisedQuery as $singleOperatorAndExpression){
//           $operator = $singleOperatorAndExpression[0]; 
//           $expression= $singleOperatorAndExpression[1]; 
//           print_r($operator.'  ['.$expression."]\n");  
//        }
    }

    public function test_KeyValueValidator() {
        print __METHOD__ . "\n";
        $keyValValidator = new \org\gocdb\services\KeyValueValidator();

        $errors = array();
        $errors = $keyValValidator->validate(' VOa= testa ', $errors);
        //print_r($errors); 
        $this->assertEmpty($errors);

        $errors = array();
        $errors = $keyValValidator->validate(' VOa testa ', $errors); // missing = char
        $this->assertEquals(1, count($errors));

        $errors = array();
        $errors = $keyValValidator->validate('VOc=testc;', $errors); 
        $this->assertEquals(0, count($errors));
        
        $errors = array();
        // no equals char and empty string 
        $multipleInvalidKVpairs = array('no equals char', 'key=(charIn value);', ''); 
        foreach($multipleInvalidKVpairs as $keyValPair){
            $errors = $keyValValidator->validate($keyValPair, $errors);
        }
        $this->assertEquals(2, count($errors));
        //print_r($errors); 
        
    }

    public function test_ExensionsParser2() {
        print __METHOD__ . "\n";
        $extP = new \org\gocdb\services\ExtensionsParser2();

        $query = "(VO2=bing)AND(VO2=baz) OR(VO=bar) NOT(s1p1=v1)";
        $normalisedStripped = $extP->parseQuery($query); 
        $this->assertEquals('AND', $normalisedStripped[0][0]); 
        $this->assertEquals('VO2=bing', $normalisedStripped[0][1]); 
        $this->assertEquals('AND', $normalisedStripped[1][0]); 
        $this->assertEquals('VO2=baz', $normalisedStripped[1][1]); 
        $this->assertEquals('OR', $normalisedStripped[2][0]); 
        $this->assertEquals('VO=bar', $normalisedStripped[2][1]); 
        $this->assertEquals('NOT', $normalisedStripped[3][0]); 
        $this->assertEquals('s1p1=v1', $normalisedStripped[3][1]); 


        $query = "  AND (key= color: \) #888; )  " . // note leading/trailing whitespace is allowed 
                "OR (key= body \( color: \( #333; \) ) 
        NOT (key= body \( color=\= \( #333; \) ) 
        (key=help\\ me \(forest\) help me ) 
        NOT (key=color: blue;\(\) \n\n\n )
        (key=color: blue;\(\) \ ) 
        (key= \ ) 
        (key= \\ )
        OR (key= tab\ttab )
        (key=\\\)
        (key=\\\\)
        NOT(key=\\\\////)  AND(A=b)";
       
        $normalisedStripped = $extP->parseQuery($query); 
        $this->assertEquals('AND', $normalisedStripped[0][0]); 
        $this->assertEquals('key= color: ) #888; ', $normalisedStripped[0][1]); 
        
        $this->assertEquals('OR', $normalisedStripped[1][0]); 
        $this->assertEquals('key= body ( color: ( #333; ) ', $normalisedStripped[1][1]); 
        
        $this->assertEquals('NOT', $normalisedStripped[2][0]); 
        $this->assertEquals('key= body ( color== ( #333; ) ', $normalisedStripped[2][1]); 

        $this->assertEquals('NOT', $normalisedStripped[3][0]); 
        $this->assertEquals('key=help me (forest) help me ', $normalisedStripped[3][1]); 

        $this->assertEquals('NOT', $normalisedStripped[4][0]); 
        $this->assertEquals("key=color: blue;() \n\n\n ", $normalisedStripped[4][1]); // note we need double quote to evaluate the newline 

        $this->assertEquals('NOT', $normalisedStripped[6][0]); 
        $this->assertEquals('key=  ', $normalisedStripped[6][1]);  // two spaces 

        $this->assertEquals('NOT', $normalisedStripped[7][0]); 
        $this->assertEquals('key=  ', $normalisedStripped[7][1]);  // two spaces 

        $this->assertEquals('OR', $normalisedStripped[8][0]); 
        $this->assertEquals("key= tab\ttab ", $normalisedStripped[8][1]); // note we need double quote to evaluate the tab 
                
        $this->assertEquals('OR', $normalisedStripped[9][0]); 
        $this->assertEquals('key=\\', $normalisedStripped[9][1]);  // slingle slash (extra slash is to escape closing quote ' 
        
        $this->assertEquals('OR', $normalisedStripped[10][0]); 
        $this->assertEquals('key=\\', $normalisedStripped[10][1]);  // single slash (extra slash is to escape closing quote ' 

        $this->assertEquals('NOT', $normalisedStripped[11][0]); 
        $this->assertEquals('key=\////', $normalisedStripped[11][1]); // single slash followed by 4 forward slash  
        
        $this->assertEquals('AND', $normalisedStripped[12][0]); 
        $this->assertEquals('A=b', $normalisedStripped[12][1]); 
        
        //print_r($normalisedStripped); 
    }
    
    /*public function not_testDemoValidateFullExpressionAndExtractCaptures(){
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
     }*/

    
}


