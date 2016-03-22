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
 * Defines lookup values for countries and joins all {@see Site}s that have
 * the same country value.   
 * 
 * @author John Casson
 * @author David Meredith <david.meredith@stfc.ac.uk> 
 * 
 * @Entity @Table(name="Countries")
 */
class Country {

    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /** @Column(type="string", unique=true)  */
    protected $name;

    /** @Column(type="string")  */
    protected $code;

    /** @OneToMany(targetEntity="Site", mappedBy="country")  */
    protected $sites = null;

    public function __construct() {
       $this->sites = new ArrayCollection();        
    }

    /**
     * @return int The PK of this entity or null if not persisted. 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * The unique name of the country. 
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * The unique code for this country. 
     * @return string
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * A unique country name. 
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Set the unique country code. 
     * @param string $code
     */
    public function setCode($code) {
        $this->code = $code;
    }

    /**
     * Get all the sites that have this country value. 
     * @return ArrayCollection of {@see Site}s
     */
    public function getSites() {
        return $this->sites;
    }

    /**
     * Add the given Site to this entities list of Sites. 
     * Note, this method calls <code>$site->setCountry($this);</code> to 
     * establish the join on both sides of the relationship. 
     * @param Site $site
     */
    public function addSiteDoJoin($site) {
        $this->sites[] = $site;
        $site->setCountry($this);
    }

}
