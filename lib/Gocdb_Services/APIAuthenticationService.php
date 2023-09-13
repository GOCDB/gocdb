<?php

/**
 * GOCDB Stateless service facade (business routines) for group objects.
 * The public API methods are transactional.
 *
 * @author Ian Neilson after originals -
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */

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

namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__ . '/Validate.php';
require_once __DIR__ .  '/../Doctrine/entities/APIAuthentication.php';

use Doctrine\ORM\QueryBuilder;
use org\gocdb\services\Validate;

class APIAuthenticationService extends AbstractEntityService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the APIAuthentication entity associated with the given identifier.
     *
     * @param string $ident Identifier (e.g. X.509 DN as string)
     * @return \APIAuthentication[] APIAuthentication associated with this identifier
     */
    public function getAPIAuthentication($ident)
    {

        if (!is_string($ident)) {
            throw new \LogicException("Expected string APIAuthentication identifier.");
        }

        $dql = "SELECT a FROM APIAuthentication a " .
                "WHERE (a.identifier = :ident)" ;

        /* @var $qry \Doctine\DBAL\query */
        $qry = $this->em->createQuery($dql);
        $qry->setParameter('ident', $ident);

        $apiAuths = $qry->getResult();

        return $apiAuths;
    }

        /**
     * Update the fields of an APIAuthentication entity and commit the resulting entity
     *
     * @param \Site Parent site
     * @param \User Owning user
     * @param array Array containing new values
     * @throws \Exception on error with commit rolled back
     * @return \APIAuthentication
     */
    public function addAPIAuthentication(\Site $site, \User $user, $newValues)
    {

        $identifier = $newValues['IDENTIFIER'];
        $type = $newValues['TYPE'];
        $allowWrite = $newValues['ALLOW_WRITE'];

        //Check that an identifier has been provided
        if (empty($identifier)) {
            throw new \Exception("A value must be provided for the identifier");
        }

        //validate the values against the schema
        $this->validate($newValues, $identifier, $type);

        //Check there isn't already a credential with that identifier for that Site
        $this->uniqueAPIAuthEnt($site, $identifier);

        //Add the properties
        $this->em->getConnection()->beginTransaction();
        try {
            $authEnt = new \APIAuthentication();
            $authEnt->setIdentifier($identifier);
            $authEnt->setAllowAPIWrite($allowWrite);
            $authEnt->setType($type);

            $site->addAPIAuthenticationEntitiesDoJoin($authEnt);
            $user->addAPIAuthenticationEntitiesDoJoin($authEnt);

            $this->em->persist($authEnt);

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $authEnt;
    }

    /**
     * Update the fields of an APIAuthentication entity and commit the resulting entity
     *
     * @param \APIAuthentication Entity to delete
     * @throws \Exception on error with commit rolled back
     */
    public function deleteAPIAuthentication(\APIAuthentication $authEntity)
    {

        $this->em->getConnection()->beginTransaction();

        $parentSite = $authEntity->getParentSite();
        $user = $authEntity->getUser();

        try {
            //Remove the authentication entity from the site then remove the entity
            $parentSite->getAPIAuthenticationEntities()->removeElement($authEntity);
            $user->getAPIAuthenticationEntities()->removeElement($authEntity);

            $this->em->remove($authEntity);

            $this->em->persist($parentSite);
            $this->em->persist($user);

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Update the fields of an APIAuthentication entity and commit the resulting entity
     *
     * @param \APIAuthentication Entity to update
     * @param \User Owning user
     * @param mixed $newValues Holds the new data for updating the
     *                         `APIAuthentication` entity.
     *
     * @throws \Exception on error with commit rolled back
     */
    public function editAPIAuthentication(\APIAuthentication $authEntity, \User $user, $newValues)
    {
        $isRenewalRequest = $newValues['isRenewalRequest'] || false;

        try {
            if ($isRenewalRequest) {
                $this->handleRenewalRequest(
                    $authEntity,
                    $user,
                    $isRenewalRequest
                );
            } else {
                $this->handleEditRequest(
                    $authEntity,
                    $user,
                    $newValues,
                    $isRenewalRequest
                );
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
    /**
     * Set the last use time field to the current UTC time
     *
     * @param \APIAuthentication[] $authEntities entity to update
     * @throws \Exception if the update fails
     */
    public function updateLastUseTime(array $authEntities)
    {
        $this->em->getConnection()->beginTransaction();

        try {
            /* @var \APIAuthentication $authEntity */
            foreach ($authEntities as $authEntity) {
                $authEntity->setLastUseTime();
                $this->em->persist($authEntity);
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
     * Fail if there is already an API credential with a given identifier
     * for a given Site.
     *
     * Note that there is an implicit assumption that an identifier value is
     * unique across all types (X.509, OIDC token etc.)
     *
     * @param \Site $site field values for an APIAuthentication object
     * @param string $identifier to check
     * @throws \Exception if the data can't be validated.
     */
    public function uniqueAPIAuthEnt(\Site $site, $identifier)
    {

        $authEntities = $this->getAPIAuthentication($identifier);

        foreach ($authEntities as $authEnt) {
            if ($authEnt->getParentSite()->getId() == $site->getId()) {
                throw new \Exception(
                    "An authentication credential with identifier " .
                    "\"$identifier\" already exists for " . $site->getName()
                );
            }
        }
    }
    /**
     * Validates the user inputted site data against the
     * checks in the gocdb_schema.xml and applies additional logic checks
     * that can't be described in the gocdb_schema.xml.
     *
     * @param array $data field values for an APIAuthentication object
     * @param mixed $type a valid
     * @throws \Exception if the data can't be validated.
     * @return null
     */
    private function validate($data, $identifier, $type)
    {

        $serv = new Validate();
        foreach ($data as $field => $value) {
            $valid = $serv->validate('APIAUTHENTICATION', $field, $value);
            if (!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
        //If the entity is of type X.509, do a more thorough check than the validate service (as we know the type)
        //Note that we are allowing ':' as they can appear in robot DN's
        if ($type == 'X.509' && !preg_match("/^(\/[A-Za-z]+=[a-zA-Z0-9\/\-\_\s\.,'@:\/]+)*$/", $identifier)) {
            throw new \Exception("Invalid X.509 DN");
        }

        //If the entity is of type OIDC subject, do a more thorough check again
        if (
            $type == 'OIDC Subject' &&
            !preg_match("/^([a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12})$/", $identifier)
        ) {
            throw new \Exception("Invalid OIDC Subject");
        }
    }

    /**
     * Helper to handle the renewal request for API authentication code flow.
     *
     * @param \APIAuthentication $authEntity Entity to update.
     * @param \User $user Owning user.
     * @param bool $isRenewalRequest A boolean indicating
     *                               if it's a renewal request.
     */
    private function handleRenewalRequest(
        \APIAuthentication $authEntity,
        \User $user,
        $isRenewalRequest
    ) {
        $this->em->getConnection()->beginTransaction();

        $this->updateLastRenewTime($authEntity, $user, $isRenewalRequest);

        $user->addAPIAuthenticationEntitiesDoJoin($authEntity);

        $this->em->persist($authEntity);
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Helper to handles the edit request for API authentication code flow.
     *
     * @param \APIAuthentication $authEntity Entity to update.
     * @param \User $user Owning user.
     * @param array $newValues An array containing data for
     *                         updating the APIAuthentication entity.
     * @param bool $isRenewalRequest A boolean indicating
     *                               if it's a renewal request.
     *
     * @throws \Exception Throws an exception if the identifier is empty.
     */
    private function handleEditRequest(
        \APIAuthentication $authEntity,
        \User $user,
        $newValues,
        $isRenewalRequest
    ) {
        $identifier = $newValues['IDENTIFIER'];
        $type = $newValues['TYPE'];
        $allowWrite = $newValues['ALLOW_WRITE'];

        // Check that an identifier has been provided
        if (empty($identifier)) {
            throw new \Exception(
                "A value must be provided for the identifier"
            );
        }

        $this->validate($newValues, $identifier, $type);
        $this->em->getConnection()->beginTransaction();

        $this->updateLastRenewTime($authEntity, $user, $isRenewalRequest);
        $this->updateAuthenticationEntity(
            $authEntity,
            $identifier,
            $type,
            $allowWrite
        );
        $user->addAPIAuthenticationEntitiesDoJoin($authEntity);

        $this->em->persist($authEntity);
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Validates whether to update the `LastRenewTime`
     * of the APIAuthentication entity or NOT.
     *
     * @param \APIAuthentication $authEntity Entity to update.
     * @param \User $user Owning user.
     * @param bool $isRenewalRequest A boolean indicating
     *                               if it's a renewal request.
     */
    private function updateLastRenewTime(
        \APIAuthentication $authEntity,
        \User $user,
        $isRenewalRequest
    ) {
        /**
         * This would probably be the place hook for any
         * future policy acceptance tracking.
         */
        if (
            ($user->getId() != $authEntity->getUser())
            || $isRenewalRequest
        ) {
            $authEntity->setLastRenewTime();
        }
    }

    /**
     * Helper to update the APIAuthentication entity with edited values.
     *
     * @param \APIAuthentication $authEntity Entity to update.
     * @param string $identifier Unique identifier for the
     *                           API authentication entity.
     * @param string $type Type for the API authentication entity.
     * @param bool $allowWrite Helps to identify write functionality
     *                         of the API is enabled or NOT.
     */
    private function updateAuthenticationEntity(
        \APIAuthentication $authEntity,
        $identifier,
        $type,
        $allowWrite
    ) {
        $authEntity->setIdentifier($identifier);
        $authEntity->setType($type);
        $authEntity->setAllowAPIWrite($allowWrite);
    }
}
