<?php

use Doctrine\Common\Collections\ArrayCollection;

require_once 'IScopedEntity.php';

/**
 * @Entity @Table(name="Services")
 */
class Service implements IScopedEntity {
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string") */
    protected $hostName;

    /** @Column(type="string", nullable=true) */
    protected $description;

    /** @Column(type="boolean") */
    protected $production;

    /** @Column(type="boolean") */
    protected $beta;

    /** @Column(type="boolean") */
    protected $monitored;

    /** @Column(type="string", nullable=true) */
    protected $dn;

    /** @Column(type="string", nullable=true) */
    protected $ipAddress;
	
	/** @Column(type="string", nullable=true) */
    protected $ipV6Address;
	
    /** @Column(type="string", nullable=true) */
    protected $operatingSystem;

    /** @Column(type="string", nullable=true) */
    protected $architecture;

    /** @Column(type="string", nullable=true) */
    protected $email;

    /** @Column(type="string", nullable=true) */
    protected $url;

    /**
     * @ManyToOne(targetEntity="Site", inversedBy="services")
     * @JoinColumn(name="parentSite_id", referencedColumnName="id")
     */
    protected $parentSite;

    /**
     * @ManyToMany(targetEntity="ServiceGroup", mappedBy="services")
     */
    protected $serviceGroups = null;

    /**
     * @ManyToOne(targetEntity="ServiceType", inversedBy="services")
     */
    protected $serviceType;

    /**
     * @ManyToMany(targetEntity="Downtime", mappedBy="services" )
     */
    protected $downtimes;
	
	/**
     * @OneToMany(targetEntity="EndpointLocation", mappedBy="service" )
     */
    protected $endpointLocations;

	/**
     * Bidirectional - A Service (INVERSE ORM SIDE) can have many properties
     * @OneToMany(targetEntity="ServiceProperty", mappedBy="parentService", cascade={"remove"})
     */
    protected $serviceProperties = null;
	
    /**
     * Unidirectional - Scope tags associated with the service
     *
     * @ManyToMany(targetEntity="Scope")
     * @JoinTable(name="Services_Scopes",
     *      joinColumns={@JoinColumn(name="service_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="scope_Id", referencedColumnName="id")}
     *      )
     */
    protected $scopes = null;

	/* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    
    /** @Column(type="datetime", nullable=false) **/
	protected $creationDate;


    public function __construct() {
	    // Make sure all dates are treated as UTC!
	    date_default_timezone_set("UTC");
        
        // Set cretion date
        $this->creationDate =  new \DateTime("now");
		
        $this->scopes = new ArrayCollection();
        $this->endpointLocations = new ArrayCollection();
		$this->serviceProperties = new ArrayCollection();
        $this->downtimes = new ArrayCollection();
        $this->serviceGroups = new ArrayCollection();
    }

    // Getters and setters
    public function getId() {
        return $this->id;
    }
	
	public function getUrl(){
		return $this->url;
	}
	
    public function getHostName() {
        return $this->hostName;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getProduction() {
        return $this->production;
    }

    public function getBeta() {
        return $this->beta;
    }

    public function getMonitored() {
        return $this->monitored;
    }

    public function getDn() {
        return $this->dn;
    }

    public function getIpAddress() {
        return $this->ipAddress;
    }

	public function getIpV6Address() {
        return $this->ipV6Address;
    }
	
    public function getOperatingSystem() {
        return $this->operatingSystem;
    }

    public function getArchitecture() {
        return $this->architecture;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getParentSite() {
        return $this->parentSite;
    }

    public function getServiceType() {
        return $this->serviceType;
    }

	public function getServiceProperties(){
		return $this->serviceProperties;
	}


    public function getEndpointLocations() {
        return $this->endpointLocations;
    }

    public function getScopes() {
        return $this->scopes;
    }

    public function getServiceGroups() {
    	return $this->serviceGroups;
    }
    
     /**
     * provides a string containg a list of the names of scopes with which the 
     * object been tagged.
     * @return string  string containing ", " seperated list of the names 
     */
    public function getScopeNamesAsString() {
        //Get the scopes for the service
        $scopes = $this->getScopes();
        
        //Create an empty array to contain scope names
        $scopeNames= array();
        
        //populate the array
        foreach ($scopes as $scope){
            $scopeNames[]=$scope->getName();
        }
        
        sort($scopeNames);

        //Turn into a string
        $scopeNamesAsString = implode(", " , $scopeNames);
        
        return $scopeNamesAsString;
    }

    public function getCreationDate() {
        return $this->creationDate;
    }
    
    public function getDowntimes() {
        return $this->downtimes;
    }
    
	public function setUrl($url){
		$this->url = $url;
	}
	
    public function setHostName($hostName) {
        $this->hostName = $hostName;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

	public function setIpV6Address($ipAddress) {
        $this->ipV6Address = $ipAddress;
    }
	
    public function setProduction($production) {
        $this->production = $production;
    }

    public function setBeta($beta) {
        $this->beta = $beta;
    }

    public function setMonitored($monitored) {
        $this->monitored = $monitored;
    }

    public function setDn($dn) {
        $this->dn = $dn;
    }

    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;
    }

    public function setOperatingSystem($operatingSystem) {
        $this->operatingSystem = $operatingSystem;
    }

    public function setArchitecture($architecture) {
        $this->architecture = $architecture;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setParentSiteDoJoin(Site $parentSite) {
        $this->parentSite = $parentSite;
    }

    public function setServiceType(ServiceType $serviceType) {
        $this->serviceType = $serviceType;
    }

    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }
    
    /**
     * Add a ServiceProperty entity to this Service's collection of properties. 
     * This method also sets the ServiceProperty's parentService.  
     * @param \ServiceProperty $serviceProperty
     */
	public function addServicePropertyDoJoin($serviceProperty) {
        $this->serviceProperties[] = $serviceProperty;
        $serviceProperty->_setParentService($this);
    }

    /**
     * Do not call in client code. 
     * Instead call <code>$downtime->addService($service);</code> to keep 
     * both sides of the bi-directional relationship consitent.  
     * @param Downtime $downtime
     */
//    public function _addDowntime(Downtime $downtime) {		
//		$this->downtimes[] = $downtime;
//        // adding below would cause a race condition 
//		//$downtime->addService($this); 
//    }
	
//	public function removeDowntime(Downtime $downtime) {
//    	$this->downtimes->removeElement($downtime);
//    }
	
    /**
     * Add the given EL to the list. This method internally calls 
     * <code>$endpointLocation->setServiceDoJoin($this)</code> to keep both  
     * sides of the bidirectional relationship consistent (i.e. don't separately 
     * call <code>$endpointLocation->setServiceDoJoin($this)</code>). 
     * <p>
     * Service is the INVERSE side of the relation so the internal call to 
     * <code>$endpointLocation->setServiceDoJoin($this)</code> actually establishes the relationship in the DB. 
     * 
     * @param EndpointLocation $endpointLocation
     */
    public function addEndpointLocationDoJoin(EndpointLocation $endpointLocation) {
        $this->endpointLocations[] = $endpointLocation;
        $endpointLocation->setServiceDoJoin($this);    
	}
	
    public function addScope(Scope $scope) {
        $this->scopes[] = $scope;
    }

    public function addServiceGroup(ServiceGroup $sg) {
    	$this->serviceGroups[] = $sg;
    }

    /**
     * Removes the association between this service and a scope
     *
     * @param Scope $removeScope The scope to be removed.
     */
    public function removeScope(Scope $removeScope) {
        $this->scopes->removeElement($removeScope);
    }


    public function __toString() {
        return $this->getServiceType()->getName() . " " . $this->getHostName();		
    }

}