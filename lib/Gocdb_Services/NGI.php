<?php
namespace org\gocdb\services;
/* Copyright (c) 2011 STFC
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

/**
 * GOCDB Stateless service facade (business routnes) for group objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */

require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/Role.php';
require_once __DIR__ . '/Validate.php';
require_once __DIR__ . '/RoleConstants.php';
require_once __DIR__ . '/RoleActionAuthorisationService.php';
require_once __DIR__.  '/Scope.php';
require_once __DIR__.  '/Config.php';
use Doctrine\ORM\QueryBuilder;

class NGI extends AbstractEntityService{

    private $roleActionAuthorisationService;
    private $scopeService;
    private $configService;

    function __construct(/*$roleActionAuthorisationService*/) {
        parent::__construct();
        //$this->roleActionAuthorisationService = $roleActionAuthorisationService;
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
     * Finds a single NGI by ID and returns its entity
     * @param int $id the NGI ID
     * @return NGI an NGI entity
     */
    public function getNgi($id) {
        $dql = "SELECT n FROM NGI n
                WHERE n.id = :id";
        $ngi = $this->em->createQuery($dql)
                   ->setParameter('id', $id)
                   ->getSingleResult();
        return $ngi;
    }

    /**
     * Return all {@see \NGI}s that satisfy the specfied filter parameters.
     *
     * <p>
     * $filterParams defines an associative array of optional parameters for
     * filtering. The supported Key => Value pairs include:
     * <ul>
     * <li>'roc' => String name of the NGI/ROC</li>
     * <li>'scope' => 'String,comma,sep,list,of,scopes,e.g.,egi,wlcg'</li>
     * <li>'scope_match' => String 'any' or 'all' </li>
     * <ul>
     *
     * @param array $filterParams
     * @return array NGI array
     */
    public function getNGIsFilterByParams($filterParams) {
        require_once __DIR__ . '/PI/GetNGI.php';
        $getNgi = new GetNGI( $this->em );
        $getNgi->validateParameters( $filterParams );
        $getNgi->createQuery();
        $ngis = $getNgi->executeQuery();
        return $ngis;
    }

    /**
     * Get all NGIs as an object array with joined scopes.
     *
     * @see NGI
     * @return \NGI object array
     */
    public function getNGIs($scope = NULL) {
        $qb = $this->em->createQueryBuilder();
        $qb->select( 'n', 'sc' )->from( 'NGI', 'n' )->leftjoin( 'n.scopes', 'sc' )->orderBy( 'n.name', 'ASC' );

        if ($scope != null && $scope != '%%') {
            $qb->andWhere( $qb->expr()->like( 'sc.name', ':scope' ) )->setParameter( ':scope', $scope );
        }
        $query = $qb->getQuery();
        $ngis = $query->execute();
        return $ngis;
    }

    /**
     * Return all of the NGIs that the user has permission to execute the
     * specified action on.
     *
     * @param type $action
     * @param \User $user
     * @return \org\gocdb\services\NGI
     * @throws \LogicException
     */
    public function getNGIsBySupportedAction($action, \User $user=null) {
        //throw new \LogicException('not implemented yet');
        if ($user == null) {
            return array();
        }
        if (!in_array($action, \Action::getAsArray())) {
            throw new \LogicException('Coding Error - Invalid action');
        }
        $roleService = new Role(); // to inject
        $roleService->setEntityManager($this->em);
        $grantedUserRoles = $roleService->getUserRoles($user, \RoleStatus::GRANTED);
        $ngiArray = array();
        foreach ($grantedUserRoles as $grantedUserRole) {
            $entity = $grantedUserRole->getOwnedEntity();
            if ($entity instanceof \NGI) {
                //$enablingRoles = $this->authorize Action($action, $entity, $user);
                if($this->roleActionAuthorisationService->authoriseAction($action, $entity, $user)->getGrantAction()){
                    //print_r($enablingRoles);
                    if(!in_array($entity, $ngiArray)){
                        $ngiArray[] = $entity;
                    }
                }
            }
        }
        return $ngiArray;
    }


    /**
     * Get an array of Role names granted to the user that permit the requested
     * action on the given NGI. If the user has no roles that
     * permit the requested action, then return an empty array.
     * <p>
     * Supported actions: EDIT_OBJECT, NGI_ADD_SITE,
     * GRANT_ROLE, REJECT_ROLE, REVOKE_ROLE
     *
     * @see \Action
     * @param string $action suppored action
     * @param \NGI $ngi
     * @param \User $user
     * @return array of RoleName values
     * @throws \LogicException
     */
    /*public function authorize Action($action, \NGI $ngi, \User $user = null){
        if(is_null($user)){
            return array(); // return empty array
        }
        if(!in_array($action, \Action::getAsArray())){
            throw new \LogicException('Coding Error - Invalid action not known');
        }
        $roleService = new Role(); // to inject
        $roleService->setEntityManager($this->em);

        if($action == \Action::EDIT_OBJECT) {
            // D and D' can edit an NGI
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($ngi, $user);
            $requiredRoles = array(
                \RoleTypeName::REG_FIRST_LINE_SUPPORT,
                \RoleTypeName::REG_STAFF_ROD,
                \RoleTypeName::NGI_SEC_OFFICER,
                \RoleTypeName::NGI_OPS_DEP_MAN,
                \RoleTypeName::NGI_OPS_MAN);
             $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));

        } else if($action == \Action::NGI_ADD_SITE) {
            // Only D' can add a site to an owned group/ngi
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($ngi, $user);
            $requiredRoles = array(
                \RoleTypeName::NGI_SEC_OFFICER,
                \RoleTypeName::NGI_OPS_DEP_MAN,
                \RoleTypeName::NGI_OPS_MAN);
             $enablingRoles = array_intersect($requiredRoles, array_unique($usersActualRoleNames));

        } else if($action == \Action::GRANT_ROLE ||
                $action == \Action::REJECT_ROLE || $action == \Action::REVOKE_ROLE){
            // NGI (D') roles can manage roles on an owned group/ngi
            $requiredNgiRoles = array(
                \RoleTypeName::NGI_SEC_OFFICER,
                \RoleTypeName::NGI_OPS_DEP_MAN,
                \RoleTypeName::NGI_OPS_MAN);
            $usersActualRoleNames = $roleService->getUserRoleNamesOverEntity($ngi, $user);

            // Project (E) level roles required to approve-reject/revoke role
            // requests over the owned NGI (to bootstrap)
            $requiredProjectRoles = array(
                //\RoleTypeName::CIC_STAFF, // not sure this role should be used to manage roles over ngi
                \RoleTypeName::COD_STAFF,
                \RoleTypeName::COD_ADMIN,
                \RoleTypeName::EGI_CSIRT_OFFICER,
                \RoleTypeName::COO);
            // Get all user's project level roles for all the projects that group the site's ngi
            if(count($ngi->getProjects()) > 0){
                foreach ($ngi->getProjects() as $parentProject){
                  $usersActualRoleNames = array_merge($usersActualRoleNames,
                          $roleService->getUserRoleNamesOverEntity($parentProject, $user));
                }
            }
            // rather than below that queries for all user roles and extracts ANY project role:
            //$allUserRoles = $roleService->getUserRoles($user, \RoleStatus::GRANTED);
            //foreach ($allUserRoles as $role) {
            //    if (in_array($role->getRoleType()->getName(), $requiredProjectRoles)) {
            //        $usersActualRoleNames[] = $role->getRoleType()->getName();
            //    }
            //}
            $enablingRoles = array_intersect(array_merge($requiredNgiRoles, $requiredProjectRoles), array_unique($usersActualRoleNames));
        } else {
            throw new \LogicException('Unsupported Action');
        }
        // finally add the gocdb admin role
        if($user->isAdmin()){
           $enablingRoles[] = \RoleTypeName::GOCDB_ADMIN;
        }
        return array_unique($enablingRoles);
    }*/

    /**
     * Check to see if the current user is allowed to edit the passed NGI
     * @param NGI $ngi The NGI
     * @param org\gocdb\services\User $user The user
     * @throws \Exception If the user isn't authorised
     * @return null
     */
    /*public function edit Authorization(\NGI $ngi, \User $user = null) {
        require_once __DIR__ . '/Factory.php';

        if(is_null($user)) {
            throw new \Exception("Unregistered users can't edit an NGI.");
        }

        // see if the user has a D or D' role over the NGI
        $classifications = array("D", "D'");
        if (\Factory::getRoleService()
                ->userHasNgiRole($classifications, $ngi, $user)) {
            return;
        }

        // If we've reached this point the user doesn't have permission
        throw new \Exception("You don't have permission to edit this NGI.");
    }*/

    /**
     * Updates an NGI
     * Returns the updated NGI
     *
     * Accepts an array $newValues as a parameter. $newVales' format is as follows:
     * <pre>
     *  Array
     *  (
     *	    [DESCRIPTION] => NGI_DE
     *	    [EMAIL] => ngi-de-jru-leitung@listserv.dfn.de
     *	    [HELPDESK_EMAIL] =>
     *	    [ROD_EMAIL] =>
     *	    [SECURITY_EMAIL] =>
     *	    [GGUS_SU] =>
     *	    [ID] => 14
     *	)
     * </pre>
     * @param NGI The NGI to update
     * @param array $newValues Array of updated data, specified above.
     * @param User The current user
     * return NGI The updated NGI entity
     */
    public function editNgi(\NGI $ngi, $newValues, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        if ($user == null) {
           throw new \Exception("You don't have permission to edit this NGI (null user).");
        }
        if ($this->roleActionAuthorisationService->authoriseAction(
                \Action::EDIT_OBJECT, $ngi, $user)->getGrantAction() == FALSE) {
            throw new \Exception("You don't have permission to edit this NGI.");
        }
        $this->validate($newValues['NGI']);

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

        // If not admin, Check user edits to the ngi's Reserved scopes:
        // Required to prevent users manually crafting a POST request in an attempt
        // to select reserved scopes, this is unlikely but it is a possible hack.
        if (!$user->isAdmin()) {
            $selectedReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $selectedScopesToApply);

            $existingReservedScopes = $this->scopeService->getScopesFilterByParams(
                    array('excludeNonReserved' => true), $ngi->getScopes()->toArray());

            foreach($selectedReservedScopes as $sc){
                // Reserved scopes must already be assigned to site or parent
                if(in_array($sc, $existingReservedScopes)){
                    continue;
                }
                throw new \Exception("A reserved Scope Tag was selected that is not assigned to the NGI");
            }
        }

        //check there are the required number of optional scopes specified
        $this->checkNumberOfScopes($this->scopeService->getScopesFilterByParams(
               array('excludeReserved' => true), $selectedScopesToApply));

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();
        try {
            //Update the NGI
            $ngi->setEmail($newValues['NGI']['EMAIL']);
            $ngi->setHelpdeskEmail($newValues['NGI']['HELPDESK_EMAIL']);
            $ngi->setRodEmail($newValues['NGI']['ROD_EMAIL']);
            $ngi->setSecurityEmail($newValues['NGI']['SECURITY_EMAIL']);
            $ngi->setGgus_Su($newValues['NGI']['GGUS_SU']);


            // update the NGIs scopes
            // firstly remove all existing scope links
            $scopes = $ngi->getScopes();
            foreach($scopes as $s) {
                $ngi->removeScope($s);
            }

            //find then link each scope specified to the NGI
//            foreach ($newValues['SCOPES'] as $scopeId){
//                $dql = "SELECT s FROM Scope s WHERE s.id = ?1";
//                $scope = $this->em->createQuery($dql)
//                             ->setParameter(1, $scopeId)
//                             ->getSingleResult();
//                $ngi->addScope($scope);
//            }
            foreach($selectedScopesToApply as $scope){
                $ngi->addScope($scope);
            }

            $this->em->merge($ngi);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        return $ngi;
    }

    /**
     * Validates the user inputted NGI data against the
     * checks in the gocdb_schema.xml.
     * @param array $ngiData containing all the fields for a GOCDB_USER
     *                       object
     * @throws \Exception If the NGI data can't be
     *                   validated. The \Exception message will contain a human
     *                   readable description of which field failed validation.
     * @return null */
    private function validate($ngiData) {
        //require_once __DIR__.'/Factory.php';
        //$serv = \Factory::getValidateService();
        $serv =  new Validate(); //org\gocdb\services\Validate();
        foreach($ngiData as $field => $value) {
            $valid = $serv->validate('ngi', $field, $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
    }

    public function addNgi($valuesarray, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //isAdmin can't be called on null, so check first that the user is registered
        if (is_null($user)) {
            throw new \Exception("Unregistered users may not make changes");
        }

        //Only admins should be able to add an NGI
        if (!$user->isAdmin()) {
            throw new \Exception("You must be a global administrator to add an NGI");
        }

        //seperate values and scope arrays
        $values = $valuesarray['NGI'];

        // Add SCOPE TAGS:
        // collate selected scopeIds (reserved and non-reserved)
        $scopeIdsToApply = array();
        foreach($valuesarray['Scope_ids'] as $sid){
            $scopeIdsToApply[] = $sid;
        }
        foreach($valuesarray['ReservedScope_ids'] as $sid){
            $scopeIdsToApply[] = $sid;
        }
        $selectedScopesToApply = $this->scopeService->getScopes($scopeIdsToApply);

        //check values are there
        if (!((array_key_exists('NAME', $values))
                and ( array_key_exists('EMAIL', $values))
                and ( array_key_exists('HELPDESK_EMAIL', $values))
                and ( array_key_exists('SECURITY_EMAIL', $values))
                and ( array_key_exists('ROD_EMAIL', $values)))) {
            throw new \Exception("A name and email adresses must be fed to the function, even if they are empty strings");
        }


        //Check that the name is not null
        if (empty($values['NAME'])) {
            throw new \Exception("A name must be specified for the NGI");
        }

        //Validate
        $this->validate($values);

        //check there are the required number of scopes specified
        $this->checkNumberOfScopes($valuesarray['Scope_ids'] );

        //check the name is unique
        if (!$this->NGINameIsUnique($values['NAME'])) {
            throw new \Exception("NGI names must be unique, '" . $values['NAME'] . "' is already in use");
        }

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();

        try {
            //create the new NGI
            $ngi = new \NGI();
            $ngi->setName($values['NAME']);
            $ngi->setEmail($values['EMAIL']);
            $ngi->setHelpdeskEmail($values['HELPDESK_EMAIL']);
            $ngi->setRodEmail($values['ROD_EMAIL']);
            $ngi->setSecurityEmail($values['SECURITY_EMAIL']);

            //find then link each scope specified to the NGI
//            foreach ($scopeIds as $scopeId) {
//                $dql = "SELECT s FROM Scope s WHERE s.id = ?1";
//                $scope = $this->em->createQuery($dql)
//                        ->setParameter(1, $scopeId)
//                        ->getSingleResult();
//                $ngi->addScope($scope);
//            }
            foreach($selectedScopesToApply as $scope){
                $ngi->addScope($scope);
            }

            $this->em->persist($ngi);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        return $ngi;
    }

    /**
     * Returns true if the name given is not currently in use for a NGI
     * @param type $name potential service type name
     * @return boolean
     */
    public function NGINameIsUnique($name){
        $dql = "SELECT n from NGI n
                WHERE n.name = :name";
        $query = $this->em->createQuery($dql);
        $result = $query->setParameter('name', $name)->getResult();

        if(count($result)==0){
            return true;
        }
        else {
            return false;
        }
    }

    private function checkNumberOfScopes($scopeIds){
        require_once __DIR__ . '/Config.php';
        $configService = new \org\gocdb\services\Config();
        $minumNumberOfScopes = $configService->getMinimumScopesRequired('ngi');
        if(sizeof($scopeIds)<$minumNumberOfScopes){
            throw new \Exception("A NGI must have at least " . $minumNumberOfScopes . " scope(s) assigned to it.");
        }
    }

   /**
    * Delete the given NGI and cascade delete all of the NGI's child entities.
    * These include Sites, Services, EndpointLocations, Downtimes that will be
    * orphaned, CertificationStatusLogs and Roles that previously linked to the
    * deleted owned entities.
    *
    * @param \NGI $ngi
    * @param \User $user - must be an admin user
    * @param boolean $logNgiSiteServiceInArchives Record the deletion of the ngi,
    * its child sites and services in the archive tables.
    * @throws \org\gocdb\services\Exception
    */
    public function deleteNgi(\NGI $ngi, \User $user = null, $logNgiSiteServiceInArchives = true) {
        require_once __DIR__ . '/../DAOs/SiteDAO.php';
        require_once __DIR__ . '/../DAOs/ServiceDAO.php';
        require_once __DIR__ . '/../DAOs/NGIDAO.php';
        require_once __DIR__ . '/ServiceService.php';
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        $this->em->getConnection()->beginTransaction();
        try {
            $ngiDAO = new \NGIDAO;
            $ngiDAO->setEntityManager($this->em);
            $siteDAO = new \SiteDAO;
            $siteDAO->setEntityManager($this->em);
            $serviceDAO = new \ServiceDAO;
            $serviceDAO->setEntityManager($this->em);

            //Archive ngi
            if($logNgiSiteServiceInArchives){
              $ngiDAO->addNGIToArchive($ngi, $user);
            }

            //delete each child site
            foreach ($ngi->getSites() as $site){
                //Archive site
                if($logNgiSiteServiceInArchives){
                  $siteDAO->addSiteToArchive($site, $user);
                }

                //delete each child service
                foreach($site->getServices() as $service){
                    //archive the srvice
                    if($logNgiSiteServiceInArchives){
                      $serviceDAO->addServiceToArchive($service, $user);
                    }
                    //remove the service (and any downtimes only associated with it)
                    $serviceDAO->removeService($service);
                }

                //remove the site
                $siteDAO->removeSite($site);
            }

            //remove the NGI
            $ngiDAO->removeNGI($ngi);

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
}
