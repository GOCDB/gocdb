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

require_once 'NGI.php';

/**
 * A Site is an adminstrative domain (often a physical site) for hosting zero or more {@see Service}s.
 * A Site has a single parent {@see NGI). Users hold Roles over their Sites which
 * cascade to their services. Sites can be scoped using {@see Scope} tags
 * for resource filtering/matching.
 *
 * @author John Casson
 * @author David Meredith <david.meredith@stfc.ac.uk>
 *
 * @Entity @Table(name="Sites", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class Site extends OwnedEntity implements IScopedEntity{

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

    /** @Column(type="boolean", options={"default": false}) */
    protected $notify = false;

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

    /** @Column(type="datetime", nullable=true) */
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

    /** @ManyToOne(targetEntity="Country", inversedBy="sites")  */
    protected $country;

    /**
     * Dont' use the Timezone entity, use the timezoneId variable instead.
     * @deprecated since version 5.4
     * @ManyToOne(targetEntity="Timezone", inversedBy="sites")
     */
    protected $timezone;

    /** @Column(type="string", nullable=true) */
    protected $timezoneId;

    /** @ManyToOne(targetEntity="Tier", inversedBy="sites")  */
    protected $tier;

    /**
     * @ManyToOne(targetEntity="SubGrid", inversedBy="sites")
     * @JoinColumn(name = "subgrid_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $subGrid;

    /**
     * Users for whom this is their home site
     * @OneToMany(targetEntity="User", mappedBy="homeSite")
     */
    protected $users = null;


    /** @Column(type="datetime", nullable=false) **/
    protected $creationDate;

    /**
     * Bidirectional - A Site can have many APIAuthenication Entities.
     * @OneToMany(targetEntity="APIAuthentication", mappedBy="parentSite", cascade={"remove"})
     */
    protected $APIAuthenticationEntities = null;

    public function __construct() {
        parent::__construct();
        $this->creationDate =  new \DateTime("now");
        $this->services = new ArrayCollection();
        $this->siteProperties = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->certificationStatusLog = new ArrayCollection();
    }


    /**
     * The Site's legacy GOCDBv4 PK.
     * Other operational tools require this as the unique identifier for a site.
     * For new sites created in v5 this value is programmatically generated.
     * @return string
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    /**
     * @return string Unique short name of this site
     */
    public function getShortName() {
        return $this->shortName;
    }

    /**
     * @see Site::getShortName()
     * @return string Unique short name of this site
     */
    public function getName(){
       return $this->shortName;
    }

    /**
     * @return string The long/official name of the site.
     */
    public function getofficialName() {
        return $this->officialName;
    }

    /**
     * @return string Nullable human readable description of this site, max 2000 chars.
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return string Nullable home URL for this Site.
     */
    public function getHomeUrl() {
        return $this->homeUrl;
    }

    /**
     * @return string Nullable email addresses for this Site.
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @return string Nullable tel number for this Site.
     */
    public function getTelephone() {
        return $this->telephone;
    }

    /**
     * @return string Nullable field to record the URL of the Grid Info Service
     */
    public function getGiisUrl() {
        return $this->giisUrl;
    }

    /**
     * @return float Nullable latitute value for this site.
     */
    public function getLatitude() {
        return $this->latitude;
    }

    /**
     * @return float Nullable longitude value for this site.
     */
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

    public function getNotify() {
        return $this->notify;
    }

    public function getServices() {
        return $this->services;
    }

    /**
     * The Site's list of {@see SiteProperty} extension objects. When the
     * Site is deleted, the SiteProperties are also cascade deleted.
     * @return ArrayCollection
     */
    public function getSiteProperties(){
        return $this->siteProperties;
    }

    /**
     * The Site's list of {@see APIAuthenication} entities.
     * @return ArrayCollection
     */
    public function getAPIAuthenticationEntities(){
        return $this->APIAuthenticationEntities;
    }

    /**
     * @return \NGI The Site's owning NGI
     */
    public function getNgi() {
        return $this->ngi;
    }

    /**
     * @return ArrayCollection Contains parent NGI or empty collection if no parent.
     */
    public function getParentOwnedEntities() {
        $ngiArray = new ArrayCollection();
        if($this->ngi != null){
            $ngiArray->add($this->ngi);
        }
        return $ngiArray;
    }

    /**
     * @return \Infrastructure
     */
    public function getInfrastructure() {
        return $this->infrastructure;
    }

    /**
     * @return \CertificationStatus
     */
    public function getCertificationStatus() {
        return $this->certificationStatus;
    }

    /**
     * Get the DateTime when the certification status was last changed.
     * @return \DateTime
     */
    public function getCertificationStatusChangeDate(){
        return $this->certificationStatusChangeDate;
    }

    /**
     * Get the Site's {@see CertificationStatusLog} objects.
     * @return ArrayCollection
     */
    public function getCertificationStatusLog(){
        return $this->certificationStatusLog;
    }

    /**
     * Get all the Site's joined {@see Scope} objects.
     * @return ArrayCollection
     */
    public function getScopes() {
        return $this->scopes;
    }

    /**
     * @return \Country
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Don't use, use the getTimezoneId() instead.
     * @deprecated since version 5.4
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * Get the timezone identifier label.
     * Labels should be of the IANA form, e.g.  Europe/London
     * @link http://www.iana.org/time-zones IANA timezones
     * @return String
     */
    public function getTimezoneId() {
        return $this->timezoneId;
    }

    /**
     * @return \Tier
     */
    public function getTier() {
        return $this->tier;
    }

    /**
     * @return \SubGrid
     */
    public function getSubGrid() {
        return $this->subGrid;
    }

    /**
     * Get all the Site's {@see \User}s.
     * @return ArrayCollection
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * A CSV string listing the names of scopes with which the
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

    /**
     * @return \DateTime The datetime when the Site was created.
     */
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

    public function setNotify($notify) {
        $this->notify = $notify;
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
     * Add an API authentication entity to this Site's collection of autherntication
     * entities. This method also sets the authentication entity's parentSite.
     * @param \APIAuthentication $authenticationEntity
     */
    public function addAPIAuthenticationEntitiesDoJoin(\APIAuthentication $authenticationEntity) {
        $this->APIAuthenticationEntities[] = $authenticationEntity;
        $authenticationEntity->_setParentSite($this);
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

    /**
     * Tag this Site with the given Scope.
     * @param Scope $scope
     */
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

    /**
     * Add the given {@see User} to the Site's list of users.
     * This method also calls <code>user->setHomeSiteDoJoin($this);</code> to
     * ensure the relationship is established from both sides.
     * @param User $user
     */
    public function addUserDoJoin(User $user) {
        $this->users[] = $user;
        $user->setHomeSiteDoJoin($this);
    }

    /**
     * Set this Site's country.
     * @param \Country $country
     */
    public function setCountry($country) {
        $this->country = $country;
    }

    /**
     * Don't use, use the setTimezoneId() instead.
     * @deprecated since version 5.4
     */
    public function setTimezone($timezone) {
        $this->timezone = $timezone;
    }

    /**
     * Set the timezone identifier label.
     * Labels should be of the IANA form, e.g.  Europe/London
     * @link http://www.iana.org/time-zones IANA timezones
     * @return null
     */
    public function setTimezoneId($timezoneId){
        $this->timezoneId = $timezoneId;
    }

    /**
     * @param \Tier $tier
     */
    public function setTier($tier) {
        $this->tier = $tier;
    }

    /**
     * @param \SubGrid $subGrid
     */
    public function setSubGrid($subGrid) {
        $this->subGrid = $subGrid;
    }

    /**
     * Returns this Site's unique shortName
     * @return string
     */
    public function __toString () {
        return $this->getShortName();
    }

    /**
     * Returns value of {@link \OwnedEntity::TYPE_SITE}
     * @see \OwnedEntity::getType()
     * @return string
     */
    public function getType() {
        return 'site';
    }

}
