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
use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/IScopedEntity.php';

/**
 * A Service defines an instance of an e-Infrastructure service that
 * is linked to a service type, has endpoints and is owned by a parent Site.
 * <p>
 * More formally: a logical view of actual software components that
 * participate in the creation of an entity providing one or more
 * functionalities useful in an e-infrastructure  environment (from GLUE2).
 *
 * @author David Meredith <david.meredithh@stfc.ac.uk>
 * @author John Casson
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

    /** @Column(type="boolean", options={"default": false}) */
    protected $notify = false;

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
        // Set cretion date
        $this->creationDate =  new \DateTime("now");
        $this->scopes = new ArrayCollection();
        $this->endpointLocations = new ArrayCollection();
        $this->serviceProperties = new ArrayCollection();
        $this->downtimes = new ArrayCollection();
        $this->serviceGroups = new ArrayCollection();
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * The main network URL of this service. Note, a Service can define
     * additional {@see EndpointLocation}s to define different contact endpoints
     * as required. External monitoring will usually contact this URL to test
     * service state.
     *
     * @return string or null
     */
    public function getUrl(){
        return $this->url;
    }

    /**
     * The main hostname used for this service.
     * This is largely required for external monitoring that requires a distinct
     * host name to ping.
     * @return string
     */
    public function getHostName() {
        return $this->hostName;
    }

    /**
     * A nullable human readable description of this service.
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Is this service considered production or not.
     * @return boolean
     */
    public function getProduction() {
        return $this->production;
    }

    /**
     * Is this service considered beta or not.
     * @return boolean
     */
    public function getBeta() {
        return $this->beta;
    }

    /**
     * Is this service monitored or not.
     * @return boolean
     */
    public function getMonitored() {
        return $this->monitored;
    }

    /**
     * Defines the DN of the services x509 certificate.
     * @return string or null
     */
    public function getDn() {
        return $this->dn;
    }

    /**
     * The IPv4 address of the service.
     * @return string or null
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * The IPv6 address of the service.
     * @return string or null
     */
    public function getIpV6Address() {
        return $this->ipV6Address;
    }

    /**
     * A label for the service OS such as 'linux' or 'windows'.
     * Refer to the GLUE2 OSName_t Enum type.
     * @return string or null
     */
    public function getOperatingSystem() {
        return $this->operatingSystem;
    }

    /**
     * A label to identify the Services architecture, e.g. x86_64
     * @return string or null
     */
    public function getArchitecture() {
        return $this->architecture;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getNotify() {
        return $this->notify;
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

    public function setNotify($notify) {
        $this->notify = $notify;
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
//        $this->downtimes[] = $downtime;
//        // adding below would cause a race condition
//        //$downtime->addService($this);
//    }

//    public function removeDowntime(Downtime $downtime) {
//        $this->downtimes->removeElement($downtime);
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

    /**
     * Create a relationship between the given Scope and this Service.
     * @param Scope $scope
     */
    public function addScope(Scope $scope) {
        $this->scopes[] = $scope;
    }

    /**
     * Adds the given ServiceGroup to this service's list of SGs.
     * Do not call in client code, always use the opposite
     * <code>$sg->addService($service)</code> instead which internally calls this
     * method to keep the bidirectional relationship consistent.
     * @param ServiceGroup $sg
     */
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


    /**
     * Returns a string of the form 'serviceTypeName hostname'
     * @return string
     */
    public function __toString() {
        return $this->getServiceType()->getName() . " " . $this->getHostName();
    }

}
