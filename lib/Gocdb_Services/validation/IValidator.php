<?php

/**
 * Generic validator interface for validating objects. 
 * The interface defines a method to determine the supported object type 
 * targeted for validation and the subsequent validate method.   
 * 
 * @author David Meredith  
 */
interface IValidator {
  /**
   * Determine if the given object can be validated. 
   * @param mixed $object The object targed for validation.  
   * @return boolean true if supported otherwise false
   */  
  public function supports($object); 
  
  /**
   * Validate the target object. Validation error strings will be added to the
   * given errors array (the array must exist). If there are no 
   * errors, the size of the array will not be changed. 
   * 
   * @param mixed $object validate the given object 
   * @param array $errors add error strings to the given array 
   * @return array the errors array 
   * @throws \LogicException if given params are not of expected type
   */
  public function validate($object, $errors); 
}
