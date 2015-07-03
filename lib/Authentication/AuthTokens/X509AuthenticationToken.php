<?php
namespace org\gocdb\security\authentication;

require_once __DIR__ . '/../IAuthentication.php';

//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;

/**
 * An implementation of <code>IAuthentication</code> for use with X509 certificates. 
 *
 * @see IAuthentication
 * @author David Meredith 
 */
class X509AuthenticationToken implements IAuthentication {

    private $userDetails = null;
    private $authorities = array();
    private $initialDN = null;
    /** @var \Psr\Log\LoggerInterface logger methods */
    //private $logger; 

    public function __construct() {
        // create logger 
        //$this->logger = new Logger('X509AuthenticationTokenLogger');
        //$this->logger->pushHandler(new StreamHandler(__DIR__.'/../../../gocdb.log', Logger::DEBUG));
        
        $this->initialDN = $this->getDN();
        $this->userDetails = array('AuthenticationRealm' => array('IGTF')); 
    }

    /**
     * {@see IAuthentication::isStateless()} 
     */
    public static function isStateless() {
        return true;
    } 

    /**
     * {@see IAuthentication::isPreAuthenticating()} 
     */
    public static function isPreAuthenticating() {
        return true;   
    }

    /**
     * {@see IAuthentication::getCredentials()}
     * @return string An empty string as passwords are not used in X509. 
     */
    public function getCredentials() {
        return "";
    }

    /**
     * {@see IAuthentication::eraseCredentials()}
     * Does nothing, passwords not ussed in X509. 
     */
    public function eraseCredentials() {
        // do nothing, password not used for X509
    }

    /**
     * Return the user's DN string from the client's certificate stored in their 
     * browser. The DN is fetched once and cached on object creation. Subsequent 
     * invocations return the cached value.  
     * {@see IAuthentication::getPrinciple()) 
     * 
     * @return String Certifiate DN. 
     * @throws \RuntimeException if DN can't be extracted. 
     */
    public function getPrinciple() {
        return $this->initialDN;
    }

    /**
     * {@see IAuthentication::validate()} 
     * @throws AuthenticationException if validation fails
     */
    public function validate() {
        // if current DN is not the same as intial DN, if not raise hue and cry ! 
        if (strcmp($this->initialDN, $this->getDN()) != 0) {
            throw new AuthenticationException(null, 'Invalid state, DN is now different');
        }
    }

    private function getDN() {
        //$this->logger->addDebug('getDN()'); 
        if (isset($_SERVER['SSL_CLIENT_CERT'])) {
            $Raw_Client_Certificate = $_SERVER['SSL_CLIENT_CERT'];
            if (isset($Raw_Client_Certificate)) {
                $Plain_Client_Cerfificate = openssl_x509_parse($Raw_Client_Certificate);
                $User_DN = $Plain_Client_Cerfificate['name'];
                if (isset($User_DN)) {
                    // harmonise "email" field that can be different depending on version of SSL
                    $dn = str_replace("emailAddress=", "Email=", $User_DN);
                    if ($dn != null && $dn != '') {
                        return $dn;
                    }
                }
            }
        }
    }

    /**
     * A custom object used to store additional user details.  
     * Allows non-security related user information (such as email addresses, 
     * telephone numbers etc) to be stored in a convenient location. 
     * {@see IAuthentication::getDetails()}
     * 
     * @return Object or null if not used 
     */
    public function getDetails() {
        return $this->userDetails;
    }

    /**
     * {@see IAuthentication::getDetails()}
     * @param Object $userDetails
     */
    public function setDetails($userDetails) {
        $this->userDetails = $userDetails;
    }

    /**
     * {@see IAuthentication::getAuthorities()}
     * @return array 
     */
    public function getAuthorities() {
        return $this->authorities;
    }

    /**
     * {@see IAuthentication::setAuthorities($authorities)} 
     * @param array $authorities
     */
    public function setAuthorities($authorities) {
        $this->authorities = $authorities;
    }



}

