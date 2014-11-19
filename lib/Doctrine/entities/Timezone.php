<?php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="Timezones")
 */
class Timezone {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
	/** @OneToMany(targetEntity="Site", mappedBy="timezone") **/
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