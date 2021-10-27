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
 * Keeps a record of deleted services and some information about the deletion.
 * This is a standalone table that has no relationships, therefore these records
 * will persist even when an {@see Service} is deleted.
 *
 * @author George Ryall
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @Entity @Table(name="ArchivedServices", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class ArchivedService {

    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /*
     * Note, we define the entity attributes as simple types rather than linking
     * to related entities because we need to record a history/log, including
     * recordig information on entitites that may be deleted. For example the
     * parents site is stored as a string containing the sitres name These record the
     * name of that entity on the day the service was delted. Similary, we
     * record the user's DN rather than linking to the User object as that User
     * may be deleted in future.
     */

    /**
     * DN of deleting user
     * @Column(type="string", nullable=false)
     */
    protected $deletedBy;

    /* DATETIME NOTE:
     * Doctrine checks whether a date's been updated by doing a byreference comparison.
     * If you just update an existing DateTime object, Doctrine won't persist it!
     * Create a new DateTime object and reference that for it to persist during an update.
     * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
     */

    /**
     * @Column(type="datetime", nullable=false)
     */
    protected $deletedDate;

    /**
     * Name of Service  (not unique - only servicetype/hostname will be
     * @Column(type="string", nullable=false)
     */
    protected $hostName;

    /**
     * service type of service
     * @Column(type="string", nullable=false)
     */
    protected $serviceType;

    /**
     * Scopes applied to the service group at the time it was deleted
     * @Column(type="string", nullable=true) */
    protected $scopes = null;

    /**
     * Name of parent site when it was deleted.
     * @Column(type="string", nullable=true) */
    protected $parentSite = null;

    /**
     * Was service monitored at the time it was deleted
     * @Column(type="boolean", nullable=true) */
    protected $monitored = null;

    /**
     * Was the service beta when it was deleted
     * @Column(type="boolean", nullable=true) */
    protected $beta = null;

    /**
     * Was the service production status when it was deleted
     * @Column(type="boolean", nullable=true) */
    protected $production = null;


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
     * The UTC DateTime when the target Service was deleted.
     * @return \DateTime
     */
    public function getDeletedDate() {
        return $this->deletedDate;
    }

    /**
     * Service host name.
     * @return string
     */
    public function getHostName() {
        return $this->hostName;
    }

    /**
     * Comma separated list of scope names applied to the service at the time it was deleted.
     * @return String or null
     */
    public function getScopes() {
        return $this->scopes;
    }

    /**
     * The name of the site that owned this service.
     * @return string or null
     */
    public function getParentSite() {
        return $this->parentSite;
    }

    /**
     * The DateTime when the Service was originally created.
     * @return \DateTime
     */
    public function getOriginalCreationDate() {
        return $this->originalCreationDate;
    }

    /**
     * Was this service monitored.
     * @return boolean or null
     */
    public function getMonitored() {
        return $this->monitored;
    }

    /**
     * Was this a beta service.
     * @return boolean or null
     */
    public function getBeta() {
        return $this->beta;
    }

    /**
     * Was this a production service.
     * @return boolean or null
     */
    public function getProduction() {
        return $this->production;
    }

    /**
     * Get the name of the service type.
     * @return string
     */
    public function getServiceType() {
        return $this->serviceType;
    }

    /**
     * ID/DN of deleting user. Required.
     * @param string $deletedBy
     */
    public function setDeletedBy($deletedBy) {
        $this->deletedBy = $deletedBy;
    }

    /**
     * Host name of service. Required.
     * @param string $hostName
     */
    public function setHostName($hostName) {
        $this->hostName = $hostName;
    }

    /**
     * Comma separated list of scope names which applied to the servcie before
     * it was deleted.
     * @param string $scopes
     */
    public function setScopes($scopes) {
        $this->scopes = $scopes;
    }

    /**
     * Name of owning parent site.
     * @param string $parentSite
     */
    public function setParentSite($parentSite) {
        $this->parentSite = $parentSite;
    }

    /**
     * Date time when the service was created. Required.
     * @param \DateTime $originalCreationDate
     */
    public function setOriginalCreationDate($originalCreationDate) {
        $this->originalCreationDate = $originalCreationDate;
    }

    /**
     * Was this service monitored.
     * @param boolean $monitored
     */
    public function setMonitored($monitored) {
        $this->monitored = $monitored;
    }

    /**
     * Was this service beta.
     * @param boolean $beta
     */
    public function setBeta($beta) {
        $this->beta = $beta;
    }

    /**
     * Was this service production.
     * @param boolean $production
     */
    public function setProduction($production) {
        $this->production = $production;
    }

    /**
     * Service type of service. Required.
     * @param string $serviceType
     */
    public function setServiceType($serviceType) {
        $this->serviceType = $serviceType;
    }
}