<?php

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Do not use, this will eventually be dropped from the schema.
 * Entities such as Sites should specify their timezone directly as attributes
 * on the owning entity rather than joining to this entity.
 * @deprecated since version 5.4
 * @Entity @Table(name="Timezones", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class Timezone {

    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string", unique=true) */
    protected $name;

    /** @OneToMany(targetEntity="Site", mappedBy="timezone") */
    protected $sites = null;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSites() {
        return $this->sites;
    }

    public function addSiteDoJoin($site) {
        $this->sites[] = $site;
        $site->setTimezone($this);
    }

    public function __construct() {
        $this->sites = new ArrayCollection();
    }

}
