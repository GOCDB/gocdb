<?php
/*
 * Copyright (C) 2016 STFC
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
 *
 * User defined access control entities that defines authentication tokens that can be used
 * to access the
 * <p>
 * When the owning parent Site is deleted, its SiteProperties 
 * are also cascade-deleted.  
 * 
 * @author Tom Byrne
 * @author David Meredith
 * @Entity @Table(name="Site_ACLs",uniqueConstraints={@UniqueConstraint(name="site_keypairs", columns={"parentSite_id", "keyName", "keyValue"})})
 */
class SiteACE {
   
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /**
     * Bidirectional - Many SiteProperties (SIDE OWNING FK) can be linked to 
     * one Site (OWNING ORM SIDE). 
     *   
     * @ManyToOne(targetEntity="Site", inversedBy="siteProperties") 
     * @JoinColumn(name="parentSite_id", referencedColumnName="id", onDelete="CASCADE") 
     */
    protected $parentSite = null; 
    
    /** @Column(type="string", nullable=false) */
    protected $keyName = null; 
    
    /** @Column(type="string", nullable=true) */
    protected $keyValue = null; 
   
    public function __construct() {
    }

    /**
     * Get the owning parent {@see Site}. When the Site is deleted, 
     * these properties are also cascade deleted.  
     * @return \Site
     */
    public function getParentSite(){
        return $this->parentSite; 
    }

    /**
     * Get the key name, usually a simple alphaNumeric name, but this is not 
     * enforced by the entity. 
     * @return string
     */
    public function getKeyName(){
        return $this->keyName; 
    }

    /**
     * Get the key value, can contain any char. 
     * @return String
     */
    public function getKeyValue(){
        return $this->keyValue; 
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Do not call in client code, always use the opposite
     * <code>$site->addSiteProperties($siteProperties)</code>
     * instead which internally calls this method to keep the bidirectional 
     * relationship consistent.  
     * <p>
     * This is the OWNING side of the ORM relationship so this method WILL 
     * establish the relationship in the database. 
     * 
     * @param \Site $site
     */
    public function _setParentSite($site){
        $this->parentSite = $site; 
    }

    /**
     * The custom keyname of this key=value pair. 
     * This value should be a simple alphanumeric name without special chars, but 
     * this is not enforced here by the entity.   
     * @param string $keyName
     */
    public function setKeyName($keyName){
        $this->keyName = $keyName; 
    }

    /**
     * The custom value of this key=value pair. 
     * This value can contain any chars. 
     * @param string $keyValue
     */
    public function setKeyValue($keyValue){
        $this->keyValue = $keyValue; 
    }

}

?>
