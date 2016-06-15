<?php
namespace org\gocdb\services;
/* Copyright ? 2011 STFC
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
require_once __DIR__ . '/../Doctrine/entities/User.php';

/**
 * GOCDB Stateless service facade (business routnes) for user objects.
 * The public API methods are transactional.
 *
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */
class User extends AbstractEntityService{

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
     * Gets a user object from the DB
     * @param $id User ID
     * @return User object
     */
    public function getUser($id) {
        return $this->em->find("User", $id);
    }

    /**
     * Lookup a User object by user's principle id string.
     * @param string $userPrinciple the user's principle id string, e.g. DN.
     * @return User object or null if no user can be found with the specified principle
     */
    public function getUserByPrinciple($userPrinciple){
       if(empty($userPrinciple)){
           return null;
       }
       $dql = "SELECT u from User u WHERE u.certificateDn = :certDn";
       $user = $this->em->createQuery($dql)
                  ->setParameter(":certDn", $userPrinciple)
                  ->getOneOrNullResult();
       return $user;
    }

    /**
     * Updates the users last login time to the current time in UTC.
     * @param \User $user
     */
    public function updateLastLoginTime(\User $user){
        $nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
        $this->em->getConnection()->beginTransaction();
        try {
            // Set the user's member variables
            $user->setLastLoginDate($nowUtc);
            $this->em->merge($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        return $user;
    }

    /**
     * Find sites a user has a role over with the specified role status.
     * @param \User $user The user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \Site objects or emtpy array
     */
    public function getSitesFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF Site
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $sites = array();

        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($sites as $site) {
                if($site == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $sites[] = $role->getOwnedEntity();
        }

        return $sites;
    }

    /**
     * Find NGIs a user has a role over with the specified role status.
     * @param \User $user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \NGI objects or empty array
     */
    public function getNgisFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF NGI
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $ngis = array();
        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($ngis as $ngi) {
                if($ngi == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $ngis[] = $role->getOwnedEntity();
        }

        return $ngis;
    }

    /**
     * Find service groups a user has a role over with the specified role status.
     * @param \User $user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \ServiceGroup objects or empty array
     */
    public function getSGroupsFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF ServiceGroup
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $sGroups = array();

        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($sGroups as $sGroup) {
                if($sGroup == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $sGroups[] = $role->getOwnedEntity();
        }

        return $sGroups;
    }

    /**
     * Find Projects a user has a role over with the specified role status.
     * @param \User $user
     * @param string $roleStatus Optional role status string @see \RoleStatus (default is GRANTED)
     * @return array of \Project objects or empty array
     */
    public function getProjectsFromRoles(\User $user, $roleStatus = \RoleStatus::GRANTED) {
        $dql = "SELECT r FROM Role r
                INNER JOIN r.user u
                INNER JOIN r.ownedEntity o
                WHERE u.id = :id
                AND o INSTANCE OF Project
                AND r.status = :status";
        $roles = $this->em->createQuery($dql)
                    ->setParameter(":id", $user->getId())
                    ->setParameter(":status", $roleStatus)
                    ->getResult();
        $projects = array();

        foreach($roles as $role) {
            // Check whether this site is already in the list
            // (A user can hold more than one role over an entity)
            foreach($projects as $project) {
                if($project == $role->getOwnedEntity()) {
                    continue 2;
                }
            }
            $projects[] = $role->getOwnedEntity();
        }

        return $projects;
    }

    /**
     * Updates a User
     * Returns the updated user
     *
     * Accepts an array $newValues as a parameter. $newVales' format is as follows:
     * <pre>
     *  Array
     *  (
     *      [TITLE] => Mr
     *      [FORENAME] => Will
     *      [SURNAME] => Rogers
     *      [EMAIL] => WAHRogers@STFC.ac.uk
     *      [TELEPHONE] => 01235 44 5011
     *  )
     * </pre>
     * @param User The User to update
     * @param array $newValues Array of updated user data, specified above.
     * @param User The current user
     * return User The updated user entity
     */
    public function editUser(\User $user, $newValues, \User $currentUser = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // validate the input fields for the user
        $this->validateUser($newValues);

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();

        try {
            // Set the user's member variables
            $user->setTitle($newValues['TITLE']);
            $user->setForename($newValues['FORENAME']);
            $user->setSurname($newValues['SURNAME']);
            $user->setEmail($newValues['EMAIL']);
            $user->setTelephone($newValues['TELEPHONE']);
            $this->em->merge($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        return $user;
    }


    /**
     * Check to see if the current user has permission to edit a user entity
     * @param org\gocdb\services\User $user The user being edited or deleted
     * @param User $currentUser The current user
     * @throws \Exception If the user isn't authorised
     * @return null
     */
    public function editUserAuthorization(\User $user, \User $currentUser = null) {
        if(is_null($currentUser)){
            throw new \Exception("unregistered users may not edit users");
        }

        if($currentUser->isAdmin()) {
            return;
        }
        // Allow the current user to edit their own info
        if($currentUser == $user) {
            return;
        }
        throw new \Exception("You do not have permission to edit this user.");
    }

    /**
     * Validates the user inputted user data against the
     * checks in the gocdb_schema.xml.
     * @param array $user_data containing all the fields for a GOCDB_USER
     *                       object
     * @throws \Exception If the site data can't be
     *                   validated. The \Exception message will contain a human
     *                   readable description of which field failed validation.
     * @return null */
    private function validateUser($userData) {
        require_once __DIR__ .'/Validate.php';
        $serv = new \org\gocdb\services\Validate();
        foreach($userData as $field => $value) {
            $valid = $serv->validate('user', $field, $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
    }

    /**
     * Array
     * (
     *     [TITLE] => Mr
     *     [FORENAME] => Testing
     *     [SURNAME] => TestFace
     *     [EMAIL] => JCasson@gmail.com
     *     [TELEPHONE] => 01235 44 5010
     *     [CERTIFICATE_DN] => /C=UK/O=eScience/OU=CLRC/L=RAL/CN=claire devereuxxxx
     * )
     * @param array $values User details, defined above
     */
    public function register($values) {
        // validate the input fields for the user
        $this->validateUser($values);

        // Check the DN isn't already registered
        $user = $this->getUserByPrinciple($values['CERTIFICATE_DN']);
        if(!is_null($user)) {
            throw new \Exception("DN is already registered in GOCDB");
        }

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();
        $user = new \User();
        try {
            $user->setTitle($values['TITLE']);
            $user->setForename($values['FORENAME']);
            $user->setSurname($values['SURNAME']);
            $user->setEmail($values['EMAIL']);
            $user->setTelephone($values['TELEPHONE']);
            $user->setCertificateDn($values['CERTIFICATE_DN']);
            $user->setAdmin(false);
            $this->em->persist($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $ex;
        }
        return $user;
    }

    /**
     * Update a user's DN
     * @param \User $user user to have DN updated
     * @param string $dn new DN
     * @param \User $currentUser User doing the updating
     * @throws \Exception
     * @throws \org\gocdb\services\Exception
     */
    public function editUserDN(\User $user, $dn, \User $currentUser = null){
        //Authorisation - only GOCDB Admins shoud be able to change DNs (Throws exception if not)
        $this->checkUserIsAdmin($currentUser);

        //Check the DN is changed
        if($dn == $user->getCertificateDn()) {
            throw new \Exception("The specified certificate DN is the same as the current DN");
        }

        //Check the DN is unique (if not null)
        if(!is_null($this->getUserByPrinciple($dn))) {
            throw new \Exception("DN is already registered in GOCDB");
        }

        //Validate the DN
        $dnInAnArray['CERTIFICATE_DN']= $dn;
        $this->validateUser($dnInAnArray);

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();
        try {
            $user->setCertificateDn($dn);
            $this->em->merge($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Deletes a user
     * @param \User $user To be deleted
     * @param \User $currentUser Making the request
     * @throws \Exception If user can't be authorized */
    public function deleteUser(\User $user, \User $currentUser = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        $this->editUserAuthorization($user, $currentUser);
        $this->em->getConnection()->beginTransaction();
        try {
            $this->em->remove($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Returns all users in GOCDB or those matching optional criteria note
     * forename and surname are handled case insensitivly
     * @param string $surname surname of users to be returned (matched case insensitivly)
     * @param string $forename forename of users to be returned (matched case insensitivly)
     * @param string $dn dn of user to be returned. If specified only one user will be returned. Matched case sensitivly
     * @param mixed $isAdmin if null then admin status is ignored, if true only admin users are returned and if false only non admins
     * @return array An array of site objects
     */
    public function getUsers($surname=null, $forename=null, $dn=null, $isAdmin=null) {

        $dql =
            "SELECT u FROM User u
             WHERE (UPPER(u.surname) LIKE UPPER(:surname) OR :surname is null)
             AND (UPPER(u.forename) LIKE UPPER(:forename) OR :forename is null)
             AND (u.certificateDn LIKE :dn OR :dn is null)
             AND (u.isAdmin = :isAdmin OR :isAdmin is null)
             ORDER BY u.surname";

        $users = $this->em
            ->createQuery($dql)
            ->setParameter(":surname", $surname)
            ->setParameter(":forename", $forename)
            ->setParameter(":dn", $dn)
            ->setParameter(":isAdmin", $isAdmin)
            ->getResult();

        return $users;
    }

    /**
     * Changes the isAdmin user property.
     * @param \User $user           The user who's admin status is to change
     * @param \User $currentUser    The user making the change, who themselvess must be an admin
     * @param boolean $isAdmin      The new property. This must be boolean true or false.
     */
    /*public function setUserIsAdmin(\User $user, \User $currentUser = null, $isAdmin= false){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        //Throws exception if the current user is not an administrator - only admins can make admins
        $this->checkUserIsAdmin($user);

        //Check that $isAdmin is boolean
        if(!is_bool($isAdmin)){
            throw new \Exception("the setUserAdmin function takes on boolean values for isAdmin");
        }

        //Check user is not changing themselves - prevents lone admin acidentally demoting themselves
        if($user==$currentUser){
            throw new \Exception("To ensure there is always at least one administrator, you may not demote yourself, please ask another administrator to do it");
        }

        //Actually make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $user->setAdmin($isAdmin);
            $this->em->merge($user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

    }*/
}
