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
 * Defines a unique value for the certification status of each {@see Site} such as 
 * 'Certified' or 'Suspended'. Each unique cert status record is joined to 
 * the Sites which have that value. When a site CertificationStatus is updated, 
 * the Site is re-joined to another CertificationStatus record.  
 * 
 * @author John Casson
 * @author David Meredith <david.meredith@stfc.ac.uk> 
 * 
 * @Entity @Table(name="CertificationStatuses")
 */
class CertificationStatus {
    
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="string", unique=true) **/
    protected $name;

    /** 
     * Bidirectional - A single CertStatus (INVERSE ORM SIDE) can be linked to many Sites. 
     * @OneToMany(targetEntity="Site", mappedBy="certificationStatus") 
     */
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
     * Get the unique certification status value, e.g. 'Certified' or 'Suspended'. 
     * @return string
     */
    public function getName() {
	return $this->name;
    }

    /**
     * Set the unique value of this cert status. Required. 
     * @param string $name
     */
    public function setName($name) {
	$this->name = $name;
    }

    /**
     * Fetch all the {@see Site} objects that are linked to this certification status.  
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getSites() {
	return $this->sites;
    }

    /**
     * Add the given Site to this certifcation status sites list. 
     * Note, this method calls the opposite <code>$site->setCertificationStatus($this);</code>
     * method to establish the join on both sides of the relationship. 
     * @param \Site $site
     */
    public function addSiteDoJoin($site) {
	$this->sites[] = $site;
	$site->setCertificationStatus($this);
    }

    /**
     * Return the name of this CertificationStatus record. 
     * @see CertificationStatus#getName(); 
     * @return string
     */
    public function __toString() {
	return $this->getName();
    }
}