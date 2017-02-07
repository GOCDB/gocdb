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

require_once 'Site.php';
require_once 'IScopedEntity.php';

/**
 * An NGI represents the administrative domain for grouping child Sites.
 * It is directly comparable to the GLUE2 AdminDomain class from OGF.
 * <p>
 * An NGI groups zero or more child {@see Site}s, and is linked to one or more
 * parent {@see Project}s. Users can request Roles over the NGI which grants
 * various permissions over the NGI and its child Sites.
 *
 * @author David Meredith <david.meredithh@stfc.ac.uk>
 * @author John Casson
 *
 * @Entity @Table(name="NGIs")
 */
class NGI extends OwnedEntity implements IScopedEntity {

    /** @Column(type="string", unique=true) */
    protected $name;

    /** @Column(type="string", nullable=true) */
    protected $email;

    /** @Column(type="string", nullable=true) */
    protected $rodEmail;

    /** @Column(type="string", nullable=true) */
    protected $helpdeskEmail;

    /** @Column(type="string", nullable=true) */
    protected $securityEmail;

    /** @Column(type="string", nullable=true) */
    protected $description;

    /** @Column(type="string", nullable=true) */
    protected $ggus_Su;

    /** @OneToMany(targetEntity="Site", mappedBy="ngi") */
    protected $sites = null;

    /**
     * Bidirectional - Many NGIs (INVERSE SIDE) can link to many Projects
     *
     * @ManyToMany(targetEntity="Project", mappedBy="ngis")
     */
    protected $projects = null;

    /**
     * Unidirectional - Scope tags associated with the NGI
     *
     * @ManyToMany(targetEntity="Scope")
     * @JoinTable(name="NGIs_Scopes",
     *      joinColumns={@JoinColumn(name="ngi_Id", referencedColumnName="id")},
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
        parent::__construct();

        // Set cretion date
        $this->creationDate =  new \DateTime("now");

        $this->sites = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->scopes = new ArrayCollection();
    }

    //public function getId() {
    //    return $this->id;
    //}

    /**
     * A unique non-null name for this NGI.
     * @return String The unique name of this NGI
     */
    public function getName() {
        return $this->name;
    }

    /**
     * A nullable string that concatenates contact email addresses for the NGI.
     * @return String email
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Nullable string that concatenates ROD email addresses (Regional Operator on Duty).
     * @return string
     */
    public function getRodEmail() {
        return $this->rodEmail;
    }

    public function getHelpdeskEmail() {
        return $this->helpdeskEmail;
    }

    public function getSecurityEmail() {
        return $this->securityEmail;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getGgus_Su(){
        return $this->ggus_Su;
    }

    public function getSites() {
        return $this->sites;
    }

    public function getProjects() {
        return $this->projects;
    }

    /**
     * @return ArrayCollection Contains parent Projects or empty collection.
     */
    public function getParentOwnedEntities() {
        return $this->projects;
    }

    public function getScopes() {
        return $this->scopes;
    }

    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * A string containg a list of concatenated scope names with which the
     * object been tagged.
     * @return string  string containing ", " seperated list of the names
     */
    public function getScopeNamesAsString() {
        //Get the scopes for the NGI
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
     * Add the given site to the NGI list.
     *
     * @see Site::setNgiDoJoin()
     * @param Site $site
     */
    public function addSiteDoJoin(Site $site) {
        $this->sites[] = $site;
        $site->setNgiDoJoin($this);
    }

    /**
     * Note this is called by addNgi($ngi) in the project class. $prj->addNgi($ngi)
     * should always be used rather than $ngi->addProject($prj)
     * @param \Project $project
     */
    public function addProject(\Project $project) {
        $this->projects[]=$project;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setRodEmail($rodEmail) {
        $this->rodEmail = $rodEmail;
    }

    public function setHelpdeskEmail($helpdeskEmail) {
        $this->helpdeskEmail = $helpdeskEmail;
    }

    public function setSecurityEmail($securityEmail) {
        $this->securityEmail = $securityEmail;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setGgus_Su($ggus_su){
        $this->ggus_Su = $ggus_su;
    }

    public function addScope(Scope $scope) {
        $this->scopes[] = $scope;
    }

    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
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
     * Returns value of {@link \OwnedEntity::TYPE_NGI}
     * @see \OwnedEntity::getType()
     * @return string
     */
    public function getType() {
        return parent::TYPE_NGI;
    }

}
