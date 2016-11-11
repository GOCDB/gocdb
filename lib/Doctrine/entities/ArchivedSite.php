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
 * Keeps a record of deleted Sites and selected information about the deletion
 * and deleted {@see Site}. This is a standalone table that has no relationships,
 * therefore these records will persist even when the Site is deleted.
 *
 * @author George Ryall
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @Entity @Table(name="ArchivedSites")
 */
class ArchivedSite {

    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /*
     * Note, we record various site attributes and information about the Site's
     * relations as simple types (e.g. recording the country value as a string).
     *
     * We can't link ArchivedSite to 'live' entities (e.g. linking AchivedSite to Country)
     * because we need to record a snapshot of historical information at the
     * point of deletion (while the live data is subject to change/deletion).
     *
     * For example, we don't want to link ArchivedSite to the 'Country' entity
     * because the Country entity may be deleted in the future.
     *
     * This is why we record the name name of the parent NGI rather than its id.
     * Similary, we record the user's DN rather than linking to the User object as
     * that User may be deleted in future.
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

    /**
     * @Column(type="datetime", nullable=false)
     */
    protected $deletedDate;

    /**
     * Name of Site
     * @Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * Scopes applied to the NGI at the time it was deleted
     * @Column(type="string", nullable=true)
     */
    protected $scopes = null;

    /**
     * Certification status value of the site at the time it was deleted
     * @Column(type="string", nullable=true)
     */
    protected $CertStatus = null;

    /**
     * Version 4 'Primary Key'
     * @Column(type="string", nullable=true)
     */
    protected $V4PrimaryKey = null;

    /**
     * Country on deletion
     * @Column(type="string", nullable=true)
     */
    protected $Country = null;

    /**
     * Name of parent NGI
     * @Column(type="string", nullable=true)
     */
    protected $parentNgi = null;

    /**
     * Infrastructure value (i.e. Production status value) at point of deletion
     * @Column(type="string", nullable=true)
     */
    protected $Infrastructure = null;

    /**
     * @Column(type="datetime", nullable=false)
     */
    protected $originalCreationDate;

    public function __construct() {
        $this->deletedDate =  new \DateTime(null, new \DateTimeZone('UTC'));
    }

    /**
     * @return int The PK of this entity or null if not persisted.
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
     * The Date Time the site was deleted.
     * @return \DateTime
     */
    public function getDeletedDate() {
        return $this->deletedDate;
    }

    /**
     * The name of the site.
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
     * The Certification status of the site when it was deleted, e.g. 'Uncertified'
     * @return string or null
     */
    public function getCertStatus() {
        return $this->CertStatus;
    }

    /**
     * The legacy v4 PK string of the deleted site.
     * @return string or null
     */
    public function getV4PrimaryKey() {
        return $this->V4PrimaryKey;
    }

    /**
     * The country code for the deleted site.
     * @return string or null
     */
    public function getCountry() {
        return $this->Country;
    }

    /**
     * The name of the owning parent NGI.
     * @return string or null
     */
    public function getParentNgi() {
        return $this->parentNgi;
    }

    /**
     * The date time when site was originally created.
     * @return \DateTime
     */
    public function getOriginalCreationDate() {
        return $this->originalCreationDate;
    }

    /**
     * The infrastructure of the Site.
     * @return string or null
     */
    public function getInfrastructure() {
        return $this->Infrastructure;
    }

    /**
     * The DN/ID of the user who deleted the site. Required.
     * @param string $deletedBy
     */
    public function setDeletedBy($deletedBy) {
        $this->deletedBy = $deletedBy;
    }

    /**
     * The name of the deleted site. Required.
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Scopes applied to the site at the time it was deleted.
     * @param string $scopesOnDeletion
     */
    public function setScopes($scopesOnDeletion) {
        $this->scopes = $scopesOnDeletion;
    }

    /**
     * The certification status of the site when it was deleted, e.g. 'Uncertified'.
     * @param string $CertStatus
     */
    public function setCertStatus($CertStatus) {
        $this->CertStatus = $CertStatus;
    }

    /**
     * The legacy v4 GOCDB PK string.
     * @param string $V4PrimaryKey
     */
    public function setV4PrimaryKey($V4PrimaryKey) {
        $this->V4PrimaryKey = $V4PrimaryKey;
    }

    /**
     * The country of the deleted site.
     * @param string $Country
     */
    public function setCountry($Country) {
        $this->Country = $Country;
    }

    /**
     * The name of the parent NGI.
     * @param string $parentNgi
     */
    public function setParentNgi($parentNgi) {
        $this->parentNgi = $parentNgi;
    }

    /**
     * The DateTime when the site was originally created. Required.
     * @param \DateTime $originalCreationDate
     */
    public function setOriginalCreationDate($originalCreationDate) {
        $this->originalCreationDate = $originalCreationDate;
    }

    /**
     * The infrastructure name of the deleted site.
     * @param string $Infrastructure
     */
    public function setInfrastructure($Infrastructure) {
        $this->Infrastructure = $Infrastructure;
    }
}