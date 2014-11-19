<?php

/**
 * Keeps a record of deleted services and some information about the deletion.
 *
 * @author George Ryall
 * @author David Meredith 
 * @Entity @Table(name="ArchivedServices")
 */
class ArchivedService {
   
    /** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

    /*
     * Note, we define the entity attributes as simple types rather than linking 
     * to related entities because we need to record a history/log, including 
     * recordig information on entitites that may be deleted. For example the 
     * parents site is stored as a string containing the sitres name These record the 
     * name of that entity on the day the service was delted. Similary, we
     * record the user's DN rather than linking to the User object as that User 
     * may be deleted in future.  
     */
    
    /**
     * DN of deleting user 
     * @Column(type="string", nullable=false) */
    protected $deletedBy; 
 
    /* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    
    /** @Column(type="datetime", nullable=false) **/
    protected $deletedDate; 

    /**
     * Name of Service  (not unique - only servicetype/hostname will be
     * @Column(type="string", nullable=false) */
    protected $hostName; 
    
    /**
     *  service type of service
     * @Column(type="string", nullable=false) */
    protected $serviceType; 
    
    /**
     * Scopes applied to the service group at the time it was deleted
     * @Column(type="string", nullable=true) */
    protected $scopes = null;
    
    /**
     * services in service group when it was deleted.
     * @Column(type="string", nullable=true) */
    protected $parentSite = null;
    
    /**
     * Was service monitored at the time it was deleted
     * @Column(type="boolean", nullable=true) */
    protected $monitored = null;
    
    /**
     * Was the service beta when it was deleted
     * @Column(type="boolean", nullable=true) */
    protected $beta = null;
    
    /**
     * Was the service production status when it was deleted
     * @Column(type="boolean", nullable=true) */
    protected $production = null;
    
    
   /** @Column(type="datetime", nullable=false) **/
    protected $originalCreationDate; 

    public function __construct() {
        // Make sure all dates are treated as UTC!
        // DM: not sure this should be here !
	    date_default_timezone_set("UTC");
        
        $this->deletedDate =  new \DateTime("now");
	}
    public function getId() {
        return $this->id;
    }

    public function getDeletedBy() {
        return $this->deletedBy;
    }

    public function getDeletedDate() {
        return $this->deletedDate;
    }

    public function getHostName() {
        return $this->hostName;
    }

    public function getScopes() {
        return $this->scopes;
    }

    public function getParentSite() {
        return $this->parentSite;
    }

    public function getOriginalCreationDate() {
        return $this->originalCreationDate;
    }
    
    public function getMonitored() {
        return $this->monitored;
    }

    public function getBeta() {
        return $this->beta;
    }

    public function getProduction() {
        return $this->production;
    }

    public function getServiceType() {
        return $this->serviceType;
    }

    public function setDeletedBy($deletedBy) {
        $this->deletedBy = $deletedBy;
    }

    public function setHostName($hostName) {
        $this->hostName = $hostName;
    }

    public function setScopes($scopes) {
        $this->scopes = $scopes;
    }

    public function setParentSite($parentSite) {
        $this->parentSite = $parentSite;
    }

    public function setOriginalCreationDate($originalCreationDate) {
        $this->originalCreationDate = $originalCreationDate;
    }

    public function setMonitored($monitored) {
        $this->monitored = $monitored;
    }

    public function setBeta($beta) {
        $this->beta = $beta;
    }

    public function setProduction($production) {
        $this->production = $production;
    }
    
    public function setServiceType($serviceType) {
        $this->serviceType = $serviceType;
    }
}