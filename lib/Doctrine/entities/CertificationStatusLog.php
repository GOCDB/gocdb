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
 * Records the site certification status change history. A Site can own many 
 * CertificationStatusLog entities. When the parent site is deleted, the 
 * Site's certStatusLogs are also cascade deleted.   
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>  
 * @Entity @Table(name="CertificationStatusLogs")
 */
class CertificationStatusLog {
   
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
     * Bidirectional - Many CertificationStatusLogs (OWNING ORM SIDE) can be linked to one Site. 
     *   
     * @ManyToOne(targetEntity="Site", inversedBy="certificationStatusLog") 
     * @JoinColumn(name="parentSite_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentSite = null; 

    /*
     * Note, we define the entity attributes as simple types rather than linking 
     * to related entities because we need to record a history/log, including 
     * recordig information on entitites that may be deleted.  
     * For exammple, oldStatus and newStatus are simple strings. These values 
     * record the relevant <code>CertificationStatus.name</code> value, i.e; 
     * either the current or historical value.   
     * Similary, we record the user's DN rather than linking to the User object 
     * as that User may be deleted in future.  
     */
    
    /** @Column(type="string", nullable=true) */
    protected $oldStatus = null; 
    
    /** @Column(type="string", nullable=true) */
    protected $newStatus = null; 
    
    /**
     * User DN  
     * @Column(type="string", nullable=true) 
     */
    protected $addedBy = null; 
 
    /* DATETIME NOTE:
     * Doctrine checks whether a date's been updated by doing a byreference comparison.
     * If you just update an existing DateTime object, Doctrine won't persist it!
     * Create a new DateTime object and reference that for it to persist during an update.
     * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
     */
    
    /** @Column(type="datetime", nullable=true) */
    protected $addedDate = null; 


    /** @Column(type="string", length=500, nullable=true) */
    protected $reason = null; 

    public function __construct() {
    }

    /**
     * Get the owning Site that owns this cert status log.
     * A Site can own many CertificationStatusLog entities. When the parent 
     * site is deleted, the Site's certStatusLogs are also cascade deleted.    
     * @return \Site
     */
    public function getParentSite(){
        return $this->parentSite; 
    }

    /**
     * Get the old/last certification status value of the site, e.g. 'Uncertified'. 
     * @return string or null 
     */
    public function getOldStatus(){
        return $this->oldStatus; 
    }

    /**
     * Get the newly updated certification status value, e.g. 'Certified' 
     * @return string or null 
     */
    public function getNewStatus(){
        return $this->newStatus; 
    }

    /**
     * Get the ID/DN of the user who created this cert status log. 
     * @return string or null 
     */
    public function getAddedBy(){
        return $this->addedBy;
    }
   
    /**
     * Get the Date Time that this cert status log was created/persited. 
     * @return \DateTime or null 
     */
    public function getAddedDate(){
        return $this->addedDate; 
    }

    /**
     * A human readable description why this cert status log was created, max
     * 500 chars. 
     * @return string or null 
     */
    public function getReason(){
        return $this->reason; 
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }
    /**
     * Do not call in client code, always use the opposite
     * <code>$site->addCertificationStatusLog($certStatusLog)</code>
     * instead which internally calls this method to keep the bidirectional 
     * relationship consistent.  
     * <p>
     * This is the OWNING side of the ORM relationship so this method WILL 
     * establish the relationship in the database. 
     * 
     * @param \Site $site
     */
    public function setParentSite(\Site $site){
        $this->parentSite = $site; 
    }

    /**
     * Set the old status of the site, e.g. 'Suspended'  
     * @param string $oldStatus
     */
    public function setOldStatus($oldStatus){
        $this->oldStatus = $oldStatus; 
    }
    
    /**
     * Set the new status of the site, e.g. 'Certified'
     * @param string $newStatus
     */
    public function setNewStatus($newStatus){
        $this->newStatus = $newStatus; 
    }

    /**
     * The ID/DN of the user who created this cert status log. 
     * @param string $addedBy
     */
    public function setAddedBy($addedBy){
        $this->addedBy = $addedBy;
    }

    /**
     * The date time when this record was added. 
     * @param \DateTime $addedDate
     */
    public function setAddedDate($addedDate){
        $this->addedDate = $addedDate; 
    }

    /**
     * Human readable reason why this record was created, max 500 chars. 
     * @param string $reason
     */
    public function setReason($reason){
        $this->reason = $reason; 
    }
}

?>
