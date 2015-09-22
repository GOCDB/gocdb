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
require_once __DIR__ . '/RoleActionAuthorisationService.php'; 

/**
 * GOCDB Stateless service facade (business routnes) for project objects.
 * The public API methods are transactional.
 *
 * @author George Ryall
 * @author John Casson
 * @author David Meredith
 */
class Project  extends AbstractEntityService{

    private $roleActionAuthorisationService;

    function __construct(/*$roleActionAuthorisationService*/) {
        parent::__construct();
        //$this->roleActionAuthorisationService = $roleActionAuthorisationService;
    }


    public function setRoleActionAuthorisationService(RoleActionAuthorisationService $roleActionAuthService){
        $this->roleActionAuthorisationService = $roleActionAuthService; 
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
     * Adds a new GOCDB project. 
     * @param array $values array containing name and description of the new project
     * @param \user $user User making the change, only admin users may add projects
     * @return \Project 
     * @throws \Exception
     * @throws \org\gocdb\services\Exception
     */
    public function addProject($values, \user $user = null){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);
        
        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);
        
        //Check all the required values are present and validate the new values using the GOCDB schema file
        $this->validate($values);
        
        //check the name is unique
        if(!$this->projectNameIsUnique($values['Name'])){
            throw new \Exception("Project names must be unique, '".$values['Name']."' is already in use");
        }

        //Start transaction
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //new project object
            $project = new \Project($values['Name']);
            //set description
            $project->setDescription($values['Description']);
            
            $this->em->persist($project);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }        

        return $project;
    }

     /**
     * Edits a GOCDB project. 
     * @param array $values array containing the new name and description of the project
     * @param \user $user User making the change
     * @return \Project 
     * @throws \Exception
     * @throws \org\gocdb\services\Exception
     */
    public function editProject(\Project $project, $values, \User $user){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Check to see whether the user has a role that covers this project
        //if(count($this->authorize Action(\Action::EDIT_OBJECT, $project, $user))==0){
        if ($this->roleActionAuthorisationService->authoriseActionAbsolute(\Action::EDIT_OBJECT, $project, $user) == FALSE) {
            throw new \Exception("You don't have a role that allows you to edit " . $project->getName());
        }

        //Check all the required values are present and validate the new values using the GOCDB schema file
        $this->validate($values);
        
        //check the name is unique (or that it has not changed)
        if(!($this->projectNameIsUnique($values['Name']) or $values['Name'] == $project->getName())){
            throw new \Exception("Project names must be unique, '".$values['Name']."' is already in use");
        }
       
        //Start transaction
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //set description
            $project->setDescription($values['Description']);
            
            //set name
            $project->setName($values['Name']);
            
            $this->em->merge($project);
            $this->em->flush();
            $this->em->getConnection()->commit();
            
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }        

        return $project;
    }
    

    /**
     * Returns true if the name given is not currently in use for a project
     * @param type $name potential project name
     * @return boolean
     */
    public function projectNameIsUnique($name){
        $dql = "SELECT p from Project p
    			WHERE UPPER(p.name) = UPPER(:name)";
    	$query = $this->em->createQuery($dql);
    	$result = $query->setParameter('name', $name)->getResult();

        if(count($result)==0){
            return true;
        }
        else {
            return false;
        }
        
    }
    
    /**
     * Gets all projects
     * @return array An array of project objects
     */
    public function getProjects() {
    	$dql = "SELECT p from Project p
    			ORDER BY p.name";
    	$query = $this->em->createQuery($dql);
    	return $query->getResult();
    }
    
     /**
     * Finds a single project by ID and returns it
     * @param int $id the project ID
     * @return \Project a project object
     */
    public function getProject($id) {
    	$dql = "SELECT p FROM Project p
				WHERE p.id = :id";

    	$project = $this->em
	    	->createQuery($dql)
	    	->setParameter('id', $id)
	    	->getSingleResult();
        
    	return $project;
    }
    
    /**
     * Returns an array of all sites that belong to NGIs that are members of the 
     * specified project.
     * @param \project $project project we want sites for
     * @return array sites belonging to NGIs that belong are project members
     */
    public function getSites(\project $project){
        $id = $project->getId();
        $dql = "SELECT s 
                FROM Site s
                WHERE s.ngi IN
                    (SELECT n
                     FROM Project p
                     JOIN p.ngis n
                     WHERE p.id = :id
                    )
                ORDER BY s.shortName";
        // Bug fix here: the dql above previously contained a 'GROUP BY s' in 
        // on the line above the ORDER BY. Oracle didn't like this and threw 
        // 'ORA-00979: not a GROUP BY expression' error.  MySQL didn't seem to mind though. 
        $sites = $this->em
                ->createQuery($dql)
                ->setParameter('id', $id)
                ->getResult();

    	return $sites;
        
    }
    
    /**
     * Deletes a project from GOCDB. Only GOCDB admins can do this.
     * Does not cascade delete the project's NGIs and users' Role objects are 
     * automatically cascade deleted.
     *  
     * @param \Project $project Project  to be deleted
     * @param \User $user User performing the deletion - must be an administrator for it to be successful
     * @throws \Exception
     */
    public function deleteProject(\Project $project, \User $user = null){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);
        
        //get list of NGIs to allow the link with them to be broken
        $ngis = $project->getNgis();
       
        
        //merge changes and remove project.
        $this->em->getConnection()->beginTransaction();
        try {
            //Break the links with ngis to the project
            foreach($ngis as $ngi) {
				$project->getNgis()->removeElement($ngi);
                $ngi->getProjects()->removeElement($project);
			}
            //remove the project - users' Role objects are automatically cascade deleted
			$this->em->remove($project);
			$this->em->flush();
			$this->em->getConnection()->commit();
		} catch (\Exception $e) {
			$this->em->getConnection()->rollback();
			$this->em->close();
			throw $e;
		}
    }
    
    /**
     * Adds Ngis to a project. Only GOCDB admins may perform this action and 
     * the code enforces this
     * @param \Project $project The project to which the NGI is being added
     * @param \Doctrine\Common\Collections\ArrayCollection $ngis ngis to be added
     * @param \User $user user making the changes
     * @throws \Exception
     */
    public function addNgisToProject (\Project $project, \Doctrine\Common\Collections\Collection $ngis, \User $user = null){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);
        
        //Check the NGIs are all NGIs
        foreach ($ngis as $ngi) {
            if(!($ngi instanceof \NGI)){
                throw new \Exception("one or more objects being added to project is not of type NGI");
            }
        }
        
        //Actually Add the NGIs
        $this->em->getConnection()->beginTransaction();
		try {
			foreach($ngis as $ngi) {
				$project->addNgi($ngi);
			}
			$this->em->merge($project);
			$this->em->flush();
			$this->em->getConnection()->commit();
		} catch (\Exception $e) {
			$this->em->getConnection()->rollback();
			$this->em->close();
			throw $e;
		}
    }
    
    /**
     * Remove given NGIs from a given project
     * @param \Project $project project from which ngis are to be removed
     * @param \Doctrine\Common\Collections\Collection $ngis ngis to be removed
     * @param \User $user user doing the removing (must be GOCDB admin)
     * @throws \Exception
     */
    public function removeNgisFromProject (\Project $project,  \Doctrine\Common\Collections\Collection $ngis, \User $user = null){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);
        
        //Check the NGIs are all NGIs
        foreach ($ngis as $ngi) {
            if(!($ngi instanceof \NGI)){
                throw new \Exception("one or more objects being added to project is not of type NGI");
            }
        }
        
        //Actually remove the NGIs
        $this->em->getConnection()->beginTransaction();
		try {
			foreach($ngis as $ngi) {
				$project->getNgis()->removeElement($ngi);
                $ngi->getProjects()->removeElement($project);
                $this->em->merge($ngi);
			}
			$this->em->merge($project);
			$this->em->flush();
			$this->em->getConnection()->commit();
		} catch (\Exception $e) {
			$this->em->getConnection()->rollback();
			$this->em->close();
			throw $e;
		}
        
    }
    

    /**
     * Get an array of Role names granted to the user that permit the requested 
     * action on the given Project. If the user has no roles that 
     * permit the requested action, then return an empty array. 
     * 
     * Suppored actions: EDIT_OBJECT, GRANT_ROLE, REJECT_ROLE, REVOKE_ROLE  
     * 
     * @param string $action @see \Action 
     * @param \ServiceGroup $sg
     * @param \User $user
     * @return array of RoleName string values that grant the requested action  
     * @throws \LogicException if action is not supported or is unknown 
     */
    /*public function authorize Action($action, \Project $project, \User $user = null){
        require_once __DIR__ . '/Role.php';
        
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
        
        if($action == \Action::EDIT_OBJECT){
            // Only Project (E) level roles can edit project 
            $requiredRoles = array(  
                //\RoleTypeName::CIC_STAFF, 
                \RoleTypeName::COD_ADMIN,
                \RoleTypeName::COD_STAFF,
                \RoleTypeName::EGI_CSIRT_OFFICER,
                \RoleTypeName::COO);
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($project, $user);  
            $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));

        } else if($action == \Action::GRANT_ROLE ||
                $action == \Action::REJECT_ROLE || $action == \Action::REVOKE_ROLE){
           $requiredRoles = array(
                //\RoleTypeName::CIC_STAFF, 
                \RoleTypeName::COD_ADMIN,
                \RoleTypeName::COD_STAFF,
                \RoleTypeName::EGI_CSIRT_OFFICER,
                \RoleTypeName::COO);
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($project, $user);
            $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));
            
        } else {
            throw new \LogicException('Unsupported Action');  
        }
        if($user->isAdmin()){
           $enablingRoles[] = \RoleTypeName::GOCDB_ADMIN;  
        }
        return array_unique($enablingRoles);
    }*/
    
    /**
     * returns all those NGIs which are not a member of a given project
     * @param \Project $project project which returned NGIs aren't in
     * @return ArrayCollection $ngis 
     */
    public function getNgisNotinProject(\Project $project){
        
        $id = $project->getId();
        $dql = "SELECT n
                FROM ngi n
                WHERE n NOT IN
                    (SELECT np
                     FROM Project p
                     JOIN p.ngis np
                     WHERE p.id = :id
                    )
                ORDER BY n.name";
        
        $ngis = $this->em
                ->createQuery($dql)
                ->setParameter('id', $id)
                ->getResult();

    	return $ngis;
        
    }

    /**
	 * Checks the required values are present and then Validates the user 
     * inputted project data against the data in the gocdb_schema.xml.
	 * @param array $projectData containing all the fields for a GOCDB project
	 *                       object
	 * @throws \Exception If the project's data can't be
	 *                    validated. The \Exception message will contain a human
	 *                    readable description of which field failed validation.
	 * @return null */
	private function validate($projectData) {
		require_once __DIR__.'/Validate.php';
		
        //check values are there (description may be "")
        if(!((array_key_exists('Name',$projectData)) and (array_key_exists('Description',$projectData)))){
            throw new \Exception("A name and description for the project must be specified");
        }    
        
        //check values are strings
        if(!((is_string($projectData['Name'])) and (is_string($projectData['Description'])))){
            throw new \Exception("The new project name and description must be valid strings");
        }
                     
        //check that the name is not null
        if(empty($projectData['Name'])){
            throw new \Exception("A name must be specified for the Project");
        }


        //remove the ID fromt he values file if present (which it will be for an edit)
        if(array_key_exists("ID",$projectData)){
            unset($projectData["ID"]);
        }
               
        $serv = new \org\gocdb\services\Validate();
		foreach($projectData as $field => $value) {
			$valid = $serv->validate('project', strtoupper($field), $value);
			if(!$valid) {
				$error = "$field contains an invalid value: $value";
				throw new \Exception($error);
			}
		}
	}
}   


