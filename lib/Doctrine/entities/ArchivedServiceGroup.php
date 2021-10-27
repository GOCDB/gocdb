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

/**
 * Keeps a record of deleted {@see ServiceGroup}s and some information about the
 * deletion.  This is a standalone table that has no relationships, therefore
 * these records will persist even when the ServiceGroup is deleted.
 *
 * @author George Ryall
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @Entity @Table(name="ArchivedServiceGroups", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class ArchivedServiceGroup {

    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /*
     * Note, we define the entity attributes as simple types rather than linking
     * to related entities because we need to record a history/log, including
     * recordig information on entitites that may be deleted.
     * For exammple child services are simple strings. These record the name of
     * that entityu on the day the service group was delted. Similary, we record
     * the user's DN rather than linking to the User object as that User may be
     *  deleted in future.
     */

    /**
     * DN of deleting user
     * @Column(type="string", nullable=false) */
    protected $deletedBy;

    /* DATETIME NOTE:
     * Doctrine checks whether a date's been updated by doing a byreference comparison.
     * If you just update an existing DateTime object, Doctrine won't persist it!
     * Create a new DateTime object and reference that for it to persist during an update.
     * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
     */

    /** @Column(type="datetime", nullable=false) **/
    protected $deletedDate;

    /**
     * Name of Service group
     * @Column(type="string", nullable=false) */
    protected $name;

    /**
     * Scopes applied to the service group at the time it was deleted
     * @Column(type="string", nullable=true) */
    protected $scopes = null;

    /**
     * services in service group when it was deleted. stored as CSV string of hostName/service type
     * @Column(type="string", length=500, nullable=true) */
    protected $services = null;

    /** @Column(type="datetime", nullable=false) **/
    protected $originalCreationDate;

    public function __construct() {
        $this->deletedDate =  new \DateTime(null, new \DateTimeZone('UTC'));
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * ID/DN of deleting user.
     * @return String
     */
    public function getDeletedBy() {
        return $this->deletedBy;
    }

    /**
     * The UTC DateTime when the target ServiceGroup was deleted.
     * @return \DateTime
     */
    public function getDeletedDate() {
        return $this->deletedDate;
    }

    /**
     * Name of the deleted service group.
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Comma separated list of scope names applied to the SG at the time it was deleted.
     * @return String or null
     */
    public function getScopes() {
        return $this->scopes;
    }

    /**
     * Date time when the ServiceGroup was originally created.
     * @return \DateTime
     */
    public function getOriginalCreationDate() {
        return $this->originalCreationDate;
    }

    /**
     * Service names belonging to the ServiceGroup.
     * Stored as CSV string of 'hostName(serviceType)' values.
     * @return string or null
     */
    public function getServices() {
        return $this->services;
    }

    /**
     * The ID/DN of the user who deleted the ServiceGroup. Required.
     * @param string $deletedBy
     */
    public function setDeletedBy($deletedBy) {
        $this->deletedBy = $deletedBy;
    }

    /**
     * The name of the ServiceGroup. Required.
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Scopes applied to the service group at the time it was deleted.
     * @param string $scopesOnDeletion
     */
    public function setScopes($scopesOnDeletion) {
        $this->scopes = $scopesOnDeletion;
    }

    /**
     * The DateTime when the ServiceGroup was originally created. Required.
     * @param \DateTime $originalCreationDate
     */
    public function setOriginalCreationDate($originalCreationDate) {
        $this->originalCreationDate = $originalCreationDate;
    }

    /**
     * Names of Services in the SG.
     * Stored as CSV string of 'hostName(serviceType)' values.
     * @param string $services
     */
    public function setServices($services) {
        $this->services = $services;
    }

}