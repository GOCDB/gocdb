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
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Defines a registered GOCDB User who may request {@see Role}s over {@see OwnedEntity} objects.
 *
 * @author John Casson
 * @author David Meredith <david.meredith@stfc.ac.uk>
 *
 * @Entity @Table(name="Users", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class User {

    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /** @Column(type="string")  */
    protected $forename;

    /** @Column(type="string")  */
    protected $surname;

    /** @Column(type="string", nullable=true)  */
    protected $title = null;

    /** @Column(type="string", nullable=true)  */
    protected $email = null;

    /** @Column(type="string", nullable=true)  */
    protected $telephone = null;

    /** @Column(type="string", nullable=true)  */
    protected $workingHoursStart = null;

    /** @Column(type="string", nullable=true)  */
    protected $workingHoursEnd = null;

    /** @Column(type="string", nullable=true)  */
    protected $certificateDn = null;

    /** @Column(type="string", nullable=true)  */
    protected $username1 = null;

    /** @Column(type="boolean") */
    protected $isAdmin;

    /** @OneToMany(targetEntity="Role", mappedBy="user")  */
    protected $roles = null;

    /**
     * @ManyToOne(targetEntity="Site", inversedBy="users")
     * @JoinColumn(name="homeSite_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $homeSite = null;

    /** @Column(type="datetime", nullable=false)  */
    protected $creationDate;

    /** @Column(type="datetime", nullable=true)  */
    protected $lastLoginDate;

    /**
     * Bidirectional - A User (INVERSE ORM SIDE) can have many identifiers
     * @OneToMany(targetEntity="UserIdentifier", mappedBy="parentUser", cascade={"remove"})
     */
    protected $userIdentifiers = null;

    /**
     * Bidirectional - A User can have many APIAuthenication Entities.
     * @OneToMany(targetEntity="APIAuthentication", mappedBy="user")
     */
    protected $APIAuthenticationEntities = null;

    /*
     * TODO:
     * This entity will need to own a property bag (akin to custom props)
     * to store zero-to-many additional attributes (e.g. SAML/AAA attributes).
     * For example, when a user authenticates via an IdP, a bunch of different attributes
     * can be sent in a SAML auth response which will need persisting in the
     * DB so that other users can see this data before they are approve any roles
     * for this user.
     */


    public function __construct() {
        // Set cretion date
        $this->creationDate = new \DateTime("now");
        //$this->sites = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->userIdentifiers = new ArrayCollection();
        $this->APIAuthenticationEntities = new ArrayCollection();
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string The user's first name.
     */
    public function getForename() {
        return $this->forename;
    }

    /**
     * @return string The user's second name.
     */
    public function getSurname() {
        return $this->surname;
    }

    /**
     * @return string User's optional title or null.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return string User's contact email address or null.
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @return string User's contact tel or null.
     */
    public function getTelephone() {
        return $this->telephone;
    }

    /**
     * Nullable string for the user's working hours.
     * @deprecated since version 5.4
     * @return string
     */
    public function getWorkingHoursStart() {
        return $this->workingHoursStart;
    }

    /**
     * Nullable string for the user's working hours.
     * @deprecated since version 5.4
     * @return string
     */
    public function getWorkingHoursEnd() {
        return $this->workingHoursEnd;
    }

    /**
     * Get the user's unique ID string, typically an X.509 DN string.
     * This should return null once the user has user identifiers.
     * @return string
     */
    public function getCertificateDn() {
        return $this->certificateDn;
    }

    /**
     * An optional field to define an extra username.
     * This field was added to store the EGI SSO username, but other values
     * could also be applied.
     * @return string or null
     */
    public function getUsername1() {
        return $this->username1;
    }

    /**
     * Get the user's optional home.
     * @deprecated since version 5.4
     * @return \Site or null
     */
    public function getHomeSite() {
        return $this->homeSite;
    }

    /**
     * The DateTime when the user account was created.
     * @return \DateTime
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * The DateTime of the user's last authentication/login.
     * @return \DateTime
     */
    public function getLastLoginDate(){
        return $this->lastLoginDate;
    }

    /**
     * The User's list of {@see UserIdentifier} extension objects. When the
     * User is deleted, the userIdentifiers are also cascade deleted.
     * @return ArrayCollection
     */
    public function getUserIdentifiers() {
        return $this->userIdentifiers;
    }

    /**
     * Is the user a GOCDB admin user. Defaults to false.
     * @return boolean
     */
    public function isAdmin() {
        return $this->isAdmin;
    }

    /**
     * Set the user's first name. Required.
     * @param string $forename
     */
    public function setForename($forename) {
        $this->forename = $forename;
    }

    /**
     * Set the user's first name. Required.
     * @param string $surname
     */
    public function setSurname($surname) {
        $this->surname = $surname;
    }

    /**
     * Set the user's optional title.
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Set the user's optional contact email address.
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * Set the user's optional contact tel.
     * @param string $telephone
     */
    public function setTelephone($telephone) {
        $this->telephone = $telephone;
    }

    /**
     * @deprecated since version 5.4
     * @param string $workingHoursStart
     */
    public function setWorkingHoursStart($workingHoursStart) {
        $this->workingHoursStart = $workingHoursStart;
    }

    /**
     * @deprecated since version 5.4
     * @param string $workingHoursEnd
     */
    public function setWorkingHoursEnd($workingHoursEnd) {
        $this->workingHoursEnd = $workingHoursEnd;
    }

    /**
     * Set the user's unique ID string, typically an X.509 DN string.
     * This should only be used to set the value to null
     * when user identifiers are first added to an old user.
     * @param string $certificateDn
     */
    public function setCertificateDn($certificateDn) {
        $this->certificateDn = $certificateDn;
    }

    /**
     * Set an optional/additional name  to define an extra username.
     * This field was added to store the EGI SSO username, but other values
     * could also be applied.
     * @param string $username1
     */
    public function setUsername1($username1) {
        $this->username1 = $username1;
    }

    /**
     * Set the user's home Site, Legacy and optional.
     * @deprecated since version 5.4
     * @param mixed $homeSite
     */
    public function setHomeSiteDoJoin($homeSite) {

        // Allow homesite to be unset. No joining is done since 5.4.
        // In production $homesite should never be a Site instance, but
        //  legacy test datasets still invoke this function.
        if ($homeSite != null && !$homeSite instanceof \Site) {
            throw new LogicException("Expected null for deprecated Home Site value.");
        }

        $this->homeSite = $homeSite;
    }

    /**
     * Set this user as a GOCDB admin user. Defaults is false.
     * @param boolean $isAdmin
     */
    public function setAdmin($isAdmin) {
        $this->isAdmin = $isAdmin;
    }

    /**
     * Set the Date time when this user was registered. Required.
     * @param \DateTime $creationDate
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }

    /**
     * Set the date time of the user's last authentication/login
     * @param \DateTime $lastLoginDate
     */
    public function setLastLoginDate($lastLoginDate){
        $this->lastLoginDate = $lastLoginDate;
    }

    /**
     * @return string Concat of 'forename surname'
     */
    public function getFullName() {
        return $this->forename . " " . $this->surname;
    }

    /**
     * Add the given Role to the user's list of roles.
     * Note, this method calls the <code>$role->setUser($this);</code> to
     * establish the join on both sides of the relationship.
     * @param \Role $role
     */
    public function addRoleDoJoin(\Role $role) {
        $this->roles[] = $role;
        $role->setUser($this);
    }

    /**
     * Get all the users {@see Role}s.
     * @return ArrayCollection
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * Add a UserIdentifier entity to this User's collection of identifiers.
     * This method also sets the UserIdentifier's parentUser.
     * @param \UserIdentifier $userIdentifier
     */
    public function addUserIdentifierDoJoin($userIdentifier) {
        $this->userIdentifiers[] = $userIdentifier;
        $userIdentifier->_setParentUser($this);
    }

    /**
     * Keep track of the authentication entities associated with this user,
     * including updating the entity itself as the owning side of the
     * relation.
     * @param \APIAuthentication $authenticationEntity
     */
    public function addAPIAuthenticationEntitiesDoJoin (\APIAuthentication $authenticationEntity) {
        $this->APIAuthenticationEntities->add($authenticationEntity);
        //$this->APIAuthenticationEntities[] = $authenticationEntity;
        $authenticationEntity->_setUser($this);
    }
    /**
     * Get the user's API credentials
     * @return ArrayCollection
     */
    public function getAPIAuthenticationEntities () {
        return $this->APIAuthenticationEntities;
    }
}
