<?php
namespace org\gocdb\security\authentication;


/**
 * Description of UsernameNotFoundException
 *
 * @author David Meredith
 */
class UsernameNotFoundException extends \Exception {
    private $errorDetail = '';
    private $priorException = null;

    /**
     * Note, the $priorException replicates the final getPrevious()
     * method that was added in PHP 5.3 (this enables exception nesting in PHP 5.0+).
     * @see http://www.php.net/manual/en/language.exceptions.extending.php
     *
     * @param \Exception $priorException
     * @param type $errorDetail
     */
     public function __construct(\Exception $priorException=null, $errorDetail=''){
        $this->errorDetail = $errorDetail;
        $this->priorException = $priorException;
         parent::__construct($errorDetail);
     }
    /**
     * Get the detail error message (if any)
     * @return string
     */
    public function getErrorDetail(){
        return $this->errorDetail;
    }

    /**
     * Get the nested causal exception (if any). Note, this replicates the final
     * getPrevious() method that was added in PHP 5.3 (adding our own method
     * enables exception nesting in PHP 5.0+).
     * @see http://www.php.net/manual/en/language.exceptions.extending.php
     * @return Exception
     */
    public function getPriorException(){
        // dont call this method getPrevious as in php5.3+ because this
        // method is final and we want this to run in 5 and 5.3+
        return $this->priorException;
    }
}

?>
