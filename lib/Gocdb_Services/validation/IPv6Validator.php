<?php
require_once __DIR__.'/IValidator.php';

/**
 * Validator for IPv6 address ranges. 
 *
 * @author David Meredith  
 */
class IPv6Validator implements IValidator {
   
    /**
     * Determine if the given object type can be validated by this class. 
     * @param string $object
     * @return boolean
     */
    public function supports($object) {
       return is_string($object);   
    }

    /**
     * Validate the given ipv6 address, errors are added to the $errors array. 
     * @param string $ipaddr IPv6 address with optional /int prefix  
     * @param array $errors 
     * @return array Errors array, if ip is valid, the no extra errors are added. 
     * @throws \LogicException if given params are not of expected type
     */
    public function validate($ipaddr, $errors) {
        if(!is_array($errors)){
            throw new \LogicException('$errors object is not an array'); 
        }
        if(!is_string($ipaddr)){
            throw new \LogicException('Ip address is not a string'); 
        }
        $cx = strpos($ipaddr, '/');
        if ($cx) {
            $subnet = intval((substr($ipaddr, $cx + 1)));
            if($subnet == 0){
              $errors[] = 'Invalid IPv6 netmask'; 
              return $errors; 
            }
            $ipaddr = substr($ipaddr, 0, $cx);
        } else {
            $subnet = null; // No netmask present
        }
        // FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE
        //$isValid = filter_var('2001:adb8:85a3:7334', FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV6, FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE) );
        $validIpAddr = filter_var($ipaddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        if ($validIpAddr != FALSE) {
            return $errors; // filter_val did not return false, so return 
        } else {
            $errors[] = 'Invalid IPv6 address range'; 
            return $errors; 
        }
    }

}
