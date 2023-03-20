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
 * A lookup record for different service types.
 * A single serviceType can be joined to many different {@see Service} instances.
 *
 * @author John Casson
 * @author David Meredith <david.meredith@stfc.ac.uk>
 *
 * @Entity @Table(name="ServiceTypes", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class ServiceType
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string", unique=true) */
    protected $name;

    /** @Column(type="string") */
    protected $description;

    /** @OneToMany(targetEntity="Service", mappedBy="serviceType") */
    protected $services = null;

    /**
     * An instance of a Service of this ServiceType may
     * unmonitored while in production.
     * @Column(type="boolean", options={"default":FALSE})
     */
    protected $monitoringException;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->monitoringException = false;
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the unique name of this service type.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the human readable description of this service type.
     * @return string or null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the unique name of this service type. Required.
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the human readable description of this service type.
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get all the {@see Service}s that are linked to this service type.
     * @return ArrayCollection
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Join the given Service to this ServiceType.
     * Note, this method calls the <code>$service->setServiceType($this);</code>
     * to establish the join on both sides of the relationship.
     * @param \Service $service
     */
    public function addService($service)
    {
        $this->services[] = $service;
        $service->setServiceType($this);
    }

    /**
     * Add an exception where an instance of this ServiceType is allowed
     * to be unmonitored when in production with a specific Scope.
     * Return value is the existing boolean state
     */
    public function setAllowMonitoringException($state)
    {
        $oldState = $this->getAllowMonitoringException();
        $this->monitoringException = $state;
        return $oldState;
    }
    /**
     * Check if a monitoring exception is allowed within the given scope.
     */
    public function getAllowMonitoringException()
    {
        return $this->monitoringException;
    }

    /**
     * Gets the name of this service type.
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
