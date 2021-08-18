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
 * A Project is the top level object in the core object/domain model and groups
 * zero or more child {@see NGI}s. A project is comparable to a top
 * level GLUE2 AdminDomain class.
 * <p>
 * Users can request Roles over the Project which grants
 * various permissions over the Project and its child NGIs.
 *
 * @author David Meredith <david.meredith@stfc.ac.uk>
 *
 * @Entity @Table(name="Projects", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class Project extends OwnedEntity {

    /** @Column(type="string", unique=true) */
    protected $name;

    /** @Column(type="string", length=2000, nullable=true) */
    protected $description;

    /**
     * Bidirectional - Many Projects (OWNING SIDE) can link to many NGIs
     *
     * @ManyToMany(targetEntity="NGI", inversedBy="projects")
     * @JoinTable(name="Projects_NGIs",
     *      joinColumns={@JoinColumn(name="project_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="ngi_Id", referencedColumnName="id")}
     *      )
     */
    protected $ngis;

    /* DATETIME NOTE:
     * Doctrine checks whether a date's been updated by doing a byreference comparison.
     * If you just update an existing DateTime object, Doctrine won't persist it!
     * Create a new DateTime object and reference that for it to persist during an update.
     * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
     */

    /** @Column(type="datetime", nullable=false) */
    protected $creationDate;

    /**
     * Create a new project
     * @param string $name The project's name
     */
    public function __construct($name) {
        parent::__construct();

        // Set cretion date
        $this->creationDate =  new \DateTime(null, new \DateTimeZone('UTC'));

        $this->setName($name);
        $this->ngis = new ArrayCollection();
    }


    /**
     * Get the unique name of the Project.
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * A nullable string for the human readable description of this project.
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Get all the child {@see NGI} objects that are owned by this project.
     * @return ArrayCollection
     */
    public function getNgis() {
        return $this->ngis;
    }

    /**
     * Set the unique name of the project. Must be unique in the DB.
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Set the human readable description for this project, max 2000 chars.
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Add the given NGI to this project's list of child NGIs. Note, this
     * method calls the <code>$ngi->addProject($this);</code> to set the join
     * on both sides of the relationship.
     * @param \NGI $ngi
     */
    public function addNgi(\NGI $ngi){
        $this->ngis[]= $ngi;
        $ngi->addProject($this);
    }

    /**
     * Returns value of {@link \OwnedEntity::TYPE_PROJECT}
     * @see \OwnedEntity::getType()
     * @return string
     */
    public function getType() {
        return parent::TYPE_PROJECT;
    }

    /**
     * @return ArrayCollection Empty collection, Project has no owning parents.
     */
    public function getParentOwnedEntities() {
        return new ArrayCollection();
    }

}
