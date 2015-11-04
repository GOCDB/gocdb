<?php

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="ServiceGroups")
 */
class ServiceGroup extends OwnedEntity implements IScopedEntity {
    
    //commented out as this is a duplicate definition - also in owned entity class
    /* @Id @Column(type="integer") @GeneratedValue */
    //protected $id;

    /** @Column(type="string") */
    protected $name;

    /** @Column(type="string", nullable=true) */
    protected $description;

    /** @Column(type="boolean") */
    protected $monitored;

    /** @Column(type="string") */
    protected $email;

    /**
     * Unidirectional - Scope tags associated with the service group.
     *
     * @ManyToMany(targetEntity="Scope")
     * @JoinTable(name="ServiceGroups_Scopes",
     *      joinColumns={@JoinColumn(name="serviceGroup_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="scope_Id", referencedColumnName="id")}
     *      )
     */
    protected $scopes = null;

    /**
     * @ManyToMany(targetEntity="Service", inversedBy="serviceGroups")
     * @OrderBy({"id" = "ASC"})
     * @JoinTable(name="ServiceGroups_Services",
     *      joinColumns={@JoinColumn(name="serviceGroup_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="service_Id", referencedColumnName="id")}
     *      )
     */
    protected $services = null;
    
    /* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    
    /** @Column(type="datetime", nullable=false) **/
	protected $creationDate;
    
	/**
     * Bidirectional - A ServiceGroup (INVERSE ORM SIDE) can have many properties
     * @OneToMany(targetEntity="ServiceGroupProperty", mappedBy="parentServiceGroup", cascade={"remove"})
     */
    protected $serviceGroupProperties = null;

    public function __construct() {
        parent::__construct();
        
        // Set cretion date
        $this->creationDate =  new \DateTime("now");
        $this->serviceGroupProperties = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->services = new ArrayCollection();
    }


    /**
     * @return ArrayCollection Empty collection, ServiceGroup has no owning parents.  
     */
    public function getParentOwnedEntities() {
        // return empty collection - no parents 
        return new ArrayCollection();
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getMonitored() {
        return $this->monitored;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getScopes() {
        return $this->scopes;
    }

    public function getServices() {
    	return $this->services;
    }

	public function getServiceGroupProperties(){
		return $this->serviceGroupProperties;
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

    public function setName($name) {
        $this->name = $name;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setMonitored($monitored) {
        $this->monitored = $monitored;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }
  
    public function addScope(Scope $scope) {
        $this->scopes[] = $scope;
    }

    public function addService(Service $se) {
    	$this->services[] = $se;		
    	$se->addServiceGroup($this);
    }
	
    /**
     * Add a ServiceGroupProperty entity to this Service's collection of properties. 
     * This method also sets the ServiceGroupProperty's parentService.  
     * @param \ServiceGroupProperty $serviceGroupProperty
     */
	public function addServiceGroupPropertyDoJoin($serviceGroupProperty) {
        $this->serviceGroupProperties[] = $serviceGroupProperty;
        $serviceGroupProperty->_setParentServiceGroup($this);
    }
	
    public function removeService(Service $se) {
    	$this->services->removeElement($se);
    }

    /**
     * Removes the association between this service group and a scope
     *
     * @param Scope $removeScope The scope to be removed.
     */
    public function removeScope(Scope $removeScope) {
        $this->scopes->removeElement($removeScope);
    }

    public function __toString() {
        return $this->getName();
    }

    /**
     * Returns value of {@link \OwnedEntity::TYPE_SERVICEGROUP}
     * @see \OwnedEntity::getType()
     * @return string 
     */
    public function getType() {
        return parent::TYPE_SERVICEGROUP; 
    }
}