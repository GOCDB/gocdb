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
 * A RoleType defines the type of {@see Role}. 
 * The <code>name</code> is required and must be unique in the database. 
 * 
 * @author John Casson 
 * @author David Meredith <david.meredith@stfc.ac.uk> 
 * 
 * @Entity @Table(name="RoleTypes")
 */
class RoleType {

    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /** @Column(type="string", unique=true)  */
    protected $name;

    /**
     * Create a new instance. The given name must have a value and be unique in the database. 
     * 
     * @param string $name The roleType name. 
     * @throws RuntimeException if the given args are not strings 
     */
    public function __construct($name/* , $classification */) {
        $this->setName($name);
        //$this->setClassification($classification);  
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * A unique name for this role, e.g. 'Site Administrator'
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Set/update the name of the RoleType. It must be unique in the DB. 
     * @param string $name
     * @throws RuntimeException if the given param is not a string 
     */
    public function setName($name) {
        if (!is_string($name)) {
            throw new RuntimeException('Required string for $name');
        }
        $this->name = $name;
    }


}
