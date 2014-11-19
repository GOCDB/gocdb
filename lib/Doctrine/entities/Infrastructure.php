<?php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="Infrastructures")
 */
class Infrastructure {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
	/** @OneToMany(targetEntity="Site", mappedBy="infrastructure") **/
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
		$site->setInfrastructure($this);
	}
	
	public function __construct() {
		$this->sites = new ArrayCollection();
	}
}