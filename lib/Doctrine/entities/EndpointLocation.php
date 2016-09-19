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

/**
 * A {@see Service} owns zero or more EndpointLocations. ELs are linked to {@see Downtime}s.
 * <p>
 * More formally; an EL models a network location that can be contacted to access certain
 * functionalities based on a well-defined interface. The defined attributes
 * refer to aspects such as the network location, the exposed interface name,
 * the details of the implementation and the linked downtimes.
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @author John Casson
 *
 * @Entity @Table(name="EndpointLocations")
 */
class EndpointLocation {

    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /** @Column(type="string", nullable=true)  */
    protected $name;

    /** @Column(type="string", nullable=true)  */
    protected $url;

    /** @Column(type="string", nullable=true)  */
    protected $interfaceName;

    /** @Column(type="string", length=2000, nullable=true)  */
    protected $description;

    /** @Column(type="boolean", options={"default": false}) */
    protected $monitored = false;

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

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * The human readable name of this endpoint location, e.g. 'Production GOCDB REST endpoint'.
     * @return string or null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Get a list of the {@see Downtime}s linked to this EL.
     * @return ArrayCollection
     */
    public function getDowntimes() {
        return $this->downtimes;
    }

    /**
     * Network location of an endpoint, which enables a specific component of
     * the Service to be contacted. Corresponds directly with the OGF GLUE2 Endpoint.
     * @return string or null
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * The Service instance that owns this EL.
     * @return \Service
     */
    public function getService() {
        return $this->service;
    }

    /**
     * The identification name of the primary protocol supported by the endpoint interface.
     * This corresponds directly with the OGF GLUE2 interfaceName.
     * @return string or null
     */
    public function getInterfaceName() {
        return $this->interfaceName;
    }

    /**
     * Custom Key=Value pairs (extension properties) used to augment the
     * ELs attributes.
     * @return ArrayCollection
     */
    public function getEndpointProperties() {
        return $this->endpointProperties;
    }

    /**
     * A human readable description for the EL, max 2000 chars.
     * @return string or null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Wether the SE is monitored
     * @return boolean
     */
    public function getMonitored() {
        return $this->monitored;
    }

    //Setters

    /**
     * The human readable name of this endpoint location, e.g. 'Production GOCDB REST endpoint'.
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Set the network location of an endpoint, which enables a specific component of
     * the Service to be contacted. Corresponds directly with the OGF GLUE2 Endpoint.
     * @param string $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * Set the identification name of the primary protocol supported by the endpoint interface.
     * This corresponds directly with the OGF GLUE2 interfaceName.
     * @param string $interfaceName
     */
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
    public function setServiceDoJoin($service) {
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
     * Set the human readable description for this EL, max 2000 chars.
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Set the monitored paremeter for the EL
     * @param boolean $monitored
     */
    public function setMonitored($monitored) {
        $this->monitored = $monitored;
    }


}
