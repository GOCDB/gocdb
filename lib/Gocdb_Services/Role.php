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


/**
 * GOCDB Stateless service facade (business routnes) for role objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 */
class Role extends AbstractEntityService{
    private $downtimeService; 

    
    public function __construct() {
    }   

    /**
     * Set the Downtime service
     * @param \org\gocdb\services\Downtime $downtimeService
     */
    public function setDowntimeService(\org\gocdb\services\Downtime $downtimeService){
       $this->downtimeService = $downtimeService;  
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
    public function getUserRoleNamesOverEntity(\OwnedEntity $entity, \User $user = null,
            /*$classifications = null,*/ $roleStatus = \RoleStatus::GRANTED) {
        //This method replaces userHasSiteRole, userHasNgiRole, userHasSGroupRole
        
        // user/entity is not manged, so return no roles. 
        if(is_null($user)){
            return array(); 
        }
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
        if(is_null($user)){
         return array();   
        }
        if($user->isAdmin()){
            return $this->getAllRolesByStatus(\RoleStatus::PENDING);
        }
        $siteService = new \org\gocdb\services\Site(); 
        $siteService->setEntityManager($this->em); 
        $ngiService = new \org\gocdb\services\NGI(); 
        $ngiService->setEntityManager($this->em); 
        $sgService = new \org\gocdb\services\ServiceGroup(); 
        $sgService->setEntityManager($this->em); 
        $projectService = new \org\gocdb\services\Project(); 
        $projectService->setEntityManager($this->em);  
        
        // Get all PENDNG ROLES 
        $allPendingRoles = $this->getAllRolesByStatus(\RoleStatus::PENDING); 
        // Iterate each PENDING Role request and determine 
        // if the user has GRANT_ROLE permission over each OwnedEntity 
        $grantablePendingRoles = array(); 
        foreach ($allPendingRoles as $role) {
            $entity = $role->getOwnedEntity();
            if ($entity instanceof \Site) {
                if (count($siteService->authorizeAction(\Action::GRANT_ROLE, $entity, $user)) > 0) {
                   $grantablePendingRoles[] = $role;  
                }
            } else if ($entity instanceof \NGI) {
                if (count($ngiService->authorizeAction(\Action::GRANT_ROLE, $entity, $user)) > 0) {
                   $grantablePendingRoles[] = $role;  
                }
            } else if ($entity instanceof \ServiceGroup) {
                if(count($sgService->authorizeAction(\Action::GRANT_ROLE, $entity, $user)) > 0) {
                    $grantablePendingRoles[] = $role; 
                } 
            } else if ($entity instanceof \Project) {
                if(count($projectService->authorizeAction(\Action::GRANT_ROLE, $entity, $user)) > 0) {
                    $grantablePendingRoles[] = $role; 
                }
            }
        }
        // return the PENDING roles that the user can grant 
        return $grantablePendingRoles; 
     }

    /**
     * Returns all sites the user holds a role over
     * (Includes sites under an NGI that the holds a role over)
     * @param \User $user
     * @return array Of \Site objects
     */
    public function getSites(\User $user) {
    	// Build the list of sites a user is allowed to add an SE to
    	$sites = array();
    	$roles = $user->getRoles();
    	foreach($roles as $role) {
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
    	$sites = array_unique($sites);

    	usort($sites, function($a, $b) {
		    return strcmp($a, $b);
		});
    	return $sites;
    }


    /**
     * Returns an array of \Services the user holds a role over
     * @param \User $user
     * @return array
     */
    public function getServices(\User $user) {
        $ses = array();
        // Get all sites the user has a role over
        $sites = $this->getSites($user);
        foreach($sites as $site) {
            foreach($site->getServices() as $se) {
                $ses[] = $se;
            }
        }
        return $ses;
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
    public function isValidRoleTypeName($roleTypeName){
       $roleTypeNames = \RoleTypeName::getAsArray();
       foreach($roleTypeNames as $validValue){
           if($validValue == $roleTypeName){
               return true;
           }
       }
       return false;
    }

   /**
    * Get the role type names configured for the given owned entity type. 
    * @see \RoleTypeName
    * @param \OwnedEntity $ownedEntity 
    * @return array of role type name strings 
    */
    public function getRoleTypeNamesForOwnedEntity(\OwnedEntity $ownedEntity){
        $roles = array(); 
        if($ownedEntity instanceof \Site){
            $roles[] = \RoleTypeName::SITE_ADMIN; 
            $roles[] = \RoleTypeName::SITE_OPS_DEP_MAN; 
            $roles[] = \RoleTypeName::SITE_OPS_MAN; 
            $roles[] = \RoleTypeName::SITE_SECOFFICER; 
            
        } else if($ownedEntity instanceof \NGI){
            $roles[] = \RoleTypeName::NGI_OPS_DEP_MAN; 
            $roles[] = \RoleTypeName::NGI_OPS_MAN; 
            $roles[] = \RoleTypeName::NGI_SEC_OFFICER;  
            $roles[] = \RoleTypeName::REG_FIRST_LINE_SUPPORT; 
            $roles[] = \RoleTypeName::REG_STAFF_ROD; 
            //\RoleTypeName::CIC_STAFF;  // not used
            //\RoleTypeName::REG_STAFF;  // not used  
            
        } else if($ownedEntity instanceof \Project){
            $roles[] = \RoleTypeName::COD_ADMIN; 
            $roles[] = \RoleTypeName::COD_STAFF; 
            $roles[] = \RoleTypeName::EGI_CSIRT_OFFICER; 
            $roles[] = \RoleTypeName::COO; 
            
        } else if($ownedEntity instanceof \ServiceGroup){
            $roles[] = \RoleTypeName::SERVICEGROUP_ADMIN; 
        } 
        return $roles; 
    } 

    /**
     * Get the given User's roles that have the specified role status.
     * For valid role status values, see RoleStatus class. 
     * 
     * @see \RoleStatus
     * @param \User $user Get roles for this user
     * @param string $roleStatus A role status defined in RoleStatus
     * @return array of \Role objects
     * @throws \LogicException if given roleStatus is not supported
     */
    public function getUserRoles(\User $user, $roleStatus) {
        if(!$this->isValidRoleStatus($roleStatus)){
            throw new \LogicException('Coding error - Invalid roleStatus');
        }
        if($user->getId() == null) return array(); // return empty array
        $dql = "SELECT r FROM Role r
        		JOIN r.user u
        		WHERE u.id = :id
        		AND r.status = :status";
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
         $dql = "SELECT r FROM Role r WHERE r.status = :status";
         $roles = $this->em->createQuery($dql)
            ->setParameter("status", $roleStatus)
            ->getResult();
        return $roles; 
    }

    public function getRoleTypeByName($roleTypeName){
        if(!$this->isValidRoleTypeName($roleTypeName)){
            throw new \LogicException('Coding error - Invalid roleTypeName'); 
        }
        $dql = "SELECT rt FROM RoleType rt WHERE rt.name = :roleTypeName"; 
        $roleType = $this->em->createQuery($dql)
                ->setParameter("roleTypeName", $roleTypeName)
                ->getSingleResult(); 
        return $roleType; 
    }


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
     * @param string $roleStatus @see \RoleStatus
     * @return \Role managed instance  
     * @throws \Exception if the user already has the requested role over the entity (role status GRANTED or PENDING) 
     * @throws \LogicException
     */
    public function addRole($roleTypeName, \User $user, \OwnedEntity $entity, $roleStatus){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Check the given roleName is a valid role
        if (!$this->isValidRoleStatus($roleStatus)) {
            throw new \LogicException('Coding error - invalid roleStatus');
        }
        // Check the requested roleName is suitable for the ownedEntity 
        if (!in_array($roleTypeName, $this->getRoleTypeNamesForOwnedEntity($entity))) {
            throw new \LogicException("Role requested [$roleTypeName] is not valid for this OwnedEntity");
        }
        // Check to see if user already has the same type of role GRANTED over entity 
        if (in_array($roleTypeName, $this->getUserRoleNamesOverEntity($entity, $user, \RoleStatus::GRANTED))) {
            throw new \Exception("You already have the role [$roleTypeName] over " . $entity->getName());
        }
        // Check to see if user already has the same type of role PENDING over entity
        if (in_array($roleTypeName, \Factory::getRoleService()->getUserRoleNamesOverEntity($entity, $user, \RoleStatus::PENDING))) {
            throw new \Exception("You already have a [$roleTypeName] role request pending over " . $entity->getName());
        }
         
        $this->em->getConnection()->beginTransaction();
         try {
           $roleType = $this->getRoleTypeByName($roleTypeName); 
           $r = new \Role($roleType, $user, $entity, $roleStatus);  
           $this->em->persist($r); 
           $this->em->flush();
           $this->em->getConnection()->commit();
         } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
         }
         return $r; 
    }


    public function deleteRole(\Role $role, \User $user){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

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
    }
}
