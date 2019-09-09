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
require_once __DIR__.  '/Scope.php';
require_once __DIR__.  '/Config.php';

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
    private $scopeService;
    private $configService;

    function __construct(/*$roleActionAuthorisationService*/) {
        parent::__construct();
        //$this->roleActionAuthorisationService = $roleActionAuthorisationService;
        //$this->configService = new Config();
        $this->configService = \Factory::getConfigService();
    }

    /**
     * Set class dependency (REQUIRED).
     * @todo Mandatory objects should be injected via constructor.
     * @param \org\gocdb\services\Scope $scopeService
     */
    public function setScopeService(Scope $scopeService){
        $this->scopeService = $scopeService;
    }

    /**
     * Set class dependency (REQUIRED).
     * @todo Mandatory objects should be injected via constructor.
     * @param \org\gocdb\services\RoleActionAuthorisationService $roleActionAuthService
     */
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
     * @param integer $dayLimit Limit to downtimes that are only $dayLimit old (can be null)
     */
    public function getDowntimes($id, $dayLimit) {
        if($dayLimit != null) {
            $di = \DateInterval::createFromDateString($dayLimit . 'days');
            $dayLimit = new \DateTime();
            $dayLimit->sub($di);
        }
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

        // EDIT SCOPE TAGS:
        // collate selected scopeIds (reserved and non-reserved)
        $scopeIdsToApply = array();
        foreach($newValues['Scope_ids'] as $sid){
            $scopeIdsToApply[] = $sid;
        }
        foreach($newValues['ReservedScope_ids'] as $sid){
            $scopeIdsToApply[] = $sid;
        }
        $selectedScopesToApply = $this->scopeService->getScopes($scopeIdsToApply);
        // Check Reserved scopes
        // When normal users EDIT the site, the selected scopeIds should
        // be checked to prevent users manually crafting a POST request in an attempt
        // to select reserved scopes, this is unlikely but it is a possible hack.
        //
        // Note, on edit we also don't want to enforce cascading of parent NGI scopes to the site,
        // as we need to allow an admin to de-select a site's reserved scopes
        // (which is a perfectly valid requirement) and prevent re-cascading
        // when the user next edits the site!
        if (!$user->isAdmin()) {
            $selectedReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $selectedScopesToApply);

            $existingReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $sg->getScopes()->toArray());

            if(count($selectedReservedScopes) != count($existingReservedScopes)) {
                throw new \Exception("The reserved Scope count does not match the ServiceGroups existing scope count ");
            }
            foreach($selectedReservedScopes as $sc){
                if(!in_array($sc, $existingReservedScopes)){
                    throw new \Exception("A reserved Scope Tag was selected that is not already assigned to the ServiceGroup");
                }
            }
        }
        //check there are the required number of optional scopes specified
        $this->checkNumberOfScopes($this->scopeService->getScopesFilterByParams(
               array('excludeReserved' => true), $selectedScopesToApply));
        //check there are the required number of scopes specified
        //$this->checkNumberOfScopes($newValues['Scope_ids']);

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
//            foreach ($newValues['Scope_ids'] as $scopeId) {
//                $dql = "SELECT s FROM Scope s WHERE s.id = ?1";
//                $scope = $this->em->createQuery($dql)
//                        ->setParameter(1, $scopeId)
//                        ->getSingleResult();
//                $sg->addScope($scope);
//            }
            foreach($selectedScopesToApply as $scope){
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
     * Validates the user inputted service group data against the
     * checks in the gocdb_schema.xml.
     */
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

        if (is_null($user)) {
            throw new \Exception("Unregistered users can't create service groups.");
        }
        if (is_null($user->getId())) {
            throw new \Exception("Unregistered users can't create service groups.");
        }

        $this->validate($values['SERVICEGROUP']);
        $this->uniqueCheck($values['SERVICEGROUP']['NAME']);

        // ADD SCOPE TAGS:
        // collate selected reserved and non-reserved scopeIds
        $allSelectedScopeIds = array();
        foreach($values['Scope_ids'] as $sid){
            $allSelectedScopeIds[] = $sid;
        }
        // only admin can add reserved scopes as unlike sites/serivces,
        // the reserved scopes can't be inherited from a parent.
        if($user->isAdmin()){
            // if user is admin, allow them to add any reserved scope tag
            foreach($values['ReservedScope_ids'] as $sid){
                $allSelectedScopeIds[] = $sid;
            }
        }

        //check there are the required number of Optional scopes specified
        $this->checkNumberOfScopes($values['Scope_ids']);

        $this->em->getConnection()->beginTransaction();
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
            foreach($allSelectedScopeIds as $scopeId){
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

        if (count($sgs) > 0) {
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

        if ($this->roleActionAuthorisationService->authoriseAction(
                \Action::EDIT_OBJECT, $sg, $user)->getGrantAction() == FALSE) {
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
            throw new \Exception("A service group must have at least " . $minumNumberOfScopes . " scope(s) assigned to it.");
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

        //We will use this variable to track the keys as we go along, this will be used check they are all unique later
        $keys=array();

        //We will use this variable to track teh final number of properties and ensure we do not exceede the specified limit
        $propertyCount = sizeof($existingProperties);

        $this->em->getConnection()->beginTransaction();
        try {
            foreach ($propArr as $i => $prop) {
                /*Trim off trailing and leading whitspace - as we currently don't want this.
                *The input array is awkwardly formatted as keys didn't use to have to be unique.
                */
                $key = trim($prop[0]);
                $value = trim($prop[1]);

                /**
                *Find out if a property with the provided key already exists, if
                *we are preventing overwrites, this will be a problem. If we are not,
                *we will want to edit the existing property later, rather than create it.
                */
                $property = null;
                foreach ($existingProperties as $existProp) {
                    if ($existProp->getKeyName() == $key) {
                        $property = $existProp;
                    }
                }

                /*If the property doesn't already exist, we add it. If it exists
                *and we are not preventing overwrites, we edit the existing one.
                *If it exists and we are preventing overwrites, we throw an exception
                */
                if (is_null($property)) {
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

                    //increment the property counter to enable check against property limit
                    $propertyCount++;
                } elseif (!$preventOverwrite) {
                    $this->editServiceGroupProperty($serviceGroup, $user, $property, array('SERVICEGROUPPROPERTIES'=>array('NAME'=>$key,'VALUE'=>$value)));
                } else {
                    throw new \Exception("A property with name \"$key\" already exists for this object, no properties were added.");
                }

                //Add the key to the keys array, to enable unique check
                $keys[]=$key;
            }

            //Keys should be unique, create an exception if they are not
            if(count(array_unique($keys))!=count($keys)) {
                throw new \Exception(
                    "Property names should be unique. The requested new properties include multiple properties with the same name."
                );
            }

            //Check to see if adding the new properties will exceed the max limit defined in local_info.xml, and throw an exception if so
            $extensionLimit = \Factory::getConfigService()->getExtensionsLimit();
            if ($propertyCount > $extensionLimit){
                throw new \Exception("Property(s) could not be added due to the property limit of $extensionLimit");
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
     * Deletes site properties: validates the user has permission then calls the
     * required logic
     * @param \ServiceGroup $serviceGroup
     * @param \User $user
     * @param array $propArr
     */
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
     * The User is validated, and then the logic is carried out in a try catch block
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

        $this->validatePropertyActions($user, $serviceGroup);

        $this->em->getConnection ()->beginTransaction ();

        try {
            //Make the change
            $this->editServiceGroupPropertyLogic($serviceGroup, $prop, $newValues);

            $this->em->flush ();
            $this->em->getConnection ()->commit ();
        } catch ( \Exception $ex ) {
            $this->em->getConnection ()->rollback ();
            $this->em->close ();
            throw $ex;
        }
    }

    /**
     * All the logic to edit a service group property, without the user validation
     * or database connections
     * @param \ServiceGroup $serviceGroup
     * @param \ServiceGroupProperty $prop
     * @param array $newValues
     *
     */
    protected function editServiceGroupPropertyLogic(\ServiceGroup $serviceGroup, \ServiceGroupProperty $prop, $newValues) {

        $this->validate($newValues['SERVICEGROUPPROPERTIES'], 'servicegroupproperty');

        //We don't currently want trailing or leading whitespace, so we trim it
        $keyname = trim($newValues['SERVICEGROUPPROPERTIES']['NAME']);
        $keyvalue = trim($newValues['SERVICEGROUPPROPERTIES']['VALUE']);

        //Check that the prop is from the sg
        if ($prop->getParentServiceGroup() != $serviceGroup) {
            $id = $prop->getId();
            throw new \Exception("Property {$id} does not belong to the specified ServiceGroup");
        }

        //If the properties key has changed, check there isn't an existing property with that key
        if ($keyname != $prop->getKeyName()){
            $existingProperties = $serviceGroup->getServiceGroupProperties();
            foreach ($existingProperties as $existingProp) {
                if ($existingProp->getKeyName() == $keyname) {
                    throw new \Exception("A property with that name already exists for this object");
                }
            }
        }

        // Set the site propertys new member variables
        $prop->setKeyName ($keyname);
        $prop->setKeyValue ($keyvalue);

        $this->em->merge ( $prop );

    }

}
