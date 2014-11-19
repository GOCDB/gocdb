<?php

use Doctrine\Common\Collections\ArrayCollection;

require_once 'NGI.php';

/**
 * @Entity @Table(name="Sites") 
 */
class Site extends OwnedEntity implements IScopedEntity{
    //Removed as also defined in Owned Entity class.
    /*
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    //protected $id;
    
    /* The site primary key carried over from GOCDB4.
     * Other operational tools use this 
     * as the unique identifier for a site.
     * For new sites created in v5 this value is 
     * programmatically generated.
     */
    /** @Column(type="string", unique=true, nullable=true) */
    protected $primaryKey;

    /** 
     * It is v.important that the Site name is unique for backward compatibility 
     * with the GOCDB v4 data. We therefore use a DB unique constraint. Other 
     * deployments/instances may not require this restriction. 
     * 
     * @Column(type="string", unique=true) 
     */
    protected $shortName;

    /** @Column(type="string", nullable=true) */
    protected $officialName;

    /** @Column(type="string", nullable=true) */
    protected $homeUrl;

    /** @Column(type="string", length=2000, nullable=true) */
    protected $description;

    /** @Column(type="string", nullable=true) */
    protected $email;

    /** @Column(type="string", nullable=true) */
    protected $telephone;

    /** @Column(type="string", nullable=true) */
    protected $giisUrl;

    /** @Column(type="float", nullable=true) */
    protected $latitude;

    /** @Column(type="float", nullable=true) */
    protected $longitude;

    /** @Column(type="string", nullable=true) */
    protected $csirtEmail;

    /** @Column(type="string", nullable=true) */
    protected $alarmEmail;

    /** @Column(type="string", nullable=true) */
    protected $ipRange;
	
	/** @Column(type="string", nullable=true) */
    protected $ipV6Range;
	
    /** @Column(type="string", nullable=true) */
    protected $domain;

    /** @Column(type="string", nullable=true) */
    protected $location;

    /** @Column(type="string", nullable=true) */
    protected $csirtTel;

    /** @Column(type="string", nullable=true) */
    protected $emergencyTel;

    /** @Column(type="string", nullable=true) */
    protected $emergencyEmail;

    /** @Column(type="string", nullable=true) */
    protected $helpdeskEmail;

    /** @ManyToOne(targetEntity="NGI", inversedBy="sites") */
    protected $ngi;

    /**
     * Bidirectional - A Site (INVERSE ORM SIDE) can have many services
     * @OneToMany(targetEntity="Service", mappedBy="parentSite")
     */
    protected $services = null;
		
	/**
     * Bidirectional - A Site (INVERSE ORM SIDE) can have many properties
     * @OneToMany(targetEntity="SiteProperty", mappedBy="parentSite", cascade={"remove"})
     */
    protected $siteProperties = null;

    /** @ManyToOne(targetEntity="Infrastructure", inversedBy="sites") */
    protected $infrastructure;

    /** @ManyToOne(targetEntity="CertificationStatus", inversedBy="sites") */
    protected $certificationStatus;
    
    /* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a by reference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    /** @Column(type="datetime", nullable=true) **/
    protected $certificationStatusChangeDate = null; 


    /**
     * Bidirectional - A Site (INVERSE ORM SIDE) has a history of certification statuses 
     * @OneToMany(targetEntity="CertificationStatusLog", mappedBy="parentSite")
     */
    protected $certificationStatusLog; 

    /**    
     * Unidirectional - Scope tags associated with this site.
     * 
     * @ManyToMany(targetEntity="Scope")
     * @JoinTable(name="Sites_Scopes",
     *      joinColumns={@JoinColumn(name="site_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="scope_Id", referencedColumnName="id")}
     *      )
     */
    protected $scopes = null;

    /** @ManyToOne(targetEntity="Country", inversedBy="sites") * */
    protected $country;

    /** @ManyToOne(targetEntity="Timezone", inversedBy="sites") * */
    protected $timezone;

    /** @ManyToOne(targetEntity="Tier", inversedBy="sites") * */
    protected $tier;

    /** @ManyToOne(targetEntity="SubGrid", inversedBy="sites")
     *  @JoinColumn(name = "subgrid_id", referencedColumnName="id", onDelete="SET NULL") * */
    protected $subGrid;

    /**
     * Users for whom this is their home site
     * @OneToMany(targetEntity="User", mappedBy="homeSite")
     */
    protected $users = null;

    /* DATETIME NOTE:
	 * Doctrine checks whether a date's been updated by doing a byreference comparison.
	 * If you just update an existing DateTime object, Doctrine won't persist it!
	 * Create a new DateTime object and reference that for it to persist during an update.
	 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
	 */
    
    /** @Column(type="datetime", nullable=false) **/
	protected $creationDate;
    
    public function __construct() {
        parent::__construct();
        
        // Make sure all dates are treated as UTC!
	    date_default_timezone_set("UTC");
        
        // Set cretion date
        $this->creationDate =  new \DateTime("now");        
        $this->services = new ArrayCollection();
		$this->siteProperties = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->certificationStatusLog = new ArrayCollection();
    }

    /* Getters and setters
    public function getId() {
        return $this->id;
    }
    */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    public function getShortName() {
        return $this->shortName;
    }

    public function getName(){
       return $this->shortName; 
    }

    public function getofficialName() {
        return $this->officialName;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getHomeUrl() {
        return $this->homeUrl;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getTelephone() {
        return $this->telephone;
    }

    public function getGiisUrl() {
        return $this->giisUrl;
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function getIpRange() {
        return $this->ipRange;
    }
	
	public function getIpV6Range() {
        return $this->ipV6Range;
    }
	
    public function getDomain() {
        return $this->domain;
    }

    public function getLocation() {
        return $this->location;
    }

    public function getCsirtTel() {
        return $this->csirtTel;
    }

    public function getCsirtEmail() {
        return $this->csirtEmail;
    }

    public function getAlarmEmail() {
        return $this->alarmEmail;
    }

    public function getEmergencyTel() {
        return $this->emergencyTel;
    }

    public function getEmergencyEmail() {
        return $this->emergencyEmail;
    }

    public function getHelpdeskEmail() {
        return $this->helpdeskEmail;
    }

    public function getServices() {
        return $this->services;
    }
	
    public function getSiteProperties(){
		return $this->siteProperties;
    }

    public function getNgi() {
        return $this->ngi;
    }

    public function getInfrastructure() {
        return $this->infrastructure;
    }

    public function getCertificationStatus() {
        return $this->certificationStatus;
    }
    
    public function getCertificationStatusChangeDate(){
        return $this->certificationStatusChangeDate; 
    }

    public function getCertificationStatusLog(){
        return $this->certificationStatusLog; 
    }

    public function getScopes() {
        return $this->scopes;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getTimezone() {
        return $this->timezone;
    }

    public function getTier() {
        return $this->tier;
    }

    public function getSubGrid() {
        return $this->subGrid;
    }

    public function getUsers() {
        return $this->users;
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
    
    /* ======== End of Getters ============= */
    public function setPrimaryKey($primaryKey) {
        $this->primaryKey = $primaryKey;
    }
    
    public function setShortName($shortName) {
        $this->shortName = $shortName;
    }

    public function setOfficialName($officialName) {
        $this->officialName = $officialName;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setHomeUrl($homeUrl) {
        $this->homeUrl = $homeUrl;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setTelephone($telephone) {
        $this->telephone = $telephone;
    }

    public function setGiisUrl($giisUrl) {
        $this->giisUrl = $giisUrl;
    }

    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    }

    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    }

    public function setCsirtEmail($csirtEmail) {
        $this->csirtEmail = $csirtEmail;
    }

    public function setIpRange($ipRange) {
        $this->ipRange = $ipRange;
    }
	
	public function setIpV6Range($ipRange) {
        $this->ipV6Range = $ipRange;
    }
	
    public function setDomain($domain) {
        $this->domain = $domain;
    }

    public function setLocation($location) {
        $this->location = $location;
    }

    public function setCsirtTel($csirtTel) {
        $this->csirtTel = $csirtTel;
    }

    public function setEmergencyTel($emergencyTel) {
        $this->emergencyTel = $emergencyTel;
    }

    public function setEmergencyEmail($emergencyEmail) {
        $this->emergencyEmail = $emergencyEmail;
    }

    public function setAlarmEmail($alarmEmail) {
        $this->alarmEmail = $alarmEmail;
    }

    public function setHelpdeskEmail($helpdeskEmail) {
        $this->helpdeskEmail = $helpdeskEmail;
    }

    public function addServiceDoJoin(Service $service) {
        $this->services[] = $service;
        $service->setParentSiteDoJoin($this);
    }

    /**
     * Add a SiteProperty entity to this Site's collection of properties. 
     * This method also sets the SiteProperty's parentSite. 
     * @param \SiteProperty $siteProperty
     */
	public function addSitePropertyDoJoin($siteProperty) {
        $this->siteProperties[] = $siteProperty;
        $siteProperty->_setParentSite($this);
    }


    /**
     * Sets this site's parent ngi.
     * Note, calling this method saves the join between Site and the Ngi.
     *
     * @see NGI::addSite()
     * @param NGI $ngi
     */
    public function setNgiDoJoin($ngi) {
        $this->ngi = $ngi;
    }

    public function setInfrastructure($infrastructure) {
        $this->infrastructure = $infrastructure;
    }

    public function setCertificationStatus($certificationStatus) {
        $this->certificationStatus = $certificationStatus;
    }

    public function setCertificationStatusChangeDate($certStatusChangeDate){
        $this->certificationStatusChangeDate = $certStatusChangeDate; 
    }

    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }
    
    /**
     * Add the given certStatusLog to the list. This method internally calls 
     * <code>$certStatusLog->setSite($this)</code> to keep both  
     * sides of the bidirectional relationship consistent (i.e. don't separately 
     * call <code>$certStatusLog->setSite($this)</code>). 
     * <p>
     * This is the INVERSE side of the ORM relation so the internal call to 
     * setSite actually establishes the relationship in the DB. 
     * 
     * @param \CertificationStatusLog $certStatusLog 
     */
    public function addCertificationStatusLog(\CertificationStatusLog $certStatusLog){
        $this->certificationStatusLog[] = $certStatusLog; 
        $certStatusLog->setParentSite($this); 
    }
	
    public function addScope(Scope $scope) {
        $this->scopes[] = $scope;
    }

    /**
     * Removes the association between this site and a scope
     *
     * @param Scope $removeScope The scope to be removed.
     */
    public function removeScope(Scope $removeScope) {
        $this->scopes->removeElement($removeScope);
    }

    public function addUserDoJoin(User $user) {
        $this->users[] = $user;
        $user->setHomeSiteDoJoin($this);
    }

    public function setCountry($country) {
        $this->country = $country;
    }

    public function setTimezone($timezone) {
        $this->timezone = $timezone;
    }

    public function setTier($tier) {
        $this->tier = $tier;
    }

    public function setSubGrid($subGrid) {
        $this->subGrid = $subGrid;
    }

    public function __toString () {
    	return $this->getShortName();
    }

}
