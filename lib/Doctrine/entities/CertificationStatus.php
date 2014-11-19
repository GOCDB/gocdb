<?php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="CertificationStatuses")
 */
class CertificationStatus {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
	/** 
     * Bidirectional - A single CertStatus (INVERSE ORM SIDE) can be linked to many Sites. 
     * @OneToMany(targetEntity="Site", mappedBy="certificationStatus") 
     */
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
		$site->setCertificationStatus($this);
	}

	public function __construct() {
		$this->sites = new ArrayCollection();
	}

	public function __toString() {
	    return $this->getName();
	}
}