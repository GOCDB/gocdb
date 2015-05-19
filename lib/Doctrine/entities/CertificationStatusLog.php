<?php
//use Doctrine\Common\Collections\ArrayCollection;

/**
 * Records the site certification status change history. A Site can own many 
 * CertificationStatusLog entities. When the parent site is deleted, the 
 * Site's certStatusLogs are also cascaded deleted also.   
 *
 * @author David Meredith 
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
     * @Column(type="string", nullable=true) */
    protected $addedBy = null; 
 
    /* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    /** @Column(type="datetime", nullable=true) **/
    protected $addedDate = null; 


    /** @Column(type="string", length=500, nullable=true) */
    protected $reason = null; 

    public function __construct() {
	}

    public function getParentSite(){
        return $this->parentSite; 
    }
    public function getOldStatus(){
        return $this->oldStatus; 
    }
    public function getNewStatus(){
        return $this->newStatus; 
    }
    public function getAddedBy(){
        return $this->addedBy;
    }
    public function getAddedDate(){
        return $this->addedDate; 
    }
    public function getReason(){
        return $this->reason; 
    }

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

    public function setOldStatus($oldStatus){
        $this->oldStatus = $oldStatus; 
    }
    public function setNewStatus($newStatus){
        $this->newStatus = $newStatus; 
    }
    public function setAddedBy($addedBy){
        $this->addedBy = $addedBy;
    }
    public function setAddedDate($addedDate){
        $this->addedDate = $addedDate; 
    }
    public function setReason($reason){
        $this->reason = $reason; 
    }
}

?>
