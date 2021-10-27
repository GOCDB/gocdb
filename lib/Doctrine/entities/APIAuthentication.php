<?php
/*
 * Copyright (C) 2016 STFC
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

 /**
  * The APIAuthenticationEntity defines a credential that can be used to makce
  * changes throught he API for a specific {@see Site}. Each site can have
  * 0-many APIAuthentication entities associated with it. Each entity has an ID,
  * type, identifier (e.g. DN for x509) and parent site.
  *
  * @author George Ryall (github.com/GRyall)
  *
  * @Entity @Table(name="APIAuthenticationEntities", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"}, uniqueConstraints={@UniqueConstraint(name="siteIdentifier", columns={"parentSite_id", "type", "identifier"})})
  */
   class APIAuthentication
  {
    /** @Id @Column(type="integer") @GeneratedValue  */
    protected $id;

    /**
     * One site may have zero-to-many APIAuthentication entities
     *
     * @ManyToOne(targetEntity="Site", inversedBy="APIAuthenticationEntities")
     * @JoinColumn(name="parentSite_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentSite = null;

    /**
    * Defines the type of the authentication entity (e.g 'x509').
    * @Column(type="string", nullable=false) */
    protected $type = null;

    /**
    * The unique identifier for the authentication (e.g. DN for x509)
    * @Column(type="string", nullable=false) */
    protected $identifier = null;

    /**
     * Get PK of Authentication entity
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the authentication enties parent site
     * @return \Site
     */
    public function getParentSite() {
        return $this->parentSite;
    }

    /**
     * Get they autentication type of this entity
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get the unique identifier for this autnentication entity.
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * Set the type of this authentication entity
     * @param string $name
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Set the unique identifier of this authentication entity.
     * @param string $identifier
     */
    public function setIdentifier($identifier) {
        $this->identifier = $identifier;
    }

    /**
     * Do not call in client code, always use the opposite
     * <code>$site->addAuthenticationEntityDoJoin($authenticationEntity)</code>
     * instead which internally calls this method to keep the bidirectional
     * relationship consistent.
     *
     * This is the OWNING side of the ORM relationship so this method WILL
     * establish the relationship in the database.
     *
     * @param \Site $site
     */
    public function _setParentSite($site){
        $this->parentSite = $site;
    }
  }
