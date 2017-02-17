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
 * User defined access control entities that defines credentials that can be used to access the write API.
 * <p>
 * When the owning parent Site is deleted, its SiteACLs are also cascade-deleted.
 * 
 * @author Tom Byrne
 * @author David Meredith
 * @Entity @Table(name="Site_ACLs",uniqueConstraints={@UniqueConstraint(name="site_unique_acl", columns={"parentSite_id", "credentialID", "credentialType"})})
 */
class SiteACL {
   
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /**
     * Bidirectional - Many SiteACLs (SIDE OWNING FK) can be linked to
     * one Site (OWNING ORM SIDE). 
     *   
     * @ManyToOne(targetEntity="Site", inversedBy="siteACLs")
     * @JoinColumn(name="parentSite_id", referencedColumnName="id", onDelete="CASCADE") 
     */
    protected $parentSite = null; 
    
    /** @Column(type="string", nullable=false) */
    protected $credentialID = null;
    
    /** @Column(type="string", nullable=false) */
    protected $credentialType = null;
   
    public function __construct() {
    }

    /**
     * Get the owning parent {@see Site}. When the Site is deleted, 
     * these ACLs are also cascade deleted.
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
    public function getCredentialID(){
        return $this->credentialID;
    }

    /**
     * Get the key value, can contain any char. 
     * @return String
     */
    public function getCredentialType(){
        return $this->credentialType;
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Do not call in client code, always use the opposite
     * <code>$site->addSiteACLs($siteACLs)</code>
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
     * The credentialID, can be a DN, Username/pw hash or other.
     * This value can contain any chars.
     * @param string $credentialID
     */
    public function setCredentialID($credentialID){
        $this->credentialID = $credentialID;
    }

    /**
     * The type of the credential (DN, UnPw).
     * This value can contain any chars. 
     * @param string $credentialType
     */
    public function setCredentialType($credentialType){
        $this->credentialType = $credentialType;
    }

}

?>
