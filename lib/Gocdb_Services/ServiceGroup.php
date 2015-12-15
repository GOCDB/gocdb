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
require_once __DIR__ . '/Role.php';
require_once __DIR__ . '/RoleConstants.php';
require_once __DIR__ . '/RoleActionAuthorisationService.php'; 

/**
 * GOCDB Stateless service facade (business routnes) for Service Group objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */
class ServiceGroup extends AbstractEntityService{
    
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

    private $roleActionAuthorisationService;

    function __construct(/*$roleActionAuthorisationService*/) {
        parent::__construct();
        //$this->roleActionAuthorisationService = $roleActionAuthorisationService;
    }


    public function setRoleActionAuthorisationService(RoleActionAuthorisationService $roleActionAuthService){
        $this->roleActionAuthorisationService = $roleActionAuthService; 
    }
    
    

    /**
     * Finds a single service group by ID and returns its entity
     * @param int $id the service group ID
     * @return ServiceGroup a service group object
     */
    public function getServiceGroup($id) {
        $dql = "SELECT s FROM ServiceGroup s
                WHERE s.id = :sGroupId";

        $serviceGroup = $this->em
            ->createQuery($dql)
            ->setParameter('sGroupId', $id)
            ->getSingleResult();

        return $serviceGroup;
    }

    /**
     * Return all {@see \ServiceGroup}s that satisfy the specfied filter parameters. 
     * <p>  
     * $filterParams defines an associative array of optional parameters for 
     * filtering the serviceGroups. The supported Key => Value pairs include: 
     * <ul>
     *   <li>'scope' => 'String,comma,sep,list,of,scopes,e.g.,egi,wlcg'</li>
     *   <li>'service_group_name' => String name of service group</li>
     *   <li>'scope_match' => String 'any' or 'all' </li>
     *   <li>'extensions' => String extensions expression to filter custom key=value pairs</li>
     * <ul>
     * 
     * @param array $filterParams
     * @return array ServiceGroup array
     */
    public function getServiceGroupsFilterByParams($filterParams){
    require_once __DIR__.'/PI/GetServiceGroup.php';
    $getSg = new GetServiceGroup($this->em);
    $getSg->validateParameters($filterParams);
    $getSg->createQuery();
    $sgs = $getSg->executeQuery();
    return $sgs;
    }

    /**
     * Returns an array of all Service Group entities and joined scopes. 
     * @param string $scope Scope name
     * @param string $keyname ServiceGroup extension property key name
     * @param string $keyvalue ServiceGroup extension property key value 
     * @return array An array of ServiceGroup objects
     */
    public function getServiceGroups($scope = NULL, $keyname = NULL, $keyvalue = NULL) {
    $qb = $this->em->createQueryBuilder();
    $qb->select('s', 'sc')->from('ServiceGroup', 's')
        ->leftJoin('s.scopes', 'sc');

    if ($scope != null && $scope != '%%') {
        $qb->andWhere($qb->expr()->like('sc.name', ':scope'))
            ->setParameter(':scope', $scope);
    }

    if ($keyname != null && $keyname != '%%') {
        if ($keyvalue == null || $keyvalue == '') {
        $keyvalue = '%%';
        }

        $sQ = $this->em->createQueryBuilder();
        $sQ->select('s1' . '.id')
            ->from('ServiceGroup', 's1')
            ->join('s1.serviceGroupProperties', 'sp')
            ->andWhere($sQ->expr()->andX(
                    $sQ->expr()->eq('sp.keyName', ':keyname'), $sQ->expr()->like('sp.keyValue', ':keyvalue')));

        $qb->andWhere($qb->expr()->in('s', $sQ->getDQL()));
        $qb->setParameter(':keyname', $keyname)
            ->setParameter(':keyvalue', $keyvalue);
    }

    $query = $qb->getQuery();
    $serviceGroups = $query->execute();
    return $serviceGroups;
    }

    /**
     * Returns the downtimes linked to a service group.
     * @param integer $id Service Group ID
     * @param integer $dayLimit Limit to downtimes that are only $dayLimit old (can be null) */
    public function getDowntimes($id, $dayLimit) {
        if($dayLimit != null) {
            $di = \DateInterval::createFromDateString($dayLimit . 'days');
            $dayLimit = new \DateTime();
            $dayLimit->sub($di);
        }

        /*$dql = "SELECT d FROM Downtime d
                WHERE d.id IN (
                    SELECT d2.id FROM ServiceGroup s
                    JOIN s.services ses
                    JOIN ses.downtimes d2
                    WHERE s.id = :sGroupId
                )
                AND (
                    :dayLimit IS NULL
                    OR d.startDate > :dayLimit
                )";*/
        $dql = "SELECT d FROM Downtime d
                WHERE d.id IN (
                    SELECT d2.id FROM ServiceGroup s
                    JOIN s.services ses
                    JOIN ses.endpointLocations els
                    JOIN els.downtimes d2
                    WHERE s.id = :sGroupId
                )
                AND (
                    :dayLimit IS NULL
                    OR d.startDate > :dayLimit
                )";

        $downtimes = $this->em
            ->createQuery($dql)
            ->setParameter('sGroupId', $id)
            ->setParameter('dayLimit', $dayLimit)
            ->getResult();

        return $downtimes;
    }

    /**
     *
     * @return array of all properties for a service group
     */
    public function getProperties($id) {
        $dql = "SELECT p FROM ServiceGroupProperty p WHERE p.parentServiceGroup_id = :ID";
        $properties = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();
        return $properties;
    }

    /**
     *
     * @return a single service group property
     */
    public function getProperty($id) {
        $dql = "SELECT p FROM ServiceGroupProperty p WHERE p.id = :ID";
        $property = $this->em->createQuery ( $dql )->setParameter ( 'ID', $id )->getOneOrNullResult ();

        return $property;
    }

    /**
     * Edits a service group
     * Returns the updated service group
     *
     * Accepts an array $newValues as a parameter. $newVales' format is as follows:
     * <pre>
     *  Array
     *  (
     *      [MONITORED] => Y
     *      [NAME] => NGI_AEGIS_SERVICES
     *      [DESCRIPTION] => NGI_AEGIS Core Services
     *      [EMAIL] => grid-admin@ipb.ac.rs
     *
     *  )
     * </pre>
     * @param ServiceGroup The service group to update
     * @param array $newValues Array of updated data, specified above.
     * @param User The current user
     * return ServiceGroup The updated service group
     */
    public function editServiceGroup(\ServiceGroup $sg, $newValues, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        if ($this->roleActionAuthorisationService->authoriseAction(
                        \Action::EDIT_OBJECT, $sg, $user)->getGrantAction() == FALSE) {
            throw new \Exception("You don't have permission over this service group.");
        }
        $this->validate($newValues['SERVICEGROUP']);

        //check there are the required number of scopes specified
        $this->checkNumberOfScopes($newValues['Scope_ids']);
        
        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();

        try {
            if ($newValues['MONITORED'] == "Y") {
                $monitored = true;
            } else {
                $monitored = false;
            }
            $sg->setMonitored($monitored);

            $sg->setName($newValues['SERVICEGROUP']['NAME']);
            $sg->setDescription($newValues['SERVICEGROUP']['DESCRIPTION']);
            $sg->setEmail($newValues['SERVICEGROUP']['EMAIL']);

            // Update the service group's scope
            // firstly remove all existing scope links
            $scopes = $sg->getScopes();
            foreach ($scopes as $s) {
                $sg->removeScope($s);
            }

            //find then link each scope specified to the site
            foreach ($newValues['Scope_ids'] as $scopeId) {
                $dql = "SELECT s FROM Scope s WHERE s.id = ?1";
                $scope = $this->em->createQuery($dql)
                        ->setParameter(1, $scopeId)
                        ->getSingleResult();
                $sg->addScope($scope);
            }

            $this->em->merge($sg);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        return $sg;
    }

    /**
     * Get an array of Role names granted to the user that permit the requested 
     * action on the given ServiceGroup. If the user has no roles that 
     * permit the requested action, then return an empty array. 
     * <p>
     * Suppored actions: EDIT_OBJECT 
     * GRANT_ROLE, REJECT_ROLE, REVOKE_ROLE  
     * @deprecated since version 5.5 use {@see \org\gocdb\services\RoleActionAuthorisationService::authoriseAction($action, $targetEntity, $user)} instead
     *  
     * @param string $action @see \Action 
     * @param \ServiceGroup $sg
     * @param \User $user
     * @return array of RoleName string values that grant the requested action  
     * @throws \LogicException if action is not supported or is unknown 
     */
    /*public function authorize Action($action, \ServiceGroup $sg, \User $user = null){
        if(!in_array($action, \Action::getAsArray())){
            throw new \LogicException('Coding Error - Invalid action not known'); 
        } 
        if(is_null($user)){
            return array(); 
        }
        if(is_null($user->getId())){
            return array(); 
        }
        $roleService = new \org\gocdb\services\Role(); // to inject
        $roleService->setEntityManager($this->em);
        
        if ($action == \Action::EDIT_OBJECT) {
            $requiredRoles = array(\RoleTypeName::SERVICEGROUP_ADMIN);
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($sg, $user);
            $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));
            
        } else if ($action == \Action::GRANT_ROLE ||
                $action == \Action::REJECT_ROLE || $action == \Action::REVOKE_ROLE) {
            $requiredRoles = array(\RoleTypeName::SERVICEGROUP_ADMIN);
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($sg, $user);
            $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));
            
        } else {
            throw new \LogicException('Unsupported Action');
        }
        if ($user->isAdmin()) {
            $enablingRoles[] = \RoleTypeName::GOCDB_ADMIN;
        }
        return array_unique($enablingRoles);
    }*/
    

    /**
     * Validates the user inputted service group data against the
     * checks in the gocdb_schema.xml.
     * @param array $sgData containing all the fields for a GOCDB_USER
     *                       object
     * @throws \Exception If the site data can't be
     *                    validated. The \Exception message will contain a human
     *                    readable description of which field failed validation.
     * @return null */
    private function validate($sgData, $type = NULL) {
        if ($type == NULL) {
            $type = 'service_group';
        }
        require_once __DIR__ . '/Validate.php';
        $serv = new \org\gocdb\services\Validate();
        foreach ($sgData as $field => $value) {
            $valid = $serv->validate($type, $field, $value);
            if (!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
    }

    /**
     * Attaches services to a service group
     * @param ServiceGroup $sg The service group
     * @param array $ses An array of Service s
     * @param User $user The user making the request
     */
    public function addServices(\ServiceGroup $sg, $ses, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        if ($this->roleActionAuthorisationService->authoriseAction(
                \Action::EDIT_OBJECT, $sg, $user)->getGrantAction() == FALSE) {
            throw new \Exception("You don't have permission over this service group.");
        }
        $this->em->getConnection()->beginTransaction();
        try {
            foreach ($ses as $se) {
                $sg->addService($se);
            }
            $this->em->merge($sg);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
    }

    /**
     * Removes a service from a service group
     * @param \ServiceGroup $sg The service group
     * @param \Service $se The service 
     */
    public function removeService(\ServiceGroup $sg, \Service $se, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        if ($this->roleActionAuthorisationService->authoriseAction(
                \Action::EDIT_OBJECT, $sg, $user)->getGrantAction() == FALSE) {
            throw new \Exception("You don't have permission over this service group");
        }
        $this->em->getConnection()->beginTransaction();
        try {
            $sg->removeService($se);
            $this->em->merge($sg);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
    }

    /**
     * Array
     * (
     *     [Scope] => 2
     *     [SERVICEGROUP] => Array
     *     (
     *         [MONITORED] => Y
     *         [NAME] => TEST
     *         [DESCRIPTION] => This is a test
     *         [EMAIL] => JCasson@hithere.com
     *     )
     * )
     * @param array $values Service group values, defined above
     * @param \User $user User making the request
     */
    public function addServiceGroup($values, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
                
        // Any registered user can create a service group. 
        if(is_null($user)) {
            throw new \Exception("Unregistered users can't create service groups.");
        }
        if(is_null($user->getId())) {
            throw new \Exception("Unregistered users can't create service groups.");
        }
        
    $this->em->getConnection()->beginTransaction();
    $this->validate($values['SERVICEGROUP']);
        $this->uniqueCheck($values['SERVICEGROUP']['NAME']);
        
        //check there are the required number of scopes specified
        $this->checkNumberOfScopes($values['Scope_ids']);
        
        try {
            $sg = new \ServiceGroup();
            $sg->setName($values['SERVICEGROUP']['NAME']);
            $sg->setDescription($values['SERVICEGROUP']['DESCRIPTION']);
            $sg->setEmail($values['SERVICEGROUP']['EMAIL']);

            // Set monitored
            if ($values['MONITORED'] == "Y") {
                $sg->setMonitored(true);
            } else {
                $sg->setMonitored(false);
            }
            
            // Set the scopes
            foreach($values['Scope_ids'] as $scopeId){
                $dql = "SELECT s FROM Scope s WHERE s.id = :id";
                $scope = $this->em->createQuery($dql)
                    ->setParameter('id', $scopeId)
                    ->getSingleResult();
                $sg->addScope($scope);
            }

            $this->em->persist($sg);
            
            $sgAdminroleType = $this->em->createQuery("SELECT rt FROM RoleType rt WHERE rt.name = ?1")
                ->setParameter(1, 'Service Group Administrator')
                ->getSingleResult();
            $newRole = new \Role($sgAdminroleType, $user, $sg, \RoleStatus::GRANTED);
            $this->em->persist($newRole);
                
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $sg;
    }

    /**
     * Is the passed service group name unique?
     * @param unknown_type $name
     */
    public function uniqueCheck($name) {
    $dql = "SELECT sg FROM ServiceGroup sg
        WHERE sg.name = :name";
    $sgs = $this->em->createQuery($dql)
       ->setParameter('name', $name)
       ->getResult();

    if(count($sgs) > 0) {
        throw new \Exception("A service group named $name already exists");
    }
    }

    /**
     * Deletes a service group
     * @param \ServiceGroup $sg
     * @param \User $user
     * @param $isTest when unit testing this allows for true to be supplied and this method
     * will not attempt to archive the sg which can easily cause errors for sg objects without
     * a full set of information  
     */
    public function deleteServiceGroup(\ServiceGroup $sg, \User $user = null, $isTest=false) {
        require_once __DIR__ . '/../DAOs/ServiceGroupDAO.php';
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        
        //$this->editAuthorization($sg, $user);
//        if(count($this->authorize Action(\Action::EDIT_OBJECT, $sg, $user))==0){
//            throw new \Exception("You don't have permission over $sg");  
//        }
        if ($this->roleActionAuthorisationService->authoriseAction(\Action::EDIT_OBJECT, $sg, $user)->getGrantAction() == FALSE) {
            throw new \Exception("You don't have permission over this service group.");
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $sgDAO = new \ServiceGroupDAO;
            $sgDAO->setEntityManager($this->em);
            // TODO If this SG contains siteless services, delete them
            
            //Archive site - if this is a test then don't archive            
            if($isTest==false){
                //Create entry in Audit table
                $sgDAO->addServiceGroupToArchive($sg, $user);
            }
            //remove service group
            $sgDAO->removeServiceGroup($sg);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
    
    private function checkNumberOfScopes($scopeIds){
        require_once __DIR__ . '/Config.php';
        $configService = new \org\gocdb\services\Config();
        $minumNumberOfScopes = $configService->getMinimumScopesRequired('service_group');
        
        if(sizeof($scopeIds)<$minumNumberOfScopes){
            $s = "s";
            if($minumNumberOfScopes==1){
                $s="";
            }
            throw new \Exception("A service group must have at least " . $minumNumberOfScopes . " scope".$s." assigned to it.");
        }
    }
    
    /**
     * This method will check that a user has edit permissions over a service 
     * group before allowing a user to add, edit or delete a site property.
     *
     * @param \User $user
     * @param \ServiceGroup $sg
     * @throws \Exception
     */
    public function validatePropertyActions(\User $user, \ServiceGroup $sg){
        // Check to see whether the user has a role that covers this site
//        if(count($this->authorize Action(\Action::EDIT_OBJECT, $sg, $user))==0){
//            throw new \Exception("You don't have permission over $sg");  
//        }
        if($this->roleActionAuthorisationService->authoriseAction(\Action::EDIT_OBJECT, $sg, $user)->getGrantAction()==FALSE){
            throw new \Exception("You don't have permission over ". $sg->getName());
        }
    }
    
    /** TODO
     * Before adding or editing a key pair check that the keyname is not a reserved keyname
     *
     * @param String $keyname
     */
    private function checkNotReserved(\User $user, \ServiceGroup $serviceGroup, $keyname){
        //TODO Function: This function is called but not yet filled out with an action
    }

    /**
     * @param \ServiceGroup $serviceGroup
     * @param \User $user
     * @param array $propArr
     * @param bool $preventOverwrite
     * @throws \Exception
     */
    public function addProperties(\ServiceGroup $serviceGroup, \User $user, array $propArr, $preventOverwrite = false) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        $this->validatePropertyActions($user, $serviceGroup);

        $existingProperties = $serviceGroup->getServiceGroupProperties();

        $this->em->getConnection()->beginTransaction();
        try {
            foreach ($propArr as $i => $prop) {
                $key = $prop[0];
                $value = $prop[1];
                //Check that we are not trying to add an existing key, and skip if we are, unless the user has selected the prevent overwrite mode

                foreach ($existingProperties as $existProp) {
                    if ($existProp->getKeyName() == $key && $existProp->getKeyValue() == $value) {
                        if ($preventOverwrite == false) {
                            continue 2;
                        } else {
                            throw new \Exception("A property with name \"$key\" and value \"$value\" already exists for this object, no properties were added.");
                        }
                    }
                }

                //validate key value
                $validateArray['NAME'] = $key;
                $validateArray['VALUE'] = $value;
                $validateArray['SERVICEGROUP'] = $serviceGroup->getId();
                $this->validate($validateArray, 'servicegroupproperty');

                $property = new \ServiceGroupProperty();
                $property->setKeyName($key);
                $property->setKeyValue($value);
                $serviceGroup->addServiceGroupPropertyDoJoin($property);
                $this->em->persist($property);

            }


            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    public function deleteServiceGroupProperties(\ServiceGroup $serviceGroup,\User $user = null, array $propArr) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );
        $this->validatePropertyActions($user, $serviceGroup);

        $this->em->getConnection ()->beginTransaction ();
        try {
            foreach ($propArr as $prop) {

                //check property is in service
                if ($prop->getParentServiceGroup() != $serviceGroup){
                    $id = $prop->getId();
                    throw new \Exception("Property {$id} does not belong to the specified service");
                }

                // Service is the owning side so remove elements from service.
                $serviceGroup->getServiceGroupProperties ()->removeElement ( $prop );

                // Once relationship is removed delete the actual element
                $this->em->remove($prop);
            }
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
    
    /**
     * Edits a service group property. 
     * A check is performed to confirm the given property is from the parent 
     * serviceGroup, and an exception is thrown if not.
     *
     * @param \ServiceGroup $serviceGroup
     * @param \User $user
     * @param \ServiceGroupProperty $prop
     * @param array $newValues
     *        	
     */
    public function editServiceGroupProperty(\ServiceGroup $serviceGroup,\User $user,\ServiceGroupProperty $prop, $newValues) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin ( $user );
    
        $this->validate($newValues['SERVICEGROUPPROPERTIES'], 'servicegroupproperty');
        
        $keyname = $newValues ['SERVICEGROUPPROPERTIES'] ['NAME'];
        $keyvalue = $newValues ['SERVICEGROUPPROPERTIES'] ['VALUE'];
        
        $this->checkNotReserved($user, $serviceGroup, $keyname);        
         
        $this->em->getConnection ()->beginTransaction ();
    
        try {
            //Check that the prop is from the sg 
            if ($prop->getParentServiceGroup() != $serviceGroup) {
                $id = $prop->getId();
                throw new \Exception("Property {$id} does not belong to the specified ServiceGroup");
            }
            // Set the site propertys new member variables
            $prop->setKeyName ($keyname);
            $prop->setKeyValue ($keyvalue);
    
            $this->em->merge ( $prop );
            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $ex ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $ex;
        }
    }

}
