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
 * Defines a named e-Infrastructure such as 'TEST' or 'Production' and 
 * joins all the {@see Site}s that are linked to this infrastructure. 
 *  
 * @author John Casson
 * @author David Meredith <david.meredith@stfc.ac.uk> 
 * 
 * @Entity @Table(name="Infrastructures")
 */
class Infrastructure {

    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /** @Column(type="string", unique=true)  */
    protected $name;

    /** @OneToMany(targetEntity="Site", mappedBy="infrastructure")  */
    protected $sites = null;

    public function __construct() {
        $this->sites = new ArrayCollection();
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Defines a unique name for the infrastructure, e.g. 'Test' or 'PPS' 
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set the unique name for the infrastructure. Required. 
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get all the {@see Site}s that define (are joined to) this infrastructure. 
     * @return ArrayCollection
     */
    public function getSites() {
        return $this->sites;
    }

    /**
     * Add the given Site to this objects list of sites. 
     * Note, this method calls <code>$site->setInfrastructure($this);</code> to 
     * set the join on both sides of the relationship. 
     * @param \Site $site
     */
    public function addSiteDoJoin($site) {
        $this->sites[] = $site;
        $site->setInfrastructure($this);
    }



}
