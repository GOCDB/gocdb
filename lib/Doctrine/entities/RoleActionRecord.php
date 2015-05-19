<?php

/*
 * Copyright (C) 2012 STFC
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
 * A standalone entity that logs a role action e.g approval, deletion, denial. 
 * <p>
 * The entity is standalone and has no relationships. This is to allow
 * RoleActionRecords to have a lifespan that is independent of the {@link \Role} 
 * and {@link \OwnedEntity} affected by the role action. 
 * Therefore, while roles and owned entities may be deleted, the action log is 
 * unaffected and serves as a permanent change log.  
 * Once created, object is immutable. 
 *
 * @author David Meredith 
 * @Entity @Table(name="RoleActionRecords")
 */
class RoleActionRecord {
    
    /** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

    /** @Column(type="datetime", nullable=false) **/
    protected $actionDate; 

    /**
     * Optional supplementary info for this record? 
     * @Column(type="string")
     * @var string 
     */
    //protected $description; 

    //========================================================================
    // Who created/updated this record  
    //========================================================================
    /** 
     * Id of the {@link \User} who created the record.  
     * @Column(type="integer", nullable=false) 
     * @var int 
     */
    protected $updatedByUserId; 
    /**
     * A human readable string to help identify the {@link \User} who last 
     * updated this record, e.g. a principle such as a DN, or the concat of 
     * 'Firstname, Lastname'. Note, this value should not be used as a natural 
     * foreign key and is for display purposes only.  
     * @Column(type="string", nullable=false)
     * @var string 
     */
    protected $updatedByUserPrinciple;
    
    //========================================================================
    // Which Role does this record affect  
    //========================================================================
    /** 
     * Id of the updated {@link \Role}. 
     * @Column(type="integer", nullable=false) 
     * @var int
     */
    protected $roleId; 
    /**
     * The Role status before update (e.g. STATUS_PENDING)
     * @Column(type="string", nullable=false) 
     * @var string 
     */
    protected $rolePreStatus; 
    /**
     * The Role status after update (e.g. STATUS_GRANTED, STATUS_DELETED)
     * @Column(type="string", nullable=false) 
     * @var string 
     */
    protected $roleNewStatus; 

    //========================================================================
    // What was the RoleType   
    //========================================================================
    /**
     * The RoleType Id. 
     * @Column(type="integer", nullable=false) 
     * @var int 
     */
    protected $roleTypeId; 
    /**
     * The RoleType name (e.g. 'Site Administrator')
     * @Column(type="string", nullable=false) 
     * @var string 
     */
    protected $roleTypeName; 

    //========================================================================
    // What was the Roles target OwnedEntity
    //========================================================================
    /** 
     * Id of the Role's affected {@link \OwnedEntity}.  
     * @Column(type="integer", nullable=false) 
     * @var int 
     */
    protected $roleTargetOwnedEntityId; 
    /**
     * The type of {@link \OwnedEntity}, i.e. NGI, Site, Service, ServiceGroup
     * @Column(type="string", nullable=false)
     * @var string 
     */
    protected $roleTargetOwnedEntityType; 

    //========================================================================
    // Who was the Role's target user 
    //========================================================================
    /** 
     * Id of the {@link \User}.  
     * @Column(type="integer", nullable=false) 
     * @var int 
     */
    protected $roleUserId; 
    /**
     * A human readable string to help identify the {@link \User} who created 
     * this record, e.g. a principle such as a DN, or the concat of 
     * 'Firstname, Lastname'. Note, this value should not be used as a natural 
     * foreign key and is for display purposes only.  
     * @Column(type="string", nullable=false)
     * @var string 
     */
    protected $roleUserPrinciple; 

    /**
     * Create a new instance. 
     * Calling code should use {@link RoleActionRecord::construct($callingUser, $role, $newStatus)}
     * instead to construct an instance. 
     *  
     * @param int    $updatedByUserId Id of {@link \User} who creates/updates the record. 
     * @param string $updatedByUserPrinciple Human readable string of {@link \User} 
     *               who updates the record (e.g. concat of 'firstName secondName' or principle string). 
     *               Note, this value should not be used as a natural foreign 
     *               key and is for display purposes only.
     * @param int    $roleId The target {@link \Role} 
     * @param string $rolePreStatus The Role status before update (e.g. STATUS_PENDING)
     * @param string $roleNewStatus The Role status after update (e.g. STATUS_GRANTED)
     * @param int    $roleTypeId The Id of the {@link \RoleType}
     * @param string $roleTypeName The {@link \RoleType} name value 
     * @param int    $roleTargetOwnedEntityId The {@link \OwnedEntity} id that the Role is over  
     * @param string $roleTargetOwnedEntityType The type of {@link \OwnedEntity} e.g. Site, Service, NGI, ServiceGroup 
     * @param int    $roleUserId The Id of the {@link \User} who owns the Role.  
     * @param string $roleUserPrinciple Human readable string of {@link \User} 
     *               who owns the Role (e.g. concat of 'firstName secondName' or principle string).  
     *               Note, this value should not be used as a natural foreign 
     *               key and is for display purposes only.
     */ 
    function __construct($updatedByUserId, $updatedByUserPrinciple, 
            $roleId, $rolePreStatus, $roleNewStatus, $roleTypeId, $roleTypeName, 
            $roleTargetOwnedEntityId, $roleTargetOwnedEntityType, $roleUserId, $roleUserPrinciple) {
        $this->actionDate =  new \DateTime("now");
        
        $this->updatedByUserId = $updatedByUserId;
        $this->updatedByUserPrinciple = $updatedByUserPrinciple;
        $this->roleId = $roleId;
        $this->rolePreStatus = $rolePreStatus;
        $this->roleNewStatus = $roleNewStatus;
        $this->roleTypeId = $roleTypeId;
        $this->roleTypeName = $roleTypeName;
        $this->roleTargetOwnedEntityId = $roleTargetOwnedEntityId;
        $this->roleTargetOwnedEntityType = $roleTargetOwnedEntityType;
        $this->roleUserId = $roleUserId; 
        $this->roleUserPrinciple = $roleUserPrinciple;
    }

    /**
     * Convenience method to construct a new instance. 
     * 
     * @param \User $callingUser {@link \User} who creates/updates this record.
     * @param \Role $role The target {@link \Role} 
     * @param string $newStatus
     * @return \self
     */
    public static function construct(\User $callingUser, \Role $role, $newStatus) {
        $rar = new self(
                $callingUser->getId(), /*$callingUser->getCertificateDn(),*/ $callingUser->getFullName(), 
                $role->getId(), $role->getStatus(), $newStatus, 
                $role->getRoleType()->getId(), $role->getRoleType()->getName(), 
                $role->getOwnedEntity()->getId(), $role->getOwnedEntity()->getType(), 
                $role->getUser()->getId(), $role->getUser()->getFullName());
        return $rar;
    }

    function getId() {
        return $this->id;
    }

    function getActionDate() {
        return $this->actionDate;
    }

    function getUpdatedByUserId() {
        return $this->updatedByUserId;
    }

    function getUpdatedByUserPrinciple() {
        return $this->updatedByUserPrinciple;
    }

    function getRoleId() {
        return $this->roleId;
    }

    function getRolePreStatus() {
        return $this->rolePreStatus;
    }

    function getRoleNewStatus() {
        return $this->roleNewStatus;
    }

    function getRoleTypeId() {
        return $this->roleTypeId;
    }

    function getRoleTypeName() {
        return $this->roleTypeName;
    }

    function getRoleTargetOwnedEntityId() {
        return $this->roleTargetOwnedEntityId;
    }

    function getRoleTargetOwnedEntityType() {
        return $this->roleTargetOwnedEntityType;
    }

    function getRoleUserId() {
        return $this->roleUserId;
    }

    function getRoleUserPrinciple() {
        return $this->roleUserPrinciple;
    }


    
}
