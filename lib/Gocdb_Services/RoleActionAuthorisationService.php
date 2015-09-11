<?php

/*
 * Copyright (C) 2015 STFC
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

namespace org\gocdb\services;
require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/RoleActionMappingService.php';
require_once __DIR__ . '/Role.php';

/**
 * Used to determine if a user can perform an action on an object by 
 * comparing the user's granted DB roles against the rules/roles defined in the 
 * role action mapping service. 
 *
 * @author David Meredith
 */
class RoleActionAuthorisationService  extends AbstractEntityService  {

    private $roleActionMappingService; 
    private $roleService; 
   
    /**
     * Create a new instance.  
     * @param \org\gocdb\services\RoleActionMappingService $roleActionMappingService
     * @param \org\gocdb\services\Role $roleService
     */
    public function __construct(RoleActionMappingService $roleActionMappingService, Role $roleService) {
        parent::__construct();
        $this->roleActionMappingService = $roleActionMappingService; //new RoleActionMappingService(); 
        $this->roleService = $roleService; //new Role();  
    }

   
    /**
     * Can the user peform the specified action over the target entity? if true, 
     * return all the user's roles that grant the action otherwise return an 
     * empty array.  
     * 
     * @param string $action The action the user wants to perform over the entity 
     * @param \OwnedEntity $entity The target entity for the action 
     * @param \User $user The user who wants to peform the action on the entity 
     * @return array of {@see \Role}s that grant the action over entity. 
     * Can be an empty array if no role grants the action. 
     * @throws \LogicException  if the entity is under a 
     * project that has no mapping in the role action mappings file. 
     */
    public function authoriseAction($action, \OwnedEntity $entity, \User $user){
        //throw new \LogicException('not implemented yet'); 
        if (!is_string($action) || strlen(trim($action)) == 0) {
            throw new \LogicException('Invalid action');
        } 
        // If we limit the actions, then its hard to test using some sample 
        // roleActionMapping files. 
//        if(!in_array($action, \Action::getAsArray())){
//            throw new \LogicException('Coding Error - Invalid action not known'); 
//        } 
        
        // Get a list of DB projects that are reachable from the given entity 
        // by moving up the object hierarchy to those projects. 
        $dbProjects = $this->roleService->getReachableProjectsFromOwnedEntity($entity); 

        // For each DB project, lookup the role type mappings that enable 
        // the action on the specified entity type (mappings defined in RoleActionMappings XML). 
        $requiredRoleTypesPerProj = array();
        foreach ($dbProjects as $dbProject) {
            //print_r('reachable project: ['.$dbProject->getName()."] \n"); 
            
            // Lookup the role type mappings for this project (['RoleTypeName'] => 'overOwnedEntity'). 
            // throws LogicException if the dbProjectName does not exist in the 
            // mapping file! (not so with action or entity-type)  
            // For example, the following role-types over the specified object 
            // types could enable 'actionX': 
            // array (
            //   ['Site Administrator'] => 'Site', 
            //   ['NGI Operations Manager'] => 'Ngi', 
            //   ['Chief Operations Officer'] => 'Project'
            // );      
            $roleTypeMappingsForProj = $this->roleActionMappingService->
                    getRoleTypeNamesThatEnableActionOnTargetObjectType(
                    $action, $entity->getType(), $dbProject->getName());
           
            //print_r('roleTypeMappings for reachable project: ['.$dbProject->getName()."] \n"); 
            //print_r($roleTypeMappingsForProj); 
            
            // If there are roles that enable the action, store the roles for the project 
            if (count($roleTypeMappingsForProj) > 0) {
                $requiredRoleTypesPerProj[/*$dbProject->getId()*/] = $roleTypeMappingsForProj;
            }
        }

        // Get all the roles over/above the entity. Note, in future it may be 
        // necessary to introduce getUserRolesFromEntityDescendDomainGraph($user, $entity) 
        // e.g. IF we want to enable actions on parent objects from roles held over 
        // child objects, e.g. consider the case where users with roles over site/ngis 
        // want to post comments on a Project board - a user may need a role 
        // over a child object to do this. 
        $dbUserRolesAboveEntity = $this->roleService->getUserRolesFromEntityAscendDomainGraph($user, $entity);  

        $grantingUserRoles = array();
        foreach ($dbUserRolesAboveEntity as $candidateUserRole) {
            //foreach ($requiredRoleTypesPerProj as $dbProjectId => $requiredRoleTypesForProjX) {
            foreach ($requiredRoleTypesPerProj as $requiredRoleTypesForProjX) {
                //print_r("candidate granting role: [".$candidateUserRole->getRoleType()->getName()."]\n"); 
                // Iterate required role types for proj X  
                foreach ($requiredRoleTypesForProjX as $grantingRoleTypeName => $overEntityType) {

                    // If user has a role with the same type name and 
                    // over the same entity type, this role grants the action  
                    if ($candidateUserRole->getRoleType()->getName() == $grantingRoleTypeName &&
                            strtoupper($candidateUserRole->getOwnedEntity()->getType()) == strtoupper($overEntityType)) {

                        if (!in_array($candidateUserRole, $grantingUserRoles)) {
                            $grantingUserRoles[] = $candidateUserRole;
                        }
                    }
                }
            }
        }

        return $grantingUserRoles; 
    }

    
}
