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
 * A single Downtime instance is linked to a single {@see Service} and to 
 * zero-or-more of the Service's {@see EndpointLocation}s. This allows selected 
 * endpoints of a service to be put into downtime - consider different types of 
 * endpoint that all belong to the same service which may/may-not be affected by the DT. 
 * <p>
 * Note, the DB constraints actually allow multiple services to be joined with a 
 * single downtime, creating a many-to-many relationship between Service and 
 * Downtime. The overlying business logic however currently limits the Downtime to a single
 * Service; linking a single Downtime to multiple Services may be needed in the 
 * future and so the DB was designed this way to cater for this requirement.   
 * 
 * @author David Meredith <david.meredithh@stfc.ac.uk> 
 * @author John Casson 
 * @Entity @Table(name="Downtimes")
 */
class Downtime {

    /** @Id @Column(type="integer") @GeneratedValue * */
    protected $id;

    /** @Column(type="string", length=4000) * */
    protected $description;

    /** @Column(type="string") * */
    protected $severity;

    /** @Column(type="string") * */
    protected $classification;

    /* DATETIME NOTE:
     * Doctrine checks whether a date's been updated by doing a byreference comparison.
     * If you just update an existing DateTime object, Doctrine won't persist it!
     * Create a new DateTime object and reference that for it to persist during an update.
     * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
     */

    /** @Column(type="datetime", nullable=true) * */
    protected $insertDate;

    /** @Column(type="datetime", nullable=true) * */
    protected $startDate;

    /** @Column(type="datetime", nullable=true) * */
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
    protected $endpointLocations = null;

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

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
	return $this->id;
    }

    /**
     * Human readable description of downtime, 4000 chars max. 
     * @return string
     */
    public function getDescription() {
	return $this->description;
    }

    /**
     * The Severity string, either WARNING or OUTAGE. 
     * @return string 
     */
    public function getSeverity() {
	return $this->severity;
    }

    /**
     * A label to classify the downtime, either SCHEDULED or UNSCHEDULED. 
     * SCHEDULED if the insertDate is 24 hours before startDate.  
     * UNSCHEDULED if the insertDate is <24 hours before startDate.  
     * @return string
     */
    public function getClassification() {
	return $this->classification;
    }

    /**
     * Downtime insert date in server's default timezone (which may not be UTC).
     * <p> 
     * You will almost certainly need to set the returned DateTime's timezone to 
     * UTC. This is not done here to allow calling code to either set the tz individually 
     * per DateTime instance, or globally via <code>date_default_timezone_set("UTC");</code> 
     * which is more performant for processing large result sets (e.g. as in the PI).  
     * 
     * @return \DateTime or null
     */
    public function getInsertDate() {
	// Adds overhead when processing large result-sets. 
//        if($this->insertDate != NULL){
//     	  $this->insertDate->setTimezone(new \DateTimeZone('UTC'));
//        }
	return $this->insertDate;
    }

    /**
     * Get downtime start date in server's default timezone (which may not be UTC).
     * <p> 
     * You will almost certainly need to set the returned DateTime's timezone to 
     * UTC. This is not done here to allow calling code to either set the tz individually 
     * per DateTime instance, or globally via <code>date_default_timezone_set("UTC");</code> 
     * which is more performant for processing large result sets (e.g. as in the PI).  
     * 
     * @return \DateTime or null
     */
    public function getStartDate() {
	// Adds overhead when processing large result-sets. 
//        if($this->startDate != NULL){
//		   $this->startDate->setTimezone(new \DateTimeZone('UTC'));
//        }
	return $this->startDate;
    }

    /**
     * Get downtime end date in server's default timezone (which may not be UTC).
     * <p> 
     * You will almost certainly need to set the returned DateTime's timezone to 
     * UTC. This is not done here to allow calling code to either set the tz individually 
     * per DateTime instance, or globally via <code>date_default_timezone_set("UTC");</code> 
     * which is more performant for processing large result sets (e.g. as in the PI).  
     * 
     * @return \DateTime or null
     */
    public function getEndDate() {
	// Adds overhead when processing large result-sets. 
//        if($this->endDate != NULL){
//		   $this->endDate->setTimezone(new \DateTimeZone('UTC'));
//        }
	return $this->endDate;
    }

    /**
     * Get the date that this downtime should be announced. 
     * For SCHEDULED downtimes, this is 24hrs before the startDate.  
     * For UNSCHEDULED downtimes, this is the insertDate.  
     * @return \DateTime or null
     */ 
    public function getAnnounceDate() {
	if ($this->getClassification() == "UNSCHEDULED") {
	    return $this->insertDate;
	}
	$di = DateInterval::createFromDateString('1 days');
	$announceDate = clone $this->startDate;
	$announceDate->sub($di);
	return $announceDate;
    }

    /**
     * Get the list of {@see Service}s that this Downtime instance is linked to. 
     * @return Doctrine\Common\Collections\ArrayCollection Of {@see Service}s
     */
    public function getServices() {
	return $this->services;
    }

    /**
     * Get the list of {@see EndpointLocation}s that this Downtime is linked to. 
     * @return Doctrine\Common\Collections\ArrayCollection Of {@see EndpointLocation}s 
     */
    public function getEndpointLocations() {
	return $this->endpointLocations;
    }

    /**
     * The legacy Downtime primary key carried over from GOCDB4.
     * Other operational tools use this as the unique identifier for a Downtime.
     * For new Downtimes created in v5 this value is programmatically generated. 
     * @return string 
     */
    public function getPrimaryKey() {
	return $this->primaryKey;
    }

    /**
     * Provide the reasons for this downtime, 4000chars max. 
     * @param string $description
     */
    public function setDescription($description) {
	$this->description = $description;
    }

    /**
     * Flag to indicate the severity of the downtime, either OUTAGE or WARNING. 
     * @param string $severity
     */
    public function setSeverity($severity) {
	$this->severity = $severity;
    }

    public function setCode($code) {
	// DM: I don't know why this is needed, to remove?
	$this->code = $code;
    }

    /**
     * A label to classify the downtime, either SCHEDULED or UNSCHEDULED. 
     * SCHEDULED if the insertDate is 24 hours before startDate.  
     * UNSCHEDULED if the insertDate is <24 hours before startDate.   
     * @param string $classification
     */
    public function setClassification($classification) {
	$this->classification = $classification;
    }

    /**
     * The UTC DateTime when this Downtime was created/inserted.  
     * @param \DateTime $insertDate
     */
    public function setInsertDate($insertDate) {
	$this->insertDate = $insertDate;
    }

    /**
     * The UTC DateTime when this downtime starts. 
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate) {
	$this->startDate = $startDate;
    }

    /**
     * The UTC DateTime when this downtime ends. 
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate) {
	$this->endDate = $endDate;
    }

    /**
     * The legacy Downtime primary key carried over from GOCDB4. 
     * Other operational tools use this as the unique identifier for a Downtime. 
     * For new Downtimes created in v5 this value is programmatically generated
     * using the {@see \PrimaryKey} ID property with 'G0' appended.
     * 
     * @param string $primaryKey
     */
    public function setPrimaryKey($primaryKey) {
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
     * @throws \AlreadyLinkedException If this downtime is already linked to the service. 
     */
    public function addService($service) {
	require_once __DIR__ . '/AlreadyLinkedException.php';
	// Check this SE isn't already registered (not sure we strictly need this) 
	foreach ($this->services as $existingSe) {
	    if ($existingSe == $service) {
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
	$nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
	$endDateUtc = $this->getEndDate()->setTimezone(new \DateTimeZone('UTC'));
	$startDateUtc = $this->getStartDate()->setTimezone(new \DateTimeZone('UTC'));
	if ($startDateUtc < $nowUtc && $endDateUtc > $nowUtc) {
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
	$nowUtc = new \DateTime(null, new \DateTimeZone('UTC'));
	$startDateUtc = $this->getStartDate()->setTimezone(new \DateTimeZone('UTC'));
	if ($startDateUtc < $nowUtc) {
	    return true;
	} else {
	    return false;
	}
    }

}
