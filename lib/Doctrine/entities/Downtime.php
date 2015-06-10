<?php
// Country.php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="Downtimes")
 */
class Downtime {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", length=4000) **/
	protected $description;
    /** @Column(type="string") **/
	protected $severity;
    /** @Column(type="string") **/
	protected $classification;
	/* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    /** @Column(type="datetime", nullable=true) **/
	protected $insertDate;
    /** @Column(type="datetime", nullable=true) **/
	protected $startDate;
    /** @Column(type="datetime", nullable=true) **/
	protected $endDate;


    
		
	/**
	* @ManyToMany(targetEntity="Service", inversedBy="downtimes")
	* @JoinTable(name="Downtimes_Services",
	*      joinColumns={@JoinColumn(name="downtime_id", referencedColumnName="id")},
	*      inverseJoinColumns={@JoinColumn(name="service_id", referencedColumnName="id")}
	*      )
	*/
	protected $services = null;


    
    /**
     * Bidirectional - Many Downtimes (OWNING SIDE) can link to many ELs. 
     * 
     * The data model allows a Downtime to be linked to many ELs of a Service. 
     * IF required, this allows selected ELs of a Service to be put into 
     * downtime as per GLUE2. Alternatively, higher level business logic can 
     * be used to limit the number of ELs per service to 1. In doing this, 
     * the whole Service can then be put into downtime. This multiplicity choice
     * is left to the implementation.   
     * 
     * @ManyToMany(targetEntity="EndpointLocation", inversedBy="downtimes")
     * @JoinTable(name="Downtimes_EndpointLocations",
     *      joinColumns={@JoinColumn(name="downtime_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="endpointLocation_id", referencedColumnName="id")}
     *      )
     */
	protected $endpointLocations= null;

    /** 
     * The legacy Downtime primary key carried over from GOCDB4.
     * Other operational tools use this as the unique identifier for a Downtime.
     * For new Downtimes created in v5 this value is programmatically generated.
     * 
     * @Column(type="string", unique=true, nullable=true) 
     */
    protected $primaryKey;

    
	const DATE_FORMAT = 'd-M-y H:i:s';


    public function __construct() {
		$this->services = new ArrayCollection();
		$this->endpointLocations = new ArrayCollection();
	}

	public function getId() {
		return $this->id;
	}

	public function getDescription() {
		return $this->description;
	}

    public function getSeverity() {
		return $this->severity;
	}

    public function getClassification() {
		return $this->classification;
	}

    public function getInsertDate() {
		return $this->insertDate;
	}

    public function getStartDate() {
		return $this->startDate;
	}

    public function getEndDate() {
		return $this->endDate;
	}

	/* SCHEDULED: 	Announce Date = 24 hours before Start Date
	 * UNSCHEDULED: Announce Date = Insert Date
	 */
	public function getAnnounceDate() {
		if($this->getClassification() == "UNSCHEDULED") {
			return $this->insertDate;
		}
		$di = DateInterval::createFromDateString('1 days');
		$announceDate = clone $this->startDate;
		$announceDate->sub($di);
		return $announceDate;
	}

    public function getServices() {
        return $this->services;
    }

    public function getEndpointLocations() {
        return $this->endpointLocations;
    }

    public function getPrimaryKey(){
        return $this->primaryKey; 
    }

    public function setDescription($description) {
		$this->description = $description;
	}

    public function setSeverity($severity) {
		$this->severity = $severity;
	}

	public function setCode($code) {
		$this->code = $code;
	}

    public function setClassification($classification) {
		$this->classification = $classification;
	}

    public function setInsertDate($insertDate) {
		$this->insertDate = $insertDate;
	}

    public function setStartDate($startDate) {
		$this->startDate = $startDate;
	}

    public function setEndDate($endDate) {
		$this->endDate = $endDate;
	}

    public function setPrimaryKey($primaryKey){
        $this->primaryKey = $primaryKey; 
    }
	
    /**
	 * Add a service to this downtime's collection. 
     * This method will establish the relationship on both sides by internally 
     * calling <code>$service->_addDowntime($this);</code>. 
     * Downtime is the OWNING side so this method WILL establish the relationship 
     * in the database.
     * 
     * @param Service $service
     */
	public function addService($service) {
		require_once __DIR__.'/AlreadyLinkedException.php';
		// Check this SE isn't already registered (not sure we strictly need this) 
		foreach($this->services as $existingSe) {
			if($existingSe == $service) {
					throw new AlreadyLinkedException("Downtime {$this->getId()} is already "
					. "linked to service {$existingSe->getHostName()}");
			}
		}
		$this->services[] = $service;
		//$service->_add Downtime($this); 
        $dts = $service->getDowntimes();
        $dts[] = $this; 
	}
	
	/** 
     * Remove given service from $this services list and remove 
     * $this instance from the given service's downtime list.  
	 * Downtime is the OWNING side so this method WILL remove the relationship from
     * the database.
     * 
     * @param Service $service service for removal
     */
	public function removeService(Service $service) {
		//$service->remove Downtime($this); 
        $service->getDowntimes()->removeElement($this); 
        $this->services->removeElement($service);        
	}
  
    
    /**
     * Add the given EL to this downtime's EL list and then  
     * calls <code>$endpointLocation->_addDowntime($this)</code> to 
     * keep both sides of the relationship consistent.  
     * <p>
     * This is the OWNING side so this method WILL establish the relationship in the database.
     * 
     * @param EndpointLocation $endpointLocation
     */
    public function addEndpointLocation(EndpointLocation $endpointLocation) {
		$this->endpointLocations[] = $endpointLocation;
        //$endpointLocation->_add Downtime($this); 
        $dts = $endpointLocation->getDowntimes(); 
        $dts[] = $this;
	}

    /**
     * Remove the downtime from the specified ELs list of downtimes and 
     * then remoeve it from this downtime's EL list.  
     * calls to <code>$endpointLocation->_removeDowntime($this)</code> to 
     * keep both sides of the relationship consistent (i.e. no need to 
     * call <code>$endpointLocation->removeDowntime($this)</code> in client code).  
     * <p>
     * This is the OWNING side so this method WILL remove the relationship from
     * the database.
     * 
     * @param EndpointLocation $endpointLocation endpoint location for removal
     */
    public function removeEndpointLocation(EndpointLocation $endpointLocation) {
        $endpointLocation->getDowntimes()->removeElement($this); 
		//$endpointLocation->_remove Downtime($this); 
        $this->endpointLocations->removeElement($endpointLocation);        
	}
    
	/**
	 * Is this downtime ongoing? Returns true or false
	 * @return boolean
	 */
	public function isOngoing() {
	    $now = new \DateTime(null, new \DateTimeZone('UTC'));
	    if($this->getStartDate() < $now && $this->getEndDate() > $now) {
	        return true;
	    } else {
	        return false;
	    }
	}

	/**
	 * Has this downtime started? Returns true or false
	 * @return boolean
	 */
	public function hasStarted() {
        $now = new \DateTime(null, new \DateTimeZone('UTC'));
        if($this->getStartDate() < $now) {
            return true;
        } else {
            return false;
        }
	}
}