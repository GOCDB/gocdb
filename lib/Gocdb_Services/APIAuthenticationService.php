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
 * GOCDB Stateless service facade (business routines) for group objects.
 * The public API methods are transactional.
 *
 * @author Ian Neilson after originals -
 * @author John Casson
 * @author David Meredith
 * @author George Ryall
 */

require_once __DIR__ . '/AbstractEntityService.php';
require_once __DIR__.  '/../Doctrine/entities/APIAuthentication.php';

use Doctrine\ORM\QueryBuilder;

class APIAuthenticationService extends AbstractEntityService{

    function __construct() {
        parent::__construct();
    }

    /**
     * Returns the APIAuthentication entity associated with the given identifier.
     *
     * @param string $ident Identifier (e.g. X.509 DN as string)
     * @param string $type  Identifyer type (e.g. "X509")
     * @return \APIAuthentication APIAuthentication associated with this identifier
     */
    public function getAPIAuthentication($ident, $type) {

        if (!is_string($ident)) {
            throw new \LogicException("Expected string APIAuthentication identifier.");
        }

        $dql = "SELECT a FROM APIAuthentication a " .
                "WHERE (a.identifier = :ident AND a.type = :type)" ;

        $qry = $this->em->createQuery($dql);
        $qry->setParameter('ident', $ident);
        $qry->setParameter('type', $type);

        $apiAuth = $qry->getOneOrNullResult();

        return $apiAuth;
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
    public function addAPIAuthentication(\Site $site, \User $user, $newValues) {

        $identifier = $newValues['IDENTIFIER'];
        $type = $newValues['TYPE'];
        $allowWrite = $newValues['ALLOW_WRITE'];

        //Check that an identifier has been provided
        if(empty($identifier)){
            throw new \Exception("A value must be provided for the identifier");
        }

        //validate the values against the schema
        $this->validate($newValues, $identifier, $type);

        //Check there isn't already a identifier of that type with that identifier for that Site
        $this->uniqueAPIAuthEnt($site, $identifier, $type);

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
    public function deleteAPIAuthentication(\APIAuthentication $authEntity) {

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
     * @param array Array containing new values
     * @throws \Exception on error with commit rolled back
     * @return \APIAuthentication
     */
    public function editAPIAuthentication(\APIAuthentication $authEntity, \User $user, $newValues) {

        $identifier = $newValues['IDENTIFIER'];
        $type = $newValues['TYPE'];
        $allowWrite = $newValues['ALLOW_WRITE'];

        //Check that an identifier ha been provided
        if(empty($identifier)){
            throw new \Exception("A value must be provided for the identifier");
        }

        //validate the values against the schema
        $this->validate($newValues, $identifier, $type);

        //Edit the property
        $this->em->getConnection()->beginTransaction();
        try {
            // This would probably be the place hook for any future policy acceptance tracking
            if ($user->getId() != $authEntity->getUser()) {
                $authEntity->setLastRenewTime();
            }
            $authEntity->setIdentifier($identifier);
            $authEntity->setType($type);
            $authEntity->setAllowAPIWrite($allowWrite);
            $user->addAPIAuthenticationEntitiesDoJoin($authEntity);

            $this->em->persist($authEntity);
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
     * Set the last use time field to the current UTC time
     *
     * @param \APIAuthentication $authEntity entity to update
     * @throws \Exception if the update fails
     */
    public function updateLastUseTime(\APIAuthentication $authEntity) {

        $this->em->getConnection()->beginTransaction();

        try {
            $authEntity->setLastUseTime();

            $this->em->persist($authEntity);

            $this->em->flush();
            $this->em->getConnection()->commit();

        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }
    /**
     * Fail if there is already an identifier of given type and identifier
     * for a given Site.
     *
     * @param \Site $site field values for an APIAuthentication object
     * @param string $identifier to check
     * @param string $type to check
     * @throws \Exception if the data can't be validated.
     */
    public function uniqueAPIAuthEnt(\Site $site, $identifier, $type) {

        $authEnt = $this->getAPIAuthentication($identifier, $type);

        if (!is_null($authEnt) &&
                $authEnt->getParentSite()->getId() == $site->getId()) {
            throw new \Exception(
                "An authentication object of type \"$type\" and with identifier " .
                "\"$identifier\" already exists for " . $site->getName()
            );
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
    private function validate($data, $identifier, $type) {

        require_once __DIR__.'/Validate.php';
        $serv = new \org\gocdb\services\Validate();
        foreach($data as $field => $value) {
            $valid = $serv->validate('APIAUTHENTICATION', $field, $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
        //If the entity is of type X509, do a more thorough check than the validate service (as we know the type)
        //Note that we are allowing ':' as they can appear in robot DN's
        if ($type == 'X509' && !preg_match("/^(\/[A-Za-z]+=[a-zA-Z0-9\/\-\_\s\.,'@:\/]+)*$/", $identifier)) {
            throw new \Exception("Invalid x509 DN");
        }


    }
}
