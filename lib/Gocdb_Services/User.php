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
     * @return \User object
     */
    public function getUser($id) {
        return $this->em->find("User", $id);
    }

    /**
     * Lookup a User object by user's principle id string.
     * @param string $userPrinciple the user's principle id string, e.g. DN.
     * @return \User object or null if no user can be found with the specified principle
     */
    public function getUserByCertificateDn($userPrinciple) {
        if (empty($userPrinciple)) {
            return null;
        }
        $dql = "SELECT u from User u WHERE u.certificateDn = :certDn";
        $user = $this->em->createQuery($dql)
                  ->setParameter(":certDn", $userPrinciple)
                  ->getOneOrNullResult();
        return $user;
    }

    /**
     * Lookup a User object by user's principle ID string from UserIdentifier.
     * @param string $userPrinciple the user's principle ID string, e.g. DN.
     * @return User object or null if no user can be found with the specified principle
     */
    public function getUserByPrinciple($userPrinciple) {
        if (empty($userPrinciple)) {
            return null;
        }

        $dql = "SELECT u FROM User u JOIN u.userIdentifiers up WHERE up.keyValue = :keyValue";
        $user = $this->em->createQuery($dql)
                  ->setParameters(array('keyValue' => $userPrinciple))
                  ->getOneOrNullResult();

        return $user;
    }

    /**
     * Lookup a User object by user's principle ID string and auth type from UserIdentifier.
     * @param string $userPrinciple the user's principle ID string, e.g. DN.
     * @param string $authType the authorisation type e.g. X.509.
     * @return User object or null if no user can be found with the specified principle
     */
    public function getUserByPrincipleAndType($userPrinciple, $authType) {
        if (empty($userPrinciple) || empty($authType)) {
            return null;
        }

        $dql = "SELECT u FROM User u JOIN u.userIdentifiers up
                WHERE up.keyName = :keyName
                AND up.keyValue = :keyValue";
        $user = $this->em->createQuery($dql)
                  ->setParameters(array('keyName' => $authType, 'keyValue' => $userPrinciple))
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
        $this->validate($newValues, 'user');

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();

        try {
            // Set the user's member variables
            $user->setTitle($newValues['TITLE']);
            $user->setForename($newValues['FORENAME']);
            $user->setSurname($newValues['SURNAME']);
            $user->setEmail($newValues['EMAIL']);
            $user->setTelephone($newValues['TELEPHONE']);
            if (key_exists("UNLINK_HOMESITE", $newValues)) {
                $user->setHomeSiteDoJoin(null);
            }
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
    private function validate($userData, $type) {
        require_once __DIR__ .'/Validate.php';
        $serv = new \org\gocdb\services\Validate();
        foreach($userData as $field => $value) {
            $valid = $serv->validate($type, $field, $value);
            if (!$valid) {
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
     * )
     * Array
     * (
     *     [NAME] => X.509
     *     [VALUE] => /C=UK/O=eScience/OU=CLRC/L=RAL/CN=a person
     * )
     * @param array $userValues User details, defined above
     * @param array $userIdentifierValues User Identifier details, defined above
     */
    public function register($userValues, $userIdentifierValues) {
        // validate the input fields for the user
        $this->validate($userValues, 'user');

        //Explicity demarcate our tx boundary
        $this->em->getConnection()->beginTransaction();
        $user = new \User();
        $identifierArr = array($userIdentifierValues['NAME'], $userIdentifierValues['VALUE']);
        try {
            $user->setTitle($userValues['TITLE']);
            $user->setForename($userValues['FORENAME']);
            $user->setSurname($userValues['SURNAME']);
            $user->setEmail($userValues['EMAIL']);
            $user->setTelephone($userValues['TELEPHONE']);
            $user->setAdmin(false);
            $this->em->persist($user);
            $this->em->flush();
            $this->addUserIdentifier($user, $identifierArr, $user);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        return $user;
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
     * @param string $idString ID string of user to be returned. If specified only one user will be returned. Matched case sensitivly
     * @param mixed $isAdmin if null then admin status is ignored, if true only admin users are returned and if false only non admins
     * @return array An array of site objects
     */
    public function getUsers($surname=null, $forename=null, $idString=null, $isAdmin=null) {

        $dql =
            "SELECT u FROM User u LEFT JOIN u.userIdentifiers up
             WHERE (UPPER(u.surname) LIKE UPPER(:surname) OR :surname is null)
             AND (UPPER(u.forename) LIKE UPPER(:forename) OR :forename is null)
             AND (u.certificateDn LIKE :idString OR up.keyValue LIKE :idString OR :idString is null)
             AND (u.isAdmin = :isAdmin OR :isAdmin is null)
             ORDER BY u.surname";

        $users = $this->em
            ->createQuery($dql)
            ->setParameter(":surname", $surname)
            ->setParameter(":forename", $forename)
            ->setParameter(":idString", $idString)
            ->setParameter(":isAdmin", $isAdmin)
            ->getResult();

        return $users;
    }

    /**
     * Returns a single user identifier from its ID
     * @param $id ID of user identifier
     * @return \UserIdentifier
     */
    public function getIdentifierById($id) {
        $dql = "SELECT p FROM UserIdentifier p WHERE p.id = :ID";
        $identifier = $this->em->createQuery($dql)->setParameter('ID', $id)->getOneOrNullResult();
        return $identifier;
    }

    /**
     * Returns a single user identifier from its ID string
     * @param $idString ID string of user identifier
     * @return \UserIdentifier
     */
    public function getIdentifierByIdString($idString) {
        $dql = "SELECT p FROM UserIdentifier p WHERE p.keyValue = :IDSTRING";
        $identifier = $this->em->createQuery($dql)->setParameter('IDSTRING', $idString)->getOneOrNullResult();
        return $identifier;
    }

    /**
     * Returns list of authentication types
     * List composed of AuthenticationRealms defined within tokens
     * Order of tokens determined by order listed in MyConfig1
     * Order of realms hardcoded based on order within tokens
     * @param bool $reducedRealms if true only return the "main" authentication types
     * @return array of authentication types
     */
    public function getAuthTypes($reducedRealms=true) {

        require_once __DIR__ . '/../Authentication/_autoload.php';
        // Get list of tokens in order they are currently used
        $myConfig1 = new \org\gocdb\security\authentication\MyConfig1();
        $authTokenNames = $myConfig1->getAuthTokenClassList();

        // Hardcoded authentication realms in same order as in token definitions
        $x509Realms = ['X.509'];
        if ($reducedRealms) {
            $shibRealms = ['EGI Proxy IdP'];
        } else {
            $shibRealms = ['EUDAT_SSO_IDP', 'UK_ACCESS_FED', 'EGI Proxy IdP'];
        }
        $irisRealms = ['IRIS IAM - OIDC'];

        // Add auth types to a list in the correct order
        $authTypes = array();
        foreach ($authTokenNames as $authTokenName) {
            if (strpos($authTokenName, 'Shib') !== false) {
                $authTypes = array_merge($authTypes, $shibRealms);
            }
            if (strpos($authTokenName, 'X509') !== false) {
                $authTypes = array_merge($authTypes, $x509Realms);
            }
            if (strpos($authTokenName, 'IAM') !== false) {
                $authTypes = array_merge($authTypes, $irisRealms);
            }
        }
        return $authTypes;
    }

    /**
     * Get one of the user's unique ID strings, favouring certain types
     * If user does not have user identifiers, returns certificateDn
     * @param \User $user User whose ID string we want
     * @return string
     */
    public function getDefaultIdString($user) {

        $authTypes = $this->getAuthTypes();
        $idString = null;

        // For each ordered auth type, check if an identifier matches
        // Gets certifcateDn if no user identifiers and X.509 listed
        foreach ($authTypes as $authType) {
            $idString = $this->getIdStringByAuthType($user, $authType);
            if ($idString !== null) {
                break;
            }
        }

        // If user only has unlisted identifiers, return first identifier
        if ($idString === null) {
            $idString = $user->getUserIdentifiers()[0]->getKeyValue();
        }

        return $idString;
    }

    /**
     * Get a user's ID string of specified authentication type
     * If user does not have user identifiers, returns certificateDn for X.509
     * @param \User $user User whose ID string we want
     * @param $authType authentication type of ID string we want
     * @return string
     */
    public function getIdStringByAuthType($user, $authType) {

        $identifiers = $user->getUserIdentifiers();
        $idString = null;

        // For each auth type, check if an identifier matches
        foreach ($identifiers as $identifier) {
            if ($identifier->getKeyName() === $authType) {
                $idString = $identifier->getKeyValue();
            }
        }

        // If no user identifiers and want X.509, return certificateDn
        if (count($identifiers) === 0 && $authType === 'X.509') {
            $idString = $user->getCertificateDn();
        }

        return $idString;
    }

    /**
     * Adds an identifier to a user.
     * @param \User $user user having identifier added
     * @param array $identifierArr identifier name and value
     * @param \User $currentUser user adding the identifier
     * @throws \Exception
     */
    public function addUserIdentifier(\User $user, array $identifierArr, \User $currentUser) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // Add the identifier
        $this->em->getConnection()->beginTransaction();
        try {
            $this->addUserIdentifierLogic($user, $identifierArr);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Logic to add an identifier to a user.
     * @param \User $user user having identifier added
     * @param array $identifierArr identifier name and value
     * @throws \Exception
     */
    protected function addUserIdentifierLogic(\User $user, array $identifierArr) {

        // We will use this variable to track the keys as we go along, this will be used check they are all unique later
        $keys = array();

        $existingIdentifiers = $user->getUserIdentifiers();

        // We will use this variable to track the final number of identifiers and ensure we do not exceede the specified limit
        $identifierCount = count($existingIdentifiers);

        // Trim off any trailing and leading whitespace
        $keyName = trim($identifierArr[0]);
        $keyValue = trim($identifierArr[1]);

        $this->addUserIdentifierValidation($keyName, $keyValue);

        /* Find out if an identifier with the provided key already exists for this user
        * If it does, we will throw an exception
        */
        $identifier = null;
        foreach ($existingIdentifiers as $existIdentifier) {
            if ($existIdentifier->getKeyName() === $keyName) {
                $identifier = $existIdentifier;
            }
        }

        /* If the identifier does not already exist, we add it
        * If it already exists, we throw an exception
        */
        if (is_null($identifier)) {
            $identifier = new \UserIdentifier();
            $identifier->setKeyName($keyName);
            $identifier->setKeyValue($keyValue);
            $user->addUserIdentifierDoJoin($identifier);
            $this->em->persist($identifier);

            // Increment the identifier counter to enable check against extension limit
            $identifierCount++;
        } else {
            throw new \Exception("An identifier with name \"$keyName\" already exists for this object, no identifiers were added.");
        }

        // Add the key to the keys array, to enable unique check
        $keys[] = $keyName;

        // Keys should be unique, create an exception if they are not
        if (count(array_unique($keys)) !== count($keys)) {
            throw new \Exception(
                "Identifier names should be unique. The requested new identifiers include multiple identifiers with the same name."
            );
        }

        // Check to see if adding the new identifiers will exceed the max limit defined in local_info.xml, and throw an exception if so
        $extensionLimit = \Factory::getConfigService()->getExtensionsLimit();
        if ($identifierCount > $extensionLimit) {
            throw new \Exception("Identifier could not be added due to the extension limit of $extensionLimit");
        }
    }

    /**
     * Migrates a user's identifier from certificateDn to UserIdentifiers.
     * certificateDn is overwritten with a placeholder, before the user's
     * ID string and its auth type are added as an identifier
     * @param \User $user user having first identifier added
     * @param array $identifierArr identifier name and value
     * @param \User $currentUser user adding the identifier
     * @throws \Exception
     */
    public function migrateUserCredentials(\User $user, array $identifierArr, \User $currentUser) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // Check the user identifier being added corresponds to the current certificateDn
        $idString = trim($identifierArr[1]);
        if ($idString !== $user->getCertificateDn()) {
            throw new \Exception("ID string must match the current certificateDn");
        }

        // Overwrite certificateDn and add the identifier
        $this->em->getConnection()->beginTransaction();
        try {
            $this->setDefaultCertDn($user);
            $this->addUserIdentifierLogic($user, $identifierArr);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Overwrites a user's certificateDn to a default value
     * Currently set to null
     * @param \User $user user having certificate DN overwritten
     * @throws \Exception
     */
    private function setDefaultCertDn(\User $user) {
        $user->setCertificateDn(null);
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Validation when adding a user identifier
     * @param string $keyName
     * @param string $keyValue
     * @throws \Exception
     */
    protected function addUserIdentifierValidation($keyName, $keyValue) {
        // Validate against schema
        $validateArray['NAME'] = $keyName;
        $validateArray['VALUE'] = $keyValue;
        $this->validate($validateArray, 'useridentifier');

        // Check the ID string does not already exist
        $this->valdidateUniqueIdString($keyValue);

        // Check auth type is valid
        $this->valdidateAuthType($keyName);
    }

    /**
     * Edit a user's identifier.
     * @param \User $user user that owns the identifier
     * @param \UserIdentifier $identifier identifier being edited
     * @param array $newIdentifierArr new key and/or value for the identifier
     * @param \User $currentUser user editing the identifier
     * @throws \Exception
     */
    public function editUserIdentifier(\User $user, \UserIdentifier $identifier, array $newIdentifierArr, \User $currentUser) {
        // Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($currentUser);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->editUserIdentifierLogic($user, $identifier, $newIdentifierArr);
            $this->em->flush ();
            $this->em->getConnection ()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection ()->rollback();
            $this->em->close ();
            throw $e;
        }
    }

    /**
     * Logic to edit a user's identifier, without the user validation.
     * Validation of the edited identifier values is performed by a seperate function.
     * @param \User $user user that owns the identifier
     * @param \UserIdentifier $identifier identifier being edited
     * @param array $newIdentifierArr new key and/or value for the identifier
     * @throws \Exception
     */
    protected function editUserIdentifierLogic(\User $user, \UserIdentifier $identifier, array $newIdentifierArr) {

        // Trim off trailing and leading whitespace
        $keyName = trim($newIdentifierArr[0]);
        $keyValue = trim($newIdentifierArr[1]);

        // Validate new identifier
        $this->editUserIdentifierValidation($user, $identifier, $keyName, $keyValue);

        // Set the user identifier values
        $identifier->setKeyName($keyName);
        $identifier->setKeyValue($keyValue);
        $this->em->merge($identifier);
    }

    /**
     * Validation when editing a user's identifier
     * @param \User $user
     * @param \UserIdentifier $identifier
     * @param string $keyName
     * @param string $keyValue
     * @throws \Exception
     */
    protected function editUserIdentifierValidation(\User $user, \UserIdentifier $identifier, $keyName, $keyValue) {

        // Validate new values against schema
        $validateArray['NAME'] = $keyName;
        $validateArray['VALUE'] = $keyValue;
        $this->validate($validateArray, 'useridentifier');

        // Check that the identifier is owned by the user
        if ($identifier->getParentUser() !== $user) {
            $id = $identifier->getId();
            throw new \Exception("Identifier {$id} does not belong to the specified user");
        }

        // Check the identifier has changed
        if ($keyName === $identifier->getKeyName() && $keyValue === $identifier->getKeyValue()) {
            throw new \Exception("The specified user identifier is the same as the current user identifier");
        }

        // Check the ID string is unique if it is being changed
        if ($keyValue !== $identifier->getKeyValue()) {
            $this->valdidateUniqueIdString($keyValue);
        }

        // Check auth type is valid
        $this->valdidateAuthType($keyName);

        // If the identifiers key has changed, check there isn't an existing identifier with that key
        if ($keyName !== $identifier->getKeyName()) {
            $existingIdentifiers = $user->getUserIdentifiers();
            foreach ($existingIdentifiers as $existingIdentifier) {
                if ($existingIdentifier->getKeyName() === $keyName) {
                    throw new \Exception("An identifier with that name already exists for this object");
                }
            }
        }
    }

    /**
     * Validate authentication type based on known list.
     * @param string $authType
     * @throws \Exception
     */
    protected function valdidateAuthType($authType) {
        if (!in_array($authType, $this->getAuthTypes(false))) {
            throw new \Exception("The authentication type entered is invalid");
        }
    }

    /**
     * Validate ID string is unique.
     * Checks both user identifiers and certificateDns
     * @param string $idString
     * @throws \Exception
     */
    protected function valdidateUniqueIdString($idString) {
        $oldUser = $this->getUserByCertificateDn($idString);
        $newUser = $this->getUserByPrinciple($idString);
        if (!is_null($oldUser) || !is_null($newUser)) {
            throw new \Exception("ID string is already registered in GOCDB");
        }
    }

    /**
     * Delete a user identifier
     * Validates the user has permission, then calls the required logic
     * @param \User $user user having the identifier deleted
     * @param \UserIdentifier $identifier identifier being deleted
     * @param \User $currentUser user deleting the identifier
     */
    public function deleteUserIdentifier(\User $user, \UserIdentifier $identifier, \User $currentUser) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        // Check to see whether the current user can edit this user
        $this->editUserAuthorization($user, $currentUser);

        // Make the change
        $this->em->getConnection()->beginTransaction();
        try {
            $this->deleteUserIdentifierLogic($user, $identifier);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Logic to delete a user's identifier
     * Before deletion a check is done to confirm the identifier is from the parent user
     * specified by the request, and an exception is thrown if this is not the case
     * @param \User $user user having the identifier deleted
     * @param \UserIdentifier $identifier identifier being deleted
     */
    protected function deleteUserIdentifierLogic(\User $user, \UserIdentifier $identifier) {
        // Check that the identifier's parent user is the same as the one given
        if ($identifier->getParentUser() !== $user) {
            $id = $identifier->getId();
            throw new \Exception("Identifier {$id} does not belong to the specified user");
        }
        // Check the user has more than one identifier
        if (count($user->getUserIdentifiers()) < 2) {
            throw new \Exception("Users must have at least one identity string.");
        }
        // User is the owning side so remove elements from the user
        $user->getUserIdentifiers()->removeElement($identifier);

        // Once relationship is removed, delete the actual element
        $this->em->remove($identifier);
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
