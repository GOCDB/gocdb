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
 * @deprecated since version 5.4
 * @author John Casson
 * @Entity @Table(name="SubGrids", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class SubGrid {

    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string", unique=true) */
    protected $name;

    /** @OneToMany(targetEntity="Site", mappedBy="subGrid") */
    protected $sites = null;

    /**
     * @ManyToOne(targetEntity="NGI")
     * @JoinColumn(name="NGI_Id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $ngi;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getSites() {
        return $this->sites;
    }

    public function getNgi() {
        return $this->ngi;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function addSiteDoJoin($site) {
        $this->sites[] = $site;
        $site->setSubGrid($this);
    }

    public function setNgi($ngi) {
        $this->ngi = $ngi;
    }

    public function __construct() {
        $this->sites = new ArrayCollection();
    }

}
