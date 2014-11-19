<?php

/**
 * Keeps a record of deleted Sites and selected information about the deletion and deleted site. 
 *
 * @author George Ryall
 * @author David Meredith 
 * @Entity @Table(name="ArchivedSites")
 */
class ArchivedSite {
   
    /** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;

    /*
     * Note, we record various site attributes and information about the Site's 
     * relations as simple types (e.g. recording the country value as a string). 
     *  
     * We can't link ArchivedSite to 'live' entities (e.g. linking AchivedSite to Country) 
     * because we need to record a snapshot of historical information at the 
     * point of deletion (while the live data is subject to change/deletion). 
     * 
     * For example, we don't want to link ArchivedSite to the 'Country' entity 
     * because the Country entity may be deleted in the future. 
     *  
     * This is why we record the name name of the parent NGI rather than its id. 
     * Similary, we record the user's DN rather than linking to the User object as 
     * that User may be deleted in future.  
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
     * Name of Site 
     * @Column(type="string", nullable=false) */
    protected $name; 
    
    /**
     * Scopes applied to the NGI at the time it was deleted
     * @Column(type="string", nullable=true) */
    protected $scopes = null;
    
    /**
     * Certification status value of the site at the time it was deleted
     * @Column(type="string", nullable=true) */
    protected $CertStatus = null;
    
    /**
     * Version 4 'Primary Key'
     * @Column(type="string", nullable=true) */
    protected $V4PrimaryKey = null;
    
    /**
     * Country on deletion
     * @Column(type="string", nullable=true) */
    protected $Country = null;
    
    /**
     * Name of parent NGI
     * @Column(type="string", nullable=true) */
    protected $parentNgi = null;
    
    /**
     * Infrastructure value (i.e. Production status value) at point of deletion
     * @Column(type="string", nullable=true) */
    protected $Infrastructure = null;
    
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

    public function getName() {
        return $this->name;
    }

    public function getScopes() {
        return $this->scopes;
    }

    public function getCertStatus() {
        return $this->CertStatus;
    }

    public function getV4PrimaryKey() {
        return $this->V4PrimaryKey;
    }

    public function getCountry() {
        return $this->Country;
    }

    public function getParentNgi() {
        return $this->parentNgi;
    }

    public function getOriginalCreationDate() {
        return $this->originalCreationDate;
    }
    
    public function getInfrastructure() {
        return $this->Infrastructure;
    }

    public function setDeletedBy($deletedBy) {
        $this->deletedBy = $deletedBy;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setScopes($scopesOnDeletion) {
        $this->scopes = $scopesOnDeletion;
    }

    public function setCertStatus($CertStatus) {
        $this->CertStatus = $CertStatus;
    }

    public function setV4PrimaryKey($V4PrimaryKey) {
        $this->V4PrimaryKey = $V4PrimaryKey;
    }

    public function setCountry($Country) {
        $this->Country = $Country;
    }

    public function setParentNgi($parentNgi) {
        $this->parentNgi = $parentNgi;
    }

    public function setOriginalCreationDate($originalCreationDate) {
        $this->originalCreationDate = $originalCreationDate;
    }

    public function setInfrastructure($Infrastructure) {
        $this->Infrastructure = $Infrastructure;
    }
}