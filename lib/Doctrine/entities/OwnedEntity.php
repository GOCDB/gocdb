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
 * Defines an 'owned' entity, i.e. one where a user can request a {@see Role}
 * over an implementing entity.
 * <p>
 * Implementations include {@see Project}, {@see Site}, {@see ServiceGroup} and {@see NGI}.
 * Owning a Role over an OwnedEntity grants certain permissions over that object,
 * allowing the user to edit/delete attributes for example.
 *
 * @author David Meredith <david.meredithh@stfc.ac.uk>
 * @author John Casson
 *
 * @Entity  @Table(name="OwnedEntities")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"site" = "Site", "ngi" = "NGI", "project" = "Project",
 *                      "serviceGroup" = "ServiceGroup"})
 */
abstract class OwnedEntity {

    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    const TYPE_NGI = 'ngi';
    const TYPE_SITE = 'site';
    const TYPE_SERVICEGROUP = 'servicegroup';
    const TYPE_PROJECT = 'project';

    /**
     * @OneToMany(targetEntity="Role", mappedBy="ownedEntity")
     * @OrderBy({"id" = "ASC"})
     */
    protected $roles = null;

    public function __construct() {
        $this->roles = new ArrayCollection();
    }

    /**
     * Get the array of {@see Role}s held by users over the implementing entity.
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Join the given Role to this OwnedEntity.
     * <p>
     * This method also calls <code>$role->setOwnedEntity($this)</code> which
     * establishes the relationship 'on both sides' and so does not need to be
     * called (again) by client code.
     * @param \Role $role
     */
    public function addRoleDoJoin(\Role $role) {
        $this->roles[] = $role;
        $role->setOwnedEntity($this);
    }


    /**
     * Get the entity type as a string.
     * The value is same as the discriminator column of the database (case may be different).
     * <p>
     * Returns one of the following depending on the extending class type:
     * <ul>
     * <li>OwnedEntity::TYPE_NGI</li>
     * <li>OwnedEntity::TYPE_PROJECT</li>
     * <li>OwnedEntity::TYPE_SERVICEGROUP</li>
     * <li>OwnedEntity::TYPE_SITE</li>
     * </ul>
     * @return string The entity type as a string.
     */
    abstract public function getType();

    /**
     * Get the entity name.
     * @return string The entity name as a string.
     */
    abstract public function getName();


    /**
     * Get the direct parent OwnedEntities that own this instance.
     * @return \Doctrine\Common\Collections\ArrayCollection An array of {@see \OwnedEntity}
     * instances or an empty array
     */
    abstract public function getParentOwnedEntities();

    /**
     * Get the direct child OwnedEntities owned by this instance.
     * @return \Doctrine\Common\Collections\ArrayCollection An array of {@see \OwnedEntity}
     * instances or an empty array
     */
    //abstract public function getChildOwnedEntities();
}