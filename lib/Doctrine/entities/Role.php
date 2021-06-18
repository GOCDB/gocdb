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
//use Doctrine\Common\Collections\ArrayCollection;

/**
 * A Role establishes that the {@see User} has the specified {@see \RoleType} over the
 * {@see \OwnedEntity} with the specified status.
 * <p>
 * A Role doubles as a Role-Request if its status is e.g. 'STATUS_PENDING'
 * and becomes an official Role if it is updated to 'STATUS_GRANTED'. Ownership of a
 * Role usually grants certain permissions over the OwnedEntity object.
 * <p>
 * A single Role has ManyToOne relationships with RoleType, User and OwnedEntity
 * (Role is on the many side).
 * <p>
 * A joined Role must be deleted before a joined User, OwnedEntity or RoleType
 * can be deleted.
 * A user can't own duplcate roles, this is enforced with a 'NoDuplicateRoles' DB
 * constraint which prevents persisting of user+role type+ownedentity combinations
 * that already exist.
 *
 * @author David Meredith <david.meredithh@stfc.ac.uk>
 * @author John Casson
 * @Entity @Table(name="Roles", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"}, uniqueConstraints={@UniqueConstraint(name="NoDuplicateRoles", columns={"user_id", "roleType_id", "ownedEntity_id"})})
 *
 */
class Role {

    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @ManyToOne(targetEntity="RoleType") **/
    protected $roleType;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="roles")
     * @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * Allows polymorphic queries - a role can be related to an OwnedEntity whose
     * type can be different, e.g. Site, NGI. The onDelete means that if the
     * OwnedEntity is deleted, then all linked Roles are also deleted.
     *
     * @ManyToOne(targetEntity="OwnedEntity", inversedBy="roles")
     * @JoinColumn(name="ownedEntity_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $ownedEntity = null;

    /**
     * Current status of this role, e.g. STATUS_GRANTED, STATUS_PENDING
     * @Column(type="string", nullable=false)
     */
    protected $status;

    ///** @Column(type="integer") **/
    //protected $lastUpdatedByUserId;

    /*
     * A transient extension object used to decorate this instance with extra information.
     * Note, this object is <b>NOT</b> persisted to the DB. It is intended for transient
     * operations such as holding extra view parameters for transer/rendering in the view layer.
     */
    protected $decoratorObject;

    /**
     * Create a new role which establishes that the specified User has the
     * RoleType over the OwnedEntity.
     *
     * @param \RoleType $roleType The role's type
     * @param string $name The role name
     * @param \User $user The role is for this user
     * @param \OwnedEntity $ownedEntity The role is over this entity
     * @param string $status The current Role status, e.g. 'STATUS_PENDING' for
     *   role requests and 'STATUS_GRANTED' for granted roles.
     */
    public function __construct(\RoleType $roleType, \User $user, \OwnedEntity $ownedEntity, $status) {
        $this->roleType = $roleType;
        $this->setStatus($status);
        //$this->setName($name);
        $ownedEntity->addRoleDoJoin($this);
        $user->addRoleDoJoin($this);
        // @link http://www.doctrine-project.org/blog/doctrine-2-give-me-my-constructor-back.html Using constructors in Entities
    }

    /**
     * @return int The PK of this entity or null if not persisted.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return \RoleType The type of this role.
     */
    public function getRoleType() {
        return $this->roleType;
    }

    /**
     * @return \User The owner of this role.
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return \OwnedEntity The object that this Role is over.
     */
    public function getOwnedEntity() {
        return $this->ownedEntity;
    }

    /**
     * @param \RoleType $roleType
     */
    public function setRoleType($roleType) {
        $this->roleType = $roleType;
    }

    /**
     * @param \User $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * A Role can have a relationship with different types of OwnedEntity (e.g.
     * Site, NGI and other OwnedEntity sub-classes).
     *
     * @param \OwnedEntity $owned The associated entity
     */
    public function setOwnedEntity(\OwnedEntity $owned) {
        $this->ownedEntity = $owned;
    }

    public function getStatus(){
        return $this->status;
    }

    /**
     * Set the status of this Role, e.g. STATUS_GRANTED or STATUS_PENDING.
     * @param String $status
     * @throws RuntimeException
     */
    public function setStatus($status){
        if(!is_string($status)){
            throw new RuntimeException('String expected for status value');
        }
        $this->status = $status;
    }

    /*public function getName(){
        return $this->name;
    }

    public function setName($name){
        if(!is_string($name)){
            throw new RuntimeException('String expected for name value');
        }
        $this->name = $name;
    }*/

    /**
     * Set a transient extension object used to decorate this instance with extra information.
     * Note, this object is <b>NOT</b> persisted to the DB. It is intended for transient
     * operations such as holding extra view parameters for transer/rendering in the view layer.
     *
     * @param mixed $decoratorObject
     */
    public function setDecoratorObject($decoratorObject){
      $this->decoratorObject = $decoratorObject;
    }

    /**
     * Get the transient extension object used to decorate this instance with extra information.
     * Note, this object is <b>NOT</b> persisted to the DB. It is intended for transient
     * operations such as holding extra view parameters for transer/rendering in the view layer.
     * @return mixed or null
     */
    public function getDecoratorObject(){
       return $this->decoratorObject;
    }

}