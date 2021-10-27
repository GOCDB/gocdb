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
 * A Scope is a tag that is joined to entities that define the
 * {@see \IScopedEntity} interface.
 * <p>
 * Scopes are used for resource matching and filtering, e.g. find all Sites
 * that have a Scope value of 'X'.
 * The relationship between Scope and an {@see \IScopedEntity} is a
 * uni-directional aggregation (IScopedEntities do not own scopes, they are
 * only linked to Scope instances).
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @Entity @Table(name="Scopes", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class Scope {

    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /** @Column(type="string", unique=true)  */
    protected $name;

    /** @Column(type="string", nullable=true)  */
    protected $description;

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the unique name of the scope.
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Get a human readable description of this scope.
     * @return string or null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set the unique name of this Scope instance.
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Set the human readable description of this scope.
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Returns the unique name of this Scope instance.
     * @return string
     */
    public function __toString() {
        return $this->getName();
    }

}
