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
 * Used to create the PRIMARY_KEY field exposed through the PI.
 * This maintains backwards compatiblity with the v4 PRIMARY_KEY FIELDS. 
 * This sequence can be used when required to get the next primary key by 
 * creating and persisting an instance of PrimarKey and calling the getId() 
 * method. This value can then be used to programatically set the legacy 
 * PRIMARY_KEY fields when persisting new entities. 
 *
 * @author John Casson
 *
 * @Entity @Table(name="PrimaryKeys")
 */
class PrimaryKey {

    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the Id value. 
     * @return string
     */
    public function __toString() {
        return $this->id;
    }

}
