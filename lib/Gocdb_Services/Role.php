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
require_once __DIR__ . '/RoleConstants.php';
require_once __DIR__ . '/Downtime.php';
require_once __DIR__ . '/Site.php';
require_once __DIR__ . '/NGI.php';
require_once __DIR__ . '/ServiceGroup.php';
require_once __DIR__ . '/Project.php';
require_once __DIR__ . '/RoleActionAuthorisationService.php';
require_once __DIR__ . '/RoleActionMappingService.php';


/**
 * GOCDB Stateless service facade (business routnes) for role objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 */
class Role extends AbstractEntityService{
    private $downtimeService;
    private $roleActionAuthorisationService;
    private $roleActionMappingService;


    public function __construct() {
        parent::__construct();
        $this->roleActionMappingService = new RoleActionMappingService();
    }


    /**
     * Set the Downtime service
     * @param \org\gocdb\services\Downtime $downtimeService
     */
    public function setDowntimeService(\org\gocdb\services\Downtime $downtimeService){
       $this->downtimeService = $downtimeService;
    }

    public function setRoleActionAuthorisationService(RoleActionAuthorisationService $roleActionAuthService){
        $this->roleActionAuthorisationService = $roleActionAuthService;
    }

    public function setRoleActionMappingService(RoleActionMappingService $roleActionMappingService){
        $this->roleActionMappingService = $roleActionMappingService;
    }


    /**
     * Get all {@see \Project}s that are reachable from the given ownedEntity
     * when moving up the domain model, e.g. Site->Ngi->Project.
     * <p>
     * The ownedEntity therefore comes under the 'remit' of the returned projects.
     *
     * @param \OwnedEntity $ownedEntity Search up the domain graph from this entity
     * @return array of \Project entities
     */
    public function getReachableProjectsFromOwnedEntity(\OwnedEntity $ownedEntity){
        if ($ownedEntity instanceof \Site) {
            /* @var $ownedEntity \Site */
            $projects = $ownedEntity->getNgi()->getProjects()->toArray();

        } else if($ownedEntity instanceof \NGI) {
            /* @var $ownedEntity NGI */
            $projects = $ownedEntity->getProjects()->toArray();

        } else if($ownedEntity instanceof \Project){
           $projects = array($ownedEntity);

        } else {
            //throw new \LogicException('ownedEntity type is not a descendant '
            //        . 'of Project ['.$ownedEntity->getType().']');
            return array();
        }
        return $projects;
    }


    /**
     * Get all the user's granted {@see \Role}s over {@see \OwnedEntity} objects
     * that are descendants of the specified targetProject.
     * <p>
     * The returned roles will be over: {@see \Site}, {@see \NGI}s and {@see \Project}s,
     * note (no {@see \ServiceGroup}s as they are not children of a project.
     *
     * @param \User $user
     * @param \Project $targetProject Only roles over entities under the targetProject are returned.
     * @return array Return the granted {@see \Role}s that the user owns
     * over entities in the given project.
     */
    public function getUserRolesByProject(\User $user, \Project $targetProject){
        //fail early
        if($targetProject->getId() == null){
            throw new \LogicException('Project does not exist in the DB');
        }
        if($user->getId() == null){
            throw new \LogicException('User does not exist in the DB');
        }
        // get all the user's granted roles
        //$userRoles = $user->getRoles();
        $userRoles = $this->getUserRoles($user, \RoleStatus::GRANTED);
        $rolesInProject = array();

        // foreach role, get the ownedEntity, and navigate up the domain model
        // to reach the entity's owning project. If this project is the
        // same as the targetProject, then the role is collected/returned.
        /* @var $role \Role */
        foreach($userRoles as $role){
            if($role->getStatus() != \RoleStatus::GRANTED){
                continue;
            }

            $ownedEntity = $role->getOwnedEntity();
            if ($ownedEntity instanceof \Site) {
                // query may be better performance?

                if($ownedEntity->getNgi() != null){
                    $projects = $ownedEntity->getNgi()->getProjects();
                    foreach($projects as $proj){
                        if($proj->getId() == $targetProject->getId()){
                        //if($proj == $targetProject){
                            $rolesInProject[] = $role;
                        }
                    }
                }
            } else if ($ownedEntity instanceof \NGI) {
                  $projects = $ownedEntity->getProjects();
                  foreach($projects as $proj){
                    if($proj->getId() == $targetProject->getId()){
                    //if($proj == $targetProject ){
                        $rolesInProject[] = $role;
                    }
                }
            } else if ($ownedEntity instanceof \Project) {
                if( $ownedEntity->getId() == $targetProject->getId()){
                //if( $ownedEntity == $targetProject){
                    $rolesInProject[] = $role;
                }
            }
            // note, dont include \ServiceGroup because a sg is not under
            // a project in terms of domain model hierarchy
        }
        return $rolesInProject;
    }


    /**
     * Get all the RoleType names that the user has DIRECTLY over the given entity,
     * and optionally limit the returned RoleType names to those with the specified
     * RoleStatus (GRANTED by default).
     *
     * @param \OwnedEntity $entity An entity that extends OwnedEntity (Site, NGI, ServiceGroup, Project)
     * @param \User $user
     * @param string RoleStatus value
     * @return array of RoleType names or an empty array
     * @throws \LogicException if the classifications array is given and contains unknown values.
     */
    public function getUserRoleNamesOverEntity(\OwnedEntity $entity, \User $user,
            /*$classifications = null,*/ $roleStatus = \RoleStatus::GRANTED) {
        //This method replaces userHasSiteRole, userHasNgiRole, userHasSGroupRole

        if($user->getId() == null || $entity->getId() == null){
            return array();
        }

        // Would be better to use the get_class method to determine the instance
        // class name, however, there have been instances where this has returned
        // a class name of the following form: 'DoctrineProxies\__CG__\Site'
        // which would cause an issue with dql query below.
        //
        //$entityClassName= get_class($entity);
        require_once __DIR__.'/OwnedEntity.php';
        $OwnedEntityService = new \org\gocdb\services\OwnedEntity();
        $entityClassName = $OwnedEntityService->getOwnedEntityDerivedClassName($entity);

        $dql = "SELECT rt.name
            FROM Role r
            JOIN r.roleType rt
            JOIN r.user u
            JOIN r.ownedEntity o
            WHERE u.id = :userId
            AND o.id = :entityId
            AND r.status = :roleStatus
            AND o INSTANCE OF $entityClassName";
        /*if ($classifications != null) {
            $dql = $dql." AND rt.classification IN (:classifications)";
        }*/
        $query = $this->em->createQuery($dql)
                ->setParameter('userId', $user->getId())
                ->setParameter('roleStatus', $roleStatus)
                ->setParameter('entityId', $entity->getId());
        /*if ($classifications != null) {
            $query->setParameter('classifications', $classifications);
        }*/
        $roleNames = $query->getScalarResult();
        //print_r($roleNames);
        $transform = function($item) {
           return $item['name'];
        };
        $retArray = array_map($transform, $roleNames);
        return $retArray;
    }


    /**
     * Get all Pending Role requests (Role objects with RoleStatus::PENDING)
     * that the user has permission to grant.
     * @todo - check and finish
     * @param \User $user
     * @return array of Pending Role array
     */
     public function getPendingRolesUserCanApprove(\User $user = null) {
         if (is_null($user)) {
            return array();
        }
        if ($user->isAdmin()) {
            return $this->getAllRolesByStatus(\RoleStatus::PENDING);
        }
        // Get all PENDNG ROLES
        $allPendingRoles = $this->getAllRolesByStatus(\RoleStatus::PENDING);
        // Iterate each PENDING Role request and determine
        // if the user has GRANT_ROLE permission over each OwnedEntity
        $grantablePendingRoles = array();
        /* @var $role \Role */
        foreach ($allPendingRoles as $role) {
            $targetEntity = $role->getOwnedEntity();
            if($this->roleActionAuthorisationService->authoriseAction(\Action::GRANT_ROLE, $targetEntity, $user)->getGrantAction() ){
               $grantablePendingRoles[] = $role;
            }
        }
        return $grantablePendingRoles;
     }

    /**
     * Returns an array of sites where the user has a GRANTED role over a
     * parent OwnedObject including Site and NGI, but not Project.
     * Important: This does <b>NOT</b> grant permissions over the returned sites,
     * it simply means that the user has a Role over one of the owning OwnedObjects.
     *
     * @param \User $user
     * @return array Of \Site objects
     */
    public function getReachableSitesFromOwnedObjectRoles(\User $user) {
        // Build the list of sites a user is allowed to add an SE to
        $sites = array();
        $roles = $user->getRoles();
        foreach($roles as $role) {
            if($role->getStatus() == \RoleStatus::GRANTED){
                if($role->getOwnedEntity() instanceof \Site) {
                    $sites[] = $role->getOwnedEntity();
                }

                // If the role is over an NGI add all of the NGI's child sites to the list
                if($role->getOwnedEntity() instanceof \NGI) {
                    $ngiSites = $role->getOwnedEntity()->getSites();
                    foreach($ngiSites as $site) {
                        $sites[] = $site;
                    }
                }
            }
        }
        $sites = array_unique($sites);

        usort($sites, function($a, $b) {
            return strcmp($a, $b);
        });
        return $sites;
    }


    /**
     * Returns an array of services where the user has a GRANTED role over a
     * parent OwnedObject including Site and NGI, but not Project.
     * Important: This does <b>NOT</b> grant permissions over the returned services,
     * it simply means that the user has a Role over one of the owning OwnedObjects.
     *
     * @param \User $user
     * @return array of \Service entities
     */
    public function getReachableServicesFromOwnedObjectRoles(\User $user) {
        $ses = array();
        // Get all sites the user has a role over
        $sites = $this->getReachableSitesFromOwnedObjectRoles($user);
        foreach($sites as $site) {
            foreach($site->getServices() as $se) {
                $ses[] = $se;
            }
        }
        return array_unique($ses);
    }

    /**
     * Is the given role status value is valid according to the configured RoleStatus values.
     * @see \RoleStatus
     * @param string $roleStatus role status
     * @return boolean
     */
    public function isValidRoleStatus($roleStatus) {
        $roleStatuses = \RoleStatus::getAsArray();
        foreach($roleStatuses as $statusValue){
              if($statusValue == $roleStatus){
                  return TRUE;
              }
        }
        return FALSE;
    }

    /**
     * Is the given roleTypeName valid according to the configured roleTypeNames.
     * @see \RoleTypeName
     * @param string $roleTypeName
     * @return boolean True if a valid otherwise false
     */
    /*public function isValidRoleTypeName($roleTypeName){
       $roleTypeNames = \RoleTypeName::getAsArray();
       foreach($roleTypeNames as $validValue){
           if($validValue == $roleTypeName){
               return true;
           }
       }
       return false;
    }*/


   /**
    * Get the role type names configured for the given owned entity type.
    * <p>
    * The method consults the role action mapping xml file that defines which
    * role types are defined for the entity type.
    * @param \OwnedEntity $ownedEntity Get roles type names for this entity
    * @return array Role type names for entity
    */
    public function getRoleTypeNamesForOwnedEntity(\OwnedEntity $ownedEntity) {
        $roleTypeNamesForOE = array();
    $projRoleMappings = $this->roleActionMappingService->getRoleTypeNamesForProject(null);
    foreach ($projRoleMappings as $keyRoleTypeName => $valOwnedObjectType) {
        if(strtoupper($ownedEntity->getType()) == strtoupper($valOwnedObjectType)) {
        $roleTypeNamesForOE[] =  $keyRoleTypeName;
        }
    }
        return $roleTypeNamesForOE;
    }

    /**
    * Get the role type names configured for the given owned entity type.
    * @see \RoleTypeName
    * @param \OwnedEntity $ownedEntity
    * @return array of role type name strings
    */
//    public function getRoleTypeNamesForOwnedEntity(\OwnedEntity $ownedEntity){
//        $roles = array();
//        if($ownedEntity instanceof \Site){
//            $roles[] = \RoleTypeName::SITE_ADMIN;
//            $roles[] = \RoleTypeName::SITE_OPS_DEP_MAN;
//            $roles[] = \RoleTypeName::SITE_OPS_MAN;
//            $roles[] = \RoleTypeName::SITE_SECOFFICER;
//
//        } else if($ownedEntity instanceof \NGI){
//            $roles[] = \RoleTypeName::NGI_OPS_DEP_MAN;
//            $roles[] = \RoleTypeName::NGI_OPS_MAN;
//            $roles[] = \RoleTypeName::NGI_SEC_OFFICER;
//            $roles[] = \RoleTypeName::REG_FIRST_LINE_SUPPORT;
//            $roles[] = \RoleTypeName::REG_STAFF_ROD;
//            //\RoleTypeName::CIC_STAFF;  // not used
//            //\RoleTypeName::REG_STAFF;  // not used
//
//        } else if($ownedEntity instanceof \Project){
//            $roles[] = \RoleTypeName::COD_ADMIN;
//            $roles[] = \RoleTypeName::COD_STAFF;
//            $roles[] = \RoleTypeName::EGI_CSIRT_OFFICER;
//            $roles[] = \RoleTypeName::COO;
//
//        } else if($ownedEntity instanceof \ServiceGroup){
//            $roles[] = \RoleTypeName::SERVICEGROUP_ADMIN;
//        }
//        return $roles;
//    }

    /**
     * Get the given User's roles that have the specified role status.
     * For valid role status values, see RoleStatus class.
     * \RoleStatus::GRANTED is default.
     *
     * @see \RoleStatus
     * @param \User $user Get roles for this user
     * @param string $roleStatus A role status defined in RoleStatus
     * @return array of \Role objects
     * @throws \LogicException if given roleStatus is not supported
     */
    public function getUserRoles(\User $user, $roleStatus = NULL) {
        if($roleStatus == null){
            $roleStatus = \RoleStatus::GRANTED;
        }
        if(!$this->isValidRoleStatus($roleStatus)){
            throw new \LogicException('Coding error - Invalid roleStatus');
        }
        if($user->getId() == null){
            return array(); // return empty array
        }
        $dql = "SELECT r FROM Role r
                JOIN r.user u
                WHERE u.id = :id
                AND r.status = :status
                ORDER BY r.id
                ";
         $roles = $this->em->createQuery($dql)
            ->setParameter("id", $user->getId())
            ->setParameter("status", $roleStatus)
            ->getResult();
        return $roles;
    }

    /**
     * Get all the Role objects with the specified status.
     * @param string $roleStatus Must be a valid \RoleStatus
     * @return array of Role objects
     * @throws \LogicException
     */
    private function getAllRolesByStatus($roleStatus){
        if(!$this->isValidRoleStatus($roleStatus)){
            throw new \LogicException('Coding error - Invalid roleStatus');
        }
         $dql = "SELECT r FROM Role r WHERE r.status = :status ORDER BY r.id";
         $roles = $this->em->createQuery($dql)
            ->setParameter("status", $roleStatus)
            ->getResult();
        return $roles;
    }

    /**
     * Get the \RoleType instance with the specified name.
     * @param string $roleTypeName
     * @return \RoleType
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getRoleTypeByName($roleTypeName){
        //if(!$this->isValidRoleTypeName($roleTypeName)){
        //    throw new \LogicException('Coding error - Invalid roleTypeName');
        //}
        $dql = "SELECT rt FROM RoleType rt WHERE rt.name = :roleTypeName";
        $roleType = $this->em->createQuery($dql)
                ->setParameter("roleTypeName", $roleTypeName)
                ->getSingleResult();
        return $roleType;
    }


    /**
     * @param int $id Id of Role
     * @return \Role
     */
    public function getRoleById($id){
        // will only load a lazy loading proxy until method on the proxy is called
        //$entity = $this->em->find('Role', (int)$id);
        //return $entity;
        $dql = "SELECT r from Role r where r.id = :id";
        $role = $this->em->createQuery($dql)->setParameter(":id", $id)->getSingleResult();
        return $role;
    }


    /**
     * Create and return a new \Role instance linking the given user and entity.
     * The function performs validation of the requested role including
     * <ul>
     * <li>is the given roleTypeName valid</li>
     * <li> is the requested role type valid for the given entity </li>
     * <li> does the user already have the requested role over the entity </li>
     * <li> does the user already have the requested role pending over the entity</li>
     * </ul>
     *
     * @param string $roleTypeName @see \RoleTypeName
     * @param \User $user
     * @param \OwnedEntity $entity
     * @return \Role managed instance
     * @throws \Exception if the user already has the requested role over the entity (role status GRANTED or PENDING)
     * @throws \LogicException
     */
    public function addRole($roleTypeName, \User $user, \OwnedEntity $entity){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        $roleStatus = \RoleStatus::PENDING;

        // Check the given roleName is a valid role
        if (!$this->isValidRoleStatus($roleStatus)) {
            throw new \LogicException('Coding error - invalid roleStatus');
        }
        // Check the requested roleName is suitable for the ownedEntity
        $roleTypeNamesForOE = $this->getRoleTypeNamesForOwnedEntity($entity);

        if (!in_array($roleTypeName, $roleTypeNamesForOE) ) {
            throw new \LogicException("Role requested [$roleTypeName] is not valid for this OwnedEntity");
        }
        // Check to see if user already has the same type of role GRANTED over entity
        if (in_array($roleTypeName, $this->getUserRoleNamesOverEntity($entity, $user, \RoleStatus::GRANTED))) {
            throw new \Exception("You already have the role [$roleTypeName] over " . $entity->getName());
        }
        // Check to see if user already has the same type of role PENDING over entity
        if (in_array($roleTypeName, $this->getUserRoleNamesOverEntity($entity, $user, \RoleStatus::PENDING))) {
            throw new \Exception("You already have a [$roleTypeName] role request pending over " . $entity->getName());
        }

        $this->em->getConnection()->beginTransaction();
        try {
            // getRoleTypeName throws a NoResultException if the roleType with the
            // specfied name don't exist in in the DB.
            $roleType = $this->getRoleTypeByName($roleTypeName);
            $r = new \Role($roleType, $user, $entity, $roleStatus);
            $this->em->persist($r);

            // create a RoleActionRecord after role has been persisted (to get id)
            $rar = \RoleActionRecord::construct($user, $r, \RoleStatus::PENDING);
            $this->em->persist($rar);

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        return $r;
    }


    /**
     * Calling user attempts to grant the given Role request.
     * <p>
     * The callingUser's permissions are checked before the Role is granted.
     * Exception is thrown if callingUser does not have permission to grant.
     *
     * @param \Role $role Role must be {@link \RoleStatus::PENDING}
     * @param \User $callingUser
     * @throws \LogicException
     * @throws \Exception
     */
    public function grantRole(\Role $role, \User $callingUser) {
        if ($callingUser == null) {
            throw new \Exception("Unregistered users can't grant roles");
        }
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($callingUser);
        // Santity check that it has pending status
        if ($role->getStatus() != \RoleStatus::PENDING) {
            throw new \LogicException("Invalid role request - does not have status of PENDING");
        }
        $entity = $role->getOwnedEntity();
        if($entity == null){
           throw new \LogicException('Error - target entity of role is null');
        }
        // check calling user has permission to grant this role
        //$grantingRoles = $this->authorize Action(\Action::GRANT_ROLE, $entity, $callingUser);
        if ($this->roleActionAuthorisationService->authoriseAction(\Action::GRANT_ROLE, $entity, $callingUser)->getGrantAction() == FALSE) {
            throw new \Exception('You do not have permission to grant this role');
        }

        $this->em->getConnection()->beginTransaction();
        try {
            // Create roleActionRecord before its status is updated (to get its existing status)
            $rar = \RoleActionRecord::construct($callingUser, $role, \RoleStatus::GRANTED);
            $this->em->persist($rar);

            // Update the role status
            $role->setStatus(\RoleStatus::GRANTED);
            //$roleRequest->setLastUpdatedByUserId($user->getId());

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Calling user attempts to revoke the given Role.
     * Role rejection is slightly different revoking a role: a rejected role is
     * not previously granted while a revoked role may have been.
     * <p>
     * If the callingUser owns the given Role, then the role can be revoked otherwise
     * the callingUser's permissions are checked before the Role is revoked.
     * Successful revocation deletes the Role from the DB.
     * Exception is thrown if callingUser does not have permission to revoke.
     *
     * @param \Role $role
     * @param \User $callingUser
     * @throws \Exception If the callingUser does not have permission to revoke
     * @throws \LogicException If the Role's OwnedEntity is null
     */
    public function revokeRole(\Role $role, \User $callingUser){
        if ($callingUser == null) {
            throw new \Exception("Unregistered users can't revoke roles");
        }
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($callingUser);

        $entity = $role->getOwnedEntity();
        if ($entity == null) {
            throw new \LogicException('Error - target entity of role is null');
        }

        // if calling user is not the same person, need to check permissions
        if ($role->getUser() != $callingUser) {
            // Revocation by 2nd party
            //$grantingRoles = $this->authorize Action(\Action::REVOKE_ROLE, $entity, $callingUser);
            if($this->roleActionAuthorisationService->authoriseAction(\Action::REVOKE_ROLE, $entity, $callingUser)->getGrantAction() == FALSE){
                throw new \Exception('You do not have permission to revoke this role');
            }
        } else {
            // self revoke - user is allowed to revoke their own roles
        }

        if($role->getStatus() == \RoleStatus::PENDING){
            // if this role has not yet been granted, then new status is REJECTION
            $newStatus = \RoleStatus::REJECTED;
        } else {
            $newStatus = \RoleStatus::REVOKED;
        }

        // ok, lets delete the role
        //$this->deleteRole($role);
        $this->em->getConnection()->beginTransaction();
        try {
            // Create a RoleActionRecord before we delete the role
            $rar = \RoleActionRecord::construct($callingUser, $role, $newStatus);
            $this->em->persist($rar);

            $this->em->remove($role);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

    }

    /**
     * Calling user attempts to reject the given Role request.
     * Role rejection is slightly different revoking a role: a rejected is not
     * previously granted while a revoked role may have already been granted.
     * <p>
     * The callingUser's permissions are checked before the Role is rejected.
     * Successful rejection deletes the Role from the DB.
     * Exception is thrown if callingUser does not have permission to reject.
     *
     * @param \Role $role Role must be {@link \RoleStatus::PENDING}
     * @param \User $callingUser
     * @throws Exception If the callingUser does not have permission to revoke
     * @throws LogicException If Role does not have a status of RoleStatus::PENDING
     */
    public function rejectRoleRequest(\Role $role, \User $callingUser) {
        if ($callingUser == null) {
            throw new \Exception("Unregistered users can't reject roles");
        }
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($callingUser);

        if ($role->getStatus() != \RoleStatus::PENDING) {
            throw new \LogicException("Invalid role request - does not have status of PENDING");
        }
        $entity = $role->getOwnedEntity();
        if ($entity == null) {
            throw new \LogicException('Error - target entity of role is null');
        }
        //$grantingRoles = $this->authorize Action(\Action::REJECT_ROLE, $entity, $callingUser);
        if($this->roleActionAuthorisationService->authoriseAction(\Action::REJECT_ROLE, $entity, $callingUser)->getGrantAction() == FALSE){
            throw new \Exception('You do not have permission to reject this role');
        }
        // ok, lets delete the role
        //$this->deleteRole($role);
        $this->em->getConnection()->beginTransaction();
        try {
            // Create a RoleActionRecord before we remove the role
            $rar = \RoleActionRecord::construct($callingUser, $role, \RoleStatus::REJECTED);
            $this->em->persist($rar);

            $this->em->remove($role);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Get an array of {@link \RoleActionRecord}s for the {@link \OwnedEntity}
     * that has the given id and type.
     * @param integer $id
     * @param string $ownedEntityType One of ngi, site, service, project, servicegroup
     * @return array of {@link \RoleActionRecord}s
     */
    public function getRoleActionRecordsById_Type($id, $ownedEntityType){

        $dql = "SELECT ra FROM RoleActionRecord ra
                WHERE ra.roleTargetOwnedEntityId = :id
                AND ra.roleTargetOwnedEntityType = :type
                ORDER BY ra.id DESC";
         $roleActionRecords = $this->em->createQuery($dql)
            ->setParameter("id", $id)
            ->setParameter("type", $ownedEntityType)
            ->getResult();
        return $roleActionRecords;
    }


    /**
     * Get the RoleActionRecords (if any exist) that have the specified roleId.
     * Note, it is possible that some Role instances may NOT have an associated
     * RoleActionRecord returning an empty array.
     * @param integer $roleId
     * @return array of {@link \RoleActionRecord}s or empty array
     */
    public function getRoleActionRecordByRoleId($roleId){
       $dql = "SELECT ra FROM RoleActionRecord ra
               WHERE ra.roleId = :roleId";
       $rar = $this->em->createQuery($dql)->setParameter(":roleId", $roleId)->getResult();
       return $rar;
    }

    /**
     * Delete the given role in a TX. Rollback on error.
     * @param \Role $role
     * @throws \Exception
     */
    /*public function deleteRole(\Role $role) {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->em->remove($role);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }*/

    /**
     * Get an array of Role names granted to the user that permit the requested
     * action on the given OwnedEntity. If the user has no roles that
     * permit the requested action, then return an empty array.
     * <p>
     * Supported actions: EDIT_OBJECT, NGI_ADD_SITE, GRANT_ROLE, REJECT_ROLE, REVOKE_ROLE
     *
     * @param string $action
     * @param \OwnedEntity $entity
     * @param \User $callingUser
     * @return array of RoleName values
     * @throws LogicException If unsupported enitity type or action is passed
     */
    /*public function authorize Action($action, \OwnedEntity $entity, \User $callingUser) {
        $siteService = new \org\gocdb\services\Site();
        $siteService->setEntityManager($this->em);
        $ngiService = new \org\gocdb\services\NGI();
        $ngiService->setEntityManager($this->em);
        $sgService = new \org\gocdb\services\ServiceGroup();
        $sgService->setEntityManager($this->em);
        $projectService = new \org\gocdb\services\Project();
        $projectService->setEntityManager($this->em);

        if ($entity instanceof \NGI) {
            $grantingRoles = $ngiService->authorize Action($action, $entity, $callingUser);
        } else if ($entity instanceof \Site) {
            $grantingRoles = $siteService->authorize Action($action, $entity, $callingUser);
        } else if ($entity instanceof \Project) {
            $grantingRoles = $projectService->authorize Action($action, $entity, $callingUser);
        } else if ($entity instanceof \ServiceGroup) {
            $grantingRoles = $sgService->authorize Action($action, $entity, $callingUser);
        } else {
            throw new \LogicException('Unsuppored OwnedEntity type');
        }
        return $grantingRoles;
    }*/

}
