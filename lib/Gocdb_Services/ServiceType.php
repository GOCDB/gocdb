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
include_once __DIR__ . '/AbstractEntityService.php';

/**
 * GOCDB Stateless service facade (business routnes) for service type objects.
 * The public API methods are transactional.
 *
 * @author George Ryall
 * @author John Casson
 * @author David Meredith
 */
class ServiceType extends AbstractEntityService{

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
     * Gets all ServiceTypes
     * @return array An array of service type objects
     */
    public function getServiceTypes() {
        $dql = "SELECT s from ServiceType s
                ORDER BY s.name";
        $query = $this->em->createQuery($dql);
        return $query->getResult();
    }

     /**
     * Finds a single service type by ID and returns its entity
     * @param int $id the service type ID
     * @return ServiceType a service type object
     */
    public function getServiceType($id) {
        $dql = "SELECT s FROM ServiceType s
                WHERE s.id = :id";

        $serviceType = $this->em
            ->createQuery($dql)
            ->setParameter('id', $id)
            ->getSingleResult();

        return $serviceType;
    }

    /**
     * Finds and returns all services which have the service type with
     * the input id.
     * @param int $id the service type id
     */
    public function getServices($id) {
        $dql = "SELECT se FROM Service se
                JOIN se.serviceType st
                WHERE st.id = :id";
        $services =  $this->em->createQuery($dql)
                            ->setParameter('id', $id)
                            ->getResult();
        return $services;
    }

        /**
     * Deletes a downtime
     * @param \Downtime $dt
     * @param \User $user
     */
    public function deleteServiceType(\ServiceType $serviceType, \User $user = null) {
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        //Start a transaction
        $this->em->getConnection()->beginTransaction();
        try {
            //Check there are no services with this service type
            if (sizeof($this->getServices($serviceType->getId()))!=0){
                 throw new \Exception("This Service Type is in use");
            }
            //remove the service type
            $this->em->remove($serviceType);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     *
     * @param array $newValues array containing the name and description for the
     *                        new service type
     * @param \user $user   User adding the service type, used for permissions check
     * @return \org\gocdb\services\ServiceType returns created service type
     */
    public function addServiceType($values, \user $user = null){
        //Check the portal is not in read only mode, throws exception if it is
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($user);

        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        //Check the values are actually there, then validate the values as per the GOCDB schema
        $this->validate($values);

        //check the name is unique
        if(!$this->serviceTypeNameIsUnique($values['Name'])){
            throw new \Exception("Service type names must be unique, '".$values['Name']."' is already in use");
        }


        //Start transaction
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //new service type object
            $serviceType = new \ServiceType();
            //set name
            $serviceType->setName($values['Name']);
            //set description
            $serviceType->setDescription($values['Description']);
            //set flag for monitoring exception allowed
            $serviceType->setAllowMonitoringException($values['AllowMonitoringException']);

            $this->em->persist($serviceType);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $serviceType;
    }

    /**
     * Edit a service type
     * @param \ServiceType $serviceType service type to be altered
     * @param array $newValues new values to be applied to the service type
     * @param \User $user   user making the changes
     * @return \ServiceType the altered service type
     * @throws \Exception
     */
    public function editServiceType(\ServiceType $serviceType, $newValues, \User $user = null){
        //Throws exception if user is not an administrator
        $this->checkUserIsAdmin($user);

        //Validate the values as per the GOCDB schema and check values are present and valid.
        $this->validate($newValues);

        //check the name is unique, if it has changed
        if($newValues['Name']!=$serviceType->getName()){
            if(!$this->serviceTypeNameIsUnique($newValues['Name'])){
                throw new \Exception("Service type names must be unique, '".$newValues['Name']."' is already in use");
            }
        }


        //Start transaction
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            //set name
            $serviceType->setName($newValues['Name']);
            //set description
            $serviceType->setDescription($newValues['Description']);
            //flag for monitoring exception allowed
            $serviceType->setAllowMonitoringException($newValues['AllowMonitoringException']);

            $this->em->merge($serviceType);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $serviceType;
    }




    /**
     * Returns true if the name given is not currently in use for a service type
     * @param type $name potential service type name
     * @return boolean
     */
    public function serviceTypeNameIsUnique($name){
        $dql = "SELECT s from ServiceType s
                WHERE s.name = :name";
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
     * Performs some basic checks on the values aray and then validates the user
     * inputted service type data against the data in the gocdb_schema.xml.
     * @param array $serviceTypeData containing all the fields for a GOCDB
     *                               service type object
     * @throws \Exception If the project's data can't be
     *                    validated. The \Exception message will contain a human
     *                    readable description of which field failed validation.
     * @return null */
    private function validate($serviceTypeData) {
        require_once __DIR__.'/Validate.php';

        //check values are there
        if(!((array_key_exists('Name',$serviceTypeData)) and (array_key_exists('Description',$serviceTypeData)))){
            throw new \Exception("A name and description for the service type must be specified");
        }

        //check values are strings
        if(!((is_string($serviceTypeData['Name'])) and (is_string($serviceTypeData['Description'])))){
            throw new \Exception("The new service type name and description must be valid strings");
        }

        //check that the name is not null
        if(empty($serviceTypeData['Name'])){
            throw new \Exception("A name must be specified for the Service Type");
        }

        //check that the description is not null
        if(empty($serviceTypeData['Description'])){
            throw new \Exception("A description must be specified for the Service Type");
        }


        //remove the ID from the values file if present (which it may be for an edit)
        if(array_key_exists("ID",$serviceTypeData)){
            unset($serviceTypeData["ID"]);
        }

        $serv = new \org\gocdb\services\Validate();
        foreach ($serviceTypeData as $field => $value) {
            $valid = $serv->validate('service_type', strtoupper($field), $value);
            if(!$valid) {
                $error = "$field contains an invalid value: $value";
                throw new \Exception($error);
            }
        }
    }
}


