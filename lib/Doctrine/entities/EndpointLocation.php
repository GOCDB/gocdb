<?php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="EndpointLocations")
 */
class EndpointLocation {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", nullable=true) **/
    protected $name;
	/** @Column(type="string", nullable=true) **/
	protected $url;
    /** @Column(type="string", nullable=true) **/
    protected $interfaceName;	

     /** @Column(type="string", length=2000, nullable=true) **/
    protected $description;	

    /**
     * Bidirectional - An EndpointLocation (OWNING ORM SIDE) can have many properties
     * @OneToMany(targetEntity="EndpointProperty", mappedBy="parentEndpoint", cascade={"remove"})
     */
    protected $endpointProperties = null;
	
	
    /*
     * To make the relationship one-to-one between SE and EL add the unique=true e.g.
     * @ ManyToOne(targetEntity="Service", inversedBy="endpointLocations")
     * @ JoinColumn(name="service_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
     */
    
    /**
     * Bidirectional - Many endpointlocations (DB OWNING SIDE) can link to one Service. 
     *  
     * @ManyToOne(targetEntity="Service", inversedBy="endpointLocations")
     * @JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $service = null;


    /**
     * Bidirectional - Many Endpoints (INVERSE SIDE) can link to many Downtimes. 
     * Note, we do not configure any cascade=remove behaviour here as we need to 
     * have fine-grained programmatic control over which downtimes are deleted when a service
     * endpoint is deleted (i.e. we only want to delete those DTs that exclusively link to 
     * this EL only which would subsequently be orphaned). We do this managed 
     * deletion of DTs in the DAO/Service layer. 
     *   
     * @ManyToMany(targetEntity="Downtime", mappedBy="endpointLocations")
     */
    protected $downtimes = null;

    public function __construct() {
        $this->downtimes = new ArrayCollection();
        $this->endpointProperties = new ArrayCollection();
	}
	
	//Getters
	public function getId() {
		return $this->id;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function getDowntimes() {
    	return $this->downtimes;
    }
	
	public function getUrl() {
		return $this->url;
	}
	
	public function getService(){
        return $this->service;
    }
	
	public function getInterfaceName(){
        return $this->interfaceName;
    }

    public function getEndpointProperties(){
        return $this->endpointProperties; 
    }

    public function getDescription(){
        return $this->description; 
    }
	
	//Setters
	public function setName($name) {
		$this->name = $name;
	}
	
	public function setUrl($url) {
		$this->url = $url;
	}

	public function setInterfaceName($interfaceName) {
		$this->interfaceName = $interfaceName;
	}	
    /**
     * Do not call in client code, always use the opposite
     * <code>$service->addEndpointLocationDoJoin($endpointLocation)</code>
     * instead which internally calls this method to keep the bidirectional 
     * relationship consistent.  
     * <p>
     * EndpointLocation is the OWNING side so this method WILL establish the relationship in the database. 
     * 
     * @param Service $service
     */
    public function setServiceDoJoin($service){
        $this->service = $service;
    }


    /**
     * Add a endpointProperty entity to this Endpoint's collection of properties. 
     * This method also sets the EndpointProperty's parentEndpoint.  
     * @param \EndpointProperty $endpointProperty
     */
	public function addEndpointPropertyDoJoin($endpointProperty) {
        $this->endpointProperties[] = $endpointProperty;
        $endpointProperty->_setParentEndpoint($this);
        //$endpointProperty->getParentEndpoint() = $this; 
    }
    
    /**
     * Do not call in client code, always use the opposite 
     * <code>$downtime->addEndpointLocation($thisEl)</code> instead which internally 
     * calls this method to keep the bidirectional relationship consistent.   
     * <p>
     * This is the INVERSE side so this method will NOT establish the relationship in the database. 
     *  
     * @param Downtime $downtime
     */
//    public function _addDowntime(Downtime $downtime) {
//    	$this->downtimes[] = $downtime;
//    }
    
    /**
     * Do not call in client code, always use the opposite 
     * <code>$downtime->removeEndpointLocation($thisEl)</code> instead which internally 
     * calls this method to keep the bidirectional relationship consistent.   
     * <p>
     * This is the INVERSE side so this method will NOT remove the relationship in the database. 
     *  
     * @param Downtime $downtime downtime to be removed
     */
//    public function _removeDowntime(Downtime $downtime) {
//    	$this->downtimes->removeElement($downtime);
//    }
	
     public function setDescription($description){
         $this->description = $description; 
     }
	
}	