<?php
namespace org\gocdb\services;
/* Copyright © 2011 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/Config.php';

/**
 * GOCDB Stateless service facade (business routnes) for scope objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */
class Scope extends AbstractEntityService{
    protected $configService;

    function __construct() {
        parent::__construct();
        $this->configService = \Factory::getConfigService();
    }


    /**
     * Set the Conifg service used by this class.
     * <p>
     * Used to override the default Conf service created on class construction.
     *
     * @param \org\gocdb\services\Config $configService
     */
    public function setConfigService(Config $configService){
        $this->configService = $configService;
    }

    /*
     * All the public service methods in a service facade are typically atomic -
     * they demarcate the tx boundary at the start and end of the method
     * (getConnection/commit/rollback). A service facade should not be too 'chatty,'
     * ie where the client is required to make multiple calls to the service in
     * order to fetch/update/delete data. Inevitably, this usually means having
     * to refactor the service facade as the business requirements evolve.
     *
     * If the tx needs to be propagated across different service methods,
     * consider refactoring those calls into a new transactional service method.
     * Note, we can always call out to private helper methods to build up a
     * 'composite' service method. In doing so, we must access the same DB
     * connection (thus maintaining the atomicity of the service method).
     */

    /**
     * Get either all scopes in the database or the scopes with the specified ids.
     * @param mixed $scopeIdArray Array of scope IDs as ints or null
     * @return array An array of Scope objects
     */
    public function getScopes($scopeIdArray = NULL) {
        // Note: empty array is converted to null by non-strict equal '==' comparison.
        // Use is_null() or '===' if there is possible of getting empty array.
        if($scopeIdArray === NULL){
            // get all scopes in the DB by default
            $dql = "SELECT s from Scope s ORDER BY s.name";
            $query = $this->em->createQuery($dql);
            return $query->getResult();
        }
        if(count($scopeIdArray) > 0){
            $dql = "SELECT s from Scope s WHERE s.id IN(:scopeIdArray) ORDER BY s.name";
            $query = $this->em->createQuery($dql)->setParameter('scopeIdArray', $scopeIdArray);
            return $query->getResult();
        }
        return array();
    }


    /**
     * Return a new filtered array by filtering the given $scopeArray or all of the
     * {@see \Scope}s in the DB according to the associative $filterParams.
     * <p>
     * Supported parameters in $filterParams include:
     * <ul>
     *   <li>'excludeDefault' => boolean (if true, exclude scopes that have a
     *       default value listed in the 'local_info.xml' config file</li>
     *   <li>'excludeNonDefault' => boolean (if true exclude scopes that are not default)</li>
     *   <li>'excludeReserved' => boolean (if true, exclude 'reserved' scopes,
     *      i.e. those that are flagged as reserved in the database scopes table</li>
     *   <li>'excludeNonReserved' => boolean (if true, exclude 'normal' scopes,
     *     i.e. those that are not flagged as reserved in the database scopes table</li>
     * </ul>
     *
     * @param array $filterParams Associative array
     * @param mixed $scopeArray Array of {@see \Scope} entities or null to filter
     *   all scopes in the DB.
     * @return array New array of \Scope instances
     */
    public function getScopesFilterByParams(array $filterParams, $scopeArray) {
        // The filterParams array can be extended with new key/val pairs. E.g.
        // 'excludeProvided' => array(of Scope instances to filter/exclude) from results

        if (!is_null($scopeArray)) {
            //Check that each entity scope is a scope
            foreach ($scopeArray as $scope) {
                if (!($scope instanceof \Scope)) {
                    throw new \InvalidArgumentException("object is not an instance of Scope.");
                }
            }
            $allScopes = $scopeArray;
        } else {
            $allScopes = $this->getScopes();
        }

        // Check the parameter keys are supoported
        $supportedParams = array('excludeNonDefault', 'excludeDefault', 'excludeReserved', 'excludeNonReserved');
        $testParamKeys = array_keys($filterParams);
        foreach ($testParamKeys as $key) {
        // if givenkey is not defined in supportedkeys it is unsupported
          if (!in_array($key, $supportedParams)) {
              throw new \InvalidArgumentException('Unsupported parameter key');
          }
    }

        $defaultScopeName = $this->configService->getDefaultScopeName();

        if (isset($filterParams['excludeNonDefault']) && $filterParams['excludeNonDefault'] == TRUE) {
            foreach ($allScopes as $scope) {
                if ($scope->getName() != $defaultScopeName) {
                    unset($allScopes[array_search($scope, $allScopes)]);
                }
            }
        }
        if (isset($filterParams['excludeDefault']) && $filterParams['excludeDefault'] == TRUE) {
            foreach ($allScopes as $scope) {
                if ($scope->getName() == $defaultScopeName) {
                    unset($allScopes[array_search($scope, $allScopes)]);
                }
            }
        }
        if(isset($filterParams['excludeReserved']) && $filterParams['excludeReserved'] == TRUE){
          foreach ($allScopes as $scope) {
            if($scope->getReserved()){
              unset($allScopes[array_search($scope, $allScopes)]);
            }
          }
        }
        if(isset($filterParams['excludeNonReserved']) && $filterParams['excludeNonReserved'] == TRUE){
          foreach ($allScopes as $scope) {
            if(!$scope->getReserved()){
              unset($allScopes[array_search($scope, $allScopes)]);
            }
          }
        }
        return $allScopes;
    }

    /**
     * Returns a Scope entity
     * @param integer $id Scope ID
     */
    public function getScope($id) {
        $dql = "SELECT s from Scope s WHERE s.id = :id";
        $query = $this->em->createQuery($dql)
                     ->setParameter('id', $id);
        return $query->getSingleResult();
    }

    /**
     * Finds all sites with a given scope tag
     * @param \Scope $scope
     * @return array collection of sites with specified scope
     */
    public function getSitesFromScope(\Scope $scope){
        $dql = "SELECT si
                FROM Site si
                JOIN si.scopes sc
                WHERE sc.id = :id
                ORDER BY si.shortName";
        $query = $this->em->createQuery($dql)
                          ->setParameter(":id", $scope->getId());
        return $query->getResult();
    }

     /**
     * Finds all NGIs with a given scope tag
     * @param \Scope $scope
     * @return array collection of NGIs with specified scope
     */
    public function getNgisFromScope(\Scope $scope){
        $dql = "SELECT n
                FROM NGI n
                JOIN n.scopes sc
                WHERE sc.id = :id
                ORDER BY n.name";
        $query = $this->em->createQuery($dql)
                          ->setParameter(":id", $scope->getId());
        return $query->getResult();
    }

     /**
     * Finds all Service Groups with a given scope tag
     * @param \Scope $scope
     * @return array collection of service groups with specified scope
     */
    public function getServiceGroupsFromScope(\Scope $scope){
        $dql = "SELECT sg
                FROM ServiceGroup sg
                JOIN sg.scopes sc
                WHERE sc.id = :id
                ORDER BY sg.name";
        $query = $this->em->createQuery($dql)
                          ->setParameter(":id", $scope->getId());
        return $query->getResult();
    }

     /**
     * Finds all services with a given scope tag
     * @param \Scope $scope
     * @return array collection of services with specified scope
     */
    public function getServicesFromScope(\Scope $scope){
        $dql = "SELECT se
                FROM Service se
                JOIN se.scopes sc
                WHERE sc.id = :id
                ORDER BY se.hostName";
        $query = $this->em->createQuery($dql)
                          ->setParameter(":id", $scope->getId());
        return $query->getResult();
    }

    /**
     * Deletes a scope. throws an error if the scope is in use, unless $inUse
     * Overide is set to true
     *
     * @param \scope $scope         Scope to be deleted
     * @param \User $user           User doing the deltion
     * @param boolean $inUseOveride If true, then the fact the scope is currently in use is ignored.
     * @throws \Exception
     */
    public function deleteScope(\scope $scope, \User $user= null, $inUseOveride=false){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        // get details of entities currently using this scope
        $ngis = $this->getNgisFromScope($scope);
        $sites =$this->getSitesFromScope($scope);
        $serviceGroups = $this->getServiceGroupsFromScope($scope);
        $services = $this->getServicesFromScope($scope);


        if (!$inUseOveride){
            //check to see if there are NGIs, Sites, Service Groups, & services
            // with this scope tag. If there are, throw exception.
            if(sizeof($ngis)>0){
              throw new Exception("This scope tag is still applied to one or more NGIs. ". $scope->getName() ."can not be deleted until these links are removed");
            }
            if(sizeof($sites)>0){
              throw new Exception("This scope tag is still applied to one or more Sites. ". $scope->getName() ."can not be deleted until these links are removed");
            }
            if(sizeof($serviceGroups)>0){
              throw new Exception("This scope tag is still applied to one or more Service Groups. ". $scope->getName() ."can not be deleted until these links are removed");
            }
            if(sizeof($services)>0){
              throw new Exception("This scope tag is still applied to one or more Services. ". $scope->getName() ."can not be deleted until these links are removed");
            }
        }

        //Start a transaction
        $this->em->getConnection()->beginTransaction();
        try {
            //remove scope from entites, if overise is true
            if($inUseOveride){
                foreach($ngis as $ngi){
                    $ngi->removeScope($scope);
                    $this->em->merge($ngi);
                }
                foreach($sites as $site){
                    $site->removeScope($scope);
                    $this->em->merge($site);
                }
                foreach($serviceGroups as $serviceGroup){
                    $serviceGroup->removeScope($scope);
                    $this->em->merge($serviceGroup);
                }
                foreach($services as $service){
                    $service->removeScope($scope);
                    $this->em->merge($service);
                }
            }

            //remove the scope
            $this->em->remove($scope);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Adds a new scope
     * @param array $values array containing the name of the new scope
     * @param \user $user user making the change
     * @return \Scope scope created
     * @throws \Exception
     */
    public function addScope($values, \user $user = null){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        //Check the values are actually there, the name is unique and validate the values as per the GOCDB schema
        $this->validate($values, true);

        //Start transaction
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //new scope object
            $scope = new \Scope();
            
            $this->populateScope($scope, $values);

            $this->em->persist($scope);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $scope;
    }

    /**
     * Edit an existing scope
     * @param \Scope $scope the scope to be changed
     * @param array $newValues array containing the name of the scope
     * @param \User $user user making the changes
     * @return \Scope $altered scope
     * @throws \Exception
     */
    public function editScope(\Scope $scope, $newValues, \User $user = null){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        //Validate the values as per the GOCDB schema and check values are present and valid and check the name is unique, if it has changed.
        $this->validate($newValues, false, $scope->getName());

        //Start transaction
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //set name
            $scope->setName($newValues['Name']);
            $scope->setDescription($newValues['Description']);
            
            $this->populateScope($scope, $newValues);

            $this->em->merge($scope);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $scope;
    }



    /**
     * Returns a 2D array - each element wraps an associative array for every
     * Scope in the DB; each child array nests a Scope and a boolean to indicate if
     * the Scope appears in the given array.
     * <p>
     * Used to provide checkboxes for scope selection when editing
     * exisiting entities in the web portal
     * <p>
     * Each element nests a child associative array;
     * array( 'scope' => {@see \Scope}, 'applied' => boolean )
     *
     * @param array $entityScopes \Scope objects
     * @return array 2D Array - elements are associative arrays containing the \Scope and a boolean
     * @throws \LogicException if given collection does not contain {@see \Scope} instances.
     */
    public function getAllScopesMarkProvided($entityScopes){
        //Check that each entity scope is a scope
        foreach ($entityScopes as $scope){
            if (!($scope instanceof \Scope)){
                throw new \LogicException("object is not a scope.");
            }
        }

        //create an array containing scopes that can be applied to an entity
        // and wheter or not they appear in the $entityScopes list
        $scopeArray=array();
        $allScopes = $this->getScopes();
        foreach ($allScopes as $scope) {
            $innerArray = array('scope'=>$scope, 'applied' => false);
            foreach ($entityScopes as $entityScope){
                if ($entityScope == $scope){
                    $innerArray['applied'] = true;
                }
            }
            $scopeArray[]=$innerArray;
        }

        return $scopeArray;
    }

    /**
     * Returns a 2D array - each element wraps an associative array for every
     * Scope in the DB; each child array nests a Scope and a boolean to indicate if
     * the Scope is a default scope.
     * <p>
     * Used to provide checkboxes for scope selection when adding new entities in the web portal.
     * <p>
     * Each element nests a 2D child associative array;
     * array( 'scope' => {@see \Scope}, 'applied' => boolean )
     *
     * @return array 2D Array - elements are associative arrays containing the \Scope and a boolean
     */
    public function getAllScopesMarkDefault(){
        //create an array containing scopes that can be applied to an entity
        // and wheter or not they appear in the $entityScopes list
        $scopeArray=array();
        $scopes = $this->getScopes();

        $defaultScopeName = $this->configService->getDefaultScopeName();

        foreach ($scopes as $scope) {
            $innerArray = array('scope'=>$scope, 'applied' => false);
            if ($scope->getName() == $defaultScopeName){
                $innerArray['applied'] = true;
            }
            $scopeArray[]=$innerArray;
        }
        return $scopeArray;
    }

    /**
     * Returns true if the name given is not currently in use for a scope
     * @param string $name potential scope type name
     * @return boolean
     */
     private function scopeNameIsUnique($name){
        $dql = "SELECT s from Scope s
                WHERE s.name = :name";
        $query = $this->em->createQuery($dql);
        $result = $query->setParameter('name', $name)->getResult();

        return count($result)==0;
    }

    /** 
     * Populate the scope instance
     * Note that to reserve a scope the Reserved key MUST be set in the input array
     * This is to be consistent with html checkbox behaviour
    */
    private function populateScope(\Scope $scope, $values) {
        
        $scope->setName($values['Name']);
        $scope->setDescription($values['Description']);
        $scope->setReserved(false);

        if (array_key_exists('Reserved', $values)) {
            $scope->setReserved($values['Reserved'] == true);
        } 
    }

    /**
     * Performs some basic checks on the values aray and then validates the user
     * inputted scope type data against the data in the gocdb_schema.xml.
     * @param array $scopeData containing all the fields for a GOCDB scope object
     * @param boolean $scopeIsNew true if the values are for a new scope
     * @param string $oldScopeName name of the sope before this cvhange. Only
     *                             relevant if scopeIsNew = false
     * @throws \Exception If the project's data can't be
     *                    validated. The \Exception message will contain a human
     *                    readable description of which field failed validation.
     * @return null */
    private function validate($scopeData, $scopeIsNew, $oldScopeName = '') {
        require_once __DIR__ . '/Validate.php';

        //check values are there
        if (!((array_key_exists('Name', $scopeData)) and ( array_key_exists('Description', $scopeData)))) {
            throw new \Exception("A name scope must be specified");
        }

        //check values are strings
        if (!((is_string($scopeData['Name'])) and ( is_string($scopeData['Description'])))) {
            throw new \Exception("The new scope name must be a valid string");
        }

        //check that the name is not null
        if (empty($scopeData['Name'])) {
            throw new \Exception("A name must be specified for the Scope");
        }

        //check the name is unique
        if (($scopeIsNew) or ( $scopeData['Name'] != $oldScopeName)) {
            if (!$this->scopeNameIsUnique($scopeData['Name'])) {
                throw new \Exception("Scope names must be unique, '" . $scopeData['Name'] . "' is already in use");
            }
        }
        
        // if reserved status specified it must be TRUE or false
        if (array_key_exists('Reserved', $scopeData)) {
          if ($scopeData['Reserved'] != 0 and $scopeData['Reserved'] != 1){
            throw new \Exception("Scope reserved status must be true(1) or false(0), '" . $scopeData['Reserved'] . "' is invalid.");
          }
        }

        //remove the ID fromt the values file if present (which it may be for an edit)
        if (array_key_exists("Id", $scopeData)) {
            unset($scopeData["Id"]);
        }

        $serv = new \org\gocdb\services\Validate();
        foreach ($scopeData as $field => $value) {
            $valid = $serv->validate('scope', strtoupper($field), $value);
            if (!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
    }

}