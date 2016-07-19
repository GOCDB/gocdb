<?php
namespace org\gocdb\security\authentication;

require_once __DIR__.'/AuthenticationException.php';
/**
 * Thrown if an authentication request is rejected because the credentials are invalid.
 *
 * @author David Meredith
 */
class BadCredentialsException extends AuthenticationException {

     public function __construct(\Exception $priorException=null, $errorDetail=''){
        $this->errorDetail = $errorDetail;
        $this->priorException = $priorException;
        parent::__construct($priorException=null, $errorDetail);
     }

}

?>
