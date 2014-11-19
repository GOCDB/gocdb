<?php
// Country.php
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="Countries")
 */
class Country {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
	/** @Column(type="string", unique=true) **/
	protected $name;
	/** @Column(type="string") **/
	protected $code;
	/** @OneToMany(targetEntity="Site", mappedBy="country") **/
	protected $sites = null;
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getCode() {
		return $this->code;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function setCode($code) {
		$this->code = $code;
	}
	
	public function getSites() {
		return $this->sites;
	}
	
	public function addSiteDoJoin($site) {
		$this->sites[] = $site;
		$site->setCountry($this);
	}
	
	public function __construct() {
	}
}